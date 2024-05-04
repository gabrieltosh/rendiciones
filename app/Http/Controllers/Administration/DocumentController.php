<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Session;
use Redirect;
use App\Models\Document;
class DocumentController extends Controller
{
    public function HandleStoreUser(UserRequest $request){
        User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'username'=>$request->username,
            'type'=>$request->type,
            'distribution_rule_one'=>$request->distribution_rule_one,
            'distribution_rule_second'=>$request->distribution_rule_second,
            'distribution_rule_three'=>$request->distribution_rule_three,
            'password'=>$request->password,
            'status'=>'PreActivo'
        ]);
        Session::flash('message', "Usuario creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.user.index');
    }
    public function HandleUpdateUser(UserRequest $request)
    {
        Session::flash('message', "Usuario actualizado correctamente");
        Session::flash('type', 'positive');
        User::findOrFail($request->id)->fill([
            'name'=>$request->name,
            'email'=>$request->email,
            'username'=>$request->username,
            'type'=>$request->type,
            'distribution_rule_one'=>$request->distribution_rule_one,
            'distribution_rule_second'=>$request->distribution_rule_second,
            'distribution_rule_three'=>$request->distribution_rule_three,
            'status'=>$request->status
        ])->save();
        return Redirect::route('panel.user.index');
    }
    public function HandleEditUser($id)
    {
        Session::put('title', 'Editar Usuario');
        return Inertia::render(
            'administration/users/EditUser',
            [
                'user' => User::where('id', $id)->first()
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
        return Inertia::render(
            'administration/users/CreateUser'
        );
    }
    public function HandleIndexUser()
    {
        Session::put('title', 'Usuarios');
        $data = User::where('id','!=',Auth::user()->id)->orderBy('id','desc')->get();
        return Inertia::render("administration/users/IndexUser")->with('data', $data);
    }
}
