<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'subscription_tier',
        'queries_this_month',
        'queries_reset_at',
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
            'queries_reset_at' => 'datetime',
        ];
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function isPro(): bool
    {
        return $this->subscription_tier === 'pro';
    }

    public function hasReachedFreeLimit(): bool
    {
        if ($this->isPro()) {
            return false;
        }

        $this->resetQueriesIfNeeded();
        return $this->queries_this_month >= 10;
    }

    public function incrementQueryCount(): void
    {
        $this->resetQueriesIfNeeded();
        $this->increment('queries_this_month');
    }

    private function resetQueriesIfNeeded(): void
    {
        if (is_null($this->queries_reset_at) || $this->queries_reset_at->lt(now()->startOfMonth())) {
            $this->update([
                'queries_this_month' => 0,
                'queries_reset_at' => now(),
            ]);
        }
    }
}
