<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
class PanelController extends Controller
{
    public function HandleIndexDashboard(){
        return Inertia::render("welcome");
    }
}
