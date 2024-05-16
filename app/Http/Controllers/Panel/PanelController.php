<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Session;
use Barryvdh\DomPDF\Facade\Pdf;
class PanelController extends Controller
{
    public function HandleIndexDashboard(){
        Session::put('title', 'Inicio');
        return Inertia::render("welcome");
    }
    public function test(){
        $pdf = Pdf::loadView('pdf.accountability');
        return $pdf->stream();
    }
}
