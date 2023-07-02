<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

use \App\Http\Controllers\HomeController;
use \App\Http\Controllers\FacebookController;
use \App\Http\Controllers\GeneralController;

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

Route::get('terms', function () {
    return view('terms');
});

Route::get('privacy-policy', function () {
    return view('privacypolicy');
});

Route::get('facebook/login', [FacebookController::class, 'login'])->name('facebook.login');
Route::get('facebook/callback', [FacebookController::class, 'callback'])->name('facebook.callback');

Route::middleware('auth')->group(function() {
    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::get('facebook/ig/remove', [FacebookController::class, 'igAccRemove'])->name('facebook.ig.remove');
    Route::get('facebook/ig/add', [FacebookController::class, 'igAccAdd'])->name('facebook.ig.add');

});

Route::middleware(['nova'])->prefix('nova-vendor/nova-belongsto-depend')->group(function() {
    Route::post('/', [\Forkage\NovaBelongsToDepend\Http\Controllers\FieldController::class, 'index']);
});

