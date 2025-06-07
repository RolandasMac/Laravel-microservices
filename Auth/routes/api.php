<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'Sveiki iš auth test routo!', 'testUser' => ['name' => 'testUser']]);
});
// **********************************
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/validate-token', function (Request $request) {
    $token = $request->bearerToken();

    if (! $token) {
        return response()->json(['message' => 'Tokenas nepateiktas.'], 401);
    }

    // Bandome rasti tokeną duomenų bazėje
    $accessToken = PersonalAccessToken::findToken($token);

    if (! $accessToken || $accessToken->expires_at && $accessToken->expires_at->isPast()) {
        return response()->json(['message' => 'Neteisingas arba pasibaigęs tokenas.'], 401);
    }

    // Rasti vartotoją, susijusį su tokenu
    $user = $accessToken->tokenable;

    if (! $user) {
        return response()->json(['message' => 'Vartotojas nerastas.'], 404);
    }

    return response()->json(['user' => $user->toArray()], 200); // Grąžiname vartotojo duomenis
});
