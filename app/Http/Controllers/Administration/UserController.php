<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Administration\UserRequest;
use Session;
use Redirect;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Auth;

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
            'password' => $request->password,
            'status' => 'PreActivo'
        ]);
        foreach ($request->profiles as $name_profile) {
            $profile = Profile::where('name', $name_profile)->first();
            UserProfile::create([
                'user_id' => $user->id,
                'profile_id' => $profile->id
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

        $profiles_request = [];
        foreach ($request->profiles as $name_profile) {
            $profile = Profile::where('name', $name_profile)->first();
            array_push($profiles_request, $profile->id);
        }

        $to_delete_profile = array_diff($profiles, $profiles_request);
        $to_create_profile = array_diff($profiles_request, $profiles);

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

        User::findOrFail($request->id)->fill([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'type' => $request->type,
            'distribution_rule_one' => $request->distribution_rule_one,
            'distribution_rule_second' => $request->distribution_rule_second,
            'distribution_rule_three' => $request->distribution_rule_three,
            'status' => $request->status
        ])->save();
        return Redirect::route('panel.user.index');
    }
    public function HandleEditUser($id)
    {
        Session::put('title', 'Editar Usuario');
        $profiles = Profile::select('name as label', 'id')->get();
        $user =User::where('id', $id)->first();
        $user->profiles=UserProfile::select('T1.name')
                        ->join('profiles as T1','T1.id','profile_id')
                        ->where('user_id',$id)
                        ->get()
                        ->pluck('name');
        return Inertia::render(
            'administration/users/EditUser',
            [
                'user' => $user,
                'profiles' => $profiles
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
        return Inertia::render(
            'administration/users/CreateUser',
            [
                'profiles' => $profiles
            ]
        );
    }
    public function HandleIndexUser()
    {
        Session::put('title', 'Usuarios');
        $data = User::orderBy('id', 'desc')->get();
        return Inertia::render("administration/users/IndexUser")->with('data', $data);
    }
}
