<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Session;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Accountability;
class PanelController extends Controller
{
    public function HandleIndexDashboard(){
        Session::put('title', 'Inicio');
        return Inertia::render("welcome");
    }
    public function test(){
        $data=Accountability::with('profile','user','detail.document')
                    ->where('id',1)
                    ->first();
        $pdf = Pdf::loadView('pdf.accountability_detail',[
            'data'=>$data
        ]);
        $pdf->setPaper('letter', 'portrait');
        return $pdf->stream();
    }
}
