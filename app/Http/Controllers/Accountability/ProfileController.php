<?php

namespace App\Http\Controllers\Accountability;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Session;
use Inertia\Inertia;
class ProfileController extends Controller
{
    public function HandleIndexProfiles(Request $request){
        Session::put('title', 'Crear Usuario');
        $profiles=UserProfile::where('user_id',$request->user()->id)->with('profile')->get();
        return Inertia::render(
            'accountability/IndexProfiles',
            [
                'profiles'=>$profiles
            ]
        );
    }
}
