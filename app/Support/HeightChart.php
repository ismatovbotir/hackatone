<?php

namespace App\Support;

use App\Enums\Sex;
use App\Services\Health\GrowthReference;

/** WHO height-for-age band (median, +/-2 SD) with the client's points, ages 5 to 19. Port of the prototype chartSvg. */
final class HeightChart
{
    private const W = 640;

    private const H = 320;

    private const L = 46;

    private const R = 44;

    private const T = 14;

    private const B = 40;

    /** @param  list<array{0: float, 1: float}>  $points  [age, height] */
    public static function svg(Sex $sex, array $points): string
    {
        $x = fn (float $g) => self::L + ($g - 5) / 14 * (self::W - self::L - self::R);
        $y = fn (float $h) => self::H - self::B - (min(200, max(100, $h)) - 100) / 100 * (self::H - self::T - self::B);
        $at = fn (float $g, int $k) => GrowthReference::heightMedian($sex, $g) + $k * GrowthReference::heightSd($sex, $g);
        $f = fn (float $n) => number_format($n, 1, '.', '');

        $ages = range(5, 19, 0.5);
        $line = fn (int $k) => implode('', array_map(fn ($g, $i) => ($i ? 'L' : 'M').$f($x($g)).' '.$f($y($at($g, $k))), $ages, array_keys($ages)));
        $up = array_map(fn ($g) => $f($x($g)).' '.$f($y($at($g, 2))), $ages);
        $lo = array_map(fn ($g) => $f($x($g)).' '.$f($y($at($g, -2))), array_reverse($ages));

        $s = '<svg class="chart" viewBox="0 0 '.self::W.' '.self::H.'" role="img" aria-label="'.e(t('ch.aria')).'">';
        for ($h = 100; $h <= 200; $h += 20) {
            $s .= '<line class="ax" x1="'.self::L.'" x2="'.(self::W - self::R).'" y1="'.$f($y($h)).'" y2="'.$f($y($h)).'"/>'
                .'<text x="'.(self::L - 8).'" y="'.$f($y($h) + 4).'" text-anchor="end">'.$h.'</text>';
        }
        for ($ag = 5; $ag <= 19; $ag += 2) {
            $s .= '<text x="'.$f($x($ag)).'" y="'.(self::H - self::B + 18).'" text-anchor="middle">'.$ag.'</text>';
        }
        $s .= '<text x="'.((self::L + self::W - self::R) / 2).'" y="'.(self::H - 6).'" text-anchor="middle">'.e(t('ch.age')).'</text>'
            .'<text x="4" y="10">'.e(t('u.cm')).'</text>';
        $s .= '<path class="band" d="M'.implode('L', [...$up, ...$lo]).'Z"/>'
            .'<path class="edge" d="'.$line(2).'"/><path class="edge" d="'.$line(-2).'"/><path class="med" d="'.$line(0).'"/>';
        $ex = self::W - self::R + 6;
        $s .= '<text x="'.$ex.'" y="'.$f($y($at(19, 2)) + 4).'">+2 SD</text>'
            .'<text x="'.$ex.'" y="'.$f($y($at(19, 0)) + 4).'">'.e(t('ch.median')).'</text>'
            .'<text x="'.$ex.'" y="'.$f($y($at(19, -2)) + 4).'">-2 SD</text>';

        $n = count($points);
        foreach (array_values($points) as $i => [$ag, $h]) {
            $last = $i === $n - 1;
            $s .= '<circle class="pt'.($last ? ' last' : '').'" cx="'.$f($x($ag)).'" cy="'.$f($y($h)).'" r="'.($last ? 6 : 4).'"/>';
        }

        return $s.'</svg>';
    }
}
