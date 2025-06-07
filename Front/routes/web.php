<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestController;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/set-language', function (Request $request): JsonResponse {
    $locale = $request->input('locale');

                                           // Patikrinkite, ar pasirinkta kalba yra leidžiama
    if (in_array($locale, ['en', 'lt'])) { // Papildykite leistinomis kalbomis
        Session::put('locale', $locale);       // Išsaugojame sesijoje
        app()->setLocale($locale);             // Nustatome aplikacijos kalbą dabartinei užklausai
        return response()->json(['success' => true, 'message' => 'Kalba sėkmingai pakeista.']);
    }

    return response()->json(['success' => false, 'message' => 'Neteisinga kalba.'], 400);
})->name('language.set'); // Suteikiame maršrutui pavadinimą, jei naudojate Ziggy

// Test route

Route::get('/test', function () {
    echo __('messages.welcome');                    // Išves "Welcome to our application!" arba "Sveiki atvykę į mūsų programėlę!"
    echo __('messages.hello', ['name' => 'Jonas']); // Išves "Hello Jonas, how are you?"
});

Route::get('/test_page', [TestController::class, 'index'])->name('test_page');

require __DIR__ . '/auth.php';
