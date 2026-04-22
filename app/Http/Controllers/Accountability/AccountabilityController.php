<?php

namespace App\Http\Controllers\Accountability;

use App\Notifications\Accountability\CreateAccountabilityNotification;
use App\Http\Requests\Accountability\AccountabilityRequest;
use App\Http\Requests\Accountability\DocumentRequest;
use App\Http\Controllers\Controller;
use App\Models\AccountabilityDetail;
use App\Models\GeneralAccounts;
use App\Models\DetailAccounts;
use App\Models\AccountAlias;
use App\Models\Accountability;
use App\Models\Management;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Document;
use App\Models\Profile;
use App\Models\AccountabilityField;
use Inertia\Inertia;
use Notification;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Redirect;
use Session;
use Auth;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Config;
use App\Helpers\Hana;
use App\Models\User;
use App\Models\UserAuthorizationCycle;
use Http;
use App\Models\Audit;
class AccountabilityController extends Controller
{
    public function HandleGetReportAccountability($profile_id, $accountability_id)
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
    public function HandleUpdateStatus($profile_id, $accountability_id, Request $request)
    {
        $accountability = Accountability::where('id', $accountability_id)->first();
        $user = User::with('user_authorization', 'authorizationCycle.cycle.levels.levelUsers.user')
            ->where('id', $accountability->user_id)->first();

        // Determine first level of cycle (if assigned) and notify accordingly
        $cycleRecord = UserAuthorizationCycle::with('cycle.levels.levelUsers.user')
            ->where('user_id', $user->id)
            ->first();

        $firstLevel = null;
        if ($cycleRecord && $cycleRecord->cycle && $cycleRecord->cycle->levels->isNotEmpty()) {
            $firstLevel = $cycleRecord->cycle->levels->first();
        }

        Accountability::findOrFail($accountability_id)->fill([
            'status'           => $request->status,
            'current_level_id' => $firstLevel ? $firstLevel->id : null,
        ])->save();

        $params = Management::where('group', 'accountability')->get();
        if ($params->where('name', 'notification_email')->first()->value == 'SI') {
            if ($firstLevel) {
                // Notify cycle level authorizers
                $levelAuthorizers = User::whereIn('id', $firstLevel->levelUsers->pluck('user_id'))->get();
                Notification::send($levelAuthorizers, new CreateAccountabilityNotification($accountability));
            } else {
                // Legacy: notify direct authorizers
                $authorizations = User::whereIn('id', $user->user_authorization->pluck('auth_user_id'))->get();
                Notification::send($authorizations, new CreateAccountabilityNotification($accountability));
            }
        }

        Session::flash('message', "Documento enviado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);
    }
    public function HandleUpdateDocument(DocumentRequest $request, $profile_id, $accountability_id)
    {
        ['code' => $acctCode, 'name' => $aliasName] = $this->HandleParseAccountKey($request->account);
        $account = DetailAccounts::where('account_code', $acctCode)->first();
        AccountabilityDetail::findOrFail($request->id)->fill([
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
            'distribution_rule_one' => $request->distribution_rule_one['OcrCode'] ?? $request->distribution_rule_one['PrcCode'] ?? null,
            'distribution_rule_second' => $request->distribution_rule_second['OcrCode'] ?? $request->distribution_rule_second['PrcCode'] ?? null,
            'distribution_rule_three' => $request->distribution_rule_three['OcrCode'] ?? $request->distribution_rule_three['PrcCode'] ?? null,
            'distribution_rule_four' => $request->distribution_rule_four['OcrCode'] ?? $request->distribution_rule_four['PrcCode'] ?? null,
            'distribution_rule_five' => $request->distribution_rule_five['OcrCode'] ?? $request->distribution_rule_five['PrcCode'] ?? null,
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
        Session::flash('message', "Documento actualizado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);

    }
    public function HandleEditDocument($profile_id, $accountability_id, $document_id)
    {
        $params = Management::where('group', 'supplier')->get();
        $profile = Profile::where('id', $profile_id)->first();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $accounts = $this->HandleBuildDetailAccounts($profile->id);
        $documents = Document::with('fields')->where('profile_id', $profile->id)->get();
        $data = AccountabilityDetail::with('field')->where('id', $document_id)->first();
        $data->account = $this->HandleMatchAccountKey($accounts, $data->account, $data->account_name);
        $data->distribution_rule_one = $this->HandleGetDistribution($data->distribution_rule_one, 1);
        $data->distribution_rule_second = $this->HandleGetDistribution($data->distribution_rule_second, 2);
        $data->distribution_rule_three = $this->HandleGetDistribution($data->distribution_rule_three, 3);
        $data->distribution_rule_four = $this->HandleGetDistribution($data->distribution_rule_four, 4);
        $data->distribution_rule_five = $this->HandleGetDistribution($data->distribution_rule_five, 5);

        return Inertia::render(
            'accountability/Detail/EditDetailNew',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'suppliers' => $this->HandleGetSuppliers($params),
                'data' => $data,
                'distribution' => $this->HandleGetDistributions(),
                'projects' => $this->HandleGetProjects(),
            ]
        );
    }
    public function HandleDeleteDocument($profile_id, $accountability_id, $document_id)
    {
        AccountabilityDetail::findOrFail($document_id)->delete();
        Session::flash('message', "Documento eliminado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);
    }
    public function HandleStoreDocument(DocumentRequest $request, $profile_id, $accountability_id)
    {
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
            'distribution_rule_one' => $request->distribution_rule_one['OcrCode'] ?? $request->distribution_rule_one['PrcCode'] ?? null,
            'distribution_rule_second' => $request->distribution_rule_second['OcrCode'] ?? $request->distribution_rule_second['PrcCode'] ?? null,
            'distribution_rule_three' => $request->distribution_rule_three['OcrCode'] ?? $request->distribution_rule_three['PrcCode'] ?? null,
            'distribution_rule_four' => $request->distribution_rule_four['OcrCode'] ?? $request->distribution_rule_four['PrcCode'] ?? null,
            'distribution_rule_five' => $request->distribution_rule_five['OcrCode'] ?? $request->distribution_rule_five['PrcCode'] ?? null,
        ]);
        foreach ($request->field as $key => $field) {
            AccountabilityField::create([
                'value' => $field['value'],
                'field_id' => $field['id'],
                'name' => $field['name'],
                'accountability_detail_id' => $detail->id
            ]);
        }
        Session::flash('message', "Documento creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);
    }

    public function HandleGetSuppliers($params)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        $business_name = $params->where('name', 'business_name')->first()->value;
        $nit = $params->where('name', 'nit')->first()->value;
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db = Config::get('database.connections.hana.database');
            $sql =
                <<<SQL
                select
                    T1."$business_name" as "business_name",
                    T1."$nit" as "nit"
                from $db.OCRD as T1
SQL;
            $sap_suppliers = Hana::query($sql);
        } else {
            $sap_suppliers = DB::connection('sap')
                ->table('OCRD')
                ->select(
                    $business_name . ' as business_name',
                    $nit . ' as nit'
                )
                ->get()->toArray();
        }
        $bd_suppliers = AccountabilityDetail::select(
            'business_name',
            'nit'
        )
            ->groupBy('business_name', 'nit')
            ->get()
            ->toArray();
        return array_merge($sap_suppliers, $bd_suppliers);
    }

    public function HandleCreateDocument($profile_id, $accountability_id)
    {
        $params = Management::where('group', 'supplier')->get();
        $profile = Profile::where('id', $profile_id)->first();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $accounts = $this->HandleBuildDetailAccounts($profile->id);
        $documents = Document::with('fields')->where('profile_id', $profile->id)->get();
        return Inertia::render(
            'accountability/Detail/CreateDetailNew',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'distribution' => $this->HandleGetDistributions(),
                'projects' => $this->HandleGetProjects(),
                'suppliers' => $this->HandleGetSuppliers($params)
            ]
        );
    }
    public function HandleDetailAccountability($profile_id, $accountability_id)
    {
        $profile = Profile::where('id', $profile_id)->first();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::where('accountability_id', $accountability_id)->get();

        // Enrich with global aliases
        $allCodes = collect([$accountability->account_code])
            ->merge($documents->pluck('account'))
            ->unique()->filter()->values();
        $aliasMap = AccountAlias::whereIn('acct_code', $allCodes)->pluck('alias', 'acct_code');

        $accountability->account_alias = $aliasMap[$accountability->account_code] ?? null;
        $documents = $documents->map(function ($doc) use ($aliasMap) {
            $doc->account_alias = $aliasMap[$doc->account] ?? null;
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

        return Inertia::render(
            'accountability/DetailAccountabilityNew',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'documents' => $documents,
                'audits' => $audits,
            ]
        );
    }
    public function HandleIndexAccountability(Request $request, $profile_id)
    {
        Session::put('title', 'Lista Rendiciones');
        $profile = Profile::where('id', $profile_id)->first();
        $accountabilities = Accountability::where('user_id', $request->user()->id)
            ->where('profile_id', $profile_id)
            ->get();
        return Inertia::render(
            'accountability/IndexAccountabilityNew',
            [
                'profile' => $profile,
                'data' => $accountabilities
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
                    DB::raw("CONCAT(T1.PrjCode,'-',T1.PrjName) as PrjName")
                )
                ->get();
        }
    }
    public function HandleGetDistribution($code, $number)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $data = array();
            $db = Config::get('database.connections.hana.database');
            $sql =
                <<<SQL
            select CONCAT(CONCAT(T1."OcrCode",'-'),T1."OcrName") as "Name", T1."OcrName",T1."OcrCode"
            from $db.OOCR as T1
            where T1."DimCode" = $number
            and T1."Active" = 'Y'
            and T1."OcrCode" = '$code'
            order by T1."OcrCode"
SQL;
            $data = Hana::query($sql);
            return $data[0] ?? [];
        } else {
            $data = DB::connection('sap')
                ->table('OPRC as T1')
                ->select(
                    DB::raw("CONCAT(T1.PrcCode,'-',T1.PrcName) as Name"),
                    'T1.PrcCode',
                    'T1.PrcName'
                )
                ->where('T1.DimCode', $number)
                ->where('T1.Locked', 'N')
                ->where('T1.PrcCode', $code)
                ->first();
            return $data ?? [];
        }
    }
    public function HandleGetDistributions()
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $data = array();
            $db = Config::get('database.connections.hana.database');
            for ($i = 1; $i <= 5; $i++) {
                $sql =
                    <<<SQL
                select CONCAT(CONCAT(T1."OcrCode",'-'),T1."OcrName") as "Name", T1."OcrName",T1."OcrCode"
                from $db.OOCR as T1
                where T1."DimCode" = $i
                and T1."Active" = 'Y'
                order by T1."OcrCode"
SQL;
                $data[$i] = Hana::query($sql);
            }
            return $data;
        } else {
            $data = array();
            for ($i = 1; $i <= 5; $i++) {
                $data[$i] =
                    DB::connection('sap')
                        ->table('OPRC as T1')
                        ->select(
                            DB::raw("CONCAT(T1.PrcCode,'-',T1.PrcName) as Name"),
                            'T1.PrcCode',
                            'T1.PrcName'
                        )
                        ->where('T1.DimCode', $i)
                        ->where('T1.Locked', 'N')
                        ->get();
            }
            return $data;
        }
    }
    public function HandleGetUserEmployee($value)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db = Config::get('database.connections.hana.database');
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
    public function HandleCreateAccountability($profile_id)
    {
        Session::put('title', 'Crear Rendición');
        $profile = Profile::where('id', $profile_id)->first();
        $employees = Employee::where('profile_id', $profile_id)->get();
        $accounts = $this->HandleBuildGeneralAccounts($profile->id);

        return Inertia::render(
            'accountability/CreateAccountability',
            [
                'profile' => $profile,
                'accounts' => $accounts,
                'employees' => $employees->count() == 0 ? $this->HandleGetUserEmployee(Auth::user()->card_code) : $employees
            ]
        );
    }
    public function HandleGetEmployee($value)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        $hana = $params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        if ($hana) {
            $db = Config::get('database.connections.hana.database');
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
    public function HandleDeleteAccountability($profile_id, $accountability_id)
    {
        AccountabilityDetail::where('accountability_id', $accountability_id)->delete();
        Accountability::findOrFail($accountability_id)->delete();
        Session::flash('message', "Rendición eliminada correctamente");
        Session::flash('type', 'positive');
    }
    public function HandleStoreAccountability(AccountabilityRequest $request, $profile_id)
    {
        ['code' => $acctCode, 'name' => $aliasName] = $this->HandleParseAccountKey($request->account);
        $account = GeneralAccounts::where('account_code', $acctCode)->first();
        $params_sap = Management::where('group', 'accountability')->get();
        $hana = $params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        $profile = Profile::where('id', $profile_id)->first();

        $employee_name = null;
        $employee_code = null;
        if (!$profile->sin_empleado) {
            $employee = $this->HandleGetEmployee($request->employee);
            $employee_name = $hana ? $employee['CardName'] : $employee->CardName;
            $employee_code = $hana ? $employee['CardCode'] : $employee->CardCode;
        }

        Accountability::create([
            'profile_id' => $profile_id,
            'user_id' => $request->user()->id,
            'employee_name' => $employee_name,
            'employee_code' => $employee_code,
            'account_code' => $acctCode,
            'account_name' => $aliasName ?? ($account->alias ?? $account->account_name),
            'total' => $request->total,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        Session::flash('message', "Rendición creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.index', $profile_id);
    }
    public function HandleConsultaFactura(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $parsedUrl = parse_url($request->url);

        if (!isset($parsedUrl['query'])) {
            return response()->json(['error' => 'URL inválida: no contiene parámetros'], 422);
        }

        parse_str($parsedUrl['query'], $params);

        $nit = $params['nit'] ?? null;
        $cuf = $params['cuf'] ?? null;
        $numero = $params['numero'] ?? null;
        $t = $params['t'] ?? null;

        if (!$nit || !$cuf || !$numero) {
            return response()->json(['error' => 'URL inválida: faltan parámetros (nit, cuf, numero)'], 422);
        }

        try {
            $origin_url = config('services.siat.origin');
            $api_url = config('services.siat.api');

            $response = Http::withoutVerifying()->withHeaders([
                'Accept' => 'application/json, text/plain, */*',
                'Origin' => $origin_url,
                'Referer' => $origin_url . '/',
            ])->put($api_url, [
                        'cuf' => $cuf,
                        'nitEmisor' => (int) $nit,
                        'numeroFactura' => (int) $numero,
                    ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Error al consultar SIAT: ' . $response->status()], 500);
            }

            $json = $response->json();

            if (!($json['transaccion'] ?? false)) {
                $msg = $json['mensajes'][0]['descripcion'] ?? 'Error desconocido';
                return response()->json(['error' => $msg], 422);
            }

            return response()->json($json['objeto']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al conectar con SIAT: ' . $e->getMessage()], 500);
        }
    }
    public function HandleUpdateAccountability(AccountabilityRequest $request, $profile_id)
    {
        ['code' => $acctCode, 'name' => $aliasName] = $this->HandleParseAccountKey($request->account);
        $account = GeneralAccounts::where('account_code', $acctCode)->first();
        $params_sap = Management::where('group', 'accountability')->get();
        $hana = $params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        $profile = Profile::where('id', $profile_id)->first();

        $employee_name = null;
        $employee_code = null;
        if (!$profile->sin_empleado) {
            $employee = $this->HandleGetEmployee($request->employee);
            $employee_name = $hana ? $employee['CardName'] : $employee->CardName;
            $employee_code = $hana ? $employee['CardCode'] : $employee->CardCode;
        }

        Accountability::findOrFail($request->id)->fill([
            'employee_name' => $employee_name,
            'employee_code' => $employee_code,
            'account_code' => $acctCode,
            'account_name' => $aliasName ?? ($account->alias ?? $account->account_name),
            'total' => $request->total,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])->save();
        Session::flash('message', "Rendición actualizada correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.index', $profile_id);
    }
    public function HandleEditAccountability($profile_id, $acc_id, )
    {
        Session::put('title', 'Crear Rendición');
        $profile = Profile::where('id', $profile_id)->first();
        $accounts = $this->HandleBuildGeneralAccounts($profile->id);
        $accountability = Accountability::where('id', $acc_id)->first();
        $accountability->account = $this->HandleMatchAccountKey($accounts, $accountability->account_code, $accountability->account_name);
        $accountability->employee = $accountability->employee_code;
        $employees = Employee::where('profile_id', $profile_id)->get();
        return Inertia::render(
            'accountability/EditAccountability',
            [
                'profile' => $profile,
                'accounts' => $accounts,
                'accountability' => $accountability,
                'employees' => $employees->count() == 0 ? $this->HandleGetUserEmployee(Auth::user()->card_code) : $employees
            ]
        );
    }

    private function HandleBuildGeneralAccounts(int $profileId): \Illuminate\Support\Collection
    {
        $rows = GeneralAccounts::select('account_code', 'format_code', 'account_name', 'alias')
            ->where('profile_id', $profileId)->get();
        $allAliases = AccountAlias::whereIn('acct_code', $rows->pluck('account_code'))
            ->get()->groupBy('acct_code');
        return $rows->flatMap(function ($row) use ($allAliases) {
            $aliases = $allAliases[$row->account_code] ?? collect();
            if ($aliases->isEmpty()) {
                $display = $row->alias ?? $row->account_name;
                return [(object)['key' => $row->account_code, 'account_code' => $row->account_code, 'account_name' => $display, 'label' => $display]];
            }
            return $aliases->map(fn($al) => (object)[
                'key' => $row->account_code . '||' . $al->alias, 'account_code' => $row->account_code, 'account_name' => $al->alias, 'label' => $al->alias,
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
            $aliases = $allAliases[$row->account_code] ?? collect();
            if ($aliases->isEmpty()) {
                $display = $row->alias ?? $row->account_name;
                return [(object)['key' => $row->account_code, 'account_code' => $row->account_code, 'account_name' => $display, 'label' => $display]];
            }
            return $aliases->map(fn($al) => (object)[
                'key' => $row->account_code . '||' . $al->alias, 'account_code' => $row->account_code, 'account_name' => $al->alias, 'label' => $al->alias,
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
