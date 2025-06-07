<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentLocaleFromSession = Session::get('locale');
        $defaultLocale            = config('app.locale');
        // Laikinai įjunkite `dd()` norėdami pamatyti reikšmes kiekvienam užklausos vykdymui
        // dd("Locale iš sesijos: " . ($currentLocaleFromSession ?? 'Nėra'), "Numatytoji kalba: {$defaultLocale}", "Užklausos URL: {$request->fullUrl()}");
        // --- DERINIMO TAŠKAS 1 PABAIGA ---

        // Nustatome aplikacijos kalbą: pirmiausia iš sesijos, jei yra, tada numatytąją.
        App::setLocale($currentLocaleFromSession ?? $defaultLocale);

        return $next($request);
    }
}
