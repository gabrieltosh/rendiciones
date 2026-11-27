<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Inertia\Inertia;
use Session;
use Redirect;
use App\Http\Requests\Administration\SupplierRequest;
use Illuminate\Database\QueryException;
class SupplierController extends Controller
{
    public function HandleStoreSupplier(SupplierRequest $request){
        Supplier::create([
            'business'=>$request->business,
            'nit'=>$request->nit
        ]);
        Session::flash('message', "Proveedor creado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.supplier.index');
    }
    public function HandleUpdateSupplier(SupplierRequest $request)
    {
        Supplier::findOrFail($request->id)->fill([
            'business'=>$request->business,
            'nit'=>$request->nit
        ])->save();
        Session::flash('message', "Proveedor actualizado correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.supplier.index');
    }
    public function HandleEditSupplier($id)
    {
        Session::put('title', 'Editar Proveedor');
        return Inertia::render(
            'administration/suppliers/EditSupplier',
            [
                'supplier' => Supplier::findOrFail($id)
            ]
        );
    }
    public function HandleDeleteSupplier($id)
    {
        try {
            Supplier::findOrFail($id)->delete();
            Session::flash('message', "Proveedor eliminado correctamente");
            Session::flash('type', 'positive');
        } catch (QueryException $e) {
            Session::flash('type', 'negative');
            // 547: violación de FK en SQL Server (1451 en MySQL)
            if ($e->errorInfo[1] == 547 || $e->errorInfo[1] == 1451) {
                Session::flash('message', 'No puedes eliminar este proveedor porque hay registros asociados');
            } else {
                Session::flash('message', $e->getMessage());
            }
        }
        return Redirect::route('panel.supplier.index');
    }
    public function HandleCreateSupplier()
    {
        Session::put('title', 'Crear Proveedor');
        return Inertia::render(
            'administration/suppliers/CreateSupplier'
        );
    }
    public function HandleIndexSupplier()
    {
        Session::put('title', 'Proveedores');
        $data = Supplier::orderBy('id','desc')->get();
        return Inertia::render("administration/suppliers/IndexSupplier")->with('data', $data);
    }
}
