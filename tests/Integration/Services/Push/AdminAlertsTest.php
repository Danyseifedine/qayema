<?php

namespace Tests\Integration\Services\Push;

use App\Models\ContactMessage;
use App\Models\DeviceToken;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Push\AdminAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\Support\FakesFirebase;
use Tests\TestCase;

class AdminAlertsTest extends TestCase
{
    use CreatesOwners, FakesFirebase, RefreshDatabase;

    public function test_a_package_request_names_the_restaurant_and_opens_it(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Beit Rami', 'ar' => 'بيت رامي']]);
        $message = ContactMessage::create([
            'name' => 'Rami', 'email' => null, 'message' => 'More room please', 'ip_address' => '1.2.3.4',
            'user_id' => $restaurant->user_id, 'package_id' => Package::findBySlug('premium')->id,
        ]);

        $push = app(AdminAlerts::class)->forContact($message);

        $this->assertSame('Package request: Premium', $push->title);
        $this->assertSame('Beit Rami asks for Premium.', $push->body);
        $this->assertSame(['type' => 'package_request', 'restaurant_id' => (string) $restaurant->id], $push->data);
    }

    public function test_a_package_request_never_names_the_person(): void
    {
        $owner = User::factory()->create(['name' => 'Rami Haddad', 'email' => 'rami@example.test']);
        $message = ContactMessage::create([
            'name' => 'Rami Haddad', 'email' => 'rami@example.test', 'message' => 'Hi', 'ip_address' => '1.2.3.4',
            'user_id' => $owner->id, 'package_id' => Package::findBySlug('pro')->id,
        ]);

        $push = app(AdminAlerts::class)->forContact($message);

        $this->assertSame('An account with no restaurant yet asks for Pro.', $push->body);
        $this->assertStringNotContainsString('Rami', $push->title.$push->body);
        $this->assertStringNotContainsString('rami@example.test', $push->title.$push->body);
        $this->assertSame(['type' => 'package_request'], $push->data);
    }

    public function test_a_message_from_the_contact_form_is_shortened(): void
    {
        $message = ContactMessage::create([
            'name' => 'Lina', 'email' => 'lina@example.test', 'ip_address' => '1.2.3.4',
            'message' => "Hello,\n\n".str_repeat('I would like a menu. ', 20),
        ]);

        $push = app(AdminAlerts::class)->forContact($message);

        $this->assertSame('New message from Lina', $push->title);
        $this->assertStringStartsWith('Hello, I would like a menu.', $push->body);
        $this->assertLessThanOrEqual(123, mb_strlen($push->body));
        $this->assertSame(['type' => 'contact_message'], $push->data);
    }

    public function test_only_admins_phones_are_told(): void
    {
        $this->fakeFirebase();
        $this->withoutDefer();
        $adminPhone = DeviceToken::factory()->for($this->admin())->create();
        DeviceToken::factory()->for(User::factory())->create();

        app(AdminAlerts::class)->contactReceived(ContactMessage::create([
            'name' => 'Lina', 'email' => null, 'message' => 'Hi', 'ip_address' => '1.2.3.4',
        ]));

        $this->assertCount(1, $this->pushes);
        $this->assertSame([$adminPhone->token], $this->pushes[0]['tokens']);
    }

    public function test_packages_ending_lists_three_and_counts_the_rest(): void
    {
        // 09:00 in Beirut, when the reminder runs.
        $this->travelTo(now('Asia/Beirut')->setTime(9, 0)->utc());
        $this->fakeFirebase();
        DeviceToken::factory()->for($this->admin())->create();
        $ending = collect([
            ['Beit Rami', now()->addHours(3)],
            ['Snack Lina', now()->addDay()->subHour()],
            ['Cedar & Salt', now()->addDays(2)->subHour()],
            ['Abou Joe', now()->addDays(3)->subHour()],
        ])->map(fn (array $row): Restaurant => $this->ownerOn('pro', ['name' => ['en' => $row[0]], 'package_ends_at' => $row[1]]));

        $this->assertSame(1, app(AdminAlerts::class)->packagesEnding($ending));

        $notification = $this->pushes[0]['message']['notification'];
        $this->assertSame('4 packages end soon', $notification['title']);
        $this->assertSame('Beit Rami (today), Snack Lina (tomorrow), Cedar & Salt (in 2 days) and 1 more.', $notification['body']);
        $this->assertSame(['type' => 'packages_ending'], $this->pushes[0]['message']['data']);
    }

    public function test_menu_editing_is_told_once_an_hour_per_restaurant(): void
    {
        $this->fakeFirebase();
        $this->withoutDefer();
        DeviceToken::factory()->for($this->admin())->create();
        $restaurant = $this->owner(['name' => ['en' => 'Beit Rami']]);
        $other = $this->owner();
        $alerts = app(AdminAlerts::class);

        $this->assertTrue($alerts->menuEditing($restaurant));
        $this->assertFalse($alerts->menuEditing($restaurant));
        $this->travel(59)->minutes();
        $this->assertFalse($alerts->menuEditing($restaurant));
        $this->assertTrue($alerts->menuEditing($other));
        $this->travel(2)->minutes();
        $this->assertTrue($alerts->menuEditing($restaurant));

        $this->assertCount(3, $this->pushes);
    }

    public function test_nothing_ending_sends_nothing(): void
    {
        $this->fakeFirebase();
        DeviceToken::factory()->for($this->admin())->create();

        $this->assertSame(0, app(AdminAlerts::class)->packagesEnding(collect()));
        $this->assertSame([], $this->pushes);
    }
}
