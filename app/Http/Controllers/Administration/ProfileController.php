<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\DetailAccounts;
use App\Models\GeneralAccounts;
use Session;
use Redirect;
use Inertia\Inertia;
use Illuminate\Database\QueryException;
use App\Models\Profile;
use DB;
use App\Http\Requests\Administration\ProfileRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Models\DocumentDetail;
use App\Models\Management;
use App\Models\DocumentField;
use Illuminate\Database\Query\JoinClause;
use App\Helpers\Hana;
use Config;
class ProfileController extends Controller
{
    public function HandleGetAccounts()
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select CONCAT(CONCAT(T1."AcctCode",'-'),T1."AcctName") as "label",
                T1."AcctName",
                T1."AcctCode",
                T1."FatherNum",
                T1."Levels"
            from $db.OACT as T1
            where T1."Levels" in (1,2,3,4,5,6,7,8,9,10)
            order by T1."Levels",T1."FatherNum"
SQL;
            $accounts_db = Hana::query($sql);
            $format_data=collect($accounts_db);
            $accounts = $format_data->map(function ($item) {
                return (object) $item;
            });

            $accountsByCode = $accounts->keyBy('AcctCode');

            $accounts->each(function ($account) use ($accountsByCode) {
                if ($account->FatherNum && isset ($accountsByCode[$account->FatherNum])) {
                    $parent = $accountsByCode[$account->FatherNum];
                    $parent->children = $parent->children ?? collect();
                    $parent->children->push($account);
                }
            });

            $level1Accounts = $accounts->filter(function ($account) {
                return $account->Levels == 1;
            });
            return $level1Accounts->values()->all();
        } else {
            $accounts = DB::connection('sap')
                ->table('OACT as T1')
                ->select(
                    DB::raw("CONCAT(T1.AcctCode,'-',T1.AcctName) as label"),
                    'T1.AcctName',
                    'T1.AcctCode',
                    'T1.FatherNum',
                    'T1.Levels'
                )
                ->whereIn('T1.Levels', range(1, 10))
                ->orderBy('T1.Levels')
                ->orderBy('T1.FatherNum')
                ->get();

            $accountsByCode = [];
            foreach ($accounts as $account) {
                $accountsByCode[$account->AcctCode] = $account;
            }

            foreach ($accounts as $account) {
                if ($account->FatherNum && isset($accountsByCode[$account->FatherNum])) {
                    $parent = $accountsByCode[$account->FatherNum];
                    if (!isset($parent->children)) {
                        $parent->children = [];
                    }
                    $parent->children[] = $account;
                }
            }

            $level1Accounts = array_filter($accountsByCode, function ($account) {
                return $account->Levels == 1;
            });

            $accounts_result = [];

            foreach ($level1Accounts as $key => $value) {
                array_push($accounts_result, $value);
            }

            return $accounts_result;
        }
    }

    public function HandleGetAccountFormat($data)
    {
        $accounts = [];
        foreach ($data as $value) {
            $account = explode('-', $value);
            array_push($accounts, $account[0]);
        }
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $format_accounts=array_map(function($e){
                return "'".$e."'";
            },$accounts);
            $accounts_string = implode(",",$format_accounts);
            $sql=
<<<SQL
                select
                    T1."AcctName",
                    T1."AcctCode",
                    T1."FormatCode"
                from $db.OACT as T1
                where T1."AcctCode" in ($accounts_string)
SQL;
            return Hana::query($sql);
        } else {
            $data_format = DB::connection('sap')
                ->table('OACT as T1')
                ->select(
                    'T1.AcctName',
                    'T1.AcctCode',
                    'T1.FormatCode'
                )
                ->whereIn('T1.AcctCode', $accounts)
                ->get();
            return $data_format;
        }
    }
    public function HandleGetEmployeeFormat($data)
    {
        $card_codes = [];
        foreach ($data as $value) {
            $card_code = explode('-', $value);
            array_push($card_codes, $card_code[0]);
        }
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $format_accounts=array_map(function($e){
                return "'".$e."'";
            },$card_codes);
            $card_codes_string = implode(",", $format_accounts);
            $sql=
<<<SQL
                select
                    T1."CardCode",
                    T1."CardName"
                from $db.OCRD as T1
                where T1."CardCode" in ($card_codes_string)
SQL;
            return Hana::query($sql);
        }else{
            $data_format = DB::connection('sap')
                ->table('OCRD as T1')
                ->select(
                    'T1.CardCode',
                    'T1.CardName',
                )
                ->whereIn('T1.CardCode', $card_codes)
                ->get();
            return $data_format;
        }
    }

    public function HandleStoreProfile(ProfileRequest $request)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        $profile = Profile::create([
            'name' => $request->name,
            'type_currency' => $request->type_currency
        ]);

        $sl=$params_sap->where('name', 'hana_enable')->first()->value == 'SI';

        foreach ($this->HandleGetAccountFormat($request->detail) as $account) {
            DetailAccounts::create([
                'profile_id'=>$profile->id,
                'account_code'=>$sl?$account['AcctCode']:$account->AcctCode,
                'format_code'=>$sl?$account['FormatCode']:$account->FormatCode,
                'account_name'=>$sl?$account['AcctName']:$account->AcctName,
            ]);
        }
        foreach ($this->HandleGetAccountFormat($request->general) as $account) {
            GeneralAccounts::create([
                'profile_id'=>$profile->id,
                'account_code'=>$sl?$account['AcctCode']:$account->AcctCode,
                'format_code'=>$sl?$account['FormatCode']:$account->FormatCode,
                'account_name'=>$sl?$account['AcctName']:$account->AcctName,
            ]);
        }
        foreach ($this->HandleGetEmployeeFormat($request->employees) as $employee) {
            Employee::create([
                'profile_id' => $profile->id,
                'card_code' => $sl?$employee['CardCode']:$employee->CardCode,
                'card_name' => $sl?$employee['CardName']:$employee->CardName
            ]);
        }
        foreach ($request->documents as $document) {
            $document_create = Document::create([
                'name' => $document['name'],
                'type_document_sap' => $document['type_document_sap'],
                'ice' => $document['ice'],
                'tasas' => $document['tasas'],
                'exento' => $document['exento'],
                'ice_status' => $document['ice_status'],
                'tasas_status' => $document['tasas_status'],
                'exento_status' => $document['exento_status'],
                'authorization_number_status' => $document['authorization_number_status'],
                'cuf_status' => $document['cuf_status'],
                'control_code_status' => $document['control_code_status'],
                'business_name_status' => $document['business_name_status'],
                'nit_status' => $document['nit_status'],
                'discount_status' => $document['discount_status'],
                'gift_card_status' => $document['gift_card_status'],
                'rate_zero_status' => $document['rate_zero_status'],
                'profile_id' => $profile->id,
            ]);
            foreach ($document['detail'] as $item) {
                DocumentDetail::create([
                    'document_id' => $document_create->id,
                    'type' => $item['type'],
                    'type_calculation' => $item['type_calculation'],
                    'percentage' => $item['percentage'],
                    'account' => $item['account'],
                    'exento'=>$item['exento'],
                    'calculation'=>$item['calculation']
                ]);
            }
            foreach ($document['fields'] as $item) {
                DocumentField::create([
                    'document_id'=> $document_create->id,
                    'account'=>$item['account'],
                    'name'=>$item['name'],
                    'type_calculation' => $item['type_calculation'],
                ]);
            }
        }
        Session::flash('message', "Perfil creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.profile.index');
    }
    public function HandleGetAccountCode($data)
    {
        $accounts = [];
        foreach ($data as $value) {
            $account = explode('-', $value);
            array_push($accounts, $account[0]);
        }
        return $accounts;
    }
    public function HandleGetAccountSAP($accounts)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $format_accounts=array_map(function($e){
                return "'".$e."'";
            },$accounts);
            $accounts_string = implode(",", $format_accounts);
            $sql=
<<<SQL
                select
                    T1."AcctName",
                    T1."AcctCode",
                    T1."FormatCode"
                from $db.OACT as T1
                where T1."AcctCode" in ($accounts_string)
SQL;
            return Hana::query($sql);
        }else{
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
    }
    public function HandleGetEmpSAP($employess)
    {
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $format_employess=array_map(function($e){
                return "'".$e."'";
            },$employess);
            $employess_string = implode(",", $format_employess);
            $sql=
<<<SQL
                select
                    T1."CardCode",
                    T1."CardName"
                from $db.OCRD as T1
                where T1."CardCode" in ($employess_string)
SQL;
            return Hana::query($sql);
        }else{
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
    }

    public function HandleUpdateProfile(ProfileRequest $request)
    {
        Session::flash('message', "Usuario actualizado correctamente");
        Session::flash('type', 'positive');
        $params_sap = Management::where('group', 'accountability')->get();
        $hana=$params_sap->where('name', 'hana_enable')->first()->value == 'SI';
        Profile::findOrFail($request->id)
            ->fill([
                'name' => $request->name,
                'type_currency' => $request->type_currency
            ])->save();
        $detail = DetailAccounts::select('account_code')
            ->where('profile_id', $request->id)
            ->pluck('account_code')
            ->toArray();
        $general = GeneralAccounts::select('account_code')
            ->where('profile_id', $request->id)
            ->pluck('account_code')
            ->toArray();
        $employees = Employee::select('card_code')
            ->where('profile_id', $request->id)
            ->pluck('card_code')
            ->toArray();

        $to_delete_detail = array_diff($detail, $this->HandleGetAccountCode($request->detail));
        $to_delete_general = array_diff($general, $this->HandleGetAccountCode($request->general));
        $to_create_detail = array_diff($this->HandleGetAccountCode($request->detail), $detail);
        $to_create_general = array_diff($this->HandleGetAccountCode($request->general), $general);

        $to_delete_employee = array_diff($employees, $this->HandleGetAccountCode($request->employees));
        $to_create_employee = array_diff($this->HandleGetAccountCode($request->employees), $employees);

        DetailAccounts::whereIn('account_code', $to_delete_detail)->delete();
        GeneralAccounts::whereIn('account_code', $to_delete_general)->delete();
        Employee::whereIn('card_code', $to_delete_employee)->delete();

        if(count($to_create_detail)>0){
            foreach ($this->HandleGetAccountSAP($to_create_detail) as $account) {
                DetailAccounts::create([
                    'profile_id' => $request->id,
                    'account_code' => $hana?$account['AcctCode']:$account->AcctCode,
                    'format_code' => $hana?$account['FormatCode']:$account->FormatCode,
                    'account_name' => $hana?$account['AcctName']:$account->AcctName,
                ]);
            }
        }
        if(count($to_create_general)>0){
            foreach ($this->HandleGetAccountSAP($to_create_general) as $account) {
                GeneralAccounts::create([
                    'profile_id' => $request->id,
                    'account_code' => $hana?$account['AcctCode']:$account->AcctCode,
                    'format_code' => $hana?$account['FormatCode']:$account->FormatCode,
                    'account_name' => $hana?$account['AcctName']:$account->AcctName,
                ]);
            }
        }
        if(count($to_create_employee)>0){
            foreach ($this->HandleGetEmpSAP($to_create_employee) as $employee) {
                Employee::create([
                    'profile_id' => $request->id,
                    'card_code' => $hana?$employee['CardCode']:$employee->CardCode,
                    'card_name' => $hana?$employee['CardName']:$employee->CardName
                ]);
            }
        }
        foreach ($request->documents as $document) {
            if (!isset($document['id'])) {
                $document_create = Document::create([
                    'name' => $document['name'],
                    'type_document_sap' => $document['type_document_sap'],
                    'ice' => $document['ice'],
                    'tasas' => $document['tasas'],
                    'exento' => $document['exento'],
                    'ice_status' => $document['ice_status'],
                    'tasas_status' => $document['tasas_status'],
                    'exento_status' => $document['exento_status'],
                    'authorization_number_status' => $document['authorization_number_status'],
                    'cuf_status' => $document['cuf_status'],
                    'control_code_status' => $document['control_code_status'],
                    'business_name_status' => $document['business_name_status'],
                    'nit_status' => $document['nit_status'],
                    'discount_status' => $document['discount_status'],
                    'gift_card_status' => $document['gift_card_status'],
                    'rate_zero_status' => $document['rate_zero_status'],
                    'profile_id' => $request->id,
                ]);
                foreach ($document['detail'] as $item) {
                    DocumentDetail::create([
                        'document_id' => $document_create->id,
                        'type' => $item['type'],
                        'percentage' => $item['percentage'],
                        'account' => $item['account'],
                        'exento' => $item['exento'],
                        'type_calculation'=> $item['type_calculation'],
                        'calculation'=>$item['calculation']
                    ]);
                }
                foreach ($document['fields'] as $item) {
                    DocumentField::create([
                        'document_id' => $document_create->id,
                        'account' => $item['account'],
                        'name' => $item['name'],
                        'type_calculation' => $item['type_calculation'],
                    ]);
                }
            } else {
                if ((int) $document['for_delete'] == 1) {
                    foreach ($document['detail'] as $item) {
                        if (isset($item['id'])) {
                            DocumentDetail::findOrFail($item['id'])->delete();
                        }
                    }
                    foreach ($document['fields'] as $item) {
                        if (isset($item['id'])) {
                            DocumentField::findOrFail($item['id'])->delete();
                        }
                    }
                    Document::findOrFail($document['id'])->delete();
                } else {
                    Document::findOrFail($document['id'])->fill([
                        'name' => $document['name'],
                        'type_document_sap' => $document['type_document_sap'],
                        'ice' => $document['ice'],
                        'tasas' => $document['tasas'],
                        'exento' => $document['exento'],
                        'ice_status' => $document['ice_status'],
                        'tasas_status' => $document['tasas_status'],
                        'exento_status' => $document['exento_status'],
                        'authorization_number_status' => $document['authorization_number_status'],
                        'cuf_status' => $document['cuf_status'],
                        'control_code_status' => $document['control_code_status'],
                        'business_name_status' => $document['business_name_status'],
                        'nit_status' => $document['nit_status'],
                        'discount_status' => $document['discount_status'],
                        'gift_card_status' => $document['gift_card_status'],
                        'rate_zero_status' => $document['rate_zero_status'],
                    ])->save();
                    foreach ($document['detail'] as $item) {
                        if (!isset($item['id'])) {
                            DocumentDetail::create([
                                'document_id' => $document['id'],
                                'type' => $item['type'],
                                'percentage' => $item['percentage'],
                                'account' => $item['account'],
                                'exento' => $item['exento'],
                                'type_calculation'=> $item['type_calculation'],
                                'calculation'=>$item['calculation']
                            ]);
                        } else {
                            if ((int) $item['for_delete'] == 1) {
                                DocumentDetail::findOrFail($item['id'])->delete();
                            } else {
                                DocumentDetail::findOrFail($item['id'])->fill([
                                    'type' => $item['type'],
                                    'percentage' => $item['percentage'],
                                    'account' => $item['account'],
                                    'exento' => $item['exento'],
                                    'type_calculation'=> $item['type_calculation'],
                                    'calculation'=>$item['calculation']
                                ])->save();
                            }
                        }
                    }
                    foreach ($document['fields'] as $item) {
                        if (!isset($item['id'])) {
                            DocumentField::create([
                                'document_id' => $document['id'],
                                'account' => $item['account'],
                                'name' => $item['name'],
                                'type_calculation' => $item['type_calculation'],
                            ]);
                        } else {
                            if ((int) $item['for_delete'] == 1) {
                                DocumentField::findOrFail($item['id'])->delete();
                            } else {
                                DocumentField::findOrFail($item['id'])->fill([
                                    'account' => $item['account'],
                                    'name' => $item['name'],
                                    'type_calculation' => $item['type_calculation'],
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
        $field_document_type = Management::where('name', 'document_type')->first();
        $profile = Profile::where('id', $id)->with([
            'documents' => function ($q) {
                $q->select(
                    'id',
                    'name',
                    'type_document_sap',
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
                )->orderBy('id', 'asc');
            },
            'documents.detail' => function ($q) {
                $q->select(
                    'id',
                    'document_id',
                    'type',
                    'percentage',
                    'account',
                    'type_calculation',
                    'exento',
                    'calculation',
                    DB::raw("0 as for_delete"),
                )->orderBy('id', 'asc');
            },
            'documents.fields' => function ($q) {
                $q->select(
                    'id',
                    'document_id',
                    'account',
                    'name',
                    'type_calculation',
                    DB::raw("0 as for_delete"),
                )->orderBy('id', 'asc');
            }
        ])->first();
        $profile->detail = DB::table('detail_accounts as T1')
            ->select(
                DB::raw("CONCAT(T1.account_code,'-',T1.account_name) as label")
            )
            ->where('profile_id', $id)
            ->pluck('label');
        $profile->general = DB::table('general_accounts as T1')
            ->select(
                DB::raw("CONCAT(T1.account_code,'-',T1.account_name) as label")
            )
            ->where('profile_id', $id)
            ->pluck('label');

        $profile->employees = DB::table('employees as T1')
            ->select(
                DB::raw("CONCAT(T1.card_code,'-',T1.card_name) as label")
            )
            ->where('profile_id', $id)
            ->pluck('label');

        return Inertia::render('administration/profile/EditProfile', [
            'type' => ['IVA', 'IT', 'IUE', 'RC-IVA'],
            'type_calculation' => ['Grossing Up', 'Grossing Down'],
            'profile' => $profile,
            'accounts' => $this->HandleGetAccounts(),
            'currencies' => $this->HandleGetCurrencies(),
            'accounts_document' => $this->HandleGetAccountsDocument(),
            'employees' => $this->HandleGetEmployees(),
            'document_types'=>$this->HandleGetDocumentType($field_document_type->value)
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
    public function HandleGetDocumentType($field){
        $field_explode=explode('_',$field);
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select
                T2."FldValue",
                T2."Descr"
            from $db.CUFD as T1
            inner join $db.UFD1 as T2 on T1."TableID" = T2."TableID"
            and T1."FieldID" = T2."FieldID"
            where T1."TableID" = 'JDT1'
            and T1."AliasID" = '$field_explode[1]'
SQL;
            return Hana::query($sql);
        }else{
            return DB::connection('sap')
                    ->table('CUFD as T1')
                    ->join('UFD1 as T2', function (JoinClause $join){
                        $join->on('T1.TableID','T2.TableID')
                            ->on('T1.FieldID','T2.FieldID');
                    })
                    ->select(
                        'T2.FldValue',
                        'T2.Descr'
                    )
                    ->where('T1.AliasID',$field_explode[1])
                    ->where('T1.TableID','JDT1')
                    ->get();
        }
    }
    public function HandleGetAccountsDocument(){
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select
                T1."AcctName",
                T1."AcctCode"
            from $db.OACT as T1
            where T1."Levels" in (1,2,3,4,5,6,7,8,9,10)
            order by T1."Levels",T1."FatherNum"
SQL;
            $accounts = Hana::query($sql);
            return $accounts;
        }else{
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
            return $accounts;
        }
    }
    public function HandleGetCurrencies(){
        $params_sap = Management::where('group', 'accountability')->get();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select
                T1."CurrCode",
                T1."CurrName"
            from $db.OCRN as T1
SQL;
            return Hana::query($sql);
        }else{
            $currencies = DB::connection('sap')
                        ->table('OCRN as T1')
                        ->select(
                            'T1.CurrCode',
                            'T1.CurrName'
                        )
                        ->get();
            return $currencies;
        }
    }
    public function HandleGetEmployees(){
        $params_sap = Management::where('group', 'accountability')->get();
        $field_name_emp = Management::where('name', 'employee_enablement_field')->first();
        $field_value_emp = Management::where('name', 'employee_enablement_field_value')->first();
        if ($params_sap->where('name', 'hana_enable')->first()->value == 'SI') {
            $db=Config::get('database.connections.hana.database');
            $sql=
<<<SQL
            select
                CONCAT(CONCAT(T1."CardCode",'-'),T1."CardName") as "label"
            from $db.OCRD as T1
            where T1."$field_name_emp->value" = '$field_value_emp->value'
SQL;
            return Hana::query($sql);
        }else{
            $employees = DB::connection('sap')
                        ->table('OCRD as T1')
                        ->select(
                            DB::raw("CONCAT(T1.CardCode,'-',T1.CardName) as label")
                        )
                        ->where($field_name_emp->value, $field_value_emp->value)
                        ->get();
            return $employees;
        }
    }
    public function HandleCreateProfile()
    {
        //return $this->HandleGetAccounts();
        Session::put('title', 'Crear Perfil');
        $field_document_type = Management::where('name', 'document_type')->first();
        return Inertia::render('administration/profile/CreateProfile', [
            'type' => ['IVA', 'IT', 'IUE', 'RC-IVA'],
            'type_calculation' => ['Grossing Up', 'Grossing Down'],
            'accounts' => $this->HandleGetAccounts(),
            'currencies' => $this->HandleGetCurrencies(),
            'accounts_document' => $this->HandleGetAccountsDocument(),
            'employees' => $this->HandleGetEmployees(),
            'document_types'=>$this->HandleGetDocumentType($field_document_type->value)
        ]);
    }
    public function HandleIndexProfile()
    {
        Session::put('title', 'Perfiles');
        $data = Profile::orderBy('id', 'desc')->get();
        return Inertia::render("administration/profile/IndexProfile")->with('data', $data);
    }
}
