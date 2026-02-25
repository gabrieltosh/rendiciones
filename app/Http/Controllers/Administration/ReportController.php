<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Session;
use DB;

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
            'filters' => $request->only(['user_id', 'area_id', 'date_from', 'date_to']),
        ]);
    }
}
