<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Http\Requests\Administration\AreaRequest;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Session;
use Redirect;

class AreaController extends Controller
{
    public function HandleIndexArea()
    {
        Session::put('title', 'Áreas');
        $data = Area::orderBy('id', 'desc')->get();
        return Inertia::render('administration/areas/IndexArea')->with('data', $data);
    }

    public function HandleCreateArea()
    {
        Session::put('title', 'Crear Área');
        return Inertia::render('administration/areas/CreateArea');
    }

    public function HandleStoreArea(AreaRequest $request)
    {
        Area::create([
            'name'        => $request->name,
            'description' => $request->description,
        ]);
        Session::flash('message', 'Área creada correctamente');
        Session::flash('type', 'positive');
        return Redirect::route('panel.area.index');
    }

    public function HandleEditArea($id)
    {
        Session::put('title', 'Editar Área');
        $area = Area::findOrFail($id);
        return Inertia::render('administration/areas/EditArea', ['area' => $area]);
    }

    public function HandleUpdateArea(AreaRequest $request)
    {
        Area::findOrFail($request->id)->fill([
            'name'        => $request->name,
            'description' => $request->description,
        ])->save();
        Session::flash('message', 'Área actualizada correctamente');
        Session::flash('type', 'positive');
        return Redirect::route('panel.area.index');
    }

    public function HandleDeleteArea($id)
    {
        try {
            Area::findOrFail($id)->delete();
            Session::flash('message', 'Área eliminada correctamente');
            Session::flash('type', 'positive');
        } catch (QueryException $e) {
            Session::flash('type', 'negative');
            if ($e->errorInfo[1] == 547 || $e->errorInfo[1] == 1451) {
                Session::flash('message', 'No puedes eliminar esta área porque hay registros asociados');
            } else {
                Session::flash('message', $e->getMessage());
            }
        }
    }
}
