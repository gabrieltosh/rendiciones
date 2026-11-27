<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Management;
use Inertia\Inertia;
use Redirect;
use Session;
use Storage;
class ManagementController extends Controller
{
    public function HandleStoreImage(Request $request){
        $file = $request->file('image');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('images', $filename, 'public');
        // Se guarda a través del modelo para que la auditoría registre el cambio
        $logo = Management::firstOrNew(['group' => 'company', 'name' => 'logo']);
        if (!$logo->exists) {
            $logo->label = 'Logo Empresarial';
            $logo->type = 'file';
        }
        $logo->value = $filename;
        $logo->save();
        // Inertia necesita una respuesta válida para continuar con el guardado del resto de datos
        return Redirect::route('panel.management.index');
    }
    public function HandleUpdateData($data){
        // Actualiza registro por registro (instancias Eloquent) para que
        // OwenIt\Auditing registre los cambios; los valores sin cambios no generan auditoría.
        foreach ((array) $data as $value) {
            if (!isset($value['group'], $value['name'])) {
                continue;
            }
            $item = Management::where('group', $value['group'])
                    ->where('name', $value['name'])
                    ->first();
            if ($item) {
                $item->value = $value['value'] ?? null;
                $item->save();
            }
        }
    }
    public function HandleUpdateManagement(Request $request){
        $this->HandleUpdateData($request->accountability);
        $this->HandleUpdateData($request->accountability_detail);
        $this->HandleUpdateData($request->employee);
        $this->HandleUpdateData($request->suppliers);
        $this->HandleUpdateData($request->company);
        Session::flash('message', "Configuración actualizada correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.management.index');
    }
    public function HandleIndexManagement(){
        Session::put('title', 'Configuración');
        $accountability=Management::where('group','accountability')
                                    ->get();
        $accountability_detail=Management::where('group','accountability_detail')
                                    ->get();
        $employee=Management::where('group','employee')
                                    ->get();
        $suppliers=Management::where('group','supplier')
                                    ->get();
        $company=Management::where('group','company')
                            ->where('name','!=','logo')
                            ->get();
        $image=Management::where('group','company')
                ->where('name','logo')
                ->first();
        $url = $image && $image->value
            ? Storage::disk('local')->url('images/'.$image->value)
            : null;
        return Inertia::render('administration/management/IndexManagement',[
            'accountability'=>$accountability,
            'accountability_detail'=>$accountability_detail,
            'employee'=>$employee,
            'suppliers'=>$suppliers,
            'company'=>$company,
            'url'=>$url
        ]);
    }
}
