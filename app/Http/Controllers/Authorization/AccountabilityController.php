<?php

namespace App\Http\Controllers\Authorization;

use App\Http\Controllers\Controller;
use App\Models\UserAuthorization;
use Illuminate\Http\Request;
use Redirect;
use Inertia\Inertia;
use Session;
use App\Models\Accountability;
use App\Http\Requests\Accountability\DocumentRequest;
use App\Models\AccountabilityDetail;
use App\Models\User;
use App\Models\DetailAccounts;
use App\Models\Profile;
use App\Models\GeneralAccounts;
use App\Models\Supplier;
use App\Models\Document;
use App\Http\Requests\Accountability\AccountabilityRequest;
use DB;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Auth;
use Throwable ;
use App\Models\Management;
use Barryvdh\DomPDF\Facade\Pdf;

class AccountabilityController extends Controller
{
    public function HandleFormatLineReport($document_line, $management)
    {
        $journal = [];
        $detail_lines = [];
        $total = 0;
        $exento_percentage = $document_line->document->exento / 100;
        $rate_percentage = $document_line->document->tasas / 100;
        $ice_percentage = $document_line->document->ice / 100;
        $total_excento = $document_line->exento_status ? $document_line->exento : $document_line->amount * $exento_percentage;
        $total_ice = $document_line->ice_status ? $document_line->ice : $document_line->amount * $ice_percentage;
        $total_tasas = $document_line->tasas_status ? $document_line->tasas : $document_line->amount * $rate_percentage;
        $amount = $document_line->amount - $total_excento - $total_tasas - $total_ice;
        $operation = $document_line->document->type_calculation == 'Grossing Up' ? 1 : -1;

        foreach ($document_line->document->detail as $detail) {
            $percentage = $detail->percentage / 100;
            $total_percentage = $amount * $percentage;
            $total += ($operation * $total_percentage);
            $detail_lines[] = [
                'AccountCode' => $detail->account,
                'Debit' => $document_line->document->type_calculation == 'Grossing Up' ? 0 : $total_percentage,
                'Credit' => $document_line->document->type_calculation == 'Grossing Up' ? $total_percentage : 0,
                'AccountName' => $detail->account_name,
            ];
        }
        $total += $document_line->amount;
        $journal[] = [
            'AccountCode' => $document_line->account,
            'Debit' => $total,
            'Credit' => 0,
            'AccountName' => $document_line->account_name,
        ];

        return array_merge($journal, $detail_lines);
    }
    public function HandleGetReport($accountability_id){
        $journal_entry_lines = array();
        $management = Management::where('group', 'accountability_detail')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::with('document.detail')->where('accountability_id', $accountability_id)->orderBy('id', 'asc')->get();
        $exchange=DB::connection('sap')
                    ->table('ORTT as T1')
                    ->select('Rate')
                    ->where('Currency','USD')
                    ->where('RateDate',$accountability->end_date)
                    ->toSql();
        foreach ($documents as $document) {
            foreach ($document->document->detail as $key => $value) {
                $value->account_name=DB::connection('sap')
                                    ->table('OACT as T1')
                                    ->select('T1.AcctCode','T1.AcctName')
                                    ->where('T1.AcctCode',$value->account)
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
        $last_line[] = [
            'AccountCode' => $accountability->account_code,
            'AccountName' => $accountability->account_name,
            'Debit' => 0,
            'Credit' => $debit,
            'ShortName' => $accountability->employee_code,
            'LineMemo' => $accountability->description,
        ];
        $journal_entry_lines = array_merge($journal_entry_lines, $last_line);

        $pdf = Pdf::loadView('pdf.accountability',[
            'accountability'=>$accountability,
            'documents'=>$journal_entry_lines,
            'exchange'=>isset($exchange->Rate)?$exchange->Rate:0
        ]);
        return $pdf->stream();
    }
    public function HandleFormatLine($document_line, $management)
    {
        $journal = [];
        $detail_lines = [];
        $total = 0;
        $exento_percentage = $document_line->document->exento / 100;
        $rate_percentage = $document_line->document->tasas / 100;
        $ice_percentage = $document_line->document->ice / 100;
        $total_excento = $document_line->exento_status ? $document_line->exento : $document_line->amount * $exento_percentage;
        $total_ice = $document_line->ice_status ? $document_line->ice : $document_line->amount * $ice_percentage;
        $total_tasas = $document_line->tasas_status ? $document_line->tasas : $document_line->amount * $rate_percentage;
        $amount = $document_line->amount - $total_excento - $total_tasas - $total_ice;
        $operation = $document_line->document->type_calculation == 'Grossing Up' ? 1 : -1;

        foreach ($document_line->document->detail as $detail) {
            $percentage = $detail->percentage / 100;
            $total_percentage = $amount * $percentage;
            $total += ($operation * $total_percentage);
            $detail_lines[] = [
                'AccountCode' => $detail->account,
                'Debit' => $document_line->document->type_calculation == 'Grossing Up' ? 0 : $total_percentage,
                'Credit' => $document_line->document->type_calculation == 'Grossing Up' ? $total_percentage : 0,
                'ShortName' => $detail->account,
                'LineMemo' => $document_line->concept,
                $management->where('name', 'date')->first()->value => $document_line->date,
                $management->where('name', 'document_number')->first()->value => $document_line->document_number,
                $management->where('name', 'authorization_number')->first()->value => $document_line->authorization_number,
                $management->where('name', 'cuf')->first()->value => $document_line->cuf,
                $management->where('name', 'control_code')->first()->value => $document_line->control_code,
                $management->where('name', 'business_name')->first()->value => $document_line->business_name,
                $management->where('name', 'nit')->first()->value => $document_line->nit,
                $management->where('name', 'amount')->first()->value => $document_line->amount,
                $management->where('name', 'discount')->first()->value => $document_line->discount,
                $management->where('name', 'excento')->first()->value => $document_line->excento,
                $management->where('name', 'rate')->first()->value => $document_line->rate,
                $management->where('name', 'gift_card')->first()->value => $document_line->gift_card,
                $management->where('name', 'ice')->first()->value => $document_line->ice,
                $management->where('name', 'document_type')->first()->value => $document_line->document->type_document_sap
            ];
        }
        $total += $document_line->amount;
        $journal[] = [
            'AccountCode' => $document_line->account,
            'Debit' => $total,
            'Credit' => 0,
            'ShortName' => $document_line->account,
            'LineMemo' => $document_line->concept,
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
            $management->where('name', 'excento')->first()->value => $document_line->excento,
            $management->where('name', 'rate')->first()->value => $document_line->rate,
            $management->where('name', 'gift_card')->first()->value => $document_line->gift_card,
            $management->where('name', 'ice')->first()->value => $document_line->ice,
            $management->where('name', 'document_type')->first()->value => $document_line->document->type_document_sap
        ];

        return array_merge($journal, $detail_lines);
    }
    public function HandleExportSAP($accountability_id, Request $request)
    {
        $journal_entry_lines = array();
        $management = Management::where('group', 'accountability_detail')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::with('document.detail')->where('accountability_id', $accountability_id)->orderBy('id', 'asc')->get();

        foreach ($documents as $document) {
            //return $this->HandleFormatLine($document,$management);
            $journal_entry_lines = array_merge($journal_entry_lines, $this->HandleFormatLine($document, $management));
        }

        $total_debit = 0;
        $total_credit = 0;
        foreach ($journal_entry_lines as $line) {
            $total_debit += (float) $line['Debit'];
            $total_credit += (float) $line['Credit'];
        }
        //return $total_debit;
        $debit = $total_debit - $total_credit;
        $last_line[] = [
            'AccountCode' => $accountability->account_code,
            'Debit' => 0,
            'Credit' => $debit,
            'ShortName' => $accountability->employee_code,
            'LineMemo' => $accountability->description,
        ];
        $journal_entry_lines = array_merge($journal_entry_lines, $last_line);
        $journal_entries = [
            'JournalVoucher' => [
                'JournalEntry' => [
                    'Memo' => $accountability->description,
                    'ReferenceDate' => $accountability->end_date,
                    'TaxDate' => $accountability->end_date,
                    'DueDate' => $accountability->end_date,
                    'JournalEntryLines' => $journal_entry_lines
                ]
            ]
        ];

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
                    Accountability::findOrFail($accountability_id)->fill([
                        'status' => $request->status,
                        'comments' => $request->comments,
                    ])->save();
                    Session::flash('message', "Documento autorizado y exportado correctamente");
                    Session::flash('type', 'positive');
                    return Redirect::route('panel.accountability.authorization.index');
                } else {
                    Session::flash('message', $response->json()['error']['message']['value']);
                    Session::flash('type', 'negative');
                }
            } else {
                Session::flash('message', $login->json()['error']['message']['value']);
                Session::flash('type', 'negative');
            }
        } catch (Throwable $e) {
            if ($e->getCode() === 7) {
                Session::flash('message', $e->getMessage());
                Session::flash('type', 'negative');
            } else {
                Session::flash('message', $e->getMessage());
                Session::flash('type', 'negative');
            }
        }
    }
    public function HandleIndexAccountability(Request $request)
    {
        Session::put('title', 'Lista Rendiciones');
        $users = UserAuthorization::where('auth_user_id', $request->user()->id)->get()->pluck('user_id');
        $accountabilities = Accountability::with('user', 'profile')
            ->where('status', 'Pendiente')
            ->whereIn('user_id', $users)
            ->get();

        $authorized = Accountability::with('user', 'profile')
            ->where('status', 'Autorizado')
            ->whereIn('user_id', $users)
            ->get();
        return Inertia::render(
            'authorization/IndexAccountability',
            [
                'data' => $accountabilities,
                'authorized'=>$authorized,
            ]
        );
    }
    public function HandleEditAccountability($accountability_id)
    {
        Session::put('title', 'Crear Rendición');
        $accountability = Accountability::with('user')->where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id', $profile->id)->get();
        $accountability->account = $accountability->account_code;
        $accountability->employee = $accountability->employee_code;
        $employees = Employee::where('profile_id', $profile->id)->get();
        $user_employee = DB::connection('sap')
            ->table('OCRD')
            ->select(
                'CardCode as card_code',
                'CardName as card_name'
            )
            ->where('CardCode', $accountability->user->card_code)
            ->get();
        return Inertia::render(
            'authorization/EditAccountability',
            [
                'profile' => $profile,
                'accounts' => $accounts,
                'accountability' => $accountability,
                'employees' => $employees->count() == 0 ? $user_employee : $employees
            ]
        );
    }
    public function HandleUpdateAccountability(AccountabilityRequest $request)
    {
        $account = GeneralAccounts::where('account_code', $request->account)->first();
        $employee = DB::connection('sap')->table('OCRD')->select('CardCode', 'CardName')->where('CardCode', $request->employee)->first();
        Accountability::findOrFail($request->id)->fill([
            'employee_name' => $employee->CardName,
            'employee_code' => $employee->CardCode,
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
    public function HandleDetailAccountability($accountability_id)
    {
        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $documents = AccountabilityDetail::where('accountability_id', $accountability_id)->get();
        return Inertia::render(
            'authorization/DetailAccountability',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'documents' => $documents,
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
            'distribution_rule_one' => $request->distribution_rule_one,
            'distribution_rule_second' => $request->distribution_rule_second,
            'distribution_rule_three' => $request->distribution_rule_three,
            'distribution_rule_four' => $request->distribution_rule_four,
            'distribution_rule_five' => $request->distribution_rule_five,
        ])->save();
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
            'distribution_rule_one' => $request->distribution_rule_one,
            'distribution_rule_second' => $request->distribution_rule_second,
            'distribution_rule_three' => $request->distribution_rule_three,
            'distribution_rule_four' => $request->distribution_rule_four,
            'distribution_rule_five' => $request->distribution_rule_five,
        ]);
        Session::flash('message', "Documento creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.detail.index', [$accountability_id]);
    }
    public function HandleCreateDocument($accountability_id)
    {
        $params=Management::where('group','supplier')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
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
                'suppliers'=>$this->HandleGetSuppliers($params),
                'distribution' => $this->HandleGetDistributions(),
                'projects' => $this->HandleGetProjects(),
            ]
        );
    }
    public function HandleGetSuppliers($params){
        $sap_suppliers= DB::connection('sap')
            ->table('OCRD')
            ->select(
                $params->where('name','business_name')->first()->value.' as business_name' ,
                $params->where('name','nit')->first()->value.' as nit'
            )
            ->get()->toArray();
        $bd_suppliers=AccountabilityDetail::select(
                                                'business_name',
                                                'nit'
                                            )
                                            ->groupBy('business_name','nit')
                                            ->get()
                                            ->toArray();
        return array_merge($sap_suppliers,$bd_suppliers);
    }
    public function HandleEditDocument($accountability_id, $document_id)
    {
        $params=Management::where('group','supplier')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $profile = Profile::where('id', $accountability->profile_id)->first();
        $accounts = DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id', $profile->id)->get();
        $documents = Document::where('profile_id', $profile->id)->get();
        $suppliers = Supplier::get();
        $data = AccountabilityDetail::where('id', $document_id)->first();
        return Inertia::render(
            'authorization/Detail/EditDetail',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'suppliers'=>$this->HandleGetSuppliers($params),
                'data' => $data,
                'distribution' => $this->HandleGetDistributions(),
                'projects' => $this->HandleGetProjects(),
            ]
        );
    }
    public function HandleGetProjects()
    {
        return DB::connection('sap')
            ->table('OPRJ as T1')
            ->select(
                'T1.PrjCode',
                'T1.PrjName'
            )
            ->get();
    }
    public function HandleGetDistributions()
    {
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
    public function HandleUpdateStatus($accountability_id, Request $request)
    {
        Accountability::findOrFail($accountability_id)->fill([
            'status' => $request->status,
            'comments' => $request->comments,
        ])->save();
        Session::flash('message', "Documento enviado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.index');
    }
}
