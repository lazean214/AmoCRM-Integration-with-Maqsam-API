<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/amocrm/callback', [\App\Http\Controllers\AmoCrmController::class, 'callback']);
Route::post('/amocrm/addlead', [\App\Http\Controllers\AdminController::class, 'addLeadToPipeline']);
Route::get('/amocrm/auth', [\App\Http\Controllers\AmoCrmController::class, 'auth']);
Route::get('/amocrm/lead', [\App\Http\Controllers\AmoCrmController::class, 'form']);
Route::get('/amocrm/account', [\App\Http\Controllers\AmoCrmController::class, 'getAccountInfo']);
Route::get('/amocrm/others', [\App\Http\Controllers\AmoCrmController::class, 'getOthers']);


//Maqsam Account Request
Route::get('/maqsam/calls', [\App\Http\Controllers\MaqsamController::class, 'maqsam']);

Route::get('/maqsam/call/{id}', [\App\Http\Controllers\MaqsamController::class, 'getRecording']);
Route::get('/maqsam/play/{id}', [\App\Http\Controllers\MaqsamController::class, 'playRecording']);

