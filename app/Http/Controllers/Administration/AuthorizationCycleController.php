<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AuthorizationCycle;
use App\Models\AuthorizationCycleLevel;
use App\Models\AuthorizationCycleLevelUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Session;
use Redirect;
use DB;

class AuthorizationCycleController extends Controller
{
    public function HandleIndexAuthorizationCycle()
    {
        Session::put('title', 'Ciclos de Autorización');
        $data = AuthorizationCycle::with('levels.users')->get();
        return Inertia::render('administration/authorization-cycles/IndexAuthorizationCycle', [
            'data' => $data,
        ]);
    }

    public function HandleCreateAuthorizationCycle()
    {
        Session::put('title', 'Crear Ciclo de Autorización');
        $users = User::select('id', 'name')
            ->whereIn('type', ['Administrador', 'Autorizador'])
            ->orderBy('name')
            ->get();
        return Inertia::render('administration/authorization-cycles/CreateAuthorizationCycle', [
            'users' => $users,
        ]);
    }

    public function HandleStoreAuthorizationCycle(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'levels' => 'required|array|min:1',
            'levels.*.name' => 'required|string|max:255',
            'levels.*.users' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $cycle = AuthorizationCycle::create([
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => $request->is_active ?? true,
            ]);

            foreach ($request->levels as $index => $levelData) {
                $level = AuthorizationCycleLevel::create([
                    'cycle_id' => $cycle->id,
                    'order'    => $index + 1,
                    'name'     => $levelData['name'],
                ]);
                foreach ($levelData['users'] as $userId) {
                    AuthorizationCycleLevelUser::create([
                        'level_id' => $level->id,
                        'user_id'  => $userId,
                    ]);
                }
            }
        });

        Session::flash('message', 'Ciclo de autorización creado correctamente');
        Session::flash('type', 'positive');
        return Redirect::route('panel.authorization-cycle.index');
    }

    public function HandleEditAuthorizationCycle($id)
    {
        Session::put('title', 'Editar Ciclo de Autorización');
        $cycle = AuthorizationCycle::with('levels.users')->findOrFail($id);
        $users = User::select('id', 'name')
            ->whereIn('type', ['Administrador', 'Autorizador'])
            ->orderBy('name')
            ->get();
        return Inertia::render('administration/authorization-cycles/EditAuthorizationCycle', [
            'cycle' => $cycle,
            'users' => $users,
        ]);
    }

    public function HandleUpdateAuthorizationCycle(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:authorization_cycles,id',
            'name'   => 'required|string|max:255',
            'levels' => 'required|array|min:1',
            'levels.*.name' => 'required|string|max:255',
            'levels.*.users' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $cycle = AuthorizationCycle::findOrFail($request->id);
            $cycle->fill([
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => $request->is_active ?? true,
            ])->save();

            // Rebuild levels
            $oldLevelIds = $cycle->levels()->pluck('id');
            AuthorizationCycleLevelUser::whereIn('level_id', $oldLevelIds)->delete();
            AuthorizationCycleLevel::whereIn('id', $oldLevelIds)->delete();

            foreach ($request->levels as $index => $levelData) {
                $level = AuthorizationCycleLevel::create([
                    'cycle_id' => $cycle->id,
                    'order'    => $index + 1,
                    'name'     => $levelData['name'],
                ]);
                foreach ($levelData['users'] as $userId) {
                    AuthorizationCycleLevelUser::create([
                        'level_id' => $level->id,
                        'user_id'  => $userId,
                    ]);
                }
            }
        });

        Session::flash('message', 'Ciclo de autorización actualizado correctamente');
        Session::flash('type', 'positive');
        return Redirect::route('panel.authorization-cycle.index');
    }

    public function HandleStoreQuickCycle(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'levels'             => 'required|array|min:1',
            'levels.*.name'      => 'required|string|max:255',
            'levels.*.user_ids'  => 'required|array|min:1',
        ]);

        $cycle = DB::transaction(function () use ($request) {
            $cycle = AuthorizationCycle::create([
                'name'      => $request->name,
                'is_active' => true,
            ]);
            foreach ($request->levels as $i => $levelData) {
                $level = AuthorizationCycleLevel::create([
                    'cycle_id' => $cycle->id,
                    'order'    => $i + 1,
                    'name'     => $levelData['name'],
                ]);
                foreach ($levelData['user_ids'] as $userId) {
                    AuthorizationCycleLevelUser::create([
                        'level_id' => $level->id,
                        'user_id'  => $userId,
                    ]);
                }
            }
            return $cycle->load(['levels.users:id,name']);
        });

        return response()->json([
            'value'  => $cycle->id,
            'label'  => $cycle->name,
            'levels' => $cycle->levels->map(fn ($l) => [
                'order' => $l->order,
                'name'  => $l->name,
                'users' => $l->users->pluck('name'),
            ]),
        ]);
    }

    public function HandleDeleteAuthorizationCycle($id)
    {
        try {
            AuthorizationCycle::findOrFail($id)->delete();
            Session::flash('message', 'Ciclo de autorización eliminado correctamente');
            Session::flash('type', 'positive');
        } catch (QueryException $e) {
            Session::flash('type', 'negative');
            if ($e->errorInfo[1] == 547) {
                Session::flash('message', 'No se puede eliminar este ciclo porque hay registros asociados');
            } else {
                Session::flash('message', $e->getMessage());
            }
        }
        return Redirect::route('panel.authorization-cycle.index');
    }
}
