<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'fullname',
        'phone_number',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'pic_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'pic_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'reporter_id');
    }

    public function isAdmin(): bool
    {
        return $this->role?->role_name === 'admin';
    }

    public function isPic(): bool
    {
        return $this->role?->role_name === 'pic';
    }

    public function isGuru(): bool
    {
        return $this->role?->role_name === 'guru';
    }

    public function isGuest(): bool
    {
        return $this->role?->role_name === 'guest';
    }

    /**
     * Sanitasi nomor telepon ke format internasional standar WhatsApp (628xxx)
     */
    public function getWhatsappNumberAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $this->phone_number);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone ?: '6281234567890';
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function createToken(string $name = 'mobile_app'): string
    {
        $plainTextToken = \Illuminate\Support\Str::random(60);

        $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'last_used_at' => now(),
        ]);

        return $plainTextToken;
    }
}
