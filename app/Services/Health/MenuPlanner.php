<?php

namespace App\Services\Health;

use App\Enums\Level;

/**
 * Daily menu per level, built for 2000 kcal and scaled to the client's energy.
 * Items: [food key, grams or null, pieces]. Meals: [meal key, % of daily kcal, items].
 */
final class MenuPlanner
{
    public const MENU = [
        'easy' => [
            ['breakfast', 25, [['oat', 250], ['egg', null, 1], ['apple', null, 1]]],
            ['snack', 10, [['kefir', 200], ['apricot', 30]]],
            ['lunch', 30, [['lentil', 300], ['rice', 120], ['cucsalad', 150], ['nonwg', 40]]],
            ['snack', 10, [['tvorog', 100], ['fruit', 150]]],
            ['dinner', 25, [['chicken', 120], ['steamveg', 200], ['potato', 150]]],
        ],
        'mid' => [
            ['breakfast', 25, [['omelet', null, 2], ['nonwg', 60], ['tvorog', 80], ['fruit', 150]]],
            ['snack', 10, [['yogurt', 150], ['walnut', 20], ['banana', null, 1]]],
            ['lunch', 30, [['shurpa', 350], ['buckwheat', 150], ['mixsalad', 150], ['non', 50]]],
            ['snack', 10, [['egg', null, 1], ['nutsfruit', 40], ['kefir', 200]]],
            ['dinner', 25, [['fish', 150], ['brownrice', 150], ['veg', 200]]],
        ],
        'strong' => [
            ['breakfast', 25, [['oatnuts', 300], ['egg', null, 3], ['apricot', 30]]],
            ['snack', 10, [['tvorog', 150], ['honey', 15], ['fruit', 150]]],
            ['lunch', 30, [['plov', 350], ['vegsalad', 200], ['yogurt', 150], ['non', 60]]],
            ['snack', 10, [['egg', null, 2], ['nuts', 30], ['banana', null, 1]]],
            ['dinner', 25, [['grill', 180], ['rb', 200], ['veg', 250], ['kefir', 200]]],
        ],
    ];

    /**
     * @return list<array{meal:string,kcal:int,items:list<array{food:string,grams:?int,pieces:?int}>}>
     */
    public function day(Level $level, int $kcal): array
    {
        $f = $kcal / 2000;

        return array_map(fn ($meal) => [
            'meal' => $meal[0],
            'kcal' => (int) EnergyPlanner::jsRound($kcal * $meal[1] / 100),
            'items' => array_map(fn ($it) => $it[1] === null
                ? ['food' => $it[0], 'grams' => null, 'pieces' => (int) max(1, EnergyPlanner::jsRound($it[2] * $f))]
                : ['food' => $it[0], 'grams' => (int) max(5, EnergyPlanner::jsRound($it[1] * $f / 5) * 5), 'pieces' => null],
                $meal[2]),
        ], self::MENU[$level->value]);
    }
}
