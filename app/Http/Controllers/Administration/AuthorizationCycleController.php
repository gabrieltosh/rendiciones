<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Accountability;
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
    public function HandleIndexAuthorizationCycle(Request $request)
    {
        Session::put('title', 'Ciclos de Autorización');
        $data = AuthorizationCycle::with('levels.users')->get();
        return Inertia::render('administration/authorization-cycles/IndexAuthorizationCycle', [
            'data' => $data,
            // Ciclo recién guardado: se resalta y se muestran sus niveles
            'highlight' => (int) $request->query('highlight') ?: null,
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

        $cycleId = DB::transaction(function () use ($request) {
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
            return $cycle->id;
        });

        Session::flash('message', 'Ciclo de autorización creado correctamente');
        Session::flash('type', 'positive');
        return Redirect::route('panel.authorization-cycle.index', ['highlight' => $cycleId]);
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

            // Los niveles se actualizan en su lugar (por su id), no se borran y recrean:
            // las aprobaciones se borran en cascada con su nivel y las rendiciones pendientes
            // quedaban con current_level_id en null (nullOnDelete), fuera del ciclo.
            $existing = $cycle->levels()->orderBy('order')->get()->keyBy('id');
            $keptIds = [];

            foreach ($request->levels as $index => $levelData) {
                $level = isset($levelData['id']) ? $existing->get((int) $levelData['id']) : null;
                if ($level) {
                    $level->fill(['order' => $index + 1, 'name' => $levelData['name']])->save();
                } else {
                    $level = AuthorizationCycleLevel::create([
                        'cycle_id' => $cycle->id,
                        'order'    => $index + 1,
                        'name'     => $levelData['name'],
                    ]);
                }

                AuthorizationCycleLevelUser::where('level_id', $level->id)->delete();
                foreach (array_unique($levelData['users']) as $userId) {
                    AuthorizationCycleLevelUser::create([
                        'level_id' => $level->id,
                        'user_id'  => $userId,
                    ]);
                }
                $keptIds[] = $level->id;
            }

            // Niveles quitados: las rendiciones pendientes en ellos pasan al último nivel
            // que queda (no se pierden del ciclo) y luego se eliminan los niveles.
            $removedIds = $existing->pluck('id')->diff($keptIds)->values();
            if ($removedIds->isNotEmpty()) {
                Accountability::whereIn('current_level_id', $removedIds)
                    ->where('status', 'Pendiente')
                    ->update(['current_level_id' => end($keptIds)]);
                AuthorizationCycleLevelUser::whereIn('level_id', $removedIds)->delete();
                AuthorizationCycleLevel::whereIn('id', $removedIds)->delete();
            }
        });

        Session::flash('message', 'Ciclo de autorización actualizado correctamente');
        Session::flash('type', 'positive');
        return Redirect::route('panel.authorization-cycle.index', ['highlight' => $request->id]);
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
