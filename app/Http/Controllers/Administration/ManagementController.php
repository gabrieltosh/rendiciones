<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Management;
use Inertia\Inertia;
use Redirect;
use Session;
class ManagementController extends Controller
{
    public function HandleUpdateData($data){
        foreach ($data as $value) {
            Management::where('group',$value['group'])
                    ->where('name',$value['name'])
                    ->update([
                        'value'=>$value['value']
                    ]);
        }
    }
    public function HandleUpdateManagement(Request $request){
        $this->HandleUpdateData($request->accountability);
        $this->HandleUpdateData($request->accountability_detail);
        $this->HandleUpdateData($request->employee);
        $this->HandleUpdateData($request->suppliers);
        Session::flash('message', "Configuración actualizadas correctamente");
        Session::flash('type', 'positive');
        return Redirect::route('panel.management.index');
    }
    public function HandleIndexManagement(){
        $accountability=Management::where('group','accountability')
                                    ->get();
        $accountability_detail=Management::where('group','accountability_detail')
                                    ->get();
        $employee=Management::where('group','employee')
                                    ->get();
        $suppliers=Management::where('group','supplier')
                                    ->get();
        return Inertia::render('administration/management/IndexManagement',[
            'accountability'=>$accountability,
            'accountability_detail'=>$accountability_detail,
            'employee'=>$employee,
            'suppliers'=>$suppliers
        ]);
    }
}
