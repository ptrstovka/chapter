<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $code
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $used_at
 * @property \App\Models\User|null $usedBy
 */
class Invitation extends Model
{
    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether the invitation is valid.
     */
    public function isValid(): bool
    {
        if (! is_null($this->used_at)) {
            return false;
        }

        if ($this->expires_at) {
            return $this->expires_at->isFuture();
        }

        return true;
    }

    /**
     * Revoke the invitation.
     */
    public function revoke(): void
    {
        $this->expires_at = now()->subSecond();

        $this->save();
    }
}
