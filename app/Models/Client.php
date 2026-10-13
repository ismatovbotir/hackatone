<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\Level;
use App\Enums\Sex;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'phone', 'first_name', 'last_name', 'birth_date', 'sex', 'lang', 'level',
    'reminders', 'reminder_time', 'consent_at', 'source',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    public const REMINDER_KEYS = ['workout', 'meals', 'water', 'measure', 'evening'];

    public const DEFAULT_REMINDERS = ['workout' => true, 'meals' => true, 'water' => false, 'measure' => true, 'evening' => true];

    protected function casts(): array
    {
        return [
            'birth_date' => DateOnly::class,
            'sex' => Sex::class,
            'level' => Level::class,
            'reminders' => 'array',
            'telegram_linked_at' => 'datetime',
            'last_reminded_on' => DateOnly::class,
            'measure_reminded_at' => 'datetime',
            'consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class)->orderBy('measured_on')->orderBy('id');
    }

    public function latestMeasurement(): HasOne
    {
        return $this->hasOne(Measurement::class)->latestOfMany('measured_on');
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function linkCodes(): HasMany
    {
        return $this->hasMany(TelegramLinkCode::class);
    }

    /** Everything the plan needs: age, gender, consent and a first measurement. Telegram sign-ups start without them. */
    public function isComplete(): bool
    {
        return $this->birth_date !== null && $this->sex !== null && $this->consent_at !== null && $this->latestMeasurement !== null;
    }

    public function isLinked(): bool
    {
        return $this->telegram_chat_id !== null;
    }

    public function fullName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function reminderEnabled(string $key): bool
    {
        // Keys added later (like "evening") use their default until the client changes them.
        return (bool) ($this->reminders[$key] ?? self::DEFAULT_REMINDERS[$key] ?? false);
    }
}
