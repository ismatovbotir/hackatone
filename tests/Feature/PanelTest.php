<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Users;
use App\Livewire\Admin\Videos;
use App\Livewire\Panel\Account;
use App\Livewire\Panel\Achievements;
use App\Livewire\Panel\Calendar;
use App\Livewire\Panel\Overview;
use App\Models\ActivityVideo;
use App\Models\Client;
use App\Models\User;
use App\Services\Clients\TrainingCalendar;
use App\Services\Media\VideoGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    private function client(): Client
    {
        $this->travelTo(Carbon::parse('2026-10-14 12:00', config('hh.timezone'))); // a Wednesday
        $client = Client::factory()->withMeasurement(150, 42)->create(['birth_date' => '2014-01-01', 'created_at' => '2026-10-01']);
        $this->actingAs($client->user);

        return $client;
    }

    public function test_level_switch_is_saved(): void
    {
        $client = $this->client();
        Livewire::test(Overview::class)->call('setLevel', 'strong');
        $this->assertSame('strong', $client->fresh()->level->value);

        Livewire::test(Overview::class)->call('setLevel', 'extreme')->assertHasErrors('level');
    }

    public function test_measurement_is_added_and_first_cannot_be_removed(): void
    {
        $client = $this->client();
        $first = $client->measurements()->first();

        Livewire::test(Achievements::class)
            ->set('log.measured_on', '2026-10-14')->set('log.height_cm', '151')->call('addMeasurement')
            ->assertHasNoErrors()
            ->assertSee('151')
            ->call('removeMeasurement', $first->id);

        $this->assertSame(2, $client->measurements()->count());
        $this->assertSame(42.0, $client->measurements()->latest('id')->first()->weight_kg); // kept last weight
    }

    public function test_calendar_day_modal_saves_training_food_and_water(): void
    {
        $client = $this->client();
        $client->update(['level' => 'mid']); // Mid trains every day

        Livewire::test(Calendar::class)
            ->call('openDay', '2026-10-14')
            ->assertSet('open', '2026-10-14')
            ->assertSee(t('cal.did'))
            ->set('day.workout_done', true)->set('day.workout_minutes', 35)
            ->set('day.meals.0', true)->set('day.meals.2', true)
            ->set('day.glasses', 6)
            ->call('saveDay')
            ->assertHasNoErrors()
            ->assertSet('open', '');

        $log = $client->dailyLogs()->first();
        $this->assertTrue($log->workout_done);
        $this->assertSame(35, $log->workout_minutes);
        $this->assertSame(2, $log->mealsEaten());
        $this->assertSame(1500, $log->water_ml);

        // Reopening loads what was saved.
        Livewire::test(Calendar::class)->call('openDay', '2026-10-14')
            ->assertSet('day.workout_done', true)->assertSet('day.glasses', 6);

        $days = app(TrainingCalendar::class)->month($client, Level::Mid, now(config('hh.timezone')));
        $status = fn ($date) => $days->first(fn ($d) => $d['date']->toDateString() === $date)['status'];
        $this->assertSame('done', $status('2026-10-14'));
        $this->assertSame('missed', $status('2026-10-13'));
        $this->assertSame('planned', $status('2026-10-15'));
        $this->assertSame('none', $status('2026-09-30'));
    }

    public function test_future_and_old_days_cannot_be_saved(): void
    {
        $this->client();

        Livewire::test(Calendar::class)->call('openDay', '2026-10-20')->call('saveDay')->assertForbidden();
    }

    public function test_delete_my_data_removes_everything(): void
    {
        $client = $this->client();
        $userId = $client->user_id;

        Livewire::test(Account::class)->call('deleteData')->assertRedirect(route('home'));

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('measurements', 0);
        $this->assertGuest();
    }

    public function test_admin_can_change_roles_but_not_their_own(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(Users::class)->call('setRole', $user->id, 'admin');
        $this->assertTrue($user->fresh()->isAdmin());

        Livewire::test(Users::class)->call('setRole', $admin->id, 'user')->assertForbidden();
    }

    public function test_settings_store_token_encrypted_and_never_show_it(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Settings::class)
            ->set('form.token', '123456:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef')
            ->set('form.username', '@hh_test_bot')
            ->set('form.adminChatIds', '12, 34')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.token', '')
            ->assertDontSee('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef');

        $this->assertNotSame('123456:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef', \DB::table('settings')->where('key', 'telegram.bot_token')->value('value'));
        $this->assertSame('123456:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef', config('services.telegram.bot_token'));
        $this->assertSame('hh_test_bot', config('services.telegram.bot_username'));
        $this->assertSame(48, strlen(config('services.telegram.webhook_secret'))); // created on save

        Livewire::test(Settings::class)->set('form.adminChatIds', '12, abc')->call('save')->assertHasErrors('form.adminChatIds');
    }

    private function gifBytes(): string
    {
        $img = imagecreatetruecolor(8, 8);
        ob_start();
        imagegif($img);

        return ob_get_clean();
    }

    public function test_admin_upload_is_published_and_replacing_it_deletes_the_old_file(): void
    {
        Storage::fake('uploads');
        $this->actingAs(User::factory()->admin()->create());

        $c = Livewire::test(Videos::class)
            ->set('files.stretch-adult', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))->call('upload', 'stretch-adult')->assertHasErrors('files.stretch-adult')
            ->set('files.stretch-adult', UploadedFile::fake()->image('stretch.gif'))->call('upload', 'stretch-adult')->assertHasNoErrors();

        $first = ActivityVideo::find('stretch-adult');
        $this->assertSame('gif', $first->media_type);
        Storage::disk('uploads')->assertExists($first->media_path);

        $c->set('files.stretch-adult', UploadedFile::fake()->image('again.gif'))->call('upload', 'stretch-adult');
        Storage::disk('uploads')->assertMissing($first->media_path);

        $c->call('removeMedia', 'stretch-adult');
        $this->assertNull(ActivityVideo::find('stretch-adult')->media_path);
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_ai_generation_makes_a_draft_that_clients_only_see_after_publishing(): void
    {
        Storage::fake('uploads');
        config(['services.huggingface.token' => 'hf_testtokentesttokentesttoken']);
        Http::fake([
            'router.huggingface.co/replicate/v1/models/*' => Http::response(['id' => 'job123', 'status' => 'processing']),
            'router.huggingface.co/replicate/v1/predictions/job123' => Http::response(['id' => 'job123', 'status' => 'succeeded', 'output' => 'https://replicate.delivery/xyz/out.gif']),
            'replicate.delivery/*' => Http::response($this->gifBytes(), 200, ['Content-Type' => 'image/gif']),
        ]);
        $this->actingAs(User::factory()->admin()->create());

        $c = Livewire::test(Videos::class)->call('generate', 'cardio-youth');
        $this->assertSame('job123', ActivityVideo::find('cardio-youth')->job_id);
        Http::assertSent(fn ($r) => str_contains($r['input']['prompt'] ?? '', VideoGenerator::SAFETY));

        $c->call('checkJobs');
        $row = ActivityVideo::find('cardio-youth');
        $this->assertNull($row->job_id);
        $this->assertNotNull($row->draft_path);
        $this->assertNull($row->media_path);

        $client = $this->client();
        $this->get(route('panel.training'))->assertDontSee($row->draft_path);

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Videos::class)->call('publishDraft', 'cardio-youth');
        $row->refresh();
        $this->assertNull($row->draft_path);
        $this->assertSame('gif', $row->media_type);

        $this->actingAs($client->user)->get(route('panel.training'))->assertSee($row->media_path);
    }

    public function test_ai_result_from_an_unknown_host_is_refused(): void
    {
        Storage::fake('uploads');
        config(['services.huggingface.token' => 'hf_testtokentesttokentesttoken']);
        Http::fake([
            'router.huggingface.co/*' => Http::response(['id' => 'job9', 'status' => 'succeeded', 'output' => 'https://evil.example/out.gif']),
        ]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Videos::class)->call('generate', 'sport-adult');

        $row = ActivityVideo::find('sport-adult');
        $this->assertNull($row->draft_path);
        $this->assertNotNull($row->job_error);
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_generate_button_does_nothing_without_a_token(): void
    {
        config(['services.huggingface.token' => null]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Videos::class)->assertSee(t('vid.ai_off'))->call('generate', 'cardio-adult');
        $this->assertNull(ActivityVideo::find('cardio-adult')?->job_id);
    }
}
