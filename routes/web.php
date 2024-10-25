<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\BookController;



Route::get('/', function () {
    return view('app');
});

Route::get("about", [App\Http\Controllers\AboutController::class, 'index']);
//Book routes

