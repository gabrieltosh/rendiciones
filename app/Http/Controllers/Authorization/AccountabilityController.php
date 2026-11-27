<?php

namespace App\Http\Controllers\Authorization;

use App\Http\Controllers\Controller;
use App\Models\UserAuthorization;
use Illuminate\Http\Request;
use Notification;
use Redirect;
use Inertia\Inertia;
use Session;
use App\Models\Accountability;
use App\Http\Requests\Accountability\DocumentRequest;
use App\Models\AccountabilityDetail;
use App\Models\DetailAccounts;
use App\Models\Profile;
use App\Models\GeneralAccounts;
use App\Models\Supplier;
use App\Models\Document;
use App\Http\Requests\Accountability\AccountabilityRequest;
use DB;
use App\Models\Employee;
use Throwable;
use App\Models\Management;
use Barryvdh\DomPDF\Facade\Pdf;
use Config;
use App\Helpers\Hana;
use App\Notifications\Accountability\StatusAccountabilityNotification;
use App\Notifications\Accountability\CreateAccountabilityNotification;
use App\Models\AccountabilityField;
use App\Models\User;
use App\Models\Audit;
use App\Models\AccountabilityLevelApproval;
use App\Models\AuthorizationCycleLevel;
use App\Models\AccountAlias;
use App\Services\Accountability\ExpenseDistribution;
use App\Services\Accountability\ExpenseItems;
use App\Services\Sap\DistributionRules;
use App\Services\Sap\DocumentLineCalculator;
use App\Services\Sap\InventoryReceiptPayloadBuilder;
use App\Services\Sap\JournalVoucherPayloadBuilder;
use App\Services\Sap\SapExporterFactory;
use App\Services\Sap\SapExportResult;
use Log;
use App\Models\SapExportLog;
use Illuminate\Support\Facades\Auth;

class AccountabilityController extends Controller
{
    /** Modo con el que se resolvió la última exportación a SAP ('SL' o 'DI'). */
    private ?string $sap_export_mode = null;

    public function HandleGetReportAccountability($accountability_id)
    {
        $params = Management::where('group', 'company')->get();
        $company = [
            'company_name' => $params->where('name', 'company_name')->first()->value,
            'company_location' => $params->where('name', 'company_location')->first()->value,
            'nit' => $params->where('name', 'nit')->first()->value,
            'logo' => $params->where('name', 'logo')->first()->value,
        ];
        $data = Accountability::with('profile', 'user', 'detail.document')
            ->where('id', $accountability_id)
            ->first();
        $pdf = Pdf::loadView('pdf.accountability_detail', [
            'data' => $data,
            'company' => $company
        ]);
        $pdf->setPaper('letter', 'portrait');
        return $pdf->stream();
    }
    /** Registra la acción del autorizador en la ronda de envío vigente de la rendición. */
    private function HandleRecordApproval($accountability_id, $level_id, $user_id, $status, $comments = null)
    {
        $round = Accountability::whereKey($accountability_id)->value('submission_round');
        AccountabilityLevelApproval::create([
            'accountability_id' => $accountability_id,
            'level_id'          => $level_id,
            'user_id'           => $user_id,
            'status'            => $status,
            'comments'          => $comments,
            'acted_at'          => now(),
            'round'             => max(1, (int) $round),
        ]);
    }

    /**
     * El usuario puede aprobar, rechazar o exportar la rendición: está Pendiente y él es
     * autorizador de su nivel actual (o del solicitante, en el sistema sin ciclo).
     * El Administrador puede actuar sobre cualquier rendición pendiente.
     */
    private function HandleCanAuthorize(Accountability $accountability, $user): bool
    {
        if ($accountability->status !== 'Pendiente') {
            return false;
        }
        if ($user->type === 'Administrador') {
            return true;
        }

        return Accountability::pendingAuthorizationFor($user->id)->where('id', $accountability->id)->exists();
    }

    private function HandleDenied(Accountability $accountability)
    {
        $message = $accountability->status === 'Pendiente'
            ? 'No es autorizador de esta rendición en su nivel actual.'
            : "La rendición está " . ($accountability->status ?? 'en borrador') . " y no se puede autorizar ni rechazar.";

        return Redirect::route('panel.accountability.authorization.index')->with(['message' => $message, 'type' => 'negative']);
    }

    /** Aviso por correo al solicitante. Un fallo del correo no debe revertir ni ocultar lo ya guardado. */
    private function HandleNotifyStatus(Accountability $accountability): void
    {
        try {
            $params = Management::where('group', 'accountability')->get();
            if ($params->where('name', 'notification_email')->first()?->value == 'SI') {
                User::find($accountability->user_id)?->notify(new StatusAccountabilityNotification($accountability->fresh()));
            }
        } catch (Throwable $e) {
            Log::warning('No se pudo notificar el estado de la rendición', [
                'accountability_id' => $accountability->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function HandleAdvanceOrFinalize($accountability, $request, $sapExported, $redirectOnFinish)
    {
        $currentLevel = $accountability->current_level_id
            ? AuthorizationCycleLevel::find($accountability->current_level_id)
            : null;

        // Record approval when using cycle system
        if ($currentLevel) {
            $nextLevel = $currentLevel->nextLevel();
            if ($nextLevel) {
                $this->HandleAdvanceLevel($accountability, $currentLevel, $nextLevel, $request->user()->id, $request->comments);

                // Sigue con la próxima rendición que le toca revisar, sin volver a la bandeja
                $message = "Nivel aprobado. La rendición #{$accountability->id} avanzó al nivel: {$nextLevel->name}";
                $nextId = $this->HandleNextPendingId($request->user(), $accountability->id);
                if ($nextId) {
                    Session::flash('message', "{$message}. Se muestra la siguiente pendiente (#{$nextId}).");
                    Session::flash('type', 'positive');
                    return Redirect::route('panel.accountability.authorization.detail.index', $nextId);
                }
                Session::flash('message', "{$message}. No tiene más rendiciones pendientes.");
                Session::flash('type', 'positive');
                return Redirect::route('panel.accountability.authorization.index');
            }
            $this->HandleRecordApproval(
                $accountability->id,
                $currentLevel->id,
                $request->user()->id,
                'aprobado',
                $request->comments
            );
        }

        // No more levels (or legacy system) — fully authorize.
        // El comentario de aprobación queda en accountability_level_approvals: no se toca
        // accountabilities.comments, que guarda el motivo del último rechazo o anulación.
        $attributes = [
            'status'      => 'Autorizado',
            'current_level_id' => null,
        ];
        // Una rendición que ya tiene asiento en SAP no vuelve a quedar "sin exportar"
        if (!$accountability->sap_trans_id && !$accountability->sap_exported) {
            $attributes['sap_exported'] = $sapExported;
        }
        $accountability->fill($attributes)->save();

        $this->HandleNotifyStatus($accountability);

        return $redirectOnFinish;
    }

    /**
     * Aprueba un nivel intermedio: registra la aprobación, pasa la rendición al
     * siguiente nivel y avisa a sus autorizadores (un fallo del correo no revierte nada).
     */
    private function HandleAdvanceLevel(Accountability $accountability, AuthorizationCycleLevel $currentLevel, AuthorizationCycleLevel $nextLevel, $userId, $comments): void
    {
        $this->HandleRecordApproval($accountability->id, $currentLevel->id, $userId, 'aprobado', $comments);
        $accountability->fill(['current_level_id' => $nextLevel->id])->save();

        try {
            $params = Management::where('group', 'accountability')->get();
            if ($params->where('name', 'notification_email')->first()?->value == 'SI') {
                foreach ($nextLevel->users as $nextUser) {
                    $nextUser->notify(new CreateAccountabilityNotification($accountability));
                }
            }
        } catch (Throwable $e) {
            Log::warning('No se pudo notificar al siguiente nivel', [
                'accountability_id' => $accountability->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** La próxima rendición que el usuario debe autorizar (la que más tiempo lleva esperando). */
    private function HandleNextPendingId($user, $excludeId): ?int
    {
        $id = Accountability::pendingAuthorizationFor($user->id)
            ->where('id', '!=', $excludeId)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Aprobación en lote desde la bandeja, solo de niveles intermedios: la autorización
     * final (que exporta a SAP) requiere la revisión contable en el detalle de cada rendición.
     * Cada rendición se valida con HandleCanAuthorize, igual que al aprobarla una por una.
     */
    public function HandleBulkApproveLevel(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
            'comments' => 'nullable|string|max:1000',
        ], [
            'ids.required' => 'Seleccione al menos una rendición.',
        ]);

        $user = $request->user();
        $comments = trim((string) $request->comments) !== '' ? trim((string) $request->comments) : null;
        $approved = [];
        $skipped = [];

        foreach (array_unique(array_map('intval', $request->ids)) as $id) {
            $accountability = Accountability::find($id);
            if (!$accountability || !$this->HandleCanAuthorize($accountability, $user)) {
                $skipped[] = "#{$id} (ya no le corresponde autorizarla)";
                continue;
            }
            $currentLevel = $accountability->current_level_id
                ? AuthorizationCycleLevel::find($accountability->current_level_id)
                : null;
            $nextLevel = $currentLevel?->nextLevel();
            if (!$nextLevel) {
                $skipped[] = "#{$id} (es la autorización final: hágala desde el detalle)";
                continue;
            }
            $this->HandleAdvanceLevel($accountability, $currentLevel, $nextLevel, $user->id, $comments);
            $approved[] = $id;
        }

        $count = count($approved);
        $message = $count
            ? "Se aprobó el nivel de {$count} rendici" . ($count === 1 ? 'ón' : 'ones') . '.'
            : 'No se aprobó ninguna rendición.';
        if ($skipped) {
            $message .= ' Sin aprobar: ' . implode(', ', $skipped) . '.';
        }

        return Redirect::back()->with([
            'message' => $message,
            'type' => $skipped ? ($count ? 'warning' : 'negative') : 'positive',
        ]);
    }

    public function HandleExportSAP($accountability_id, Request $request)
    {
        $request->validate(['comments' => 'nullable|string|max:1000']);
        $accountability = Accountability::findOrFail($accountability_id);
        if (!$this->HandleCanAuthorize($accountability, $request->user())) {
            return $this->HandleDenied($accountability);
        }

        // If cycle system: check if there's a next level before attempting SAP export
        $currentLevel = $accountability->current_level_id
            ? AuthorizationCycleLevel::find($accountability->current_level_id)
            : null;

        if ($currentLevel && $currentLevel->nextLevel()) {
            // Not the last level — just advance, no SAP export yet
            return $this->HandleAdvanceOrFinalize($accountability, $request, 0,
                Redirect::route('panel.accountability.authorization.index'));
        }

        // Last level or legacy: attempt SAP export
        $result = $this->HandleSendToSap($accountability);

        if (!$result->success) {
            // Si ya estaba en SAP no es un fallo de exportación: no se ofrece "Autorizar sin SAP".
            $alreadyExported = Accountability::whereKey($accountability->id)
                ->where(fn ($q) => $q->where('sap_exported', 1)->orWhereNotNull('sap_trans_id'))
                ->exists();
            if ($alreadyExported) {
                Session::flash('message', $result->errorMessage);
                Session::flash('type', 'negative');
            } else {
                // Clave propia (no 'message'): la página de detalle la recibe como prop
                // sap_error y ofrece autorizar sin exportar.
                Session::flash('sap_error', $result->errorMessage ?: 'No se pudo exportar la rendición a SAP.');
            }
            return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
        }

        if ($currentLevel) {
            $this->HandleRecordApproval(
                $accountability->id,
                $currentLevel->id,
                $request->user()->id,
                'aprobado',
                $request->comments
            );
        }
        $accountability->fill(array_merge([
            'status'           => 'Autorizado',
            'current_level_id' => null,
        ], $this->HandleSapExportAttributes($result)))->save();
        $this->HandleSaveReceiptDrafts($result);

        Session::flash('message', $this->HandleSapExportMessage("Rendición autorizada y exportada a SAP", $result));
        Session::flash('type', 'positive');
        Session::flash('sap_number', $result->key);

        $this->HandleNotifyStatus($accountability);
        return Redirect::route('panel.accountability.authorization.index');
    }

    /**
     * Construye el asiento y lo envía a SAP por el driver configurado
     * (Service Layer o servicio DI API).
     */
    private function HandleSendToSap(Accountability $accountability): SapExportResult
    {
        $this->sap_export_mode = null;
        $sent = null;
        $result = $this->HandleAttemptSapExport($accountability, $sent);

        // Cada intento (al autorizar o al reintentar) queda en el historial, exitoso o no.
        // Un fallo al guardar el historial no debe ocultar el resultado de SAP.
        try {
            SapExportLog::create([
                'accountability_id' => $accountability->id,
                'user_id'           => Auth::id(),
                'success'           => $result->success,
                'mode'              => $this->sap_export_mode,
                'sap_key'           => $result->key,
                'message'           => $result->success
                    ? $this->HandleSapExportMessage('Exportada correctamente', $result)
                    : $result->errorMessage,
                'payload'           => $sent ? json_encode($sent, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) : null,
            ]);
        } catch (Throwable $e) {
            Log::error('No se pudo guardar el historial de exportación a SAP', [
                'accountability_id' => $accountability->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * @param  array|null  $sent  lo enviado a SAP (asiento y borradores), para el historial
     */
    private function HandleAttemptSapExport(Accountability $accountability, ?array &$sent): SapExportResult
    {
        // El asiento ya existe en SAP: no se vuelve a enviar aunque el usuario reintente.
        // Se relee de la base por si otro usuario la exportó mientras tanto; sap_exported
        // cubre el caso en que SAP aceptó el asiento pero no se pudo obtener su número.
        $current = Accountability::whereKey($accountability->id)->first(['sap_exported', 'sap_trans_id']);
        if ($current?->sap_trans_id || $current?->sap_exported) {
            $number = $current->sap_trans_id ? " (asiento {$current->sap_trans_id})" : '';
            return SapExportResult::failure("Esta rendición ya fue exportada a SAP{$number}.");
        }

        try {
            $builder = new JournalVoucherPayloadBuilder();
            $payload = $builder->build($accountability);
            $sent = ['journal' => $payload];

            Log::info('SAP Export Data:', $payload);

            $exporter = SapExporterFactory::make();
            $this->sap_export_mode = $exporter->mode();

            // Referencia (Ref1) para ubicar el asiento en SAP. Por ahora solo en
            // Service Layer, que la usa para recuperar el número del asiento.
            if ($exporter->mode() === 'SL') {
                $payload['JournalVoucher']['JournalEntry']['Reference'] = $this->HandleSapReference($accountability);
            }

            // Facturas con ítems: una entrada de mercancías en borrador por documento
            $drafts = (new InventoryReceiptPayloadBuilder())->build(
                $accountability,
                $this->HandleSapReference($accountability)
            );
            $sent = ['journal' => $payload, 'drafts' => $drafts];
            if ($drafts) {
                Log::info('SAP Export Inventory Drafts:', $drafts);
            }

            return $exporter->addJournalVoucher(
                $payload,
                'acc-' . $accountability->id,
                $builder->udfTypes(),
                $drafts
            );
        } catch (Throwable $e) {
            return SapExportResult::failure($e->getMessage());
        }
    }

    private function HandleSapReference(Accountability $accountability): string
    {
        return 'REND-' . $accountability->id;
    }

    /**
     * Mensaje de éxito con el número del asiento preliminar generado en SAP, para
     * que el usuario pueda buscarlo allí.
     */
    private function HandleSapExportMessage(string $message, SapExportResult $result): string
    {
        $message = $result->key
            ? "{$message}. Asiento preliminar SAP N° {$result->key}"
            : "{$message}. No se pudo obtener el número del asiento en SAP";

        if ($result->drafts) {
            $message .= '. Entrada de mercancías (borrador) N° ' . implode(', ', $result->drafts);
        }

        return $message;
    }

    /** DocEntry del borrador de entrada de mercancías de cada factura con ítems. */
    private function HandleSaveReceiptDrafts(SapExportResult $result): void
    {
        foreach ($result->drafts as $detailId => $docEntry) {
            AccountabilityDetail::where('id', $detailId)->update(['sap_receipt_draft_entry' => $docEntry]);
        }
    }

    /**
     * Campos de trazabilidad que se guardan tras una exportación exitosa.
     */
    private function HandleSapExportAttributes(SapExportResult $result): array
    {
        return [
            'sap_exported'    => 1,
            'sap_trans_id'    => $result->key,
            'sap_exported_at' => now(),
            'sap_export_mode' => $this->sap_export_mode,
        ];
    }

    public function HandleForceAuthorize($accountability_id, Request $request)
    {
        $request->validate(['comments' => 'nullable|string|max:1000']);
        $accountability = Accountability::findOrFail($accountability_id);
        if (!$this->HandleCanAuthorize($accountability, $request->user())) {
            return $this->HandleDenied($accountability);
        }
        return $this->HandleAdvanceOrFinalize(
            $accountability,
            $request,
            0,
            Redirect::route('panel.accountability.authorization.index')->with([
                'message' => "Rendición autorizada. Pendiente de exportación a SAP",
                'type'    => 'warning',
            ])
        );
    }

    public function HandleIndexPendingExport(Request $request)
    {
        Session::put('title', 'Pendientes de Exportación SAP');
        $data = Accountability::with('user', 'profile', 'detail.document', 'exportLogs.user:id,name')
            ->pendingSapExport()
            ->get();
        return Inertia::render('authorization/PendingExport', [
            'data' => $data,
        ]);
    }

    public function HandleGetEditData($accountability_id)
    {
        return response()->json($this->HandleBuildEditData($accountability_id));
    }

    /**
     * Datos del formulario de cabecera (perfil, cuentas, empleados) compartidos por la
     * página de edición y el modal de Pendientes de Exportación.
     */
    private function HandleBuildEditData($accountability_id): array
    {
        $accountability = Accountability::with('user')->findOrFail($accountability_id);
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = $this->HandleBuildGeneralAccounts($profile->id);
        $accountability->account = $this->HandleMatchAccountKey($accounts, $accountability->account_code, $accountability->account_name);
        $accountability->employee = $accountability->employee_code;
        $employees = Employee::where('profile_id', $profile->id)->get(['card_code', 'card_name']);
        $employees = collect($employees->count() == 0
            ? $this->HandleGetUserEmployee($accountability->user->card_code)
            : $employees)
            ->map(fn ($e) => ['card_code' => data_get($e, 'card_code'), 'card_name' => data_get($e, 'card_name')]);

        // El empleado actual siempre figura entre las opciones, aunque no esté en la lista del perfil
        if ($accountability->employee_code && !$employees->contains('card_code', $accountability->employee_code)) {
            $employees->prepend([
                'card_code' => $accountability->employee_code,
                'card_name' => $accountability->employee_name ?? $accountability->employee_code,
            ]);
        }

        return [
            'profile' => $profile,
            'accounts' => $accounts,
            'accountability' => $accountability,
            'employees' => $employees->values(),
        ];
    }

    /**
     * Busca socios de negocio de SAP (OCRD) por código o nombre para elegir el
     * empleado de una rendición. Máximo 30 resultados.
     */
    public function HandleSearchEmployees(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()?->value == 'SI') {
            $db = Config::get('database.connections.hana.database');
            // HANA se consulta con SQL armado: se escapan comillas y comodines
            $like = str_replace(["'", '%', '_'], ["''", '', ''], mb_strtoupper($search));
            $sql =
                <<<SQL
            select top 30
                T1."CardCode" as "card_code",
                T1."CardName" as "card_name"
            from $db.OCRD as T1
            where UPPER(T1."CardCode") like '%$like%' or UPPER(T1."CardName") like '%$like%'
            order by T1."CardName"
SQL;
            $rows = Hana::query($sql);
            return response()->json(is_array($rows) ? $rows : []);
        }

        // SQL Server: los comodines se escapan entre corchetes
        $like = '%' . str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $search) . '%';
        return response()->json(
            DB::connection('sap')
                ->table('OCRD')
                ->select('CardCode as card_code', 'CardName as card_name')
                ->where(fn ($q) => $q->where('CardCode', 'like', $like)->orWhere('CardName', 'like', $like))
                ->orderBy('CardName')
                ->limit(30)
                ->get()
        );
    }

    public function HandleReExportSAP($accountability_id, Request $request)
    {
        $accountability = Accountability::findOrFail($accountability_id);
        // Reexportación en lote desde Pendientes de Exportación: responde JSON por rendición
        $asJson = $request->wantsJson();
        // Solo se reintenta lo autorizado que aún no está en SAP
        if ($accountability->status !== 'Autorizado') {
            if ($asJson) {
                return response()->json(['success' => false, 'message' => 'Solo se exportan rendiciones autorizadas.']);
            }
            return Redirect::route('panel.accountability.authorization.pending-export')->with([
                'message' => 'Solo se exportan rendiciones autorizadas.',
                'type' => 'negative',
            ]);
        }
        $result = $this->HandleSendToSap($accountability);

        if ($result->success) {
            $accountability->fill($this->HandleSapExportAttributes($result))->save();
            $this->HandleSaveReceiptDrafts($result);
        }

        if ($asJson) {
            return response()->json([
                'success' => $result->success,
                'sap_key' => $result->key,
                'message' => $result->success
                    ? $this->HandleSapExportMessage('Exportada a SAP', $result)
                    : $result->errorMessage,
            ]);
        }

        // Reintento desde el detalle de la rendición: se vuelve al detalle, donde se ve
        // el resultado y, si falló, se puede corregir y reintentar sin salir de la pantalla.
        if ($request->input('from') === 'detail') {
            Session::flash('message', $result->success
                ? $this->HandleSapExportMessage('Rendición exportada a SAP', $result)
                : 'SAP rechazó la exportación: ' . $result->errorMessage);
            Session::flash('type', $result->success ? 'positive' : 'negative');
            return Redirect::route('panel.accountability.authorization.detail.index', $accountability->id);
        }

        if ($result->success) {
            Session::flash('message', $this->HandleSapExportMessage("Rendición exportada a SAP correctamente", $result));
            Session::flash('type', 'positive');
            Session::flash('sap_number', $result->key);
        } else {
            Session::flash('message', $result->errorMessage);
            Session::flash('type', 'negative');
        }

        return Redirect::route('panel.accountability.authorization.pending-export');
    }
    /**
     * Bandeja del autorizador: las rendiciones pendientes que le toca autorizar (el
     * Administrador puede ver todas las pendientes con ?all=1). Las autorizadas se
     * cargan paginadas desde HandleIndexAuthorized.
     */
    public function HandleIndexAccountability(Request $request)
    {
        Session::put('title', 'Autorización de Rendiciones');
        $user = $request->user();
        $isAdmin = $user->type === 'Administrador';
        $showAll = $isAdmin && $request->boolean('all');

        $query = Accountability::with('user', 'profile', 'currentLevel.cycle.levels')
            ->withSum('detail', 'amount');
        $showAll
            ? $query->where('status', 'Pendiente')
            : $query->pendingAuthorizationFor($user->id);
        $pending = $query->orderBy('id')->get();

        return Inertia::render(
            'authorization/IndexAccountabilityNew',
            [
                'data'     => $this->HandleInboxRows($pending),
                'is_admin' => $isAdmin,
                'show_all' => $showAll,
                'profiles' => Profile::orderBy('name')->get(['id', 'name', 'type_currency']),
            ]
        );
    }

    /**
     * Rendiciones autorizadas (JSON paginado) para la pestaña "Autorizadas" de la bandeja.
     * Parámetros: page, per_page, search, profile_id, sap (exported|pending), sort, descending, all.
     */
    public function HandleIndexAuthorized(Request $request)
    {
        $user = $request->user();
        $showAll = $user->type === 'Administrador' && $request->boolean('all');

        $query = Accountability::with('user', 'profile')
            ->withSum('detail', 'amount')
            ->where('status', 'Autorizado');

        if (!$showAll) {
            // Las que aprobó en algún nivel del ciclo o, en el sistema sin ciclo, las de sus usuarios asignados
            $legacyUserIds = UserAuthorization::where('auth_user_id', $user->id)->pluck('user_id');
            $query->where(function ($q) use ($legacyUserIds, $user) {
                $q->whereHas('levelApprovals', fn ($q2) => $q2->where('user_id', $user->id))
                    ->orWhereIn('user_id', $legacyUserIds);
            });
        }

        if ($profileId = (int) $request->query('profile_id')) {
            $query->where('profile_id', $profileId);
        }

        $sap = $request->query('sap');
        if ($sap === 'exported') {
            $query->where('sap_exported', 1);
        } elseif ($sap === 'pending') {
            $query->where(fn ($q) => $q->where('sap_exported', 0)->orWhereNull('sap_exported'));
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            // SQL Server: los comodines se escapan entre corchetes
            $like = '%' . str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $search) . '%';
            $query->where(function ($q) use ($like, $search) {
                $q->where('description', 'like', $like)
                    ->orWhere('employee_name', 'like', $like)
                    ->orWhere('account_name', 'like', $like)
                    ->orWhere('sap_trans_id', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        $sortable = ['id', 'description', 'total', 'start_date', 'end_date', 'sap_exported_at', 'submitted_at'];
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'id';
        $query->orderBy($sort, $request->query('descending', 'true') === 'false' ? 'asc' : 'desc');
        if ($sort !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $perPage = min(100, max(5, (int) $request->query('per_page', 15)));
        $paginator = $query->paginate($perPage);
        $paginator->setCollection($this->HandleInboxRows($paginator->getCollection()));

        return response()->json($paginator);
    }

    /**
     * Agrega a cada rendición lo que muestra la bandeja: alias de la cuenta, monto
     * gastado (suma de importes de sus documentos), nivel actual del ciclo y fecha de envío.
     *
     * La fecha de envío es accountabilities.submitted_at (se guarda al pasar a Pendiente);
     * en rendiciones anteriores a ese campo sin auditoría se usa updated_at y se marca
     * como aproximada (submitted_source = 'modificacion').
     */
    private function HandleInboxRows($rows)
    {
        $aliasMap = GeneralAccounts::whereIn('profile_id', $rows->pluck('profile_id')->unique()->values())
            ->whereIn('account_code', $rows->pluck('account_code')->filter()->unique()->values())
            ->select('profile_id', 'account_code', 'alias')
            ->get()
            ->keyBy(fn ($g) => $g->profile_id . '|' . $g->account_code);

        return $rows->map(function ($a) use ($aliasMap) {
            $a->account_alias = $aliasMap[$a->profile_id . '|' . $a->account_code]->alias ?? null;
            $a->spent = round((float) $a->detail_sum_amount, 2);

            $a->level_label = null;
            // Nivel intermedio: se puede aprobar en lote desde la bandeja (no exporta a SAP)
            $a->is_intermediate = false;
            if ($a->relationLoaded('currentLevel') && $a->currentLevel) {
                $levels = $a->currentLevel->cycle?->levels ?? collect();
                $position = $levels->search(fn ($l) => $l->id === $a->currentLevel->id);
                $a->level_label = $position === false
                    ? $a->currentLevel->name
                    : ($position + 1) . ' de ' . $levels->count() . ' · ' . $a->currentLevel->name;
                $a->is_intermediate = $a->status === 'Pendiente'
                    && $levels->contains(fn ($l) => $l->order > $a->currentLevel->order);
            }

            $sent = $a->submitted_at ?? ($a->updated_at ? \Carbon\Carbon::parse($a->updated_at) : null);
            $a->submitted_source = $a->submitted_at ? 'envio' : 'modificacion';
            $a->submitted_label = $sent?->copy()->setTimezone('America/La_Paz')->format('Y-m-d H:i');
            $a->submitted_ts = $sent?->timestamp;
            $a->days_waiting = $sent ? (int) $sent->diffInDays(now()) : null;
            // La cadena de niveles solo sirve para la etiqueta: no se envía al navegador
            $a->unsetRelation('currentLevel');

            return $a;
        });
    }

    public function HandleEditAccountability($accountability_id)
    {
        Session::put('title', 'Editar Rendición');
        $data = $this->HandleBuildEditData($accountability_id);
        if ($data['accountability']->sap_exported) {
            return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id)->with([
                'message' => 'La rendición ya fue exportada a SAP y no se puede modificar.',
                'type' => 'negative',
            ]);
        }

        return Inertia::render('authorization/EditAccountability', $data);
    }
    public function HandleGetUserEmployee($value)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db = Config::get('database.connections.hana.database');
            $value = str_replace("'", "''", (string) $value);
            $sql =
                <<<SQL
            select
                T1."CardCode" as "card_code",
                T1."CardName" as "card_name"
            from $db.OCRD as T1
            where T1."CardCode" = '$value'
SQL;
            return Hana::query($sql);
        } else {
            return DB::connection('sap')
                ->table('OCRD')
                ->select(
                    'CardCode as card_code',
                    'CardName as card_name'
                )
                ->where('CardCode', $value)
                ->get();
        }
    }
    public function HandleGetEmployee($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $params_sap = Management::where('group', 'accountability')->get();
        $hana = $params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        if ($hana) {
            $db = Config::get('database.connections.hana.database');
            $value = str_replace("'", "''", (string) $value);
            $sql =
                <<<SQL
            select
                T1."CardCode",
                T1."CardName"
            from $db.OCRD as T1
            where T1."CardCode" = '$value'
SQL;
            $query = Hana::query($sql);
            return isset($query[0]) ? $query[0] : [];
        } else {
            return DB::connection('sap')->table('OCRD')->select('CardCode', 'CardName')->where('CardCode', $value)->first();
        }
    }
    public function HandleUpdateAccountability(AccountabilityRequest $request)
    {
        ['code' => $acctCode, 'name' => $aliasName] = $this->HandleParseAccountKey($request->account);
        $account = GeneralAccounts::where('account_code', $acctCode)->first();
        $auth_accountability = Accountability::findOrFail($request->id);
        $auth_profile = Profile::where('id', $auth_accountability->profile_id)->first();
        // Vuelve a la pantalla desde la que se editó: detalle, Pendientes SAP o bandeja
        $back = match ($request->input('from')) {
            'detail' => Redirect::route('panel.accountability.authorization.detail.index', $auth_accountability->id),
            'pending-export' => Redirect::route('panel.accountability.authorization.pending-export'),
            // Bandeja: se vuelve a la misma URL (conserva "Ver todas" y filtros)
            'index' => Redirect::back(),
            default => Redirect::route('panel.accountability.authorization.index'),
        };

        // Una rendición anulada no se modifica (el botón ya no se muestra; se valida igual aquí)
        if ($auth_accountability->status === 'Anulado') {
            return $back->with(['message' => 'La rendición está anulada y no se puede modificar.', 'type' => 'negative']);
        }

        // Una rendición ya registrada en SAP no se modifica: el asiento quedaría distinto.
        if ($auth_accountability->sap_exported) {
            return $back->with(['message' => 'La rendición ya fue exportada a SAP y no se puede modificar.', 'type' => 'negative']);
        }

        $employee_name = null;
        $employee_code = null;
        if (!$auth_profile || !$auth_profile->sin_empleado) {
            $employee = $this->HandleGetEmployee($request->employee);
            if (empty($employee)) {
                return Redirect::back()->withErrors(['employee' => 'El empleado no existe como socio de negocio en SAP.']);
            }
            $employee_name = data_get($employee, 'CardName');
            $employee_code = data_get($employee, 'CardCode');
        }

        $auth_accountability->fill([
            'employee_name' => $employee_name,
            'employee_code' => $employee_code,
            'account_code' => $acctCode,
            'account_name' => $aliasName ?? ($account->alias ?? $account->account_name),
            'total' => $request->total,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])->save();
        return $back->with(['message' => 'Rendición actualizada correctamente', 'type' => 'positive']);
    }
    public function HandleDetailAccountability(Request $request, $accountability_id)
    {
        if ($request->has('from')) {
            Session::put('detail_origin_' . $accountability_id, $request->from);
        }
        $from = Session::get('detail_origin_' . $accountability_id);

        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $documents = AccountabilityDetail::withCount(['lines', 'items'])->where('accountability_id', $accountability_id)->get();

        // Enrich with aliases
        $generalAlias = GeneralAccounts::where('profile_id', $profile->id)
            ->where('account_code', $accountability->account_code)
            ->value('alias');
        $accountability->account_alias = $generalAlias;

        $detailAliasCodes = $documents->pluck('account')->unique()->values();
        $detailAliasMap = DetailAccounts::where('profile_id', $profile->id)
            ->whereIn('account_code', $detailAliasCodes)
            ->pluck('alias', 'account_code');
        $documents = $documents->map(function ($doc) use ($detailAliasMap) {
            $doc->account_alias = $detailAliasMap[$doc->account] ?? null;
            return $doc;
        });

        $audits = Audit::where('auditable_type', Accountability::class)
            ->where('auditable_id', $accountability_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'user' => $audit->user ? $audit->user->name : 'Sistema',
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'ip_address' => $audit->ip_address,
                    'created_at' => \Carbon\Carbon::parse($audit->created_at)
                        ->setTimezone('America/La_Paz')
                        ->format('Y-m-d g:i A'),
                ];
            });

        $currentLevel = $accountability->current_level_id
            ? AuthorizationCycleLevel::find($accountability->current_level_id)
            : null;

        return Inertia::render(
            'authorization/DetailAccountabilityNew',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'documents' => $documents,
                'audits' => $audits,
                'from' => $from,
                'cycleChain' => $this->HandleBuildCycleChain($accountability),
                // Quién y cuándo dejó la rendición en su estado actual (auditoría más reciente)
                'status_change' => $audits->first(fn ($a) => ($a['new_values']['status'] ?? null) === $accountability->status
                    && $accountability->status !== null),
                'can_authorize' => $this->HandleCanAuthorize($accountability, $request->user()),
                'can_annul' => !$accountability->sap_exported && $accountability->status !== 'Anulado',
                // La cabecera se corrige desde el mismo detalle mientras no esté en SAP ni anulada
                'can_edit_header' => !$accountability->sap_exported && $accountability->status !== 'Anulado',
                'has_next_level' => (bool) $currentLevel?->nextLevel(),
                // Intentos de exportación a SAP (para corregir y reintentar desde el detalle)
                'export_logs' => SapExportLog::with('user:id,name')
                    // Sin el asiento enviado (payload): solo se muestra el resultado de cada intento
                    ->select('id', 'accountability_id', 'user_id', 'success', 'mode', 'sap_key', 'message', 'created_at')
                    ->where('accountability_id', $accountability->id)
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get(),
                // Solo tras un fallo real de exportación (HandleExportSAP): ofrece autorizar sin SAP
                'sap_error' => Session::get('sap_error'),
                // Documento recién creado o editado: el detalle se desplaza hasta él
                'focus' => (int) $request->query('focus') ?: null,
            ]
        );
    }

    /**
     * Cadena de aprobación del ciclo para mostrar en el detalle: los niveles con las
     * acciones de la ronda de envío vigente y, aparte, las acciones de envíos anteriores.
     * Devuelve null si la rendición no usa ciclo (sin nivel actual ni aprobaciones).
     */
    private function HandleBuildCycleChain(Accountability $accountability): ?array
    {
        $approvals = AccountabilityLevelApproval::with('user', 'level.cycle')
            ->where('accountability_id', $accountability->id)
            ->orderBy('acted_at')
            ->orderBy('id')
            ->get();

        $cycle = null;
        if ($accountability->current_level_id) {
            $cycle = AuthorizationCycleLevel::with('cycle')->find($accountability->current_level_id)?->cycle;
        }
        $cycle ??= $approvals->last()?->level?->cycle;
        if (!$cycle) {
            return null;
        }
        $cycle->load('levels.users');

        $round = $accountability->currentRound();
        $format = fn ($a) => [
            'user'     => $a->user?->name ?? '—',
            'level'    => $a->level?->name,
            'status'   => $a->status,
            'comments' => $a->comments,
            'acted_at' => \Carbon\Carbon::parse($a->acted_at)->setTimezone('America/La_Paz')->format('Y-m-d g:i A'),
        ];
        $current = $approvals->filter(fn ($a) => (int) $a->round === $round);

        return [
            'cycle_name' => $cycle->name,
            'current_level_id' => $accountability->current_level_id,
            'round' => $round,
            'levels' => $cycle->levels->map(fn ($level) => [
                'id'        => $level->id,
                'order'     => $level->order,
                'name'      => $level->name,
                'users'     => $level->users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                'approvals' => $current->where('level_id', $level->id)->values()->map($format),
            ])->values(),
            // Envíos anteriores (rechazados), del más reciente al más antiguo
            'previous_rounds' => $approvals->filter(fn ($a) => (int) $a->round < $round)
                ->groupBy(fn ($a) => (int) $a->round)
                ->sortKeysDesc()
                ->map(fn ($items, $r) => ['round' => $r, 'approvals' => $items->values()->map($format)])
                ->values(),
        ];
    }

    /**
     * Vista previa contable (JSON): el mismo asiento que se enviaría a SAP, con nombre
     * de cuenta, dimensiones, glosa, debe y haber por línea, totales y si cuadra.
     */
    public function HandlePreviewAccountability($accountability_id)
    {
        $accountability = Accountability::findOrFail($accountability_id);

        try {
            $payload = (new JournalVoucherPayloadBuilder())->build($accountability);
        } catch (Throwable $e) {
            Log::warning('No se pudo armar la vista contable', [
                'accountability_id' => $accountability->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'No se pudo armar el asiento: ' . $e->getMessage()], 422);
        }
        $lines = $payload['JournalVoucher']['JournalEntry']['JournalEntryLines'] ?? [];

        // Nombres de cuenta desde los datos de la aplicación (sin consultar SAP)
        $codes = collect($lines)->pluck('AccountCode')->filter()->unique()->values();
        $names = DetailAccounts::where('profile_id', $accountability->profile_id)
            ->whereIn('account_code', $codes)
            ->get(['account_code', 'account_name', 'alias'])
            ->mapWithKeys(fn ($a) => [$a->account_code => $a->alias ?: $a->account_name]);
        foreach (AccountabilityDetail::where('accountability_id', $accountability->id)->get(['account', 'account_name']) as $d) {
            if ($d->account && $d->account_name && !isset($names[$d->account])) {
                $names[$d->account] = $d->account_name;
            }
        }
        if ($accountability->account_code && !isset($names[$accountability->account_code])) {
            $names[$accountability->account_code] = $accountability->account_name;
        }

        $debit = 0.0;
        $credit = 0.0;
        $warnings = [];
        $rows = collect($lines)->values()->map(function ($line, $i) use ($names, &$debit, &$credit, &$warnings) {
            $lineDebit = round((float) ($line['Debit'] ?? 0), 2);
            $lineCredit = round((float) ($line['Credit'] ?? 0), 2);
            $debit += $lineDebit;
            $credit += $lineCredit;
            if (empty($line['AccountCode'])) {
                $warnings[] = 'La línea ' . ($i + 1) . ' no tiene cuenta contable.';
            }
            if ($lineDebit < 0 || $lineCredit < 0) {
                $warnings[] = 'La línea ' . ($i + 1) . ' tiene un monto negativo.';
            }

            return [
                'account_code' => $line['AccountCode'] ?? null,
                'account_name' => $names[$line['AccountCode'] ?? ''] ?? null,
                'short_name'   => $line['ShortName'] ?? null,
                'line_memo'    => $line['LineMemo'] ?? null,
                'project'      => $line['ProjectCode'] ?? null,
                'cost_centers' => array_values(array_filter([
                    $line['CostingCode'] ?? null,
                    $line['CostingCode2'] ?? null,
                    $line['CostingCode3'] ?? null,
                    $line['CostingCode4'] ?? null,
                    $line['CostingCode5'] ?? null,
                ], fn ($v) => $v !== null && $v !== '')),
                'debit'  => $lineDebit,
                'credit' => $lineCredit,
            ];
        });

        $debit = round($debit, 2);
        $credit = round($credit, 2);

        return response()->json([
            'lines'    => $rows,
            'memo'     => $payload['JournalVoucher']['JournalEntry']['Memo'] ?? null,
            'date'     => $payload['JournalVoucher']['JournalEntry']['ReferenceDate'] ?? null,
            'debit'    => $debit,
            'credit'   => $credit,
            'balanced' => abs($debit - $credit) < 0.005,
            'warnings' => $warnings,
        ]);
    }
    /**
     * Los documentos se pueden corregir hasta que la rendición se exporta a SAP;
     * después el asiento ya existe allí y no debe cambiar.
     */
    private function HandleRejectIfExported($accountability_id)
    {
        if (!Accountability::where('id', $accountability_id)->value('sap_exported')) {
            return null;
        }
        Session::flash('message', "La rendición ya fue exportada a SAP: sus documentos no se pueden modificar.");
        Session::flash('type', 'negative');
        return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
    }
    public function HandleDeleteDocument($accountability_id, $document_id)
    {
        if ($rejected = $this->HandleRejectIfExported($accountability_id)) {
            return $rejected;
        }
        // Solo un documento de esta rendición (si no, se saltaría el control de exportada)
        $detail = AccountabilityDetail::where('accountability_id', $accountability_id)->findOrFail($document_id);
        DB::transaction(function () use ($detail) {
            // Los campos adicionales no se borran en cascada (FK sin cascade)
            AccountabilityField::where('accountability_detail_id', $detail->id)->delete();
            $detail->delete();
        });
        Session::flash('message', "Documento eliminado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
    }
    public function HandleUpdateDocument(DocumentRequest $request, $accountability_id)
    {
        if ($rejected = $this->HandleRejectIfExported($accountability_id)) {
            return $rejected;
        }
        // Solo un documento de esta rendición: no se mueven documentos entre rendiciones
        AccountabilityDetail::where('accountability_id', $accountability_id)->findOrFail($request->id);
        try {
            DB::transaction(function () use ($request, $accountability_id) {
                ['code' => $acctCode, 'name' => $aliasName] = $this->HandleParseAccountKey($request->account);
                $account = DetailAccounts::where('account_code', $acctCode)->first();
                $detail = AccountabilityDetail::findOrFail($request->id);
                $detail->fill([
                    'accountability_id' => $accountability_id,
                    'account' => $acctCode,
                    'account_name' => $aliasName ?? ($account->alias ?? $account->account_name),
                    'date' => $request->date,
                    'document_id' => $request->document_id,
                    'document_number' => $request->document_number,
                    'authorization_number' => $request->authorization_number,
                    'cuf' => $request->cuf,
                    'control_code' => $request->control_code,
                    'business_name' => $request->business_name,
                    'nit' => $request->nit,
                    'concept' => $request->concept,
                    'amount' => $request->amount,
                    'discount' => $request->discount,
                    'excento' => $request->excento,
                    'rate' => $request->rate,
                    'gift_card' => $request->gift_card,
                    'rate_zero' => $request->rate_zero,
                    'ice' => $request->ice,
                    'project_code' => $request->project_code,
                    'distribution_rule_one' => DistributionRules::code($request->distribution_rule_one),
                    'distribution_rule_second' => DistributionRules::code($request->distribution_rule_second),
                    'distribution_rule_three' => DistributionRules::code($request->distribution_rule_three),
                    'distribution_rule_four' => DistributionRules::code($request->distribution_rule_four),
                    'distribution_rule_five' => DistributionRules::code($request->distribution_rule_five),
                ])->save();
                AccountabilityField::where('accountability_detail_id', $request->id)->delete();
                foreach ($request->field as $key => $field) {
                    AccountabilityField::create([
                        'value' => $field['value'],
                        'field_id' => $field['field_id'] ?? $field['id'],
                        'name' => $field['name'],
                        'accountability_detail_id' => $request->id
                    ]);
                }
                ExpenseDistribution::sync($detail, $request->splits, $request->lines);
                ExpenseItems::sync($detail, $request->items, (bool) Document::find($request->document_id)?->items_status);
            });
        } catch (Throwable $e) {
            // Se vuelve al formulario sin perder lo que el autorizador cargó.
            Log::error('No se pudo actualizar el documento de la rendición (autorización)', [
                'accountability_id' => $accountability_id,
                'error' => $e->getMessage(),
            ]);
            return Redirect::back()->withErrors([
                'general' => 'No se pudo actualizar el documento. Revise los datos e intente nuevamente; si el problema persiste, comuníquese con el administrador.',
            ]);
        }
        Session::flash('message', "Documento actualizado correctamente");
        Session::flash('type', 'positive');
        // focus: el detalle se ubica en el documento editado (misma pestaña y posición)
        return Redirect::route('panel.accountability.authorization.detail.index', ['id' => $accountability_id, 'focus' => $request->id]);

    }
    public function HandleStoreDocument(DocumentRequest $request, $accountability_id)
    {
        if ($rejected = $this->HandleRejectIfExported($accountability_id)) {
            return $rejected;
        }
        try {
            $detailId = DB::transaction(function () use ($request, $accountability_id) {
                ['code' => $acctCode, 'name' => $aliasName] = $this->HandleParseAccountKey($request->account);
                $account = DetailAccounts::where('account_code', $acctCode)->first();
                $detail = AccountabilityDetail::create([
                    'accountability_id' => $accountability_id,
                    'account' => $acctCode,
                    'account_name' => $aliasName ?? ($account->alias ?? $account->account_name),
                    'date' => $request->date,
                    'document_id' => $request->document_id,
                    'document_number' => $request->document_number,
                    'authorization_number' => $request->authorization_number,
                    'cuf' => $request->cuf,
                    'control_code' => $request->control_code,
                    'business_name' => $request->business_name,
                    'nit' => $request->nit,
                    'concept' => $request->concept,
                    'amount' => $request->amount,
                    'discount' => $request->discount,
                    'excento' => $request->excento,
                    'rate' => $request->rate,
                    'gift_card' => $request->gift_card,
                    'rate_zero' => $request->rate_zero,
                    'ice' => $request->ice,
                    'project_code' => $request->project_code,
                    'distribution_rule_one' => DistributionRules::code($request->distribution_rule_one),
                    'distribution_rule_second' => DistributionRules::code($request->distribution_rule_second),
                    'distribution_rule_three' => DistributionRules::code($request->distribution_rule_three),
                    'distribution_rule_four' => DistributionRules::code($request->distribution_rule_four),
                    'distribution_rule_five' => DistributionRules::code($request->distribution_rule_five),
                ]);
                // Campos adicionales del tipo de documento
                foreach ((array) $request->field as $field) {
                    AccountabilityField::create([
                        'value' => $field['value'] ?? null,
                        'field_id' => $field['field_id'] ?? $field['id'],
                        'name' => $field['name'],
                        'accountability_detail_id' => $detail->id,
                    ]);
                }
                ExpenseDistribution::sync($detail, $request->splits, $request->lines);
                ExpenseItems::sync($detail, $request->items, (bool) Document::find($request->document_id)?->items_status);
                return $detail->id;
            });
        } catch (Throwable $e) {
            // Se vuelve al formulario sin perder lo que el autorizador cargó.
            Log::error('No se pudo guardar el documento de la rendición (autorización)', [
                'accountability_id' => $accountability_id,
                'error' => $e->getMessage(),
            ]);
            return Redirect::back()->withErrors([
                'general' => 'No se pudo guardar el documento. Revise los datos e intente nuevamente; si el problema persiste, comuníquese con el administrador.',
            ]);
        }
        Session::flash('message', "Documento creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.detail.index', ['id' => $accountability_id, 'focus' => $detailId]);
    }
    public function HandleCreateDocument($accountability_id)
    {
        if ($rejected = $this->HandleRejectIfExported($accountability_id)) {
            return $rejected;
        }
        Session::put('title', 'Nuevo gasto');
        $accountability = Accountability::findOrFail($accountability_id);
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = $this->HandleBuildDetailAccounts($profile->id);
        $documents = Document::with('fields')->where('profile_id', $profile->id)->orderBy('name')->get();
        // Los proveedores se buscan bajo demanda (panel.supplier-lookup)
        return Inertia::render(
            'authorization/Detail/CreateDetailNew',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'distribution' => (new DistributionRules())->all(),
                'projects' => $this->HandleGetProjects(),
            ]
        );
    }
    public function HandleEditDocument($accountability_id, $document_id)
    {
        if ($rejected = $this->HandleRejectIfExported($accountability_id)) {
            return $rejected;
        }
        Session::put('title', 'Editar gasto');
        $accountability = Accountability::findOrFail($accountability_id);
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = $this->HandleBuildDetailAccounts($profile->id);
        $documents = Document::with('fields')->where('profile_id', $profile->id)->orderBy('name')->get();
        $data = AccountabilityDetail::with('field', 'items')
            ->where('accountability_id', $accountability_id)
            ->findOrFail($document_id);
        $data->account = $this->HandleMatchAccountKey($accounts, $data->account, $data->account_name);
        $distribution = new DistributionRules();
        $data->distribution_rule_one = $distribution->find($data->distribution_rule_one, 1);
        $data->distribution_rule_second = $distribution->find($data->distribution_rule_second, 2);
        $data->distribution_rule_three = $distribution->find($data->distribution_rule_three, 3);
        $data->distribution_rule_four = $distribution->find($data->distribution_rule_four, 4);
        $data->distribution_rule_five = $distribution->find($data->distribution_rule_five, 5);
        $split = ExpenseDistribution::forForm($data, $accounts);
        $data->unsetRelation('splits')->unsetRelation('lines');
        $data->splits = $split['splits'];
        $data->lines = $split['lines'];

        return Inertia::render(
            'authorization/Detail/EditDetail',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'data' => $data,
                'distribution' => $distribution->all(),
                'projects' => $this->HandleGetProjects(),
            ]
        );
    }
    public function HandleGetProjects()
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db = Config::get('database.connections.hana.database');
            $sql =
                <<<SQL
                select
                    T1."PrjCode",
                    CONCAT(CONCAT(T1."PrjCode",'-'),T1."PrjName") as "PrjName"
                from $db.OPRJ as T1
SQL;
            return Hana::query($sql);
        } else {
            return DB::connection('sap')
                ->table('OPRJ as T1')
                ->select(
                    'T1.PrjCode',
                    'T1.PrjName'
                )
                ->get();
        }
    }
    public function HandleUpdateStatus($accountability_id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:Rechazado,Anulado',
            'comments' => 'required|string|max:1000',
        ]);
        $accountability = Accountability::findOrFail($accountability_id);

        if ($request->status === 'Rechazado' && !$this->HandleCanAuthorize($accountability, $request->user())) {
            return $this->HandleDenied($accountability);
        }
        // Una rendición ya registrada en SAP no se anula aquí: el asiento seguiría en SAP
        if ($request->status === 'Anulado' && ($accountability->sap_exported || $accountability->status === 'Anulado')) {
            return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id)->with([
                'message' => $accountability->sap_exported
                    ? 'La rendición ya fue exportada a SAP y no se puede anular.'
                    : 'La rendición ya está anulada.',
                'type' => 'negative',
            ]);
        }

        // Con ciclo, el rechazo o la anulación quedan en la cadena del nivel actual.
        // Sin nivel actual (sistema sin ciclo o ya autorizada) la anulación se ve en el
        // historial de cambios y en el aviso de estado del detalle.
        if ($accountability->current_level_id) {
            $this->HandleRecordApproval(
                $accountability->id,
                $accountability->current_level_id,
                $request->user()->id,
                $request->status === 'Rechazado' ? 'rechazado' : 'anulado',
                $request->comments
            );
        }

        $accountability->fill([
            'status'           => $request->status,
            'comments'         => $request->comments,
            'current_level_id' => null,
        ])->save();

        $this->HandleNotifyStatus($accountability);
        Session::flash('message', $request->status === 'Rechazado' ? 'Rendición rechazada' : 'Rendición anulada');
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.index');
    }

    private function HandleBuildGeneralAccounts(int $profileId): \Illuminate\Support\Collection
    {
        $rows = GeneralAccounts::select('account_code', 'format_code', 'account_name', 'alias')
            ->where('profile_id', $profileId)->get();
        $allAliases = AccountAlias::whereIn('acct_code', $rows->pluck('account_code'))
            ->get()->groupBy('acct_code');
        return $rows->flatMap(function ($row) use ($allAliases) {
            $prefix = ($row->format_code ?? '') !== '' ? $row->format_code . '-' : '';
            $aliases = $allAliases[$row->account_code] ?? collect();
            if ($aliases->isEmpty()) {
                $display = $row->alias ?? $row->account_name;
                return [(object)['key' => $row->account_code, 'account_code' => $row->account_code, 'account_name' => $display, 'label' => $prefix . $display]];
            }
            return $aliases->map(fn($al) => (object)[
                'key' => $row->account_code . '||' . $al->alias, 'account_code' => $row->account_code, 'account_name' => $al->alias, 'label' => $prefix . $al->alias,
            ])->all();
        })->values();
    }

    private function HandleBuildDetailAccounts(int $profileId): \Illuminate\Support\Collection
    {
        $rows = DetailAccounts::select('account_code', 'format_code', 'account_name', 'alias')
            ->where('profile_id', $profileId)->get();
        $allAliases = AccountAlias::whereIn('acct_code', $rows->pluck('account_code'))
            ->get()->groupBy('acct_code');
        return $rows->flatMap(function ($row) use ($allAliases) {
            $prefix = ($row->format_code ?? '') !== '' ? $row->format_code . '-' : '';
            $aliases = $allAliases[$row->account_code] ?? collect();
            if ($aliases->isEmpty()) {
                $display = $row->alias ?? $row->account_name;
                return [(object)['key' => $row->account_code, 'account_code' => $row->account_code, 'account_name' => $display, 'label' => $prefix . $display]];
            }
            return $aliases->map(fn($al) => (object)[
                'key' => $row->account_code . '||' . $al->alias, 'account_code' => $row->account_code, 'account_name' => $al->alias, 'label' => $prefix . $al->alias,
            ])->all();
        })->values();
    }

    private function HandleParseAccountKey(string $key): array
    {
        $parts = explode('||', $key, 2);
        return ['code' => $parts[0], 'name' => count($parts) > 1 ? $parts[1] : null];
    }

    private function HandleMatchAccountKey(\Illuminate\Support\Collection $accounts, string $acctCode, ?string $acctName): string
    {
        return $accounts->first(fn($a) => $a->account_code === $acctCode && $a->account_name === $acctName)?->key ?? $acctCode;
    }
}
