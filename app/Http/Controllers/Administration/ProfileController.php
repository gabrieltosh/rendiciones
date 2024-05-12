<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\DetailAccounts;
use App\Models\GeneralAccounts;
use Illuminate\Http\Request;
use Session;
use Redirect;
use Inertia\Inertia;
use Auth;
use Illuminate\Database\QueryException;
use App\Models\Profile;
use DB;
use App\Http\Requests\Administration\ProfileRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\DocumentDetail;
use App\Models\Management;
use Throwable;
use Illuminate\Support\Facades\Http;
class ProfileController extends Controller
{
    public function HandleGetAccounts(){
        $params_sap = Management::where('group', 'accountability')->get();
        $service_layer=$params_sap->where('name', 'service_layer')->first()->value;
        try {
            $login = Http::withoutVerifying()
                ->baseUrl($service_layer . '/b1s/v1/')
                ->post('Login', [
                    'CompanyDB' => $params_sap->where('name', 'bd_sap')->first()->value,
                    'UserName' => $params_sap->where('name', 'user')->first()->value,
                    'Password' => $params_sap->where('name', 'password')->first()->value
                ]);
            if ($login->successful()) {
                $session = $login["SessionId"];
                $response = Http::baseUrl($service_layer . '/b1s/v1/')
                    ->withoutVerifying()
                    ->withHeaders([
                        'Cookie' => 'B1SESSION=' . $session . '; ROUTEID=.node9',
                        'Prefer'=>'odata.maxpagesize=100000'
                    ])->get('ChartOfAccounts?$orderby=AccountLevel asc,FatherAccountKey asc&$select=Name,Code,FatherAccountKey,AccountLevel');
                if ($response->successful()) {
                    $distribution = $response->collect('value');
                    $format_data = $distribution->map(function ($item) {
                        return [
                            'label' => $item['Code'] . '-' . $item['Name'],
                            'AcctName'=>$item['Name'],
                            'AcctCode' => $item['Code'],
                            'FatherNum' => $item['FatherAccountKey'],
                            'Levels' => $item['AccountLevel'],
                        ];
                    });

                    $accounts = $format_data->map(function ($item) {
                        return (object) $item;
                    });

                    $accountsByCode = $accounts->keyBy('AcctCode');

                    $accounts->each(function ($account) use ($accountsByCode) {
                        if ($account->FatherNum && isset($accountsByCode[$account->FatherNum])) {
                            $parent = $accountsByCode[$account->FatherNum];
                            $parent->children = $parent->children ?? collect();
                            $parent->children->push($account);
                        }
                    });

                    $level1Accounts = $accounts->filter(function ($account) {
                        return $account->Levels == 1;
                    });
                    Http::withoutVerifying()->baseUrl($service_layer . '/b1s/v1/')->post('Logout');
                    return $level1Accounts->values()->all();
                } else {
                    Session::flash('message', $response->json()['error']['message']['value']);
                    Session::flash('type', 'negative');
                }
            } else {
                Session::flash('message', $login->json()['error']['message']['value']);
                Session::flash('type', 'negative');
            }
        } catch (Throwable $e) {
            Session::flash('message', $e->getMessage());
            Session::flash('type', 'negative');
        }
        // $accounts = DB::connection('sap')
        //     ->table('OACT as T1')
        //     ->select(
        //         DB::raw("CONCAT(T1.AcctCode,'-',T1.AcctName) as label"),
        //         'T1.AcctName',
        //         'T1.AcctCode',
        //         'T1.FatherNum',
        //         'T1.Levels'
        //     )
        //     ->whereIn('T1.Levels', range(1, 10))
        //     ->orderBy('T1.Levels')
        //     ->orderBy('T1.FatherNum')
        //     ->get();

        // $accountsByCode = [];
        // foreach ($accounts as $account) {
        //     $accountsByCode[$account->AcctCode] = $account;
        // }

        // foreach ($accounts as $account) {
        //     if ($account->FatherNum && isset($accountsByCode[$account->FatherNum])) {
        //         $parent = $accountsByCode[$account->FatherNum];
        //         if (!isset($parent->children)) {
        //             $parent->children = [];
        //         }
        //         $parent->children[] = $account;
        //     }
        // }

        // $level1Accounts = array_filter($accountsByCode, function ($account) {
        //     return $account->Levels == 1;
        // });

        // $accounts_result=[];

        // foreach ($level1Accounts as $key => $value) {
        //     array_push($accounts_result, $value);
        // }

        // return $accounts_result;
    }

    public function HandleGetAccountFormat($data){
        $params_sap = Management::where('group', 'accountability')->get();
        $service_layer=$params_sap->where('name', 'service_layer')->first()->value;
        try {
            $login = Http::withoutVerifying()
                ->baseUrl($service_layer . '/b1s/v1/')
                ->post('Login', [
                    'CompanyDB' => $params_sap->where('name', 'bd_sap')->first()->value,
                    'UserName' => $params_sap->where('name', 'user')->first()->value,
                    'Password' => $params_sap->where('name', 'password')->first()->value
                ]);
            if ($login->successful()) {
                $session = $login["SessionId"];
                $accounts=collect();
                $filter='';
                foreach ($data as $key => $value) {
                    $account=explode('-',$value);
                    if($key == 0)
                        $filter.= "Code eq '$account[0]'";
                    else
                        $filter.= " or Code eq '$account[0]'";
                }
                $response = Http::baseUrl($service_layer . '/b1s/v1/')
                            ->withoutVerifying()
                            ->withHeaders([
                                'Cookie' => 'B1SESSION=' . $session . '; ROUTEID=.node9',
                                'Prefer'=>'odata.maxpagesize=100000'
                            ])->get('ChartOfAccounts?$select=Code,Name,FormatCode&$filter='.$filter);
                if ($response->successful()) {
                    Http::withoutVerifying()->baseUrl($service_layer . '/b1s/v1/')->post('Logout');
                    return $response->collect('value');
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

        // $accounts=[];
        // foreach ($data as $value) {
        //     $account=explode('-',$value);
        //     array_push($accounts,$account[0]);
        // }
        // $data_format = DB::connection('sap')
        //         ->table('OACT as T1')
        //         ->select(
        //             'T1.AcctName',
        //             'T1.AcctCode',
        //             'T1.FormatCode'
        //         )
        //         ->whereIn('T1.AcctCode', $accounts)
        //         ->get();
        // return $data_format;
    }
    public function HandleGetEmployeeFormat($data){
        $card_codes=[];
        foreach($data as $value){
            $card_code=explode('-',$value);
            array_push($card_codes,$card_code[0]);
        }
        $data_format=DB::connection('sap')
                        ->table('OCRD as T1')
                        ->select(
                            'T1.CardCode',
                            'T1.CardName',
                        )
                        ->whereIn('T1.CardCode',$card_codes)
                        ->get();
        return $data_format;
    }

    public function HandleStoreProfile(ProfileRequest $request){
        $profile=Profile::create([
            'name'=>$request->name,
            'type_currency'=>$request->type_currency
        ]);
        foreach ($this->HandleGetAccountFormat($request->detail) as $account){
            DetailAccounts::create([
                'profile_id'=>$profile->id,
                // 'account_code'=>$account->AcctCode,
                // 'format_code'=>$account->FormatCode,
                // 'account_name'=>$account->AcctName,
                'account_code'=>$account['Code'],
                'format_code'=>$account['FormatCode'],
                'account_name'=>$account['Name'],
            ]);
        }
        foreach ($this->HandleGetAccountFormat($request->general) as $account){
            GeneralAccounts::create([
                'profile_id'=>$profile->id,
                // 'account_code'=>$account->AcctCode,
                // 'format_code'=>$account->FormatCode,
                // 'account_name'=>$account->AcctName,
                'account_code'=>$account['Code'],
                'format_code'=>$account['FormatCode'],
                'account_name'=>$account['Name'],
            ]);
        }
        foreach($this->HandleGetEmployeeFormat($request->employees) as $employee){
            Employee::create([
                'profile_id'=>$profile->id,
                'card_code'=>$employee->CardCode,
                'card_name'=>$employee->CardName
            ]);
        }
        foreach ($request->documents as $document){
            $document_create=Document::create([
                'name'=>$document['name'],
                'type_document_sap'=>$document['type_document_sap'],
                'type_calculation'=>$document['type_calculation'],
                'ice'=>$document['ice'],
                'tasas'=>$document['tasas'],
                'exento'=>$document['exento'],
                'ice_status'=>$document['ice_status'],
                'tasas_status'=>$document['tasas_status'],
                'exento_status'=>$document['exento_status'],
                'authorization_number_status'=>$document['authorization_number_status'],
                'cuf_status'=>$document['cuf_status'],
                'control_code_status'=>$document['control_code_status'],
                'business_name_status'=>$document['business_name_status'],
                'nit_status'=>$document['nit_status'],
                'discount_status'=>$document['discount_status'],
                'gift_card_status'=>$document['gift_card_status'],
                'rate_zero_status'=>$document['rate_zero_status'],
                'profile_id'=>$profile->id,
            ]);
            foreach ($document['detail'] as $item) {
                DocumentDetail::create([
                    'document_id'=>$document_create->id,
                    'type'=>$item['type'],
                    'percentage'=>$item['percentage'],
                    'account'=>$item['account'],
                ]);
            }
        }
        Session::flash('message', "Perfil creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.profile.index');
    }
    public function HandleGetAccountCode($data){
        $accounts=[];
        foreach ($data as $value) {
            $account=explode('-',$value);
            array_push($accounts,$account[0]);
        }
        return $accounts;
    }
    public function HandleGetAccountSAP($accounts){
        $data = DB::connection('sap')
        ->table('OACT as T1')
        ->select(
            'T1.AcctName',
            'T1.AcctCode',
            'T1.FormatCode'
        )
        ->whereIn('T1.AcctCode', $accounts)
        ->get();
        return $data;
    }
    public function HandleGetEmpSAP($employess){
        $data = DB::connection('sap')
        ->table('OCRD as T1')
        ->select(
            'T1.CardCode',
            'T1.CardName',
        )
        ->whereIn('T1.CardCode', $employess)
        ->get();
        return $data;
    }

    public function HandleUpdateProfile(ProfileRequest $request)
    {
        Session::flash('message', "Usuario actualizado correctamente");
        Session::flash('type', 'positive');

        Profile::findOrFail($request->id)
                ->fill([
                    'name'=>$request->name,
                    'type_currency'=>$request->type_currency
                ])->save();
        $detail= DetailAccounts::select('account_code')
                                ->where('profile_id',$request->id)
                                ->pluck('account_code')
                                ->toArray();
        $general= GeneralAccounts::select('account_code')
                                ->where('profile_id',$request->id)
                                ->pluck('account_code')
                                ->toArray();
        $employees=Employee::select('card_code')
                            ->where('profile_id',$request->id)
                            ->pluck('card_code')
                            ->toArray();

        $to_delete_detail=array_diff($detail,$this->HandleGetAccountCode($request->detail));
        $to_delete_general=array_diff($general,$this->HandleGetAccountCode($request->general));
        $to_create_detail=array_diff($this->HandleGetAccountCode($request->detail),$detail);
        $to_create_general=array_diff($this->HandleGetAccountCode($request->general),$general);

        $to_delete_employee=array_diff($employees,$this->HandleGetAccountCode($request->employees));
        $to_create_employee=array_diff($this->HandleGetAccountCode($request->employees),$employees);

        DetailAccounts::whereIn('account_code',$to_delete_detail)->delete();
        GeneralAccounts::whereIn('account_code',$to_delete_general)->delete();
        Employee::whereIn('card_code',$to_delete_employee)->delete();

        foreach ($this->HandleGetAccountSAP($to_create_detail) as $account){
            DetailAccounts::create([
                'profile_id'=>$request->id,
                'account_code'=>$account->AcctCode,
                'format_code'=>$account->FormatCode,
                'account_name'=>$account->AcctName,
            ]);
        }
        foreach ($this->HandleGetAccountSAP($to_create_general) as $account){
            GeneralAccounts::create([
                'profile_id'=>$request->id,
                'account_code'=>$account->AcctCode,
                'format_code'=>$account->FormatCode,
                'account_name'=>$account->AcctName,
            ]);
        }
        foreach ($this->HandleGetEmpSAP($to_create_employee) as $employee){
            Employee::create([
                'profile_id'=>$request->id,
                'card_code'=>$employee->CardCode,
                'card_name'=>$employee->CardName
            ]);
        }
        foreach ($request->documents as $document){
            if(!isset($document['id'])){
                $document_create=Document::create([
                    'name'=>$document['name'],
                    'type_document_sap'=>$document['type_document_sap'],
                    'type_calculation'=>$document['type_calculation'],
                    'ice'=>$document['ice'],
                    'tasas'=>$document['tasas'],
                    'exento'=>$document['exento'],
                    'ice_status'=>$document['ice_status'],
                    'tasas_status'=>$document['tasas_status'],
                    'exento_status'=>$document['exento_status'],
                    'authorization_number_status'=>$document['authorization_number_status'],
                    'cuf_status'=>$document['cuf_status'],
                    'control_code_status'=>$document['control_code_status'],
                    'business_name_status'=>$document['business_name_status'],
                    'nit_status'=>$document['nit_status'],
                    'discount_status'=>$document['discount_status'],
                    'gift_card_status'=>$document['gift_card_status'],
                    'rate_zero_status'=>$document['rate_zero_status'],
                    'profile_id'=>$request->id,
                ]);
                foreach ($document['detail'] as $item) {
                    DocumentDetail::create([
                        'document_id'=>$document_create->id,
                        'type'=>$item['type'],
                        'percentage'=>$item['percentage'],
                        'account'=>$item['account'],
                    ]);
                }
            }else{
                if((int)$document['for_delete']==1){
                    foreach ($document['detail'] as $item) {
                        if(isset($item['id'])){
                            DocumentDetail::findOrFail($item['id'])->delete();
                        }
                    }
                    Document::findOrFail($document['id'])->delete();
                }else{
                    Document::findOrFail($document['id'])->fill([
                        'name'=>$document['name'],
                        'type_document_sap'=>$document['type_document_sap'],
                        'type_calculation'=>$document['type_calculation'],
                        'ice'=>$document['ice'],
                        'tasas'=>$document['tasas'],
                        'exento'=>$document['exento'],
                        'ice_status'=>$document['ice_status'],
                        'tasas_status'=>$document['tasas_status'],
                        'exento_status'=>$document['exento_status'],
                        'authorization_number_status'=>$document['authorization_number_status'],
                        'cuf_status'=>$document['cuf_status'],
                        'control_code_status'=>$document['control_code_status'],
                        'business_name_status'=>$document['business_name_status'],
                        'nit_status'=>$document['nit_status'],
                        'discount_status'=>$document['discount_status'],
                        'gift_card_status'=>$document['gift_card_status'],
                        'rate_zero_status'=>$document['rate_zero_status'],
                    ])->save();
                    foreach ($document['detail'] as $item) {
                        if(!isset($item['id'])){
                            DocumentDetail::create([
                                'document_id'=>$document['id'],
                                'type'=>$item['type'],
                                'percentage'=>$item['percentage'],
                                'account'=>$item['account'],
                            ]);
                        }else{
                            if((int)$item['for_delete']==1){
                                DocumentDetail::findOrFail($item['id'])->delete();
                            }else{
                                DocumentDetail::findOrFail($item['id'])->fill([
                                    'type'=>$item['type'],
                                    'percentage'=>$item['percentage'],
                                    'account'=>$item['account'],
                                ])->save();
                            }
                        }
                    }
                }
            }
        }
        return Redirect::route('panel.profile.index');
    }
    public function HandleEditProfile($id)
    {
        Session::put('title', 'Editar Profile');
        $field_name_emp=Management::where('name','employee_enablement_field')->first();
        $field_value_emp=Management::where('name','employee_enablement_field_value')->first();
        $currencies=DB::connection('sap')
            ->table('OCRN as T1')
            ->select(
                'T1.CurrCode',
                'T1.CurrName'
            )
            ->get();
        $profile = Profile::where('id',$id)->with([
            'documents'=>function($q){
                $q->select(
                    'id',
                    'name',
                    'type_document_sap',
                    'type_calculation',
                    'profile_id',
                    'ice',
                    'tasas',
                    'exento',
                    'ice_status',
                    'tasas_status',
                    'exento_status',
                    'authorization_number_status',
                    'cuf_status',
                    'control_code_status',
                    'business_name_status',
                    'nit_status',
                    'discount_status',
                    'gift_card_status',
                    'rate_zero_status',
                    DB::raw("0 as for_delete"),
                )->orderBy('id','asc');
            },
            'documents.detail'=>function($q){
                $q->select(
                    'id',
                    'document_id',
                    'type',
                    'percentage',
                    'account',
                    DB::raw("0 as for_delete"),
                )->orderBy('id','asc');
            }
        ])->first();
        $profile->detail= DB::table('detail_accounts as T1')
                            ->select(
                                DB::raw("CONCAT(T1.account_code,'-',T1.account_name) as label")
                            )
                            ->where('profile_id',$id)
                            ->pluck('label');
        $profile->general= DB::table('general_accounts as T1')
                            ->select(
                                DB::raw("CONCAT(T1.account_code,'-',T1.account_name) as label")
                            )
                            ->where('profile_id',$id)
                            ->pluck('label');
        $accounts = DB::connection('sap')
                        ->table('OACT as T1')
                        ->select(
                            'T1.AcctName',
                            'T1.AcctCode',
                        )
                        ->whereIn('T1.Levels', range(1, 10))
                        ->orderBy('T1.Levels')
                        ->orderBy('T1.FatherNum')
                        ->get();

        $profile->employees=DB::table('employees as T1')
                                ->select(
                                    DB::raw("CONCAT(T1.card_code,'-',T1.card_name) as label")
                                )
                                ->where('profile_id',$id)
                                ->pluck('label');

        $employees=DB::connection('sap')
                                ->table('OCRD as T1')
                                ->select(
                                    DB::raw("CONCAT(T1.CardCode,'-',T1.CardName) as label")
                                )
                                ->where($field_name_emp->value,$field_value_emp->value)
                                ->get();
        return Inertia::render('administration/profile/EditProfile',[
            'type'=>['IVA','IT','IUE','RC-IVA'],
            'type_calculation'=>['Grossing Up','Grossing Down'],
            'accounts'=>$this->HandleGetAccounts(),
            'currencies'=>$currencies,
            'profile'=>$profile,
            'accounts_document'=>$accounts,
            'employees'=>$employees
        ]);
    }
    public function HandleDeleteProfile($id)
    {
        try {
            Profile::findOrFail($id)->delete();
                Session::flash('message', "Perfil eliminado correctamente");
                Session::flash('type', 'positive');
        } catch (QueryException $e) {
            Session::flash('type', 'negative');
            if ($e->errorInfo[1] == 547) {
                Session::flash('message', 'No puedes eliminar este perfil porque hay registros asociados en otras tablas');
            } else {
                Session::flash('message', $e->getMessage());
            }
        }
    }
    public function HandleCreateProfile()
    {
        //return $this->HandleGetAccounts();
        Session::put('title', 'Crear Perfil');
        $field_name_emp=Management::where('name','employee_enablement_field')->first();
        $field_value_emp=Management::where('name','employee_enablement_field_value')->first();
        $currencies=DB::connection('sap')
            ->table('OCRN as T1')
            ->select(
                'T1.CurrCode',
                'T1.CurrName'
            )
            ->get();
        $accounts = DB::connection('sap')
            ->table('OACT as T1')
            ->select(
                'T1.AcctName',
                'T1.AcctCode',
            )
            ->whereIn('T1.Levels', range(1, 10))
            ->orderBy('T1.Levels')
            ->orderBy('T1.FatherNum')
            ->get();

        $employees=DB::connection('sap')
            ->table('OCRD as T1')
            ->select(
                DB::raw("CONCAT(T1.CardCode,'-',T1.CardName) as label")
            )
            ->where($field_name_emp->value,$field_value_emp->value)
            ->get();
        return Inertia::render('administration/profile/CreateProfile',[
            'type'=>['IVA','IT','IUE','RC-IVA'],
            'type_calculation'=>['Grossing Up','Grossing Down'],
            'accounts'=>$this->HandleGetAccounts(),
            'currencies'=>$currencies,
            'accounts_document'=>$accounts,
            'employees'=>$employees
        ]);
    }
    public function HandleIndexProfile()
    {
        Session::put('title', 'Perfiles');
        $data = Profile::orderBy('id','desc')->get();
        return Inertia::render("administration/profile/IndexProfile")->with('data', $data);
    }
}
