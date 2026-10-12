<?php

namespace App\Livewire\Panel\Concerns;

use App\Enums\Level;
use App\Models\Client;
use App\Services\Clients\ClientHealth;
use App\Services\Health\BodyAnalysis;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/** Shared by the client's panel tabs: their profile, latest analysis and the level switch in the header strip. */
trait InteractsWithPlan
{
    #[Computed]
    public function client(): Client
    {
        return auth()->user()->client->load('measurements');
    }

    #[Computed]
    public function analysis(): BodyAnalysis
    {
        return app(ClientHealth::class)->analyze($this->client, $this->client->measurements->last());
    }

    #[Computed]
    public function level(): Level
    {
        return app(ClientHealth::class)->level($this->client, $this->analysis);
    }

    public function setLevel(string $level): void
    {
        validator(['level' => $level], ['level' => ['required', Rule::enum(Level::class)]])->validate();

        $this->client->update(['level' => $level]);
        unset($this->client, $this->analysis, $this->level);
    }

    protected function refreshPlan(): void
    {
        unset($this->client, $this->analysis, $this->level);
    }
}
