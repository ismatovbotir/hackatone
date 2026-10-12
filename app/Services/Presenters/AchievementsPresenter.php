<?php

namespace App\Services\Presenters;

use App\Enums\Level;
use App\Models\Measurement;
use App\Services\Health\BodyAnalysis;
use App\Services\Health\GrowthProjector;
use App\Support\Tr;
use Illuminate\Support\Collection;

class AchievementsPresenter
{
    private const HABITS = [
        ['cons', [4, 6, 8], 'un.weeks'],
        ['sleep', [14, 21, 30], 'un.nights'],
        ['menu', [14, 21, 30], 'un.days'],
        ['post', [21, 30, 66], 'un.days'],
        ['who', [4, 8, 12], 'un.weeks'],
    ];

    public function __construct(private GrowthProjector $projector) {}

    /** @param  Collection<int, Measurement>  $meas  oldest first */
    public function present(BodyAnalysis $a, Collection $meas): array
    {
        $first = $meas->first();
        $last = $meas->last();
        $gain = r1($last->height_cm - $first->height_cm);

        $badges = [
            ['n' => t('bd.reg'), 'd' => t('bd.regd'), 'ok' => true, 'm' => '1'],
            ['n' => t('bd.chk'), 'd' => t('bd.chkd'), 'ok' => $meas->count() >= 2, 'm' => '2'],
        ];
        if ($a->youth) {
            $badges[] = ['n' => t('bd.who'), 'd' => t('bd.whod'), 'ok' => abs($a->z) <= 2, 'm' => 'SD'];
            $badges[] = ['n' => t('bd.med'), 'd' => t('bd.medd'), 'ok' => $a->z >= 0, 'm' => 'P50'];
        }
        if ($a->bmiStatus === 'ok') {
            $badges[] = ['n' => t('bd.bmi'), 'd' => t('bd.bmid'), 'ok' => true, 'm' => 'BMI'];
        }
        if ($a->growable()) {
            foreach ([1, 2, 3, 5, 8] as $g) {
                $badges[] = ['n' => '+'.$g.' '.t('u.cm'), 'd' => t('bd.gaind'), 'ok' => $gain >= $g, 'm' => '+'.$g];
            }
        }

        $growth = null;
        if ($a->growable()) {
            $targets = $this->projector->gainTargets($a);
            $sims = collect(Level::cases())->mapWithKeys(fn ($l) => [$l->value => $this->projector->simulate($a, $l->growthPace(), $targets)]);
            $growth = array_map(fn ($g, $k) => [
                'gain' => $g,
                'cells' => $sims->map(fn ($s) => $s[$k] === null ? null : [
                    'label' => Tr::months($s[$k]),
                    'by' => t('ac.by', now()->addMonths($s[$k])->locale(Tr::dateLocale())->translatedFormat('M Y')),
                ])->all(),
            ], $targets, array_keys($targets));
        }

        $habits = array_map(fn ($h) => [
            'name' => t('hb.'.$h[0]),
            'desc' => t('hb.'.$h[0].'d'),
            'cells' => collect(Level::cases())->mapWithKeys(fn ($l, $j) => [$l->value => ['label' => t($h[2], $h[1][$j]), 'sub' => null]])->all(),
        ], self::HABITS);

        if (! $a->youth && $a->bmiStatus !== 'ok') {
            $under = $a->bmiStatus === 'under';
            $diff = $under ? $a->kgLow - $a->weight : $a->weight - $a->kgHigh;
            $rate = $under ? [0.25, 0.35, 0.5] : [0.25, 0.5, 0.75];
            $habits[] = [
                'name' => t('hb.bmi'),
                'desc' => t($under ? 'hb.gain' : 'hb.lose', r1($diff)),
                'cells' => collect(Level::cases())->mapWithKeys(function ($l, $j) use ($diff, $rate) {
                    $weeks = max(1, (int) ceil($diff / $rate[$j]));

                    return [$l->value => ['label' => Tr::months(max(1, (int) floor($weeks / 4.345 + 0.5))), 'sub' => t('hb.rate', $rate[$j])]];
                })->all(),
            ];
        }

        return [
            'badges' => $badges,
            'won' => count(array_filter($badges, fn ($b) => $b['ok'])),
            'gain' => $gain,
            'growth' => $growth,
            'habits' => $habits,
        ];
    }
}
