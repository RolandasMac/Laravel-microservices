<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Pagalbinė funkcija, skirta gauti visus vertimus iš konkretaus failo ir kalbos
        $getTranslations = function (string $locale, string $file) {
            $path = lang_path($locale . '/' . $file . '.php');
            if (file_exists($path)) {
                // Naudojame `include` vertimų failui įkelti, nes jis grąžina masyvą
                return include $path;
            }
            return [];
        };

        return [
             ...parent::share($request),
            'auth'         => [
                // 'user' => $request->user(),
                // $request->user() arba Auth::user() grąžins prisijungusio vartotojo objektą,
                // jei AuthApiUserProvider sėkmingai jį autentifikavo.
                'user' => $request->user() ? $request->user()->toArray() : null,
                // Galite pridėti ir daugiau vartotojo susijusių duomenų, pvz., roles, permissions
                // 'roles' => $request->user() ? $request->user()->roles->pluck('name') : [],
            ],
            'locale'       => App()->getLocale(),
            'translations' => [
                // Perduodame vertimus visoms palaikomoms kalboms
                'en' => $getTranslations('en', 'messages'),
                'lt' => $getTranslations('lt', 'messages'),
                // Pridėkite daugiau kalbų/vertojimų failų, jei reikia
                // 'es' => $getTranslations('es', 'messages'),
                // 'en_validation' => $getTranslations('en', 'validation'), // Pavyzdys, jei naudojate kitus failus
            ],
            'ziggy'        => function () use ($request) {
                return array_merge((new Ziggy())->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ];
    }
}
