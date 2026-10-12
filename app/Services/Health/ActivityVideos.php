<?php

namespace App\Services\Health;

use App\Models\ActivityVideo;
use App\Support\ActivityMedia;
use Illuminate\Support\Collection;

/**
 * Example animations for each training activity, for young people (5-18) and adults.
 * Built-in prompts are for an AI video generator. They ask for an illustrated style with no real people,
 * so no real child is shown; VideoGenerator adds a safety line to every prompt.
 */
class ActivityVideos
{
    public const TYPES = ['cardio', 'strength', 'stretch', 'sport', 'recover'];

    public const AUDIENCES = ['youth', 'adult'];

    private const STYLE = 'Style: friendly 2D flat illustration animation, soft colours, plain light background, no text on screen, no logos, no real people. 15 to 20 seconds, 16:9, smooth loop. Show one animated character doing the movement slowly and with correct form, side view then front view. Calm, encouraging mood.';

    private const PROMPTS = [
        'cardio-youth' => 'An animated school-age child character (about 10 years old, sportswear, sneakers) warms up by marching on the spot, then does light jogging, skipping with a rope and star jumps on a playground. Breathing is easy; the character can still talk and smile. A water bottle sits nearby.',
        'cardio-adult' => 'An animated adult character in sportswear does brisk walking that turns into light jogging in a park, then cycling on a bike path. Steady pace, upright posture, relaxed shoulders, breathing a little faster but able to talk. Ends with a slow walk to cool down.',
        'strength-youth' => 'An animated teenage character does body-weight exercises on a mat with no weights: wall push-ups, squats with knees over toes, a short plank on the knees, and glute bridges. Slow controlled movement, straight back, short rests between sets. No heavy lifting.',
        'strength-adult' => 'An animated adult character does a basic strength routine on a mat: squats, push-ups (knees allowed), lunges, a plank and a row using a resistance band. Neutral spine, controlled tempo, exhale on effort, rest between sets.',
        'stretch-youth' => 'An animated child character does a gentle stretching routine after play: reaching up tall, side bends, a seated forward fold reaching for the toes, butterfly stretch, and a cat-cow on hands and knees. Each stretch is held calmly for a few seconds with no bouncing and no pain.',
        'stretch-adult' => 'An animated adult character does a calm full-body stretch: neck rolls, shoulder circles, standing quad stretch holding a chair, hamstring stretch, hip flexor lunge and a seated spinal twist. Slow breathing, each stretch held about 20 seconds, no bouncing.',
        'sport-youth' => 'Animated children characters play an active team game outdoors: passing a football, running, and playing tag, then a short game of basketball. Everyone wears sneakers, takes turns, drinks water and plays fairly. Bright, cheerful, safe schoolyard setting.',
        'sport-adult' => 'An animated adult character plays a recreational sport: a friendly football match, then a game of volleyball with friends. Warm-up first, good footwear, water breaks, and a cool-down walk at the end.',
        'recover-youth' => 'An animated child character has a recovery day: a slow walk with family, light stretching, drinking water, eating fruit, and going to bed early in a dark quiet room. Relaxed and restful mood.',
        'recover-adult' => 'An animated adult character has an active recovery day: an easy 20-minute walk, foam rolling the legs, gentle stretching, drinking water and an early night. Relaxed, unhurried pacing.',
    ];

    /** @return Collection<string, array{key:string, type:string, audience:string, row:?ActivityVideo, media:?array, draft:?array, prompt:string, custom:bool}> */
    public function all(): Collection
    {
        $saved = ActivityVideo::all()->keyBy('key');

        return collect(self::TYPES)->crossJoin(self::AUDIENCES)->mapWithKeys(function ($pair) use ($saved) {
            [$type, $audience] = $pair;
            $key = "$type-$audience";
            $row = $saved->get($key);

            return [$key => [
                'key' => $key,
                'type' => $type,
                'audience' => $audience,
                'row' => $row,
                'media' => ActivityMedia::make($row?->media_path, $row?->media_type),
                'draft' => ActivityMedia::make($row?->draft_path, $row?->draft_type),
                'prompt' => $row?->prompt ?: $this->defaultPrompt($key),
                'custom' => filled($row?->prompt),
            ]];
        });
    }

    public function defaultPrompt(string $key): string
    {
        return self::PROMPTS[$key].' '.self::STYLE;
    }

    /** @return array<string, array{type:string, src:string}> session type => published media, for the client's audience */
    public function mediaFor(bool $youth): array
    {
        $audience = $youth ? 'youth' : 'adult';

        return ActivityVideo::whereNotNull('media_path')->where('key', 'like', "%-$audience")->get()
            ->mapWithKeys(fn ($row) => [explode('-', $row->key)[0] => ActivityMedia::make($row->media_path, $row->media_type)])
            ->filter()->all();
    }
}
