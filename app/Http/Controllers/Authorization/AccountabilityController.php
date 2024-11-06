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
use Throwable ;
use App\Models\Management;
use Barryvdh\DomPDF\Facade\Pdf;
use Config;
use App\Helpers\Hana;
use App\Notifications\Accountability\StatusAccountabilityNotification;
use App\Models\User;
class AccountabilityController extends Controller
{
    public function HandleGetReportAccountability($accountability_id){
        $params=Management::where('group','company')->get();
        $company=[
            'company_name'=>$params->where('name','company_name')->first()->value,
            'company_location'=>$params->where('name','company_location')->first()->value,
            'nit'=>$params->where('name','nit')->first()->value,
            'logo'=>$params->where('name','logo')->first()->value,
        ];
        $data=Accountability::with('profile','user','detail.document')
                    ->where('id',$accountability_id)
                    ->first();
        $pdf = Pdf::loadView('pdf.accountability_detail',[
            'data'=>$data,
            'company'=>$company
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
            $amount_line = $document_line->amount+($field->document_field->type_calculation == 'Credito' ? 1 : -1)* $field->value;
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
    public function HandleGetReport($accountability_id){
        $journal_entry_lines = array();
        $management = Management::where('group', 'accountability_detail')->get();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::with('document.detail','document.field.document_field')->where('accountability_id', $accountability_id)->orderBy('id', 'asc')->get();
        $exchange=DB::connection('sap')
                    ->table('ORTT as T1')
                    ->select('Rate')
                    ->where('Currency','USD')
                    ->where('RateDate',$accountability->end_date)
                    ->first();
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
        $amount_line=$document_line->amount;
        //$exento_percentage = $document_line->document->exento / 100;
        $rate_percentage = $document_line->document->tasas / 100;
        $ice_percentage = $document_line->document->ice / 100;
        // $total_excento = $document_line->exento_status ? $document_line->exento : $document_line->amount * $exento_percentage;
        // $total_ice = $document_line->ice_status ? $document_line->ice : $document_line->amount * $ice_percentage;
        // $total_tasas = $document_line->tasas_status ? $document_line->tasas : $document_line->amount * $rate_percentage;
        // $amount = $document_line->amount - $total_excento - $total_tasas - $total_ice;
        // $operation = $document_line->document->type_calculation == 'Grossing Up' ? 1 : -1;

        foreach ($document_line->field as $field) {
            $amount_line += ($field->document_field->type_calculation == 'Credito' ? 1 : -1)* $field->value;
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

        $max_exento=0;
        foreach ($document_line->document->detail as $detail) {
            $exento_percentage = $detail->exento / 100;
            $total_excento = $document_line->exento_status ? $document_line->exento : $amount_line * $exento_percentage;
            $max_exento=$total_excento>$max_exento?$total_excento:$max_exento;

            //$amount = $amount_line - $total_excento - $total_tasas - $total_ice;
            $amount = ($total_excento==0?$amount_line:$total_excento) + $total_tasas + $total_ice;

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
            $management->where('name', 'excento')->first()->value => $max_exento,
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
        $documents = AccountabilityDetail::with('document.detail','field.document_field')->where('accountability_id', $accountability_id)->orderBy('id', 'desc')->get();
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

        //dd($journal_entries);

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
                    $accountability = Accountability::where('id', $accountability_id)->first();
                    $user=User::where('id',$accountability->user_id)->first();
                    $params=Management::where('group','accountability')->get();
                    if($params->where('name','notification_email')->first()->value=='SI'){
                        $user->notify(new StatusAccountabilityNotification($accountability));
                    }
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
    public function HandleGetUserEmployee($value){
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select
                T1."CardCode" as "card_code",
                T1."CardName" as "card_name"
            from $db.OCRD as T1
            where T1."CardCode" = '$value'
SQL;
            return Hana::query($sql);
        }else{
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
    public function HandleGetEmployee($value){
        $params_sap = Management::where('group', 'accountability')->get();
        $hana=$params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        if ($hana) {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select
                T1."CardCode",
                T1."CardName"
            from $db.OCRD as T1
            where T1."CardCode" = '$value'
SQL;
            $query = Hana::query($sql);
            return isset($query[0])?$query[0]:[];
        }else{
            return DB::connection('sap')->table('OCRD')->select('CardCode', 'CardName')->where('CardCode', $value)->first();
        }
    }
    public function HandleUpdateAccountability(AccountabilityRequest $request)
    {
        $account = GeneralAccounts::where('account_code', $request->account)->first();
        $params_sap = Management::where('group', 'accountability')->get();
        $hana=$params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        $employee = $this->HandleGetEmployee($request->employee);
        Accountability::findOrFail($request->id)->fill([
            'employee_name' => $hana?$employee['CardName']:$employee->CardName,
            'employee_code' => $hana?$employee['CardCode']:$employee->CardCode,
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
        $params_sap = Management::where('group', 'accountability')->get();
        $business_name =$params->where('name','business_name')->first()->value;
        $nit=$params->where('name','nit')->first()->value;
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
                select
                    T1."$business_name" as "business_name",
                    T1."$nit" as "nit"
                from $db.OCRD as T1
SQL;
            $sap_suppliers = Hana::query($sql);
        }else{
            $sap_suppliers = DB::connection('sap')
                        ->table('OCRD')
                        ->select(
                            $business_name.' as business_name' ,
                            $nit.' as nit'
                        )
                        ->get()->toArray();
        }
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
    public function HandleGetProjects(){
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
                select
                    T1."PrjCode",
                    T1."PrjName"
                from $db.OPRJ as T1
SQL;
            return Hana::query($sql);
        }else{
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
            $db=Config::get('database.connections.hana.database');
            for ($i = 1; $i <= 5; $i++) {
                $sql=
<<<SQL
                select CONCAT(CONCAT(T1."PrcCode",'-'),T1."PrcName") as "Name", T1."PrcName",T1."PrcCode"
                from $db.OPRC as T1
                where T1."DimCode" = $i
                and T1."Locked" = 'N'
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
        Accountability::findOrFail($accountability_id)->fill([
            'status' => $request->status,
            'comments' => $request->comments,
        ])->save();
        $accountability=Accountability::where('id',$accountability_id)->first();
        $user=User::where('id',$accountability->user_id)->first();
        $params=Management::where('group','accountability')->get();
        if($params->where('name','notification_email')->first()->value=='SI'){
            $user->notify(new StatusAccountabilityNotification($accountability));
        }
        Session::flash('message', "Documento enviado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.authorization.index');
    }
}
