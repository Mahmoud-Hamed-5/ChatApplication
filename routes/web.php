<?php

use App\Http\Controllers\SampleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::controller(SampleController::class)->group(function(){

    Route::get('login', 'index')->name('login');

    Route::get('register', 'register')->name('register');

    Route::get('logout', 'logout')->name('logout');
});
