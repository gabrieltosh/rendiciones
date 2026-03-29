<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\AuthController;
use App\Http\Controllers\Panel\PanelController;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\ManagementController;
use App\Http\Controllers\Administration\ProfileController;
use App\Http\Controllers\Administration\DocumentController;
use App\Http\Controllers\Administration\SupplierController;
use App\Http\Controllers\Administration\AreaController;
use App\Http\Controllers\Administration\ReportController;
use App\Http\Controllers\Accountability\ProfileController as AccountabilityProfileController;
use App\Http\Controllers\Accountability\AccountabilityController;
use App\Http\Controllers\Authorization\AccountabilityController as AuthAccountabilityController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('test',[PanelController::class,'test']);

Route::middleware('guest')->group(function() {

    Route::get('login', [AuthController::class, 'HandleIndexAuth'])->name('login');

    Route::post('login', [AuthController::class, 'HandleLoginAuth'])->name('login.store');
});


Route::middleware('auth')->group(function() {
    Route::get('/',[PanelController::class,'HandleIndexDashboard'])->middleware('verified')->name('home');
    Route::post('logout', [AuthController::class, 'HandleLogoutAuth'])->name('auth.logout');

    Route::post('siat/consulta', [AccountabilityController::class, 'HandleConsultaFactura'])->name('siat.consulta');

    Route::name('panel.')->prefix('panel')->group(function(){

        // ── Solo Administrador ───────────────────────────────────────────────
        Route::middleware('role:Administrador')->group(function(){
            Route::name('management.')->prefix('management')->controller(ManagementController::class)->group(function(){
                Route::get('','HandleIndexManagement')->name('index');
                Route::post('','HandleUpdateManagement')->name('update');
                Route::post('logo','HandleStoreImage')->name('logo');
            });
            Route::delete('{id}/user',[UserController::class,'HandleDeleteUser'])->name('user.delete');
            Route::name('user.')->prefix('user')->controller(UserController::class)->group(function(){
                Route::get('','HandleIndexUser')->name('index');
                Route::post('','HandleStoreUser')->name('store');
                Route::put('','HandleUpdateUser')->name('update');
                Route::get('{id}/edit','HandleEditUser')->name('edit');
                Route::get('create','HandleCreateUser')->name('create');
            });
            Route::delete('{id}/profile',[ProfileController::class,'HandleDeleteProfile'])->name('profile.delete');
            Route::name('profile.')->prefix('profile')->controller(ProfileController::class)->group(function(){
                Route::get('','HandleIndexProfile')->name('index');
                Route::post('','HandleStoreProfile')->name('store');
                Route::put('','HandleUpdateProfile')->name('update');
                Route::get('{id}/edit','HandleEditProfile')->name('edit');
                Route::get('create','HandleCreateProfile')->name('create');
                Route::post('{id}/copy','HandleCopyProfile')->name('copy');
            });
            Route::delete('{id}/document',[DocumentController::class,'HandleDeleteDocument'])->name('document.delete');
            Route::name('document.')->prefix('document')->controller(DocumentController::class)->group(function(){
                Route::get('','HandleIndexDocument')->name('index');
                Route::post('','HandleStoreDocument')->name('store');
                Route::put('','HandleUpdateDocument')->name('update');
                Route::get('{id}/edit','HandleEditDocument')->name('edit');
                Route::get('create','HandleCreateDocument')->name('create');
            });
            Route::delete('{id}/area',[AreaController::class,'HandleDeleteArea'])->name('area.delete');
            Route::name('area.')->prefix('area')->controller(AreaController::class)->group(function(){
                Route::get('','HandleIndexArea')->name('index');
                Route::post('','HandleStoreArea')->name('store');
                Route::put('','HandleUpdateArea')->name('update');
                Route::get('{id}/edit','HandleEditArea')->name('edit');
                Route::get('create','HandleCreateArea')->name('create');
            });
            Route::delete('{id}/supplier',[SupplierController::class,'HandleDeleteSupplier'])->name('supplier.delete');
            Route::name('supplier.')->prefix('supplier')->controller(SupplierController::class)->group(function(){
                Route::get('','HandleIndexSupplier')->name('index');
                Route::post('','HandleStoreSupplier')->name('store');
                Route::put('','HandleUpdateSupplier')->name('update');
                Route::get('{id}/edit','HandleEditSupplier')->name('edit');
                Route::get('create','HandleCreateSupplier')->name('create');
            });
            Route::get('report/audit-log', [ReportController::class, 'HandleIndexAuditLog'])->name('report.audit-log');
        });

        // ── Administrador + Autorizador ──────────────────────────────────────
        Route::middleware('role:Administrador,Autorizador')->group(function(){
            Route::get('report/accountability', [ReportController::class, 'HandleIndexReport'])->name('report.accountability');
            Route::name('accountability.authorization.')->prefix('accountability/authorization')->controller(AuthAccountabilityController::class)->group(function(){
                Route::get('','HandleIndexAccountability')->name('index');
                Route::get('pending-export','HandleIndexPendingExport')->name('pending-export');
                Route::get('{id}/edit-data','HandleGetEditData')->name('edit-data');
                Route::get('{id}/edit','HandleEditAccountability')->name('edit');
                Route::put('','HandleUpdateAccountability')->name('update');
                Route::get('{id}/report','HandleGetReportAccountability')->name('report');
                Route::name('detail.')->prefix('{id}/detail')->group(function(){
                    Route::post('status','HandleUpdateStatus')->name('status');
                    Route::post('export','HandleExportSAP')->name('export');
                    Route::post('force-authorize','HandleForceAuthorize')->name('force-authorize');
                    Route::post('re-export','HandleReExportSAP')->name('re-export');
                    Route::get('','HandleDetailAccountability')->name('index');
                    Route::get('create','HandleCreateDocument')->name('create');
                    Route::post('store','HandleStoreDocument')->name('store');
                    Route::post('update','HandleUpdateDocument')->name('update');
                    Route::delete('{document_id}/delete','HandleDeleteDocument')->name('delete');
                    Route::get('{document_id}/edit','HandleEditDocument')->name('edit');
                });
            });
        });

        // ── Todos los usuarios autenticados (Solicitante) ────────────────────
        Route::name('accountability.')->prefix('accountability')->group(function(){
            Route::get('profiles',[AccountabilityProfileController::class,'HandleIndexProfiles'])->name('profiles');
            Route::name('manage.')->prefix('{profile_id}/manage')->controller(AccountabilityController::class)->group(function(){
                Route::get('','HandleIndexAccountability')->name('index');
                Route::post('','HandleStoreAccountability')->name('store');
                Route::put('','HandleUpdateAccountability')->name('update');
                Route::get('{id}/edit','HandleEditAccountability')->name('edit');
                Route::get('create','HandleCreateAccountability')->name('create');
                Route::get('{id}/report','HandleGetReportAccountability')->name('report');
                Route::delete('{id}/delete','HandleDeleteAccountability')->name('delete');
                Route::name('detail.')->prefix('{id}/detail')->group(function(){
                    Route::post('status','HandleUpdateStatus')->name('status');
                    Route::get('','HandleDetailAccountability')->name('index');
                    Route::get('create','HandleCreateDocument')->name('create');
                    Route::post('store','HandleStoreDocument')->name('store');
                    Route::post('update','HandleUpdateDocument')->name('update');
                    Route::delete('{document_id}/delete','HandleDeleteDocument')->name('delete');
                    Route::get('{document_id}/edit','HandleEditDocument')->name('edit');
                });
            });
        });

    });

});
