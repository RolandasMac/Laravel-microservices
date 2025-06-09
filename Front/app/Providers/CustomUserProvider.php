<?php
// App/Auth/CustomUserProvider.php
namespace App\Providers;

use App\Models\CustomUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomUserProvider implements UserProvider
{
    protected $productServiceBaseurl;
    protected $authServiceUrl;
    // protected $token = "23|Vsw5XWqAv6vLLURW1MmaA5SUCUfo2YfPiLYm9huZc289d9d1";
    private $backApiUrl;

    // Initialize the property in the constructor
    public function __construct()
    {
        $this->productServiceBaseurl = env('PRODUCT_SERVICE_URL', 'http://localhost:8003/products/');
        $this->authServiceUrl        = env('AUTH_SERVICE_URL', 'http://localhost:8002/auth/');
        $this->backApiUrl            = env('BACK_API_URL', 'http://localhost:8001/getaway/');
    }
    public function retrieveById($identifier): ?Authenticatable
    {
        Log::info("CustomUserProvider: Bandome atkurti vartotoją pagal ID: " . $identifier);

        // Bandoma gauti tokeną iš sesijos.
        // Sesijos raktas turėtų būti nustatytas retrieveByApiToken, kai vartotojas prisijungia.
        $token = \Illuminate\Support\Facades\Session::get('auth_token_for_user_id_' . $identifier);

        if (! $token) {
            Log::warning("CustomUserProvider: Tokeno nerasta sesijoje atkuriant vartotoją pagal ID: " . $identifier);
            return null;
        }
        Log::info("CustomUserProvider: Galiojantis tokenas: " . $token . " atkuriam vartotoją pagal ID: " . $identifier);
        try {
            // Atliekame API iškvietimą į Auth serviso endpoint'ą, kuris grąžins vartotoją pagal ID.
            // Jums reikės sukurti tokį endpoint'ą AuthService projekte, pvz., /auth/user-by-id/{id}
            $response = Http::withToken($token)
                ->get("{$this->backApiUrl}auth/user-by-id/{$identifier}")
                ->throw();

            $userData = $response->json('user');

            if ($userData) {
                Log::info("CustomUserProvider: Vartotojas atkurtas iš AuthService pagal ID: " . $userData['email']);
                return new CustomUser($userData);
            }
        } catch (RequestException $e) {
            Log::warning("CustomUserProvider: Klaida atkuriant vartotoją pagal ID (RequestException): " . $e->getMessage() . " Status: " . ($e->response ? $e->response->status() : 'Nėra atsakymo'));
        } catch (\Throwable $e) {
            Log::error("CustomUserProvider: Netikėta klaida atkuriant vartotoją pagal ID: " . $e->getMessage());
        }

        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        // jei naudojate "remember me" tokenus
        // $user = ['name' => 'John Doe', 'email' => 'X5Ej4@example.com'];
        // return $user;
        // Įrašome informaciją į Laravel žurnalus derinimui.
        Log::info("CustomUserProvider: Bandome autentifikuoti vartotoją su tokenu.");

        try {
            // Siunčiame POST užklausą į Auth serviso /api/validate-token endpoint'ą.
            // Prie užklausos pridedame gautą tokeną.
            $response = Http::withToken($token)
                ->post("{$this->backApiUrl}auth/validate-token")
                ->throw(); // Automatiškai išmeta išimtį, jei statusas nėra 2xx.

            // Gauname vartotojo duomenis iš Auth serviso atsakymo.
            $userData = $response->json('user');

            if ($userData) {
                Log::info("CustomUserProvider: Vartotojas sėkmingai patvirtintas iš AuthService čia!!!.", $userData);
                // Sukuriame bendrą Laravel vartotojo objektą (GenericUser)
                // iš gautų duomenų. Tai leidžia Auth::user() veikti.
                // Svarbu: Auth::user()->id bus naudojamas SessionGuard, kad išsaugotų ID sesijoje.
                // Todėl $userData PRIVALO turėti 'id' lauką.
                if (! isset($userData['id'])) {
                    Log::error("CustomUserProvider: Vartotojo duomenyse trūksta 'id' lauko iš AuthService atsakymo.");
                    return null;
                }

                // Sėkmingai patvirtinus tokeną ir gavus vartotojo duomenis,
                // išsaugome tokeną sesijoje pagal vartotojo ID.
                // Tai yra KRITIŠKAI SVARBU, kad retrieveById vėliau galėtų jį panaudoti!
                \Illuminate\Support\Facades\Session::put('auth_token_for_user_id_' . $userData['id'], $token);

                return new CustomUser($userData);
            }
        } catch (RequestException $e) {
            // Apdorojame HTTP kliento klaidas (pvz., 401, 403, 500 iš AuthService).
            Log::warning("CustomUserProvider: Tokeno validavimo klaida iš AuthService: " . $e->getMessage() . " Status: " . ($e->response ? $e->response->status() : 'Nėra atsakymo'));
        } catch (\Throwable $e) {
            // Apdorojame bet kokias kitas netikėtas klaidas.
            Log::error("CustomUserProvider: Netikėta klaida validuojant tokeną per AuthService API: " . $e->getMessage());
        }

        return null; // Tokenas nepatvirtintas arba įvyko klaida.
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // atnaujina "remember me" tokeną
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        Log::info("Suveikė loginas" . $credentials['email']);
        // grąžina vartotoją pagal prisijungimo duomenis
        try {
            // Siunčiame POST užklausą į Auth serviso /api/validate-token endpoint'ą.
            // Prie užklausos pridedame gautą tokeną.
            $response = Http::post("{$this->backApiUrl}auth/login", $credentials)
                ->throw(); // Automatiškai išmeta išimtį, jei statusas nėra 2xx.

            // Gauname vartotojo duomenis iš Auth serviso atsakymo.
            $userData = $response->json('user');
            // Patikrinam ar tokenas gautas
            $token = $response->json('token');
            Log::info("CustomUserProvider: Tokenas gautas: " . $token);

            // if ($userData) {
            //     Log::info("CustomUserProvider: Vartotojas sėkmingai prisijungė.", $userData);
            //     // Sukuriame bendrą Laravel vartotojo objektą (GenericUser)
            //     // iš gautų duomenų. Tai leidžia Auth::user() veikti.
            //     return new CustomUser($userData);
            //     // return $userData['name'];
            // }
            if ($userData) {
                if (! isset($userData['id'])) {
                    Log::error("AuthApiUserProvider: Vartotojo duomenyse trūksta 'id' lauko iš AuthService atsakymo.");
                    return null;
                }

                $user = new CustomUser($userData);

                                      // --- KAIP TECHNINIAI IŠSAUGOTI TOKENĄ SESIJOJE ČIA (bet NEDARYKITE TO!) ---
                                      // Norint pasiekti sesiją iš provider'io, reikia gauti Request instanciją.
                                      // Tai nėra geriausia praktika UserProvider'yje.
                $request = request(); // Laravel pagalbinė funkcija Request instancijai gauti
                if ($request && $request->session()) {
                    $request->session()->put('auth_token_for_user_id_' . $user->id, $token);
                    Log::info("CustomUserProvider: Tokenas sėkmingai išsaugotas sesijoje retrieveByCredentials.");
                } else {
                    Log::warning("CustomUserProvider: Nepavyko pasiekti sesijos retrieveByCredentials.");
                }

                // --- KAIP TECHNINIAI PRISIJUNGTI VARTOTOJĄ ČIA (bet NEDARYKITE TO!) ---
                // Auth::login() prisijungia vartotoją sesijoje.
                // Tai yra atsakomybė, kurią turi atlikti kontroleris, o ne provider'is.
                // Kviečiant Auth::login() provider'yje, galite pažeisti Laravel autentifikavimo srautą.
                // Auth::login($user, $credentials['remember'] ?? false);
                // Log::info("CustomUserProvider: Vartotojas prisijungtas retrieveByCredentials (neteisingai).");

                return $user; // Ši eilutė įvyksta, nutraukdama metodą
            }
        } catch (RequestException $e) {
            // Apdorojame HTTP kliento klaidas (pvz., 401, 403, 500 iš AuthService).
            Log::warning("AuthApiUserProvider: Tokeno validavimo klaida iš AuthService: " . $e->getMessage() . " Status: " . ($e->response ? $e->response->status() : 'Nėra atsakymo'));
        } catch (\Throwable $e) {
            // Apdorojame bet kokias kitas netikėtas klaidas.
            Log::error("AuthApiUserProvider: Netikėta klaida validuojant tokeną per AuthService API: " . $e->getMessage());
        }
        // return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        Log::info("CustomUserProvider: Validacija prisijungimo duomenu: " . $credentials['email'] . " " . $credentials['password'] . " " . $user->email);
        return true;
        // tikrina slaptažodį ar kitus duomenis
    }
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): bool
    {
        return false; // Nes slaptažodį valdo Auth servisas
    }
}
