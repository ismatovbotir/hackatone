<?php

namespace App\Services\Presenters;

use App\Enums\Level;
use App\Services\Health\ActivityVideos;
use App\Services\Health\BodyAnalysis;
use App\Services\Health\TrainingPlanner;

class TrainingPresenter
{
    public function __construct(private TrainingPlanner $planner, private ActivityVideos $videos) {}

    public function present(BodyAnalysis $a, Level $level): array
    {
        $total = $this->planner->weeklyMinutes($level);
        $videos = $this->videos->mediaFor($a->youth);

        if ($a->youth) {
            $who = t('tr.whoy', $this->planner->youthDailyTopUp($level));
            $tone = 'neutral';
        } else {
            $who = t($total < 150 ? 'tr.who.low' : ($total <= 300 ? 'tr.who.ok' : 'tr.who.high'));
            $tone = $total < 150 ? 'warn' : 'good';
        }

        return [
            'days' => array_map(fn ($d, $i) => [
                'day' => t('d.'.$i),
                'type' => t('ty.'.$d[0]),
                'minutes' => $d[1],
                'detail' => $this->detail($d[0], $level, $a),
                'video' => $d[1] > 0 ? ($videos[$d[0]] ?? null) : null,
            ], $this->planner->week($level), array_keys($this->planner->week($level))),
            'total' => $total,
            'sessions' => $this->planner->sessions($level),
            'who' => $who,
            'whoTone' => $tone,
            'growthSleep' => $a->age < 14 ? '9–11' : '8–10',
        ];
    }

    private function detail(string $type, Level $lv, BodyAnalysis $a): string
    {
        $l = $lv->value;

        return match ($type) {
            'cardio' => t('dx.cardio.'.($a->youth ? 'y' : 'a').'.'.$l),
            'strength' => $a->youth
                ? t($lv === Level::Strong ? 'dx.strength.y.strong' : 'dx.strength.y', t('sets.'.$l))
                : t('dx.strength.a', t('sets.'.$l)),
            'stretch' => t($a->growable() ? 'dx.stretch.g' : 'dx.stretch.n'),
            'sport' => t($a->youth ? 'dx.sport.y' : 'dx.sport.a'),
            'recover' => t('dx.recover'),
            default => t('dx.rest'),
        };
    }
}
