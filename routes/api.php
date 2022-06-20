<?php

use App\Http\Controllers\BarnController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\FavouriteBarnController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/fetch/users', [UserController::class, 'fetchUsers']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

      //Spatie permissions
      Route::post('/create/role', [PermissionController::class, 'createRole']);
      Route::post('/create/permission', [PermissionController::class, 'createPermission']);
      Route::post('/assign/role', [PermissionController::class, 'assignRoleToUser']);
      Route::post('/assign/permission', [PermissionController::class, 'assignPermissionToRole']);
      Route::post('/user/permissions', [PermissionController::class, 'getLoggedUserPermissions']);
      
      //Barn Details
      Route::post('/create/barn', [BarnController::class, 'createBarn']);
      
      Route::post('/update/barn', [BarnController::class, 'updateBarn']);
      
      //Device Details
      Route::post('/create/device', [DeviceController::class, 'createDevice']);
      Route::get('/getDevices', [DeviceController::class, 'getDeviceDetails']);

      //Favorite Barns
      Route::post('/create/favorite/barn', [FavouriteBarnController::class, 'createFavoriteBarns']);
      Route::get('/getFavoriteBarns', [FavouriteBarnController::class, 'getFavoriteBarns']);
      Route::post('remove/favorite/barn', [FavouriteBarnController::class, 'deleteFavoriteBarns']);


      

    Route::group(['prefix' => 'dashboard/download'], function () {
        Route::post('/', [DashboardController::class, 'download']);
        Route::get('/pdf', [DashboardController::class, 'downloadPDF']);
        Route::get('/csv', [DashboardController::class, 'downloadCSV']);
    });
    
    Route::group(['middleware' => 'auth:sanctum'], function () {

  Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'dashboardApi']);
        Route::get('/graphs', [DashboardController::class, 'dashboardApiGraphs']);
        Route::get('/favourites', [DashboardController::class, 'dashboardApiOne']);
        Route::get('export/csv', [DashboardController::class, 'exportCSV']);
    });

    Route::get('/getBarns', [BarnController::class, 'getBarn']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::get('test', [DashboardController::class, 'test']);