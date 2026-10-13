<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['log_date', 'level', 'session', 'planned_minutes', 'workout_done', 'workout_minutes', 'meals', 'water_ml', 'pages_read', 'points', 'custom_plan', 'reading_goal'])]
class DailyLog extends Model
{
    protected function casts(): array
    {
        return [
            'log_date' => DateOnly::class,
            'workout_done' => 'boolean',
            'custom_plan' => 'boolean',
            'meals' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function mealsEaten(): int
    {
        return count(array_filter($this->meals ?? []));
    }
}
