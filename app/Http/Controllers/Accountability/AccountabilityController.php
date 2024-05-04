<?php

namespace App\Http\Controllers\Accountability;

use App\Http\Controllers\Controller;
use App\Models\AccountabilityDetail;
use App\Models\DetailAccounts;
use App\Models\GeneralAccounts;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\Document;
use Session;
use Inertia\Inertia;
use App\Models\Profile;
use App\Models\Accountability;
use DB;
use App\Http\Requests\Accountability\AccountabilityRequest;
use Redirect;

class AccountabilityController extends Controller
{
    public function HandleStoreDocument(Request $request,$profile_id,$accountability_id){
        AccountabilityDetail::create([
            'accountability_id'=>$accountability_id,
            'account'=>$request->account,
            'account_name'=>$request->account_name,
            'date'=>$request->date,
            'document_id'=>$request->document_id,
            'document_number'=>$request->document_number,
            'authorization_number'=>$request->authorization_number,
            'cuf'=>$request->cuf,
            'control_code'=>$request->control_code,
            'supplier_code'=>$request->supplier_code,
            'business_name'=>$request->business_name,
            'nit'=>$request->nit,
            'concept'=>$request->concept,
            'amount'=>$request->amount,
            'discount'=>$request->discount,
            'excento'=>$request->excento,
            'rate'=>$request->rate,
            'gift_card'=>$request->gift_card,
            'rate_zero'=>$request->rate_zero,
            'ice'=>$request->ice,
            'project_code'=>$request->project_code,
            'distribution_rule_one'=>$request->distribution_rule_one,
            'distribution_rule_second'=>$request->distribution_rule_second,
            'distribution_rule_three'=>$request->distribution_rule_three,
            'distribution_rule_four'=>$request->distribution_rule_four,
            'distribution_rule_five'=>$request->distribution_rule_five,
        ]);
        Session::flash('message', "Documento creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.manage.detail.index',[$profile_id,$accountability_id]);
    }
    public function HandleCreateDocument($profile_id,$accountability_id){
        $profile=Profile::where('id',$profile_id)->first();
        $accountability=Accountability::where('id',$accountability_id)->first();
        $accounts=DetailAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id',$profile->id)->get();
        $documents=Document::select('name','id')->where('profile_id',$profile->id)->get();
        $suppliers=Supplier::get();
        return Inertia::render(
            'accountability/Detail/CreateDetail',
            [
                'profile'=>$profile,
                'accountability'=>$accountability,
                'accounts'=>$accounts,
                'documents'=>$documents,
                'suppliers'=>$suppliers
            ]
        );
    }
    public function HandleDetailAccountability($profile_id,$accountability_id){
        $profile=Profile::where('id',$profile_id)->first();
        $accountability=Accountability::where('id',$accountability_id)->first();
        return Inertia::render(
            'accountability/DetailAccountability',
            [
                'profile'=>$profile,
                'accountability'=>$accountability,
            ]
        );
    }
    public function HandleIndexAccountability(Request $request, $profile_id){
        Session::put('title', 'Lista Rendiciones');
        $profile=Profile::where('id',$profile_id)->first();
        $accountabilities=Accountability::where('user_id',$request->user()->id)
                                        ->where('profile_id',$profile_id)
                                        ->get();
        return Inertia::render(
            'accountability/IndexAccountability',
            [
                'profile'=>$profile,
                'data'=>$accountabilities
            ]
        );
    }
    public function HandleCreateAccountability($profile_id){
        Session::put('title', 'Crear Rendición');
        $profile=Profile::where('id',$profile_id)->first();
        $accounts=GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id',$profile->id)->get();
        return Inertia::render(
            'accountability/CreateAccountability',
            [
                'profile'=>$profile,
                'accounts'=>$accounts
            ]
        );
    }
    public function HandleStoreAccountability(AccountabilityRequest $request, $profile_id){
        Accountability::create([
            'profile_id'=>$profile_id,
            'user_id'=>$request->user()->id,
            'employee'=>$request->employee,
            'account'=>$request->account,
            'total'=>$request->total,
            'description'=>$request->description,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
        ]);
        Session::flash('message', "Rendición creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.index',$profile_id);
    }
    public function HandleUpdateAccountability(AccountabilityRequest $request, $profile_id){
        Accountability::findOrFail($request->id)->fill([
            'employee'=>$request->employee,
            'account'=>$request->account,
            'total'=>$request->total,
            'description'=>$request->description,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
        ])->save();
        Session::flash('message', "Rendición actualizada correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.accountability.manage.index',$profile_id);
    }
    public function HandleEditAccountability($profile_id,$acc_id,){
        Session::put('title', 'Crear Rendición');
        $profile=Profile::where('id',$profile_id)->first();
        $accounts=GeneralAccounts::select(
            DB::raw("CONCAT(account_code,'-',account_name) as label"),
            'account_code',
            'account_name'
        )->where('profile_id',$profile->id)->get();
        $accountability=Accountability::where('id',$acc_id)->first();
        return Inertia::render(
            'accountability/EditAccountability',
            [
                'profile'=>$profile,
                'accounts'=>$accounts,
                'accountability'=>$accountability
            ]
        );
    }
}
