<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\UserAuthorization;
use App\Http\Requests\Administration\UserRequest;
use Session;
use Redirect;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use DB;
use App\Models\Management;
use App\Helpers\Hana;
use Config;
use Hash;
class UserController extends Controller
{
    public function HandleStoreUser(UserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'type' => $request->type,
            'distribution_rule_one' => $request->distribution_rule_one,
            'distribution_rule_second' => $request->distribution_rule_second,
            'distribution_rule_three' => $request->distribution_rule_three,
            'distribution_rule_four' => $request->distribution_rule_four,
            'distribution_rule_five' => $request->distribution_rule_five,
            'password' => $request->password,
            'card_code' => $request->card_code,
            'status' => 'PreActivo'
        ]);
        foreach ($request->profiles as $name_profile) {
            $profile = Profile::where('name', $name_profile)->first();
            UserProfile::create([
                'user_id' => $user->id,
                'profile_id' => $profile->id
            ]);
        }
        $users = $this->HandleFormatUser($request->users);
        foreach ($users as $id) {
            UserAuthorization::create([
                'user_id' => $user->id,
                'auth_user_id' => $id
            ]);
        }
        Session::flash('message', "Usuario creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.user.index');
    }
    public function HandleUpdateUser(UserRequest $request)
    {
        Session::flash('message', "Usuario actualizado correctamente");
        Session::flash('type', 'positive');

        $profiles = UserProfile::select('profile_id')
            ->where('user_id', $request->id)
            ->get()
            ->pluck('profile_id')
            ->toArray();

        $users = UserAuthorization::select('auth_user_id')
            ->where('user_id', $request->id)
            ->get()
            ->pluck('auth_user_id')
            ->toArray();

        $profiles_request = [];
        foreach ($request->profiles as $name_profile) {
            $profile = Profile::where('name', $name_profile)->first();
            array_push($profiles_request, $profile->id);
        }

        $to_delete_profile = array_diff($profiles, $profiles_request);
        $to_create_profile = array_diff($profiles_request, $profiles);

        $to_delete_users = array_diff($users, $this->HandleFormatUser($request->users));
        $to_create_users = array_diff($this->HandleFormatUser($request->users), $users);

        UserAuthorization::where('user_id', $request->id)
            ->whereIn('auth_user_id', $to_delete_users)
            ->delete();

        UserProfile::whereIn('profile_id', $to_delete_profile)
            ->where('user_id', $request->id)
            ->delete();

        foreach ($to_create_profile as $profile_id) {
            $profile = Profile::where('id', $profile_id)->first();
            UserProfile::create([
                'user_id' => $request->id,
                'profile_id' => $profile->id
            ]);
        }

        foreach ($to_create_users as $auth_user_id) {
            UserAuthorization::create([
                'user_id' => $request->id,
                'auth_user_id' => $auth_user_id
            ]);
        }
        if(is_null($request->password)){
            User::findOrFail($request->id)->fill([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'type' => $request->type,
                'card_code' => $request->card_code,
                'distribution_rule_one' => $request->distribution_rule_one,
                'distribution_rule_second' => $request->distribution_rule_second,
                'distribution_rule_three' => $request->distribution_rule_three,
                'distribution_rule_four' => $request->distribution_rule_four,
                'distribution_rule_five' => $request->distribution_rule_five,
                'status' => $request->status,
            ])->save();
        }else{
            User::findOrFail($request->id)->fill([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'type' => $request->type,
                'card_code' => $request->card_code,
                'distribution_rule_one' => $request->distribution_rule_one,
                'distribution_rule_second' => $request->distribution_rule_second,
                'distribution_rule_three' => $request->distribution_rule_three,
                'distribution_rule_four' => $request->distribution_rule_four,
                'distribution_rule_five' => $request->distribution_rule_five,
                'status' => $request->status,
                'password'=>Hash::make($request->password)
            ])->save();
        }

        return Redirect::route('panel.user.index');
    }
    public function HandleFormatUser($data)
    {
        $users = [];
        foreach ($data as $value) {
            $user = explode('-', $value);
            array_push($users, $user[0]);
        }
        return $users;
    }
    public function HandleEditUser($id)
    {
        Session::put('title', 'Editar Usuario');
        $profiles = Profile::select('name as label', 'id')->get();
        $user = User::where('id', $id)->first();
        $user->profiles = UserProfile::select('T1.name')
            ->join('profiles as T1', 'T1.id', 'profile_id')
            ->where('user_id', $id)
            ->get()
            ->pluck('name');
        $users = User::select(
            DB::raw("CONCAT(id,'-',name) as label"),
        )
            ->where('id', '!=', $user->id)
            ->get();
        $user->users = UserAuthorization::select(
            DB::raw("CONCAT(T1.id,'-',T1.name) as label"),
        )
            ->join('users as T1', 'T1.id', 'auth_user_id')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('label');
        return Inertia::render(
            'administration/users/EditUser',
            [
                'user' => $user,
                'profiles' => $profiles,
                'distribution' => $this->HandleGetDistributions(),
                'users' => $users
            ]
        );
    }
    public function HandleDeleteUser($id)
    {
        try {
            User::findOrFail($id)->delete();
            Session::flash('message', "Usuario eliminado correctamente");
            Session::flash('type', 'positive');
        } catch (QueryException $e) {
            Session::flash('type', 'negative');
            if ($e->errorInfo[1] == 1451) {
                Session::flash('message', 'No puedes eliminar este usuario porque hay registros asociados en otras tablas');
            } else {
                Session::flash('message', $e->getMessage());
            }
        }
    }
    public function HandleCreateUser()
    {
        Session::put('title', 'Crear Usuario');
        $profiles = Profile::select('name as label', 'id')->get();
        $users = User::select(
            DB::raw("CONCAT(id,'-',name) as label"),
        )->get();
        return Inertia::render(
            'administration/users/CreateUser',
            [
                'profiles' => $profiles,
                'distribution' => $this->HandleGetDistributions(),
                'users' => $users
            ]
        );
    }
    public function HandleIndexUser()
    {
        Session::put('title', 'Usuarios');
        $data = User::orderBy('id', 'desc')->get();
        return Inertia::render("administration/users/IndexUser")->with('data', $data);
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
                select CONCAT(CONCAT(T1."OcrCode",'-'),T1."OcrName") as "Name", T1."OcrName" as PrcName,T1."OcrCode" as PrcCode
                from $db.OOCR as T1
                where T1."DimCode" = $i
                and T1."Locked" = 'N'
                Order by T1."OcrCode" asc
SQL;
                $data[$i] = Hana::query($sql);
            }
            return $data;
        } else {
            $data = array();
            for ($i = 1; $i <= 5; $i++) {
                $data[$i] =
                    DB::connection('sap')
                        ->table('OOCR as T1')
                        ->select(
                            DB::raw("CONCAT(T1.OcrCode,'-',T1.OcrName) as Name"),
                            'T1.OcrCode as PrcCode',
                            'T1.OcrName as PrcName'
                        )
                        ->where('T1.DimCode', $i)
                        ->where('T1.Locked', 'N')
                        ->get();
            }
            return $data;
        }
    }
}
