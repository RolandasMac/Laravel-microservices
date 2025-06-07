<?php
// App/Auth/CustomUserProvider.php
namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomUserProvider implements UserProvider
{
    protected $productServiceBaseurl;
    protected $authServiceUrl;
    protected $token = "23|Vsw5XWqAv6vLLURW1MmaA5SUCUfo2YfPiLYm9huZc289d9d1";

    // Initialize the property in the constructor
    public function __construct()
    {
        $this->productServiceBaseurl = env('PRODUCT_SERVICE_URL', 'http://localhost:8003/products/');
        $this->authServiceUrl        = env('AUTH_SERVICE_URL', 'http://localhost:8002/auth/');
    }
    public function retrieveById($identifier)
    {
        // logika vartotojo radimui pagal ID, pvz., API užklausa
        $userData = ['name' => 'John Doe', 'email' => 'X5Ej4@example.com', 'id' => 1235548465];

        if ($userData) {
            Log::info("Vartotojas sėkmingai patvirtintas iš AuthService: " . $userData['email']);
            return new GenericUser($userData);
        }
    }

    public function retrieveByToken($identifier, $token)
    {
        // jei naudojate "remember me" tokenus
        // $user = ['name' => 'John Doe', 'email' => 'X5Ej4@example.com'];
        // return $user;
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
                Log::info("AuthApiUserProvider: Vartotojas sėkmingai patvirtintas iš AuthService čia!!!.", $userData);
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

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // atnaujina "remember me" tokeną
    }

    public function retrieveByCredentials(array $credentials)
    {
        Log::info("Suveikė loginas" . $credentials['email']);
        // grąžina vartotoją pagal prisijungimo duomenis
        try {
            // Siunčiame POST užklausą į Auth serviso /api/validate-token endpoint'ą.
            // Prie užklausos pridedame gautą tokeną.
            $response = Http::post("{$this->authServiceUrl}login", $credentials)
                ->throw(); // Automatiškai išmeta išimtį, jei statusas nėra 2xx.

            // Gauname vartotojo duomenis iš Auth serviso atsakymo.
            $userData = $response->json('user');

            if ($userData) {
                Log::info("CustomUserProvider: Vartotojas sėkmingai prisijungė.", $userData);
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
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // tikrina slaptažodį ar kitus duomenis
    }
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): bool
    {
        return false; // Nes slaptažodį valdo Auth servisas
    }
}
