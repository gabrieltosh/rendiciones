<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Session;
use DB;
use OwenIt\Auditing\Models\Audit;

class ReportController extends Controller
{
    public function HandleIndexReport(Request $request)
    {
        Session::put('title', 'Reporte de Rendiciones');

        $query = DB::table('accountabilities as A')
            ->join('users as U', 'U.id', '=', 'A.user_id')
            ->join('profiles as P', 'P.id', '=', 'A.profile_id')
            ->leftJoin('areas as AR', 'AR.id', '=', 'U.area_id')
            ->select([
                'A.id',
                'A.description',
                'A.employee_name',
                'A.account_name',
                'A.start_date',
                'A.end_date',
                'A.total',
                'A.status',
                'A.sap_exported',
                DB::raw("U.name as user_name"),
                DB::raw("P.name as profile_name"),
                DB::raw("AR.name as area_name"),
            ]);

        if ($request->filled('user_id')) {
            $query->where('A.user_id', $request->user_id);
        }
        if ($request->filled('area_id')) {
            $query->where('U.area_id', $request->area_id);
        }
        if ($request->filled('date_from')) {
            $query->where('A.start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('A.start_date', '<=', $request->date_to);
        }
        if ($request->filled('sap_status')) {
            if ($request->sap_status === 'exported') {
                $query->where('A.status', 'Autorizado')->where('A.sap_exported', 1);
            } elseif ($request->sap_status === 'pending') {
                $query->where('A.status', 'Autorizado')->where('A.sap_exported', 0);
            } elseif ($request->sap_status === 'not_authorized') {
                $query->where('A.status', '!=', 'Autorizado');
            }
        }

        $data = $query->orderBy('A.id', 'desc')->get();

        $users = User::select('id as value', 'name as label', 'area_id')
            ->orderBy('name')
            ->get();

        $areas = Area::select('id as value', 'name as label')
            ->orderBy('name')
            ->get();

        return Inertia::render('administration/reports/ReportAccountability', [
            'data'    => $data,
            'users'   => $users,
            'areas'   => $areas,
            'filters' => $request->only(['user_id', 'area_id', 'date_from', 'date_to', 'sap_status']),
        ]);
    }

    public function HandleIndexAuditLog(Request $request)
    {
        Session::put('title', 'Log de Auditoría');

        $modelMap = [
            'App\\Models\\Accountability'       => 'Rendición',
            'App\\Models\\AccountabilityDetail' => 'Detalle Rendición',
            'App\\Models\\User'                 => 'Usuario',
            'App\\Models\\Profile'              => 'Perfil',
            'App\\Models\\Document'             => 'Documento',
            'App\\Models\\DocumentDetail'       => 'Detalle Documento',
            'App\\Models\\DocumentField'        => 'Campo Documento',
            'App\\Models\\Management'           => 'Configuración',
            'App\\Models\\Supplier'             => 'Proveedor',
            'App\\Models\\UserAuthorization'    => 'Autorización Usuario',
            'App\\Models\\UserProfile'          => 'Perfil Usuario',
        ];

        $query = Audit::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id)
                  ->where('user_type', User::class);
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('model')) {
            $query->where('auditable_type', $request->model);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->paginate(50)->through(function ($audit) use ($modelMap) {
            return [
                'id'             => $audit->id,
                'user'           => $audit->user ? $audit->user->name : 'Sistema',
                'event'          => $audit->event,
                'auditable_type' => $modelMap[$audit->auditable_type] ?? $audit->auditable_type,
                'auditable_id'   => $audit->auditable_id,
                'old_values'     => $audit->old_values,
                'new_values'     => $audit->new_values,
                'ip_address'     => $audit->ip_address,
                'created_at'     => \Carbon\Carbon::parse($audit->created_at)
                                        ->setTimezone('America/La_Paz')
                                        ->format('Y-m-d g:i A'),
            ];
        });

        $users = User::select('id as value', 'name as label')->orderBy('name')->get();

        $modelOptions = collect($modelMap)
            ->map(fn($label, $class) => ['value' => $class, 'label' => $label])
            ->values();

        return Inertia::render('administration/reports/AuditLog', [
            'audits'  => $audits,
            'users'   => $users,
            'models'  => $modelOptions,
            'filters' => $request->only(['user_id', 'event', 'model', 'date_from', 'date_to']),
        ]);
    }
}
