<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\PackagesEndingSoon;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\Support\FakesFirebase;
use Tests\TestCase;

/**
 * The admin home's "Send test notification": to the admin's own phones or
 * every admin's, and a plain answer about what happened.
 */
class SendTestNotificationTest extends TestCase
{
    use CreatesOwners, FakesFirebase, RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = $this->admin();
        $this->actingAs($this->me);
    }

    private function send(string $to = 'mine'): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(Dashboard::class)
            ->callAction('sendTestNotification', data: ['to' => $to, 'title' => 'Hello', 'body' => 'Can you see me?']);
    }

    public function test_it_reaches_my_phones_only(): void
    {
        $this->fakeFirebase();
        $mine = DeviceToken::factory()->for($this->me)->count(2)->create();
        DeviceToken::factory()->for($this->admin())->create();
        DeviceToken::factory()->for(User::factory())->create();

        $this->send()->assertHasNoActionErrors()->assertNotified('Sent to 2 phones');

        $this->assertEqualsCanonicalizing($mine->pluck('token')->all(), $this->pushes[0]['tokens']);
        $this->assertSame(['title' => 'Hello', 'body' => 'Can you see me?'], $this->pushes[0]['message']['notification']);
        $this->assertSame(['type' => 'test'], $this->pushes[0]['message']['data']);
    }

    public function test_every_admin_reaches_all_admins_phones_and_never_an_owners(): void
    {
        $this->fakeFirebase();
        DeviceToken::factory()->for($this->me)->create();
        DeviceToken::factory()->for($this->admin())->create();
        $owner = DeviceToken::factory()->for(User::factory())->create();

        $this->send('every_admin')->assertNotified('Sent to 2 phones');

        $this->assertCount(2, $this->pushes[0]['tokens']);
        $this->assertNotContains($owner->token, $this->pushes[0]['tokens']);
    }

    public function test_a_phone_firebase_forgot_is_named_and_removed(): void
    {
        $kept = DeviceToken::factory()->for($this->me)->create();
        $gone = DeviceToken::factory()->for($this->me)->create();
        $this->fakeFirebase(gone: [$gone->token]);

        $this->send()->assertNotified('Sent to 1 of 2 phones');

        $this->assertModelExists($kept);
        $this->assertModelMissing($gone);
    }

    public function test_no_phone_says_how_to_add_one(): void
    {
        $this->fakeFirebase();

        $this->send()->assertNotified('No phone to send to');
        $this->assertSame([], $this->pushes);
    }

    public function test_without_the_key_it_says_notifications_are_off(): void
    {
        DeviceToken::factory()->for($this->me)->create();

        $this->send()->assertNotified('Notifications are off on this server');
    }

    public function test_the_form_starts_on_my_phones_with_a_ready_message(): void
    {
        $this->fakeFirebase();

        Livewire::test(Dashboard::class)
            ->mountAction('sendTestNotification')
            ->assertActionDataSet([
                'to' => 'mine',
                'title' => 'Test from Qayema',
                'body' => 'If you can read this, notifications reach this phone.',
            ]);
    }

    public function test_the_title_and_message_are_required(): void
    {
        $this->fakeFirebase();
        DeviceToken::factory()->for($this->me)->create();

        Livewire::test(Dashboard::class)
            ->callAction('sendTestNotification', data: ['to' => 'mine', 'title' => '', 'body' => ''])
            ->assertHasActionErrors(['title' => 'required', 'body' => 'required']);

        $this->assertSame([], $this->pushes);
    }

    public function test_the_home_page_still_shows_the_packages_ending_soon(): void
    {
        // The widget loads after the page, so its component is what is there.
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Send test notification')
            ->assertSeeLivewire(PackagesEndingSoon::class);
    }
}
