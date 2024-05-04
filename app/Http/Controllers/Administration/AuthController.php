<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Session;
use Auth;
use App\Http\Requests\Auth\LoginRequest;
class AuthController extends Controller
{
    public function HandleIndexAuth(){
        Session::put('title', 'Login');
        return Inertia::render('auth/LoginAuth');
    }
    public function HandleLoginAuth(LoginRequest $request){
        $user=User::where('username', $request->email)->first();
        if(isset($user->status) && $user->status=='Activo'){
            if(Auth::attempt(['username'=> $request->email,'password'=> $request->password])){
                $request->session()->regenerate();
                Session::flash('message', 'Inicio de sesion correcto');
                Session::flash('type', 'positive');
                return to_route('home');
            }else{
                Session::flash('message', 'Las credenciales no son validas');
                Session::flash('type', 'negative');
                return redirect()->back();
            }
        }else{
            if(isset($user->status) && $user->status=='pre-activo'){

            }else{
                Session::flash('message', 'El usuario esta deshabilitado o no registrado');
                Session::flash('type', 'negative');
                return redirect()->back();
            }
        }
    }
    public function HandleLogoutAuth(Request $request){
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
