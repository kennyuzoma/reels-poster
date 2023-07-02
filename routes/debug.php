<?php

use App\Http\Controllers\Debug\TestController;

Route::get('test', [TestController::class, 'test']);
Route::get('ig-post-tester/{post}', [TestController::class, 'igPostTester']);
