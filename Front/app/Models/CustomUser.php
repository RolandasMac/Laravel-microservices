<?php
namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model; // Galime extendinti Model, net jei nenaudojame DB
use Illuminate\Notifications\Notifiable;
// Jei planuojate naudoti notifikacijas

/**
 * Class CustomUser
 *
 * Šis modelis naudojamas Laravel autentifikavimo sistemos, kai vartotojo duomenys gaunami
 * iš išorinio Auth serviso. Jis leidžia Auth::user() veikti ir turi toArray() metodą,
 * kad duomenys būtų perduodami Inertia.js.
 *
 * @property int $id Vartotojo ID iš Auth serviso
 * @property string $name Vartotojo vardas
 * @property string $email Vartotojo el. paštas
 * // ... galite pridėti kitus laukus, kuriuos grąžina Auth servisas
 */
class CustomUser extends Model implements Authenticatable
{
    use Notifiable; // Jei reikia notifikacijų

                                  // Nurodome, kad šis modelis nenaudoja duomenų bazės lentelės,
                                  // nes vartotojo duomenys ateina iš išorinio serviso.
                                  // Tai tiesiog apgauna Eloquent'ą, kad nekiltų klaidų.
    protected $table      = null; // Arba galite nurodyti neegzistuojančią lentelę
    protected $primaryKey = 'id';
    public $incrementing  = false; // ID ateina iš išorės, nėra autoincrement
    public $timestamps    = false; // Nėra created_at, updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', // Svarbu: ID turi būti čia, jei jis patenka iš išorės
        'name',
        'email',
        // ... kiti laukai, pvz., 'role', 'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password', // Jei Auth servisas grąžina hash'ą
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed', // Jei password laukas bus
    ];

    // Implementuojame Authenticatable sąsajos metodus
    // Šie metodai yra reikalingi, kad Laravel atpažintų šį objektą kaip autentifikuotą vartotoją.

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->attributes[$this->getAuthIdentifierName()];
    }

    public function getAuthPassword(): string
    {
        // Slaptažodis tvarkomas Auth servise, čia jis nereikalingas,
        // bet metodas privalo egzistuoti.
        return '';
    }

    // NAUJAS METODAS: getAuthPasswordName()
    // Šis metodas reikalingas naujesnėse Laravel versijose (nuo Laravel 12).
    // Jis nurodo, koks atributo pavadinimas laiko vartotojo slaptažodį.
    // Kadangi slaptažodį tvarko Auth servisas, galime tiesiog grąžinti 'password'.
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return $this->attributes['remember_token'] ?? null;
    }

    public function setRememberToken($value): void
    {
        $this->attributes['remember_token'] = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * Konvertuoja modelio atributus į masyvą.
     * Šis metodas yra Eloquent'o dalis ir automatiškai sukuriamas,
     * kai extendinamas Illuminate\Database\Eloquent\Model.
     * Dabar HandleInertiaRequests galės kviesti toArray().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray(); // Tiesiog iškviečia tėvinės klasės metodą
    }

    // Papildomi metodai, jei reikia (pvz., vartotojo rolės patikrinimui)
    // public function hasRole(string $role): bool
    // {
    //     // Tarkime, kad Auth servisas grąžina 'role' lauką vartotojo duomenyse
    //     return $this->role === $role;
    // }
}
