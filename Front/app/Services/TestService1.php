<?php
namespace App\Services;

use App\Contracts\TestHosting;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable; // BŪTINA importuoti šią sąsają
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestService1 implements TestHosting
{
    // Declare the property, but do not initialize it directly here
    protected $productServiceBaseurl;
    protected $authServiceUrl;
    protected $token = "23|Vsw5XWqAv6vLLURW1MmaA5SUCUfo2YfPiLYm9huZc289d9d1";

    // Initialize the property in the constructor
    public function __construct()
    {
        $this->productServiceBaseurl = env('PRODUCT_SERVICE_URL', 'http://localhost:8003/products/');
        $this->authServiceUrl        = env('AUTH_SERVICE_URL', 'http://localhost:8002/auth/');
    }
    public function test()
    {

        Log::info("AuthApiUserProvider: Bandome autentifikuoti vartotoją su tokenu.");

        try {
            // Siunčiame POST užklausą į Auth serviso /api/validate-token endpoint'ą.
            // Prie užklausos pridedame gautą tokeną.
            $response = Http::get("{$this->productServiceBaseurl}labas")
                ->throw(); // Automatiškai išmeta išimtį, jei statusas nėra 2xx.

            // Gauname vartotojo duomenis iš Auth serviso atsakymo.
            $userData = $response->json();

            if ($userData) {
                Log::info("Duomenys sėkmingai gauti iš products servico");
                // Sukuriame bendrą Laravel vartotojo objektą (GenericUser)
                // iš gautų duomenų. Tai leidžia Auth::user() veikti.
                return $userData;
            }
        } catch (RequestException $e) {
            // Apdorojame HTTP kliento klaidas (pvz., 401, 403, 500 iš AuthService).
            Log::warning("AuthApiUserProvider: Tokeno validavimo klaida iš AuthService: " . $e->getMessage() . " Status: " . ($e->response ? $e->response->status() : 'Nėra atsakymo'));
        } catch (\Throwable $e) {
            // Apdorojame bet kokias kitas netikėtas klaidas.
            Log::error("AuthApiUserProvider: Netikėta klaida validuojant tokeną per AuthService API: " . $e->getMessage());
        }
    }

    public function retrieveByApiToken($token): ?Authenticatable
    {
        // Įrašome informaciją į Laravel žurnalus derinimui.
        Log::info("AuthApiUserProvider: Bandome autentifikuoti vartotoją su tokenu.");

        try {
            // Siunčiame POST užklausą į Auth serviso /api/validate-token endpoint'ą.
            // Prie užklausos pridedame gautą tokeną.
            $response = Http::withToken($token)
                ->post("{$this->authServiceUrl}validate-token")
                ->throw(); // Automatiškai išmeta išimtį, jei statusas nėra 2xx.

            // Gauname vartotojo duomenis iš Auth serviso atsakymo.
            $userData = $response->json('user');

            if ($userData) {
                Log::info("AuthApiUserProvider: Vartotojas sėkmingai patvirtintas iš AuthService.", $userData);
                // Sukuriame bendrą Laravel vartotojo objektą (GenericUser)
                // iš gautų duomenų. Tai leidžia Auth::user() veikti.
                return new GenericUser($userData);
                // return $userData['name'];
            }
        } catch (RequestException $e) {
            // Apdorojame HTTP kliento klaidas (pvz., 401, 403, 500 iš AuthService).
            Log::warning("AuthApiUserProvider: Tokeno validavimo klaida iš AuthService: " . $e->getMessage() . " Status: " . ($e->response ? $e->response->status() : 'Nėra atsakymo'));
        } catch (\Throwable $e) {
            // Apdorojame bet kokias kitas netikėtas klaidas.
            Log::error("AuthApiUserProvider: Netikėta klaida validuojant tokeną per AuthService API: " . $e->getMessage());
        }

        return null; // Tokenas nepatvirtintas arba įvyko klaida.
    }

    public function showString()
    {
        // return $this->test();
        return $this->retrieveByApiToken($this->token);
    }
}
