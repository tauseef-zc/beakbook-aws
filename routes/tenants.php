<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BarnController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here is where you can register tenant routes for your application.
|
*/

Route::get('/', function () {
    return view('tenants.login');
});




