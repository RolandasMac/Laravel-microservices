<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\CustomUser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;          // Importuojame HTTP fasadą
use Illuminate\Validation\ValidationException; // Importuojame RequestException
use Inertia\Inertia;
// Importuojame ValidationException
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    private $backApiUrl;
    private $authServiceUrl;
    public function __construct()
    {
        $this->backApiUrl     = env('BACK_API_URL');
        $this->authServiceUrl = env('AUTH_SERVICE_URL');
    }
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // $request->authenticate();

        // $request->session()->regenerate();

        // return redirect()->intended(route('dashboard', absolute: false));
        // Senas kodas********************************************************
        // 1. Validacija jau atlikta per LoginRequest, jei naudojate
        // arba atlikite ją čia: $request->validate([...]);

        // 2. Siųsti prisijungimo duomenis į AuthService
        // Šis žingsnis yra kritiškas. JŪSŲ LOGIN KONTROLERIS TURI BENDRUATI SU AUTH SERVISU.

        try {
            $response = Http::post("{$this->backApiUrl}auth/login", [
                'email'    => $request->email,
                'password' => $request->password,
                'remember' => $request->boolean('remember'), // Jei Auth servisas tvarko "remember me"
            ])->throw();                                 // Išmeta išimtį, jei statusas ne 2xx

            $data = $response->json();
            Log::info($data);
            if (! isset($data['token']) || ! isset($data['user'])) {
                throw ValidationException::withMessages([
                    'email' => ['Serveris negrąžino tokeno ar vartotojo duomenų.'],
                ]);
            }

            $authToken = $data['token'];
            $userData  = $data['user'];

            // 3. Autentifikuoti vartotoją VIETINĖJE Laravel aplikacijoje naudojant CUSTOM PROVIDER
            // Mūsų CustomUserProvider turi metodą retrieveByApiToken, bet Auth::login()
            // dirba su Authenticatable objektu.
            // Kai retrieveByCredentials grąžina GenericUser, Auth::attempt() TIK PATIKRINA.
            // Kad vartotojas būtų išsaugoti sesijoje, PRIVALOME naudoti Auth::login().

            // Sukuriame GenericUser iš gautų duomenų.
            // GenericUser privalo turėti 'id' lauką.
            if (! isset($userData['id'])) {
                throw ValidationException::withMessages([
                    'email' => ["AuthService atsakyme trūksta vartotojo ID."],
                ]);
            }
            $user = new CustomUser($userData);

            // KRITIŠKAI SVARBU: Išsaugome API tokeną sesijoje, susietą su vartotojo ID.
            // Tai leis CustomUserProvider::retrieveById() metodui gauti tokeną vėlesnėse užklausose.
            $request->session()->put('auth_token_for_user_id_' . $user->id, $authToken);

            // Atliekame prisijungimą sesijoje.
            // Tai nustato vartotojo ID sesijoje ir priverčia SessionGuard manyti, kad vartotojas prisijungęs.
            Auth::login($user, $request->boolean('remember'));

                                                             // 4. Nukreipti vartotoją
                                                             // Po sėkmingo prisijungimo, būtina atlikti nukreipimą,
                                                             // kad Laravel galėtų tinkamai išsaugoti sesiją ir vėl ją perskaityti kitoje užklausoje.
                                                             // Tai yra Inertia:Location užklausa, kuri inicijuos naują Laravel backend užklausą.
            return redirect()->intended(route('dashboard')); // Nukreipiame į dashboard maršrutą

        } catch (RequestException $e) {
            // Šis blokas apdoroja klaidas iš AuthService
            $message = 'Nepavyko prisijungti. Bandykite dar kartą.';
            $errors  = [];

            if ($e->response && $e->response->status() === 422) {
                // Validacijos klaidos iš AuthService
                $errors  = $e->response->json('errors');
                $message = $e->response->json('message', 'Validacijos klaida.');
            } else if ($e->response && $e->response->status() === 401) {
                // Neteisingi prisijungimo duomenys iš AuthService
                $message         = 'Neteisingas el. paštas arba slaptažodis.';
                $errors['email'] = [$message];
            } else if ($e->response) {
                // Kitos klaidos iš AuthService (pvz., 500)
                $message = $e->response->json('message', 'Nepavyko prisijungti dėl serverio klaidos.');
            } else {
                // Tinklo klaida, AuthService nepasiekiamas
                $message = 'Prisijungimo servisas nepasiekiamas.';
            }

            // Mesti validacijos išimtį, kad Inertia galėtų tvarkyti klaidas.
            throw ValidationException::withMessages($errors ?: ['email' => [$message]]);

        } catch (\Throwable $e) {
            Log::error("Prisijungimo klaida valdiklyje: " . $e->getMessage());
            throw ValidationException::withMessages([
                'email' => ['Įvyko netikėta klaida prisijungiant.'],
            ]);
        }

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
