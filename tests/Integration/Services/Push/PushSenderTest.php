<?php

namespace Tests\Integration\Services\Push;

use App\Models\DeviceToken;
use App\Services\Push\PushMessage;
use App\Services\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Mockery;
use Tests\Support\FakesFirebase;
use Tests\TestCase;

class PushSenderTest extends TestCase
{
    use FakesFirebase, RefreshDatabase;

    private function message(): PushMessage
    {
        return new PushMessage('A package ends soon', 'Beit Rami (tomorrow).', ['type' => 'packages_ending']);
    }

    public function test_without_a_key_nothing_is_sent(): void
    {
        DeviceToken::factory()->create();
        $this->instance(Messaging::class, Mockery::mock(Messaging::class)->shouldNotReceive('sendMulticast')->getMock());

        $this->assertFalse(PushSender::enabled());
        $this->assertSame(0, app(PushSender::class)->send(DeviceToken::query(), $this->message()));
    }

    public function test_it_sends_to_every_phone_with_the_android_channel_and_icon(): void
    {
        $this->fakeFirebase();
        $phones = DeviceToken::factory()->count(2)->create();

        $reached = app(PushSender::class)->send(DeviceToken::query(), $this->message());

        $this->assertSame(2, $reached);
        $push = $this->pushes[0];
        $this->assertEqualsCanonicalizing($phones->pluck('token')->all(), $push['tokens']);
        $this->assertSame(['title' => 'A package ends soon', 'body' => 'Beit Rami (tomorrow).'], $push['message']['notification']);
        $this->assertSame(['type' => 'packages_ending'], $push['message']['data']);
        $this->assertSame('admin_alerts', $push['message']['android']['notification']['channel_id']);
        $this->assertSame('ic_stat_qayema', $push['message']['android']['notification']['icon']);
        $this->assertSame('high', $push['message']['android']['priority']);
    }

    public function test_a_phone_firebase_no_longer_knows_is_forgotten(): void
    {
        $kept = DeviceToken::factory()->create();
        $gone = DeviceToken::factory()->create();
        $this->fakeFirebase(gone: [$gone->token]);

        $this->assertSame(1, app(PushSender::class)->send(DeviceToken::query(), $this->message()));

        $this->assertModelExists($kept);
        $this->assertModelMissing($gone);
    }

    public function test_no_phones_means_no_call(): void
    {
        $this->fakeFirebase();

        $this->assertSame(0, app(PushSender::class)->send(DeviceToken::query(), $this->message()));
        $this->assertSame([], $this->pushes);
    }

    public function test_firebase_failing_is_logged_never_thrown(): void
    {
        config(['firebase.projects.app.credentials' => 'storage/app/test-key.json']);
        $this->instance(Messaging::class, Mockery::mock(Messaging::class)
            ->shouldReceive('sendMulticast')->andThrow(new \RuntimeException('Firebase is down'))->getMock());
        Log::spy();
        $phone = DeviceToken::factory()->create();

        $this->assertSame(0, app(PushSender::class)->send(DeviceToken::query(), $this->message()));

        Log::shouldHaveReceived('warning')->withArgs(fn (string $text, array $context): bool => $context['error'] === 'Firebase is down');
        $this->assertModelExists($phone);
    }
}
