<?php
// routes/api.php
use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return response()->json(['message' => 'Sveiki iš API Products!']);
});
Route::get('/products', function () {
    return response()->json(['message' => 'Sveiki iš API Products!', 'products' => ['banana', 'apple', 'carrot']]);
});
Route::get('/labas', function () {
    return response()->json(['message' => 'Sveiki iš API Labas!', 'products' => ['banana', 'apple', 'carrot']]);
});
