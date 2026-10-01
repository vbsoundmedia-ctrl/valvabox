<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'artist_name', 'email', 'phone', 'password', 'role', 'status', 'plan_id', 'plan_expires_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class);
    }

    public function hasActivePlan(): bool
    {
        return $this->plan_id && $this->plan_expires_at && $this->plan_expires_at->isFuture();
    }

    /** The plan currently in force: the paid plan while active, otherwise the free plan. */
    public function currentPlan(): Plan
    {
        if ($this->hasActivePlan() && $this->plan) {
            return $this->plan;
        }

        return Plan::free();
    }

    public function balanceKobo(): int
    {
        return (int) $this->ledger()->sum('amount_kobo');
    }

    public function displayName(): string
    {
        return $this->artist_name ?: $this->name;
    }
}
