<?php
// routes/api.php
// routes/api.php (API Gateway projekte)

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route; // Importuojame JsonResponse

// Vidinių servisų baziniai URL adresai (geriausia laikyti .env faile)
$authServiceBaseUrl    = env('AUTH_SERVICE_URL', 'http://localhost:8002');
$productServiceBaseUrl = env('PRODUCT_SERVICE_URL', 'http://localhost:8003');
// ... kiti servisų URL

// ----------------------------------------------------------------------
// Konkretesni maršrutai (turėtų būti apibrėžti PIRMIAUSIA)
// Šie maršrutai gali būti tiesiogiai susieti su GatewayController metodais
// arba, kaip jūsų pavyzdyje, tiesiogiai apdorojami čia.
// ----------------------------------------------------------------------

// Pavyzdys: Prisijungimo maršrutas (įėjimas į sistemą)
Route::post('/auth/login', function (Request $request) use ($authServiceBaseUrl): JsonResponse {
    try
    {
        $response = Http::withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])->post("{$authServiceBaseUrl}/auth/login", $request->all())->throw();
        return response()->json(['message' => $response->json()['message'], 'data' => $response->json()['user'], 'token' => $response->json()['token']], 200);
    } catch (RequestException $e) {
        if ($e->response) {
            return response()->json($e->response->json(), $e->response->status());
        }
        return response()->json(['message' => 'Vidinis servisas nepasiekiamas arba įvyko tinklo klaida.'], 503); // 503 Service Unavailable
    }
});
Route::post('/auth/register', function (Request $request) use ($authServiceBaseUrl): JsonResponse {
    try {

        // Būtinai reikia prodėti acceptJson() arba withHeaders()

        // $response = Http::acceptJson()->post("{$authServiceBaseUrl}/auth/register", $request->all())->throw();
        $response = Http::withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])->post("{$authServiceBaseUrl}/auth/register", $request->all())->throw();

        return response()->json(['message' => $response->json()['message'], 'data' => $response->json()['user']], 200);
    } catch (RequestException $e) {
        if ($e->response) {
            return response()->json($e->response->json(), $e->response->status());
        }
        return response()->json(['message' => 'Vidinis servisas nepasiekiamas arba įvyko tinklo klaida.'], 503); // 503 Service Unavailable
    }

});
Route::post('/auth/logout', function (Request $request) use ($authServiceBaseUrl) {
    try {
        // Nukreipiame atsijungimo užklausą į AuthService
        $response = Http::withToken($request->bearerToken())->post("{$authServiceBaseUrl}/auth/logout")->throw();
        return response()->json($response->json(), $response->status());
    } catch (RequestException $e) {
        if ($e->response) {
            return response()->json($e->response->json(), $e->response->status());
        }
        return response()->json(['message' => 'Atsijungimo servisas nepasiekiamas.', 'error' => $e->getMessage()], 503);
    } catch (\Throwable $e) {
        return response()->json(['message' => 'Atsijungimo serviso klaida. ' . $e->getMessage()], 500);
    }
})->middleware('auth:sanctum'); // Apsaugota Gateway lygiu

// Pavyzdys: Vartotojo duomenų gavimo maršrutas
Route::get('/auth/user', function (Request $request) use ($authServiceBaseUrl): JsonResponse {
    // return response()->json(['message' => 'Vartotojas gautas'], 200);
    $response = Http::withToken($request->bearerToken())->get("{$authServiceBaseUrl}/auth/user");
    return response()->json($response->json(), $response->status());
})->middleware('auth:sanctum');
// ----------------------------------------------------------------------
// Dinaminis "catch-all" maršrutas (turėtų būti apibrėžtas PASKUTINIS)
// ----------------------------------------------------------------------
Route::any('/{path?}', function (Request $request, $path = null) use ($authServiceBaseUrl, $productServiceBaseUrl): JsonResponse {
                                                    // Išskaidome kelio segmentus, kad nustatytume servisą
    $segments          = explode('/', $path ?? ''); // Naudojame $path ?? '' kad išvengtume null, jei kelias tuščias
    $serviceIdentifier = $segments[0] ?? null;      // Pirmasis segmentas (pvz., 'products', 'auth')

    $targetServiceUrl = null;
    $internalApiPath  = null; // Kelias vidiniame servise

    if ($serviceIdentifier === 'products') {

        $targetServiceUrl = $productServiceBaseUrl;
        // Likutis kelio po 'products/' (pvz., '123' arba 'category/electronics')
        // implode('/', array_slice($segments, 1)) sujungia likusius segmentus
        $internalApiPath = implode('/', array_slice($segments, 1));
    } elseif ($serviceIdentifier === 'auth') {
        $targetServiceUrl = $authServiceBaseUrl;
        $internalApiPath  = implode('/', array_slice($segments, 1));
    } else {
        // Jei serviso identifikatorius neatpažintas
        return response()->json(['message' => 'Servisas nerastas arba neatpažintas.'], 404);
    }

    // Patikriname, ar tikslinis servisas nustatytas
    if (! $targetServiceUrl) {
        return response()->json(['message' => 'Nukreipimo klaida: tikslinis servisas nenustatytas.'], 500);
    }

    // Gauname originalų HTTP metodą (GET, POST, PUT, DELETE ir t.t.)
    $method = strtolower($request->method());

    // Gauname autentifikavimo tokeną iš kliento užklausos
    $token = $request->bearerToken();

    // Sukuriame HTTP kliento instanciją su antraštėmis
    $http = Http::withHeaders([
        'Authorization' => 'Bearer ' . "token", // Naudojame tikrą tokeną iš užklausos
                                                // Galite pridėti ir kitas antraštes, pvz., 'X-Forwarded-For'
    ]);

    try {
        // Siunčiame užklausą į tikslinį servisą
        // Svarbu: pridedame '/api/' prefiksą, nes servisai greičiausiai turi savo API maršrutus
        $response = $http->$method("{$targetServiceUrl}/{$serviceIdentifier}/{$internalApiPath}", $request->all())->throw();

        // Grąžiname atsakymą iš serviso klientui
        return response()->json($response->json(), $response->status());

    } catch (RequestException $e) {
        // Tvarkome HTTP kliento klaidas (pvz., servisas nepasiekiamas, 4xx/5xx atsakymas)
        if ($e->response) {
            // Jei yra atsakymas iš serviso, grąžiname jį tiesiogiai
            return response()->json($e->response->json(), $e->response->status());
        }
                                                                                                                  // Jei servisas visiškai nepasiekiamas
        return response()->json(['message' => 'Vidinis servisas nepasiekiamas arba įvyko tinklo klaida.'], 503); // 503 Service Unavailable
    } catch (\Throwable $e) {
        // Tvarkome kitas bendras klaidas
        return response()->json(['message' => 'Įvyko netikėta klaida Gateway.', 'error' => $e->getMessage()], 500);
    }
})->where('path', '.*')->middleware('auth:sanctum'); // Svarbu: 'where' apibrėžia, kad {path} gali būti bet kokia eilutė, įskaitant '/'
