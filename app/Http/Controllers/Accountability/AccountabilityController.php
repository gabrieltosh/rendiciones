<?php

namespace App\Http\Controllers\Accountability;

use App\Http\Requests\Accountability\AccountabilityRequest;
use App\Http\Requests\Accountability\DocumentRequest;
use App\Http\Controllers\Controller;
use App\Models\AccountabilityDetail;
use App\Models\GeneralAccounts;
use App\Models\DetailAccounts;
use App\Models\Accountability;
use App\Models\Management;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Document;
use App\Models\Profile;
use Inertia\Inertia;
use Redirect;
use Session;
use Auth;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;

class AccountabilityController extends Controller
{
    public function HandleGetReportAccountability($profile_id,$accountability_id){
        $data=Accountability::with('profile','user','detail.document')
                    ->where('id',$accountability_id)
                    ->first();
        $pdf = Pdf::loadView('pdf.accountability_detail',[
            'data'=>$data
        ]);
        $pdf->setPaper('letter', 'portrait');
        return $pdf->stream();
    }
    public function HandleUpdateStatus($profile_id,$accountability_id, Request $request){
        Accountability::findOrFail($accountability_id)->fill([
            'status'=>$request->status
        ])->save();
        Session::flash('message', "Documento enviado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);
    }

    public function HandleUpdateDocument(DocumentRequest $request, $profile_id, $accountability_id)
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
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);

    }
    public function HandleEditDocument($profile_id, $accountability_id, $document_id)
    {
        $params=Management::where('group','supplier')->get();
        $profile = Profile::where('id', $profile_id)->first();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $accounts = DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id', $profile->id)->get();
        $documents = Document::where('profile_id', $profile->id)->get();
        $data = AccountabilityDetail::where('id', $document_id)->first();
        return Inertia::render(
            'accountability/Detail/EditDetail',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'suppliers'=>$this->HandleGetSuppliers($params),
                'data' => $data,
                'distribution'=>$this->HandleGetDistributions(),
                'projects'=>$this->HandleGetProjects(),
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
        return Redirect::route('panel.accountability.manage.detail.index', [$profile_id, $accountability_id]);
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

    public function HandleCreateDocument($profile_id, $accountability_id)
    {
        $params=Management::where('group','supplier')->get();
        $profile = Profile::where('id', $profile_id)->first();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $accounts = DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id', $profile->id)->get();
        //return $this->HandleGetSuppliers($params);
        $documents = Document::where('profile_id', $profile->id)->get();
        return Inertia::render(
            'accountability/Detail/CreateDetail',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'accounts' => $accounts,
                'documents' => $documents,
                'distribution'=>$this->HandleGetDistributions(),
                'projects'=>$this->HandleGetProjects(),
                'suppliers'=>$this->HandleGetSuppliers($params)
            ]
        );
    }
    public function HandleDetailAccountability($profile_id, $accountability_id)
    {
        $profile = Profile::where('id', $profile_id)->first();
        $accountability = Accountability::where('id', $accountability_id)->first();
        $documents = AccountabilityDetail::where('accountability_id', $accountability_id)->get();
        return Inertia::render(
            'accountability/DetailAccountability',
            [
                'profile' => $profile,
                'accountability' => $accountability,
                'documents' => $documents,
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
            'accountability/IndexAccountability',
            [
                'profile' => $profile,
                'data' => $accountabilities
            ]
        );
    }
    public function HandleGetProjects(){
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
    public function HandleCreateAccountability($profile_id)
    {
        Session::put('title', 'Crear Rendición');
        $profile = Profile::where('id', $profile_id)->first();
        $employees = Employee::where('profile_id', $profile_id)->get();
        $user_employee = DB::connection('sap')
            ->table('OCRD')
            ->select(
                'CardCode as card_code',
                'CardName as card_name'
            )
            ->where('CardCode', Auth::user()->card_code)
            ->get();
        $accounts = GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id', $profile->id)->get();



        return Inertia::render(
            'accountability/CreateAccountability',
            [
                'profile' => $profile,
                'accounts' => $accounts,
                'employees' => $employees->count() == 0 ? $user_employee : $employees            ]
        );
    }
    public function HandleStoreAccountability(AccountabilityRequest $request, $profile_id)
    {
        $account = GeneralAccounts::where('account_code', $request->account)->first();
        $employee = DB::connection('sap')->table('OCRD')->select('CardCode', 'CardName')->where('CardCode', $request->employee)->first();
        Accountability::create([
            'profile_id' => $profile_id,
            'user_id' => $request->user()->id,
            'employee_name' => $employee->CardName,
            'employee_code' => $employee->CardCode,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'total' => $request->total,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        Session::flash('message', "Rendición creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.index', $profile_id);
    }
    public function HandleUpdateAccountability(AccountabilityRequest $request, $profile_id)
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
        return Redirect::route('panel.accountability.manage.index', $profile_id);
    }
    public function HandleEditAccountability($profile_id, $acc_id, )
    {
        Session::put('title', 'Crear Rendición');
        $profile = Profile::where('id', $profile_id)->first();
        $accounts = GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id', $profile->id)->get();
        $accountability = Accountability::where('id', $acc_id)->first();
        $accountability->account = $accountability->account_code;
        $accountability->employee = $accountability->employee_code;
        $employees = Employee::where('profile_id', $profile_id)->get();
        $user_employee = DB::connection('sap')
            ->table('OCRD')
            ->select(
                'CardCode as card_code',
                'CardName as card_name'
            )
            ->where('CardCode', Auth::user()->card_code)
            ->get();
        return Inertia::render(
            'accountability/EditAccountability',
            [
                'profile' => $profile,
                'accounts' => $accounts,
                'accountability' => $accountability,
                'employees' => $employees->count() == 0 ? $user_employee : $employees
            ]
        );
    }
}
