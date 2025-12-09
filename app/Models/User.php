<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\GeneratesQrCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, hasRoles, HasApiTokens, GeneratesQrCode;

    protected $fillable = [
        'name',
        'email',
        'password',
        'qr_token',
        'qr_path'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (!$user->qr_token) {
                $user->qr_token = Str::uuid()->toString();
            }
        });

        static::created(function ($user) {
            $qrPath = $user->generateQrCode($user->qr_token);

            $user->updateQuietly([
                'qr_path' => $qrPath,
            ]);
        });
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        });
    }

    public function scopeByUser(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        return $query->where('id', $user->id);
    }

}
