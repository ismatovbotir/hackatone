<?php

namespace App\Http\Controllers;

use App\Enums\Level;
use App\Enums\Sex;
use App\Services\Health\TrainingPlanner;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(TrainingPlanner $training): View
    {
        return view('landing', [
            'minutes' => collect(Level::cases())->mapWithKeys(fn ($l) => [$l->value => $training->weeklyMinutes($l)]),
            // Example growth curve on the landing page, as in the prototype.
            'chartSex' => Sex::Male,
            'chartPoints' => [[11, 141.5], [12, 147.5], [13, 154], [14, 159], [14.5, 161.5]],
        ]);
    }
}
