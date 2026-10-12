<?php

namespace Tests\Feature;

use App\Livewire\Auth\PhoneLogin;
use App\Models\Client;
use App\Services\Telegram\LinkCodeService;
use App\Services\Telegram\LoginCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => '123:test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    }

    private function linkedClient(string $phone = '998901234567'): Client
    {
        $client = Client::factory()->withMeasurement()->create(['phone' => $phone]);
        $client->forceFill(['telegram_chat_id' => 4242])->save();

        return $client;
    }

    /** The code the bot sent, read from the faked Telegram request. */
    private function sentCode(): string
    {
        $text = Http::recorded()->last()[0]['text'];
        preg_match('/(\d{3}) (\d{3})/', $text, $m);

        return $m[1].$m[2];
    }

    public function test_code_is_sent_to_the_linked_chat_and_signs_in(): void
    {
        $client = $this->linkedClient();

        $c = Livewire::test(PhoneLogin::class)->set('phone', '90 123 45 67')->call('sendCode')->assertSet('sent', true);
        Http::assertSent(fn ($r) => (int) $r['chat_id'] === 4242);

        $c->set('code', $this->sentCode())->call('verify')->assertRedirect(route('panel.overview'));
        $this->assertAuthenticatedAs($client->user);
    }

    public function test_unknown_or_unlinked_phone_gets_the_same_answer_and_no_message(): void
    {
        Client::factory()->withMeasurement()->create(['phone' => '998905555555']); // not linked

        Livewire::test(PhoneLogin::class)->set('phone', '+998 90 555 55 55')->call('sendCode')->assertSet('sent', true)->assertHasNoErrors();
        Livewire::test(PhoneLogin::class)->set('phone', '+998 90 000 00 00')->call('sendCode')->assertSet('sent', true)->assertHasNoErrors();

        Http::assertNothingSent();
    }

    public function test_wrong_code_fails_and_five_wrong_codes_lock_the_phone(): void
    {
        $this->linkedClient();
        $codes = app(LoginCodeService::class);
        $codes->send('998901234567', '1.1.1.1');
        $right = $this->sentCode();

        Livewire::test(PhoneLogin::class)->set('phone', '998901234567')->set('code', '000000')->call('verify')->assertHasErrors('code');
        $this->assertGuest();

        for ($i = 0; $i < 4; $i++) {
            $codes->verify('998901234567', '111111', '1.1.1.1');
        }
        $this->assertNull($codes->verify('998901234567', $right, '1.1.1.1')); // locked even with the right code
    }

    public function test_code_works_once_and_expires(): void
    {
        $client = $this->linkedClient();
        $codes = app(LoginCodeService::class);

        $codes->send('998901234567', '1.1.1.1');
        $code = $this->sentCode();
        $this->assertTrue($client->is($codes->verify('998901234567', $code, '1.1.1.1')));
        $this->assertNull($codes->verify('998901234567', $code, '1.1.1.1'));

        $codes->send('998901234567', '1.1.1.1');
        $code = $this->sentCode();
        $this->travel(6)->minutes();
        $this->assertNull($codes->verify('998901234567', $code, '1.1.1.1'));
    }

    public function test_sending_is_rate_limited(): void
    {
        $this->linkedClient();
        $c = Livewire::test(PhoneLogin::class)->set('phone', '998901234567');

        for ($i = 0; $i < 3; $i++) {
            $c->call('sendCode')->assertHasNoErrors();
        }
        $c->call('back')->call('sendCode')->assertHasErrors('phone');
    }

    public function test_login_and_link_codes_are_never_interchangeable(): void
    {
        $client = $this->linkedClient();
        $linkCode = app(LinkCodeService::class)->issue($client)['code'];

        $this->assertNull(app(LoginCodeService::class)->verify('998901234567', $linkCode, '1.1.1.1'));

        app(LoginCodeService::class)->send('998901234567', '1.1.1.1');
        $this->assertNull(app(LinkCodeService::class)->find($this->sentCode()));
    }

    public function test_login_page_shows_both_ways(): void
    {
        $this->get(route('login'))->assertOk()->assertSeeLivewire(PhoneLogin::class)->assertSee('name="password"', false);
    }
}
