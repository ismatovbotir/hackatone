<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purpose', 'code_hash', 'expires_at', 'used_at'])]
class TelegramLinkCode extends Model
{
    use MassPrunable;

    /** Codes are useless a day after they expire. */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDay());
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
