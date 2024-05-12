<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\AuthController;
use App\Http\Controllers\Panel\PanelController;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\ManagementController;
use App\Http\Controllers\Administration\ProfileController;
use App\Http\Controllers\Administration\DocumentController;
use App\Http\Controllers\Administration\SupplierController;
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
Route::get('test', [AccountabilityController::class, 'HandleExportSAP'])->name('test');


Route::middleware('guest')->group(function() {

    Route::get('login', [AuthController::class, 'HandleIndexAuth'])->name('login');

    Route::post('login', [AuthController::class, 'HandleLoginAuth'])->name('login.store');
});

Route::middleware('auth')->group(function() {

    Route::get('');
    Route::get('/',[PanelController::class,'HandleIndexDashboard'])->middleware('verified')->name('home');
    Route::post('logout', [AuthController::class, 'HandleLogoutAuth'])->name('auth.logout');

    Route::name('panel.')->prefix('panel')->group(function(){
        Route::name('management.')->prefix('management')->controller(ManagementController::class)->group(function(){
            Route::get('','HandleIndexManagement')->name('index');
            Route::post('','HandleUpdateManagement')->name('update');
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
        });
        Route::delete('{id}/document',[DocumentController::class,'HandleDeleteDocument'])->name('document.delete');
        Route::name('document.')->prefix('document')->controller(DocumentController::class)->group(function(){
            Route::get('','HandleIndexDocument')->name('index');
            Route::post('','HandleStoreDocument')->name('store');
            Route::put('','HandleUpdateDocument')->name('update');
            Route::get('{id}/edit','HandleEditDocument')->name('edit');
            Route::get('create','HandleCreateDocument')->name('create');
        });
        Route::delete('{id}/supplier',[SupplierController::class,'HandleDeleteSupplier'])->name('supplier.delete');
        Route::name('supplier.')->prefix('supplier')->controller(SupplierController::class)->group(function(){
            Route::get('','HandleIndexSupplier')->name('index');
            Route::post('','HandleStoreSupplier')->name('store');
            Route::put('','HandleUpdateSupplier')->name('update');
            Route::get('{id}/edit','HandleEditSupplier')->name('edit');
            Route::get('create','HandleCreateSupplier')->name('create');
        });

        Route::name('accountability.')->prefix('accountability')->group(function(){
            Route::name('authorization.')->prefix('authorization')->controller(AuthAccountabilityController::class)->group(function(){
                Route::get('','HandleIndexAccountability')->name('index');
                Route::get('{id}/edit','HandleEditAccountability')->name('edit');
                Route::put('','HandleUpdateAccountability')->name('update');
                Route::name('detail.')->prefix('{id}/detail')->group(function(){
                    Route::post('status','HandleUpdateStatus')->name('status');
                    Route::post('export','HandleExportSAP')->name('export');
                    Route::get('','HandleDetailAccountability')->name('index');
                    Route::get('create','HandleCreateDocument')->name('create');
                    Route::post('store','HandleStoreDocument')->name('store');
                    Route::post('update','HandleUpdateDocument')->name('update');
                    Route::delete('{document_id}/delete','HandleDeleteDocument')->name('delete');
                    Route::get('{document_id}/edit','HandleEditDocument')->name('edit');
                });
            });
            Route::get('profiles',[AccountabilityProfileController::class,'HandleIndexProfiles'])->name('profiles');
            Route::name('manage.')->prefix('{profile_id}/manage')->controller(AccountabilityController::class)->group(function(){
                Route::get('','HandleIndexAccountability')->name('index');
                Route::post('','HandleStoreAccountability')->name('store');
                Route::put('','HandleUpdateAccountability')->name('update');
                Route::get('{id}/edit','HandleEditAccountability')->name('edit');
                Route::get('create','HandleCreateAccountability')->name('create');
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
