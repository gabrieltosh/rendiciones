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
    public function HandleIndexDashboard(Request $request){
        Session::put('title', 'Inicio');
        $user = $request->user();

        $own = Accountability::where('user_id', $user->id);

        // Un status null significa que la rendición está en borrador
        $statusCounts = (clone $own)
            ->selectRaw("COALESCE(status, 'Borrador') as status_name, COUNT(*) as total, SUM(total) as amount")
            ->groupBy('status')
            ->get()
            ->keyBy('status_name');

        $summary = collect(['Borrador', 'Pendiente', 'Rechazado', 'Autorizado'])
            ->mapWithKeys(fn ($status) => [$status => [
                'count'  => (int) ($statusCounts[$status]->total ?? 0),
                'amount' => (float) ($statusCounts[$status]->amount ?? 0),
            ]]);

        $recent = (clone $own)
            ->with('profile:id,name,type_currency')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'profile_id', 'description', 'total', 'status', 'start_date', 'end_date', 'sap_exported']);

        $review = null;
        if (in_array($user->type, ['Administrador', 'Autorizador'])) {
            $review = [
                'authorization' => Accountability::pendingAuthorizationFor($user->id)->count(),
                'pending_sap'   => Accountability::pendingSapExport()->count(),
            ];
        }

        return Inertia::render("welcome", [
            'summary' => $summary,
            'recent'  => $recent,
            'review'  => $review,
        ]);
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
