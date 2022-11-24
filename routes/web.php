<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
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


Route::controller(AuthController::class)->group(function(){

    Route::get('login', 'index')->name('login');

    Route::get('register', 'register')->name('register');

    Route::get('logout', 'logout')->name('logout');

    Route::post('validate_registration', 'validate_registration')->name('validate_registration');

    Route::post('validate_login', 'validate_login')->name('validate_login');

    Route::get('dashboard', 'dashboard')->name('dashboard');
});

Route::controller(ProfileController::class)->group(function(){
    Route::get('profile', 'profile')->name('profile');
    Route::post('validate_profile', 'validate_profile')->name('validate_profile');
});



