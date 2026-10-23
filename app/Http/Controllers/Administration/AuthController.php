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
            if(Auth::attempt(['username'=> $request->email,'password'=> $request->password], $request->boolean('accept'))){
                $request->session()->regenerate();
                Session::flash('message', 'Bienvenido, '.$user->name);
                Session::flash('type', 'positive');
                return to_route('home');
            }else{
                Session::flash('message', 'Usuario o contraseña incorrectos');
                Session::flash('type', 'negative');
                return redirect()->back();
            }
        }elseif(isset($user->status) && in_array($user->status, ['PreActivo', 'pre-activo'])){
            Session::flash('message', 'Tu usuario aún no está activo. Contacta al administrador.');
            Session::flash('type', 'warning');
            return redirect()->back();
        }else{
            Session::flash('message', 'El usuario está deshabilitado o no registrado');
            Session::flash('type', 'negative');
            return redirect()->back();
        }
    }
    public function HandleLogoutAuth(Request $request){
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
