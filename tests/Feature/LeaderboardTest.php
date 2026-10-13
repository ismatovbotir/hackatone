<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Panel\Calendar;
use App\Models\Client;
use App\Models\User;
use App\Services\Clients\Leaderboard;
use App\Services\Clients\Points;
use App\Services\Clients\TrainingCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-14 12:00', config('hh.timezone')));
    }

    private function clientWith(array $pointsByDate): Client
    {
        $client = Client::factory()->withMeasurement()->create(['created_at' => '2026-09-01', 'level' => 'mid']);
        foreach ($pointsByDate as $date => $points) {
            $client->dailyLogs()->create(['log_date' => $date, 'level' => 'mid', 'session' => 'cardio', 'planned_minutes' => 40, 'workout_done' => true, 'water_ml' => 0, 'points' => $points]);
        }

        return $client;
    }

    public function test_points_rule(): void
    {
        $this->assertSame(30, Points::forDay(40, true, 40, 5, 2000, 1900));
        $this->assertSame(10, Points::forDay(40, true, 30, 0, 0, 1900));
        $this->assertSame(0, Points::forDay(0, true, 30, 0, 0, 1900)); // no training points on a rest day
        $this->assertSame(10, Points::forDay(40, false, null, 7, 0, 1900)); // meals capped at 5
        // Reading: +1 per 5 pages, up to +10; a full day with 50+ pages is 40.
        $this->assertSame(4, Points::forDay(0, false, null, 0, 0, 1900, 23));
        $this->assertSame(10, Points::forDay(0, false, null, 0, 0, 1900, 300));
        $this->assertSame(40, Points::forDay(40, true, 40, 5, 2000, 1900, 50));
    }

    public function test_top_is_ordered_ranked_and_limited(): void
    {
        $a = $this->clientWith(['2026-10-01' => 30, '2026-10-02' => 20]);
        $b = $this->clientWith(['2026-10-03' => 50]);
        $c = $this->clientWith(['2026-10-04' => 10]);
        $this->clientWith([]); // no points: not listed

        $top = app(Leaderboard::class)->top('month');

        $this->assertSame([$a->id, $b->id, $c->id], $top->pluck('client.id')->all());
        $this->assertSame([1, 1, 3], $top->pluck('rank')->all()); // equal points share a rank
        $this->assertSame([50, 50, 10], $top->pluck('points')->all());

        for ($i = 0; $i < 25; $i++) {
            $this->clientWith(['2026-10-05' => 5]);
        }
        $this->assertCount(20, app(Leaderboard::class)->top('month'));
    }

    public function test_month_and_all_time_periods(): void
    {
        $old = $this->clientWith(['2026-09-20' => 100]);
        $new = $this->clientWith(['2026-10-31' => 10]); // last day of the month counts

        $this->assertSame([$new->id], app(Leaderboard::class)->top('month')->pluck('client.id')->all());
        $this->assertSame([$old->id, $new->id], app(Leaderboard::class)->top('all')->pluck('client.id')->all());
    }

    public function test_position_of_a_client(): void
    {
        $this->clientWith(['2026-10-01' => 40]);
        $me = $this->clientWith(['2026-10-02' => 25]);
        $this->clientWith(['2026-10-03' => 10]);
        $nobody = $this->clientWith([]);

        $this->assertSame(['rank' => 2, 'of' => 3, 'points' => 25, 'streak' => 0, 'to_next' => 16], app(Leaderboard::class)->positionOf($me, 'month'));
        $this->assertNull(app(Leaderboard::class)->positionOf($nobody, 'month')['rank']);
    }

    public function test_saving_a_day_stores_points_and_can_be_saved_again(): void
    {
        $client = Client::factory()->withMeasurement(150, 42)->create(['birth_date' => '2014-01-01', 'created_at' => '2026-10-01', 'level' => 'mid']);
        $this->actingAs($client->user);

        foreach ([1, 2] as $_) {
            Livewire::test(Calendar::class)->call('openDay', '2026-10-14')
                ->set('day.workout_done', true)->set('day.workout_minutes', 45)->set('day.meals.0', true)
                ->call('saveDay')->assertHasNoErrors();
        }

        $this->assertSame(1, $client->dailyLogs()->count());
        $this->assertSame(10 + 5 + 2, $client->dailyLogs()->first()->points);
    }

    public function test_admin_dashboard_shows_the_top_20(): void
    {
        $a = $this->clientWith(['2026-10-01' => 30]);
        $b = $this->clientWith(['2026-09-01' => 90]);

        $this->actingAs(User::factory()->admin()->create());
        $ids = fn ($top) => $top->pluck('client.id')->all();

        // "Recent registrations" on the same page lists every name, so check the ranking data itself.
        Livewire::test(Dashboard::class)
            ->assertViewHas('top', fn ($top) => $ids($top) === [$a->id])
            ->assertSee($a->fullName())
            ->call('setPeriod', 'all')
            ->assertViewHas('top', fn ($top) => $ids($top) === [$b->id, $a->id])
            ->call('setPeriod', 'nonsense')->assertSet('period', 'month');
    }

    public function test_client_sees_only_their_own_position(): void
    {
        $leader = $this->clientWith(['2026-10-01' => 40]);
        $me = $this->clientWith(['2026-10-02' => 25]);
        $me->update(['birth_date' => '2014-01-01']);
        $this->actingAs($me->user);

        $this->get(route('panel.overview'))
            ->assertOk()
            ->assertSee('#2')
            ->assertDontSee($leader->first_name)
            ->assertDontSee($leader->last_name);
        $this->get(route('panel.calendar'))->assertOk()->assertDontSee($leader->last_name);
    }

    public function test_pages_read_is_the_first_field_and_counts_everywhere(): void
    {
        $client = Client::factory()->withMeasurement(150, 42)->create(['birth_date' => '2014-01-01', 'created_at' => '2026-10-01', 'level' => 'mid']);
        $this->actingAs($client->user);

        $html = Livewire::test(Calendar::class)->call('openDay', '2026-10-14')->html();
        $this->assertLessThan(strpos($html, 'day.workout_done'), strpos($html, 'day.pages_read')); // reading comes first

        Livewire::test(Calendar::class)->call('openDay', '2026-10-14')
            ->set('day.pages_read', 25)->call('saveDay')->assertHasNoErrors();
        Livewire::test(Calendar::class)->call('openDay', '2026-10-13')
            ->set('day.pages_read', -3)->call('saveDay')->assertHasErrors('day.pages_read');

        $log = $client->dailyLogs()->first();
        $this->assertSame(25, $log->pages_read);
        $this->assertSame(5, $log->points); // reading only: 25 pages = 5 points

        $this->get(route('panel.overview'))->assertSee(t('mg.title'));
        $this->assertSame(25, app(TrainingCalendar::class)->summary(
            app(TrainingCalendar::class)->month($client->fresh(), Level::Mid, now(config('hh.timezone')))
        )['pages']);
    }
}
