<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use Livewire\Form;

class MeasurementForm extends Form
{
    public string $measured_on = '';

    public string $height_cm = '';

    public string $weight_kg = '';

    public function store(Client $client): void
    {
        $data = $this->validate([
            'measured_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$client->birth_date->toDateString()],
            'height_cm' => ['required', 'numeric', 'between:80,250'],
            // Optional: the last known weight is kept when empty.
            'weight_kg' => ['nullable', 'numeric', 'between:10,300'],
        ], [
            'measured_on.after_or_equal' => t('lg.errdob'),
            'measured_on.*' => t('lg.err'),
            'height_cm.*' => t('lg.err'),
            'weight_kg.*' => t('e.weight'),
        ]);

        $client->measurements()->create([
            'measured_on' => $data['measured_on'],
            'height_cm' => r1((float) $data['height_cm']),
            'weight_kg' => r1((float) ($data['weight_kg'] ?: $client->latestMeasurement->weight_kg)),
        ]);

        $this->reset('height_cm', 'weight_kg');
    }
}
