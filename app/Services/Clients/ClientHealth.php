<?php

namespace App\Services\Clients;

use App\Enums\Level;
use App\Models\Client;
use App\Models\Measurement;
use App\Services\Health\BodyAnalysis;
use App\Services\Health\HealthAnalyzer;

/** Analysis of a client's latest measurement, and the level they are on. */
class ClientHealth
{
    public function __construct(private HealthAnalyzer $analyzer) {}

    public function analyze(Client $client, ?Measurement $m = null): ?BodyAnalysis
    {
        $m ??= $client->latestMeasurement;

        return $m ? $this->analyzer->analyze($client->sex, $client->birth_date, $m->height_cm, $m->weight_kg) : null;
    }

    public function level(Client $client, BodyAnalysis $a): Level
    {
        return $client->level ?? $a->recommendedLevel();
    }
}
