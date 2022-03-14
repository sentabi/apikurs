<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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

Route::any('/v1/convert/', [UserController::class, 'konversiMataUang']);
Route::any('/v1/', [UserController::class, 'index']);

Route::post('/aws-sns-endpoint-fxwP9nCkgFdp3J43LpLF', 'ApiController@awsSnsEndpoint');
