<?php

namespace Tests\Feature\Console;

use App\Models\DeviceToken;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\Support\FakesFirebase;
use Tests\TestCase;

class RemindPackagesEndingTest extends TestCase
{
    use CreatesOwners, FakesFirebase, RefreshDatabase;

    public function test_it_tells_the_admins_about_packages_ending_within_three_days(): void
    {
        $this->fakeFirebase();
        DeviceToken::factory()->for($this->admin())->create();
        $this->ownerOn('pro', ['name' => ['en' => 'Beit Rami'], 'package_ends_at' => now()->addDays(2)]);
        $this->ownerOn('pro', ['name' => ['en' => 'Later'], 'package_ends_at' => now()->addDays(5)]);
        $this->ownerOn('pro', ['name' => ['en' => 'Ended'], 'package_ends_at' => now()->subDay()]);
        $this->ownerOn('pro', ['name' => ['en' => 'Forever'], 'package_ends_at' => null]);

        $this->artisan('packages:remind-ending')
            ->assertSuccessful()
            ->expectsOutputToContain('1 packages end within 3 days; told 1 phones.');

        $this->assertSame('A package ends soon', $this->pushes[0]['message']['notification']['title']);
        $this->assertStringStartsWith('Beit Rami', $this->pushes[0]['message']['notification']['body']);
    }

    public function test_nothing_ending_sends_nothing(): void
    {
        $this->fakeFirebase();
        DeviceToken::factory()->for($this->admin())->create();

        $this->artisan('packages:remind-ending')->assertSuccessful()->expectsOutputToContain('0 packages end');
        $this->assertSame([], $this->pushes);
    }

    public function test_it_runs_each_morning_in_beirut(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command, 'packages:remind-ending'));

        $this->assertNotNull($event);
        $this->assertSame('0 9 * * *', $event->expression);
        $this->assertSame('Asia/Beirut', $event->timezone);
    }
}
