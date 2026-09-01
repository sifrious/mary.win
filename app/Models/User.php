<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Sifrious\AccountsClient\Contracts\AccountProjection;

/**
 * The local projection of a Zahir account.
 *
 * Not an authority on identity. It exists so sessions, preferences, and this
 * site's own data have something local to hang from, and it is keyed by the
 * opaque Zahir account ID under a unique index — two rows for one account would
 * split a person's history at their second sign-in.
 *
 * `password` survives as a nullable column the framework's contracts still
 * reference, but nothing writes it. Verification and recovery belong to the
 * external provider.
 */
class User extends Authenticatable implements AccountProjection
{
    public function zahirAccountId(): string
    {
        return (string) $this->getAttribute('zahir_account_id');
    }

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'zahir_account_id',
        'name',
        'email',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
