<?php

use App\Http\Controllers\Frontend\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'index'])->name('home');

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
