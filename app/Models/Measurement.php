<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['measured_on', 'height_cm', 'weight_kg'])]
class Measurement extends Model
{
    protected function casts(): array
    {
        return [
            'measured_on' => 'date',
            'height_cm' => 'float',
            'weight_kg' => 'float',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
