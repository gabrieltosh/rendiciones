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
use Illuminate\Support\Facades\Http;
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
use Log;

class AccountabilityController extends Controller
{
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
    public function HandleFormatLineReport($document_line, $management)
    {
        $journal = [];
        $detail_lines = [];
        $total = 0;
        //$exento_percentage = $document_line->document->exento / 100;
        $rate_percentage = $document_line->document->tasas / 100;
        $ice_percentage = $document_line->document->ice / 100;
        //$total_excento = $document_line->exento_status ? $document_line->exento : $document_line->amount * $exento_percentage;
        //$total_ice = $document_line->ice_status ? $document_line->ice : $document_line->amount * $ice_percentage;
        //$total_tasas = $document_line->tasas_status ? $document_line->tasas : $document_line->amount * $rate_percentage;
        //$amount = $document_line->amount - $total_excento - $total_tasas - $total_ice;
        //$operation = $document_line->document->type_calculation == 'Grossing Up' ? 1 : -1;

        foreach ($document_line->document->field as $field) {
            $amount_line = $document_line->amount + ($field->document_field->type_calculation == 'Credito' ? 1 : -1) * $field->value;
            $detail_lines[] = [
                'AccountCode' => $field->account,
                'Debit' => $field->document_field->type_calculation == 'Credito' ? 0 : $field->value,
                'Credit' => $field->document_field->type_calculation == 'Credito' ? $field->value : 0,
                'AccountName' => $field->document_field->account,
            ];
        }

        $total_ice = $document_line->ice_status ? $document_line->ice : $amount_line * $ice_percentage;
        $total_tasas = $document_line->tasas_status ? $document_line->tasas : $amount_line * $rate_percentage;

        foreach ($document_line->document->detail as $detail) {
            $exento_percentage = $detail->exento / 100;
            $total_excento = $document_line->exento_status ? $document_line->exento : $amount_line * $exento_percentage;
            $amount = $amount_line - $total_excento - $total_tasas - $total_ice;
            $operation = $detail->type_calculation == 'Grossing Up' ? 1 : -1;

            $percentage = $detail->percentage / 100;
            $total_percentage = $amount * $percentage;
            $total += $operation * $total_percentage;
            $detail_lines[] = [
                'AccountCode' => $detail->account,
                'Debit' => $detail->type_calculation == 'Grossing Up' ? 0 : $total_percentage,
                'Credit' => $detail->type_calculation == 'Grossing Up' ? $total_percentage : 0,
                'AccountName' => $detail->account_name,
            ];
        }


        $total += $amount_line;
        $journal[] = [
            'AccountCode' => $document_line->account,
            'Debit' => $total,
            'Credit' => 0,
            'AccountName' => $document_line->account_name,
        ];

        return array_merge($journal, $detail_lines);
    }
    public function HandleGetReport($accountability_id)
    {
        $journal_entry_lines = array();
        $management = Management::where('group', 'accountability_detail')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::with('document.detail', 'document.field.document_field')->where('accountability_id', $accountability_id)->orderBy('id', 'asc')->get();
        $exchange = DB::connection('sap')
            ->table('ORTT as T1')
            ->select('Rate')
            ->where('Currency', 'USD')
            ->where('RateDate', $accountability->end_date)
            ->first();
        foreach ($documents as $document) {
            foreach ($document->document->detail as $key => $value) {
                $value->account_name = DB::connection('sap')
                    ->table('OACT as T1')
                    ->select('T1.AcctCode', 'T1.AcctName')
                    ->where('T1.AcctCode', $value->account)
                    ->first()->AcctName;
            }
        }
        foreach ($documents as $document) {
            $journal_entry_lines = array_merge($journal_entry_lines, $this->HandleFormatLineReport($document, $management));
        }

        $total_debit = 0;
        $total_credit = 0;
        foreach ($journal_entry_lines as $line) {
            $total_debit += (float) $line['Debit'];
            $total_credit += (float) $line['Credit'];
        }
        $debit = $total_debit - $total_credit;
        $report_profile = Profile::where('id', $accountability->profile_id)->first();
        $short_name = ($report_profile && $report_profile->sin_empleado)
            ? $accountability->account_code
            : $accountability->employee_code;
        $last_line[] = [
            'AccountCode' => $accountability->account_code,
            'AccountName' => $accountability->account_name,
            'Debit' => 0,
            'Credit' => $debit,
            'ShortName' => $short_name,
            'LineMemo' => $accountability->description,
        ];
        $journal_entry_lines = array_merge($journal_entry_lines, $last_line);

        $pdf = Pdf::loadView('pdf.accountability', [
            'accountability' => $accountability,
            'documents' => $journal_entry_lines,
            'exchange' => isset($exchange->Rate) ? $exchange->Rate : 0
        ]);
        return $pdf->stream();
    }
    public function HandleFormatLine($document_line, $management)
    {
        $journal = [];
        $detail_lines = [];
        $total = 0;
        $amount_line = $document_line->amount;
        //$exento_percentage = $document_line->document->exento / 100;
        $rate_percentage = $document_line->document->tasas / 100;
        $ice_percentage = $document_line->document->ice / 100;
        // $total_excento = $document_line->exento_status ? $document_line->exento : $document_line->amount * $exento_percentage;
        // $total_ice = $document_line->ice_status ? $document_line->ice : $document_line->amount * $ice_percentage;
        // $total_tasas = $document_line->tasas_status ? $document_line->tasas : $document_line->amount * $rate_percentage;
        // $amount = $document_line->amount - $total_excento - $total_tasas - $total_ice;
        // $operation = $document_line->document->type_calculation == 'Grossing Up' ? 1 : -1;

        foreach ($document_line->field as $field) {
            $amount_line += ($field->document_field->type_calculation == 'Credito' ? 1 : -1) * $field->value;
            $detail_lines[] = [
                'AccountCode' => $field->document_field->account,
                'Debit' => $field->document_field->type_calculation == 'Credito' ? 0 : $field->value,
                'Credit' => $field->document_field->type_calculation == 'Credito' ? $field->value : 0,
                'ShortName' => $field->document_field->account,
                'LineMemo' => $document_line->concept
            ];
        }
        $total_ice = $document_line->ice_status ? $document_line->ice : $amount_line * $ice_percentage;
        $total_tasas = $document_line->tasas_status ? $document_line->tasas : $amount_line * $rate_percentage;

        $max_exento = 0;
        foreach ($document_line->document->detail as $detail) {
            $exento_percentage = $detail->exento / 100;
            $total_excento = $document_line->exento_status ? $document_line->exento : $amount_line * $exento_percentage;
            $max_exento = $total_excento > $max_exento ? $total_excento : $max_exento;

            //$amount = $amount_line - $total_excento - $total_tasas - $total_ice;
            $amount = ($total_excento == 0 ? $amount_line : $total_excento) + $total_tasas + $total_ice;

            $operation = $detail->type_calculation == 'Grossing Up' ? 1 : -1;
            $percentage = $detail->percentage / 100;
            $total_percentage = $amount * $percentage;
            $total += $operation * $total_percentage;
            $detail_lines[] = [
                'AccountCode' => $detail->account,
                'Debit' => $document_line->document->type_calculation == 'Grossing Up' ? 0 : $total_percentage,
                'Credit' => $document_line->document->type_calculation == 'Grossing Up' ? $total_percentage : 0,
                'ShortName' => $detail->account,
                'LineMemo' => $document_line->concept
            ];
        }
        $total += $amount_line;

        $udfs = [
            'ProjectCode' => $document_line->project_code,
            'CostingCode' => $document_line->distribution_rule_one,
            'CostingCode2' => $document_line->distribution_rule_second,
            'CostingCode3' => $document_line->distribution_rule_three,
            'CostingCode4' => $document_line->distribution_rule_four,
            'CostingCode5' => $document_line->distribution_rule_five,
            $management->where('name', 'date')->first()->value => $document_line->date,
            $management->where('name', 'document_number')->first()->value => $document_line->document_number,
            $management->where('name', 'authorization_number')->first()->value => $document_line->authorization_number,
            $management->where('name', 'cuf')->first()->value => $document_line->cuf,
            $management->where('name', 'control_code')->first()->value => $document_line->control_code,
            $management->where('name', 'business_name')->first()->value => $document_line->business_name,
            $management->where('name', 'nit')->first()->value => $document_line->nit,
            $management->where('name', 'amount')->first()->value => $document_line->amount,
            $management->where('name', 'discount')->first()->value => $document_line->discount,
            $management->where('name', 'excento')->first()->value => $max_exento,
            $management->where('name', 'rate')->first()->value => $document_line->rate,
            $management->where('name', 'gift_card')->first()->value => $document_line->gift_card,
            $management->where('name', 'ice')->first()->value => $document_line->ice,
            $management->where('name', 'document_type')->first()->value => $document_line->document->type_document_sap
        ];

        $journal[] = array_merge([
            'AccountCode' => $document_line->account,
            'Debit' => $total,
            'Credit' => 0,
            'ShortName' => $document_line->account,
            'LineMemo' => $document_line->concept,
        ], $udfs);

        foreach ($detail_lines as &$d_line) {
            $d_line = array_merge($d_line, $udfs);
        }

        return array_merge($journal, $detail_lines);
    }
    private function HandleRecordApproval($accountability_id, $level_id, $user_id, $status, $comments = null)
    {
        AccountabilityLevelApproval::create([
            'accountability_id' => $accountability_id,
            'level_id'          => $level_id,
            'user_id'           => $user_id,
            'status'            => $status,
            'comments'          => $comments,
            'acted_at'          => now(),
        ]);
    }

    private function HandleAdvanceOrFinalize($accountability, $request, $sapExported, $redirectOnFinish)
    {
        $currentLevel = $accountability->current_level_id
            ? AuthorizationCycleLevel::find($accountability->current_level_id)
            : null;

        // Record approval when using cycle system
        if ($currentLevel) {
            $this->HandleRecordApproval(
                $accountability->id,
                $currentLevel->id,
                $request->user()->id,
                'aprobado',
                $request->comments
            );
            $nextLevel = $currentLevel->nextLevel();
            if ($nextLevel) {
                // Advance to next level
                $accountability->fill([
                    'current_level_id' => $nextLevel->id,
                ])->save();

                // Notify next level authorizers
                $params = Management::where('group', 'accountability')->get();
                if ($params->where('name', 'notification_email')->first()->value == 'SI') {
                    $nextUsers = $nextLevel->users;
                    foreach ($nextUsers as $nextUser) {
                        $nextUser->notify(new CreateAccountabilityNotification($accountability));
                    }
                }

                $levelName = $nextLevel->name;
                Session::flash('message', "Nivel aprobado. La rendición avanzó al nivel: {$levelName}");
                Session::flash('type', 'positive');
                return Redirect::route('panel.accountability.authorization.index');
            }
        }

        // No more levels (or legacy system) — fully authorize
        $accountability->fill([
            'status'      => 'Autorizado',
            'comments'    => $request->comments,
            'sap_exported' => $sapExported,
            'current_level_id' => null,
        ])->save();

        $user = User::find($accountability->user_id);
        $params = Management::where('group', 'accountability')->get();
        if ($params->where('name', 'notification_email')->first()->value == 'SI') {
            $user->notify(new StatusAccountabilityNotification($accountability->fresh()));
        }

        return $redirectOnFinish;
    }

    public function HandleExportSAP($accountability_id, Request $request)
    {
        $journal_entry_lines = array();
        $management = Management::where('group', 'accountability_detail')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();

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
        $documents = AccountabilityDetail::with('document.detail', 'field.document_field')->where('accountability_id', $accountability_id)->orderBy('id', 'desc')->get();
        foreach ($documents as $document) {
            $journal_entry_lines = array_merge($journal_entry_lines, $this->HandleFormatLine($document, $management));
        }

        $total_debit = 0;
        $total_credit = 0;
        foreach ($journal_entry_lines as $line) {
            $total_debit += (float) $line['Debit'];
            $total_credit += (float) $line['Credit'];
        }
        $debit = $total_debit - $total_credit;
        $export_profile = Profile::where('id', $accountability->profile_id)->first();
        $export_short_name = ($export_profile && $export_profile->sin_empleado)
            ? $accountability->account_code
            : $accountability->employee_code;
        $last_line[] = [
            'AccountCode' => $accountability->account_code,
            'Debit' => 0,
            'Credit' => $debit,
            'ShortName' => $export_short_name,
            'LineMemo' => $accountability->description,
        ];
        $journal_entry_lines = array_merge($journal_entry_lines, $last_line);
        $exportUser = User::find($accountability->user_id);
        $userFieldKey = $management->where('name', 'user_field')->first()?->value;
        $journalEntry = [
            'Memo' => $accountability->description,
            'ReferenceDate' => $accountability->end_date,
            'TaxDate' => $accountability->end_date,
            'DueDate' => $accountability->end_date,
            'JournalEntryLines' => $journal_entry_lines,
        ];
        if ($userFieldKey) {
            $journalEntry[$userFieldKey] = $exportUser->name;
        }
        $journal_entries = [
            'JournalVoucher' => [
                'JournalEntry' => $journalEntry,
            ],
        ];

        Log::info('SAP Export Data:', $journal_entries);

        $params_sap = Management::where('group', 'accountability')->get();

        try {
            $login = Http::withoutVerifying()
                ->baseUrl($params_sap->where('name', 'service_layer')->first()->value . '/b1s/v1/')
                ->post('Login', [
                    'CompanyDB' => $params_sap->where('name', 'bd_sap')->first()->value,
                    'UserName' => $params_sap->where('name', 'user')->first()->value,
                    'Password' => $params_sap->where('name', 'password')->first()->value
                ]);
            if ($login->successful()) {
                $session = $login["SessionId"];
                $response = Http::baseUrl($params_sap->where('name', 'service_layer')->first()->value . '/b1s/v1/')
                    ->withoutVerifying()
                    ->withHeaders([
                        'Cookie' => 'B1SESSION=' . $session . '; ROUTEID=.node9',
                    ])->post('JournalVouchersService_Add', $journal_entries);
                if ($response->successful()) {
                    if ($currentLevel) {
                        $this->HandleRecordApproval(
                            $accountability->id,
                            $currentLevel->id,
                            $request->user()->id,
                            'aprobado',
                            $request->comments
                        );
                    }
                    $accountability->fill([
                        'status'           => 'Autorizado',
                        'comments'         => $request->comments,
                        'sap_exported'     => 1,
                        'current_level_id' => null,
                    ])->save();
                    Session::flash('message', "Documento autorizado y exportado correctamente");
                    Session::flash('type', 'positive');
                    $user = User::find($accountability->user_id);
                    $params = Management::where('group', 'accountability')->get();
                    if ($params->where('name', 'notification_email')->first()->value == 'SI') {
                        $user->notify(new StatusAccountabilityNotification($accountability->fresh()));
                    }
                    return Redirect::route('panel.accountability.authorization.index');
                } else {
                    Session::flash('message', $response->json()['error']['message']['value'] ?? 'Error al exportar a SAP');
                    Session::flash('type', 'negative');
                    return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
                }
            } else {
                Session::flash('message', $login->json()['error']['message']['value'] ?? 'Error al conectar con SAP');
                Session::flash('type', 'negative');
                return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
            }
        } catch (Throwable $e) {
            Session::flash('message', $e->getMessage());
            Session::flash('type', 'negative');
            return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
        }
    }

    public function HandleForceAuthorize($accountability_id, Request $request)
    {
        $accountability = Accountability::findOrFail($accountability_id);
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
        $data = Accountability::with('user', 'profile', 'detail.document')
            ->where('status', 'Autorizado')
            ->where('sap_exported', 0)
            ->get();
        return Inertia::render('authorization/PendingExport', [
            'data' => $data,
        ]);
    }

    public function HandleGetEditData($accountability_id)
    {
        $accountability = Accountability::with('user')->where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',ISNULL(alias, account_name)) as label"),
            'account_code',
            'account_name',
            'alias'
        )->where('profile_id', $profile->id)->get();
        $accountability->account = $accountability->account_code;
        $accountability->employee = $accountability->employee_code;
        $employees = Employee::where('profile_id', $profile->id)->get();
        return response()->json([
            'profile' => $profile,
            'accounts' => $accounts,
            'accountability' => $accountability,
            'employees' => $employees->count() == 0
                ? $this->HandleGetUserEmployee($accountability->user->card_code)
                : $employees,
        ]);
    }

    public function HandleReExportSAP($accountability_id, Request $request)
    {
        $journal_entry_lines = [];
        $management = Management::where('group', 'accountability_detail')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::with('document.detail', 'field.document_field')
            ->where('accountability_id', $accountability_id)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($documents as $document) {
            $journal_entry_lines = array_merge($journal_entry_lines, $this->HandleFormatLine($document, $management));
        }

        $total_debit = 0;
        $total_credit = 0;
        foreach ($journal_entry_lines as $line) {
            $total_debit += (float) $line['Debit'];
            $total_credit += (float) $line['Credit'];
        }
        $debit = $total_debit - $total_credit;
        $export_profile = Profile::where('id', $accountability->profile_id)->first();
        $export_short_name = ($export_profile && $export_profile->sin_empleado)
            ? $accountability->account_code
            : $accountability->employee_code;

        $last_line[] = [
            'AccountCode' => $accountability->account_code,
            'Debit' => 0,
            'Credit' => $debit,
            'ShortName' => $export_short_name,
            'LineMemo' => $accountability->description,
        ];
        $journal_entry_lines = array_merge($journal_entry_lines, $last_line);
        $reExportUser = User::find($accountability->user_id);
        $reExportUserFieldKey = $management->where('name', 'user_field')->first()?->value;
        $reExportJournalEntry = [
            'Memo' => $accountability->description,
            'ReferenceDate' => $accountability->end_date,
            'TaxDate' => $accountability->end_date,
            'DueDate' => $accountability->end_date,
            'JournalEntryLines' => $journal_entry_lines,
        ];
        if ($reExportUserFieldKey) {
            $reExportJournalEntry[$reExportUserFieldKey] = $reExportUser->name;
        }
        $journal_entries = [
            'JournalVoucher' => [
                'JournalEntry' => $reExportJournalEntry,
            ],
        ];

        $params_sap = Management::where('group', 'accountability')->get();

        try {
            $login = Http::withoutVerifying()
                ->baseUrl($params_sap->where('name', 'service_layer')->first()->value . '/b1s/v1/')
                ->post('Login', [
                    'CompanyDB' => $params_sap->where('name', 'bd_sap')->first()->value,
                    'UserName' => $params_sap->where('name', 'user')->first()->value,
                    'Password' => $params_sap->where('name', 'password')->first()->value,
                ]);
            if ($login->successful()) {
                $session = $login['SessionId'];
                $response = Http::baseUrl($params_sap->where('name', 'service_layer')->first()->value . '/b1s/v1/')
                    ->withoutVerifying()
                    ->withHeaders(['Cookie' => 'B1SESSION=' . $session . '; ROUTEID=.node9'])
                    ->post('JournalVouchersService_Add', $journal_entries);
                if ($response->successful()) {
                    Accountability::findOrFail($accountability_id)->fill(['sap_exported' => 1])->save();
                    Session::flash('message', "Rendición exportada a SAP correctamente");
                    Session::flash('type', 'positive');
                } else {
                    Session::flash('message', $response->json()['error']['message']['value'] ?? 'Error al exportar a SAP');
                    Session::flash('type', 'negative');
                }
            } else {
                Session::flash('message', $login->json()['error']['message']['value'] ?? 'Error al conectar con SAP');
                Session::flash('type', 'negative');
            }
        } catch (Throwable $e) {
            Session::flash('message', $e->getMessage());
            Session::flash('type', 'negative');
        }
        return Redirect::route('panel.accountability.authorization.pending-export');
    }
    public function HandleIndexAccountability(Request $request)
    {
        Session::put('title', 'Lista Rendiciones');
        $authUserId = $request->user()->id;

        // New system: accountabilities where current user is in the current level
        $levelIds = AuthorizationCycleLevel::whereHas('levelUsers', function ($q) use ($authUserId) {
            $q->where('user_id', $authUserId);
        })->pluck('id');

        // Legacy system: accountabilities with no cycle (current_level_id is null)
        $legacyUserIds = UserAuthorization::where('auth_user_id', $authUserId)->pluck('user_id');

        $accountabilities = Accountability::with('user', 'profile', 'currentLevel.cycle')
            ->where('status', 'Pendiente')
            ->where(function ($q) use ($levelIds, $legacyUserIds) {
                $q->whereIn('current_level_id', $levelIds)
                  ->orWhere(function ($q2) use ($legacyUserIds) {
                      $q2->whereNull('current_level_id')
                         ->whereIn('user_id', $legacyUserIds);
                  });
            })
            ->get();

        $authorized = Accountability::with('user', 'profile', 'currentLevel.cycle')
            ->where('status', 'Autorizado')
            ->where(function ($q) use ($levelIds, $legacyUserIds, $authUserId) {
                $q->whereHas('levelApprovals', function ($q2) use ($authUserId) {
                    $q2->where('user_id', $authUserId);
                })->orWhere(function ($q2) use ($legacyUserIds) {
                    $q2->whereIn('user_id', $legacyUserIds);
                });
            })
            ->get();

        // Enrich both collections with general_accounts alias
        $all = $accountabilities->merge($authorized);
        $profileIds  = $all->pluck('profile_id')->unique()->values();
        $accountCodes = $all->pluck('account_code')->unique()->values();

        $aliasMap = GeneralAccounts::whereIn('profile_id', $profileIds)
            ->whereIn('account_code', $accountCodes)
            ->select('profile_id', 'account_code', 'alias')
            ->get()
            ->keyBy(fn($g) => $g->profile_id . '|' . $g->account_code);

        $enrich = function ($collection) use ($aliasMap) {
            return $collection->map(function ($a) use ($aliasMap) {
                $a->account_alias = $aliasMap[$a->profile_id . '|' . $a->account_code]->alias ?? null;
                return $a;
            });
        };

        return Inertia::render(
            'authorization/IndexAccountabilityNew',
            [
                'data'       => $enrich($accountabilities),
                'authorized' => $enrich($authorized),
            ]
        );
    }
    public function HandleEditAccountability($accountability_id)
    {
        Session::put('title', 'Crear Rendición');
        $accountability = Accountability::with('user')->where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',ISNULL(alias, account_name)) as label"),
            'account_code',
            'account_name',
            'alias'
        )->where('profile_id', $profile->id)->get();
        $accountability->account = $accountability->account_code;
        $accountability->employee = $accountability->employee_code;
        $employees = Employee::where('profile_id', $profile->id)->get();
        return Inertia::render(
            'authorization/EditAccountability',
            [
                'profile' => $profile,
                'accounts' => $accounts,
                'accountability' => $accountability,
                'employees' => $employees->count() == 0 ? $this->HandleGetUserEmployee($accountability->user->card_code) : $employees
            ]
        );
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
    public function HandleUpdateAccountability(AccountabilityRequest $request)
    {
        $account = GeneralAccounts::where('account_code', $request->account)->first();
        $params_sap = Management::where('group', 'accountability')->get();
        $hana = $params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        $auth_accountability = Accountability::where('id', $request->id)->first();
        $auth_profile = Profile::where('id', $auth_accountability->profile_id)->first();

        $employee_name = null;
        $employee_code = null;
        if (!$auth_profile || !$auth_profile->sin_empleado) {
            $employee = $this->HandleGetEmployee($request->employee);
            $employee_name = $hana ? $employee['CardName'] : $employee->CardName;
            $employee_code = $hana ? $employee['CardCode'] : $employee->CardCode;
        }

        Accountability::findOrFail($request->id)->fill([
            'employee_name' => $employee_name,
            'employee_code' => $employee_code,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'total' => $request->total,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])->save();
        Session::flash('message', "Rendición actualizada correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.index');
    }
    public function HandleDetailAccountability(Request $request, $accountability_id)
    {
        if ($request->has('from')) {
            Session::put('detail_origin_' . $accountability_id, $request->from);
        }
        $from = Session::get('detail_origin_' . $accountability_id);

        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $documents = AccountabilityDetail::where('accountability_id', $accountability_id)->get();

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

        // Build cycle approval chain for display
        $cycleChain = null;
        if ($accountability->current_level_id) {
            $currentLevel = AuthorizationCycleLevel::with('cycle.levels.users', 'users')->find($accountability->current_level_id);
            if ($currentLevel) {
                $approvals = AccountabilityLevelApproval::with('user', 'level')
                    ->where('accountability_id', $accountability_id)
                    ->get();
                $cycleChain = [
                    'cycle_name' => $currentLevel->cycle->name,
                    'current_level_id' => $accountability->current_level_id,
                    'levels' => $currentLevel->cycle->levels->map(function ($level) use ($approvals) {
                        $levelApprovals = $approvals->where('level_id', $level->id);
                        return [
                            'id'       => $level->id,
                            'order'    => $level->order,
                            'name'     => $level->name,
                            'users'    => $level->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                            'approvals' => $levelApprovals->values()->map(fn($a) => [
                                'user'     => $a->user->name,
                                'status'   => $a->status,
                                'comments' => $a->comments,
                                'acted_at' => \Carbon\Carbon::parse($a->acted_at)->setTimezone('America/La_Paz')->format('Y-m-d g:i A'),
                            ]),
                        ];
                    }),
                ];
            }
        } elseif ($accountability->status === 'Autorizado') {
            // Show completed chain for already-authorized with cycle
            $approvals = AccountabilityLevelApproval::with('user', 'level.cycle')
                ->where('accountability_id', $accountability_id)
                ->get();
            if ($approvals->isNotEmpty()) {
                $cycle = $approvals->first()->level->cycle;
                $cycleChain = [
                    'cycle_name' => $cycle->name,
                    'current_level_id' => null,
                    'levels' => $cycle->levels->map(function ($level) use ($approvals) {
                        $levelApprovals = $approvals->where('level_id', $level->id);
                        return [
                            'id'       => $level->id,
                            'order'    => $level->order,
                            'name'     => $level->name,
                            'users'    => $level->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                            'approvals' => $levelApprovals->values()->map(fn($a) => [
                                'user'     => $a->user->name,
                                'status'   => $a->status,
                                'comments' => $a->comments,
                                'acted_at' => \Carbon\Carbon::parse($a->acted_at)->setTimezone('America/La_Paz')->format('Y-m-d g:i A'),
                            ]),
                        ];
                    }),
                ];
            }
        }

        return Inertia::render(
            'authorization/DetailAccountabilityNew',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'documents' => $documents,
                'audits' => $audits,
                'from' => $from,
                'cycleChain' => $cycleChain,
            ]
        );
    }
    public function HandleDeleteDocument($accountability_id, $document_id)
    {
        AccountabilityDetail::findOrFail($document_id)->delete();
        Session::flash('message', "Documento eliminado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);
    }
    public function HandleUpdateDocument(DocumentRequest $request, $accountability_id)
    {
        $account = DetailAccounts::where('account_code', $request->account)->first();
        AccountabilityDetail::findOrFail($request->id)->fill([
            'accountability_id' => $accountability_id,
            'account' => $request->account,
            'account_name' => $account->account_name,
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
            'distribution_rule_one' => $request->distribution_rule_one['OcrCode'] ?? null,
            'distribution_rule_second' => $request->distribution_rule_second['OcrCode'] ?? null,
            'distribution_rule_three' => $request->distribution_rule_three['OcrCode'] ?? null,
            'distribution_rule_four' => $request->distribution_rule_four['OcrCode'] ?? null,
            'distribution_rule_five' => $request->distribution_rule_five['OcrCode'] ?? null,
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
        return Redirect::route('panel.accountability.authorization.detail.index', $accountability_id);

    }
    public function HandleStoreDocument(DocumentRequest $request, $accountability_id)
    {
        $account = DetailAccounts::where('account_code', $request->account)->first();
        AccountabilityDetail::create([
            'accountability_id' => $accountability_id,
            'account' => $request->account,
            'account_name' => $account->account_name,
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
            'distribution_rule_one' => $request->distribution_rule_one['OcrCode'] ?? null,
            'distribution_rule_second' => $request->distribution_rule_second['OcrCode'] ?? null,
            'distribution_rule_three' => $request->distribution_rule_three['OcrCode'] ?? null,
            'distribution_rule_four' => $request->distribution_rule_four['OcrCode'] ?? null,
            'distribution_rule_five' => $request->distribution_rule_five['OcrCode'] ?? null,
        ]);
        Session::flash('message', "Documento creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.detail.index', [$accountability_id]);
    }
    public function HandleCreateDocument($accountability_id)
    {
        $params = Management::where('group', 'supplier')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',ISNULL(alias, account_name)) as label"),
            'account_code',
            'account_name',
            'alias'
        )->where('profile_id', $profile->id)->get();
        $documents = Document::where('profile_id', $profile->id)->get();
        $suppliers = Supplier::get();
        return Inertia::render(
            'authorization/Detail/CreateDetail',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'suppliers' => $this->HandleGetSuppliers($params),
                'distribution' => $this->HandleGetDistributions(),
                'projects' => $this->HandleGetProjects(),
            ]
        );
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
    public function HandleEditDocument($accountability_id, $document_id)
    {
        $params = Management::where('group', 'supplier')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',ISNULL(alias, account_name)) as label"),
            'account_code',
            'account_name',
            'alias'
        )->where('profile_id', $profile->id)->get();
        $documents = Document::with('fields')->where('profile_id', $profile->id)->get();
        $suppliers = Supplier::get();
        $data = AccountabilityDetail::with('field')->where('id', $document_id)->first();
        $data->distribution_rule_one = $this->HandleGetDistribution($data->distribution_rule_one, 1);
        $data->distribution_rule_second = $this->HandleGetDistribution($data->distribution_rule_second, 2);
        $data->distribution_rule_three = $this->HandleGetDistribution($data->distribution_rule_three, 3);
        $data->distribution_rule_four = $this->HandleGetDistribution($data->distribution_rule_four, 4);
        $data->distribution_rule_five = $this->HandleGetDistribution($data->distribution_rule_five, 5);

        return Inertia::render(
            'authorization/Detail/EditDetail',
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
    public function HandleUpdateStatus($accountability_id, Request $request)
    {
        $accountability = Accountability::findOrFail($accountability_id);

        // Record rejection in approvals when using cycle system
        if ($accountability->current_level_id && $request->status === 'Rechazado') {
            $this->HandleRecordApproval(
                $accountability->id,
                $accountability->current_level_id,
                $request->user()->id,
                'rechazado',
                $request->comments
            );
        }

        $accountability->fill([
            'status'           => $request->status,
            'comments'         => $request->comments,
            'current_level_id' => null,
        ])->save();

        $user = User::find($accountability->user_id);
        $params = Management::where('group', 'accountability')->get();
        if ($params->where('name', 'notification_email')->first()->value == 'SI') {
            $user->notify(new StatusAccountabilityNotification($accountability->fresh()));
        }
        Session::flash('message', "Documento enviado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.index');
    }
}
