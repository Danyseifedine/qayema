<?php

namespace Tests\Integration\Factories;

use App\Enums\MenuEventType;
use App\Models\ContactMessage;
use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The newer factories build rows the app would really write, with the
 * relations they need.
 */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_contact_message_is_a_plain_public_enquiry(): void
    {
        $message = ContactMessage::factory()->create();

        $this->assertModelExists($message);
        $this->assertNull($message->user_id);
        $this->assertNull($message->package_id);
        $this->assertFalse($message->isPackageRequest());
        $this->assertNotSame('', $message->name);
        $this->assertNotFalse(filter_var($message->email, FILTER_VALIDATE_EMAIL));
        $this->assertNotFalse(filter_var($message->ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        $this->assertNotSame('', $message->message);
    }

    public function test_a_package_request_defaults_to_pro_from_a_new_user(): void
    {
        $message = ContactMessage::factory()->packageRequest()->create();

        $this->assertTrue($message->isPackageRequest());
        $this->assertSame('pro', $message->package->slug);
        $this->assertInstanceOf(User::class, $message->user);
        $this->assertModelExists($message->user);
    }

    public function test_a_package_request_can_name_its_package(): void
    {
        $premium = Package::findBySlug('premium');

        $message = ContactMessage::factory()->packageRequest($premium)->create();

        $this->assertTrue($message->package->is($premium));
    }

    public function test_a_menu_event_is_a_whatsapp_tap_on_a_new_restaurant(): void
    {
        $event = MenuEvent::factory()->create()->fresh();

        $this->assertInstanceOf(Restaurant::class, $event->restaurant);
        $this->assertSame(MenuEventType::WhatsApp, $event->type);
        $this->assertSame(40, strlen($event->session_id));
        $this->assertNull($event->dish_id);
        $this->assertNull($event->category_id);
        $this->assertNull($event->value);
        $this->assertNotNull($event->occurred_at);
    }

    public function test_a_menu_session_is_a_direct_mobile_visit(): void
    {
        $session = MenuSession::factory()->create()->fresh();

        $this->assertInstanceOf(Restaurant::class, $session->restaurant);
        $this->assertFalse($session->via_qr);
        $this->assertSame('mobile', $session->device_type);
        $this->assertSame('en', $session->locale);
        $this->assertSame(40, strlen($session->session_id));
        $this->assertNotNull($session->viewed_at);
    }

    public function test_a_menu_session_via_qr_is_a_scan(): void
    {
        $session = MenuSession::factory()->viaQr()->create()->fresh();

        $this->assertTrue($session->via_qr);
        $this->assertDatabaseHas('menu_sessions', ['id' => $session->id, 'via_qr' => true]);
    }

    public function test_a_social_account_is_a_google_link_for_a_new_user(): void
    {
        $account = SocialAccount::factory()->create()->fresh();

        $this->assertInstanceOf(User::class, $account->user);
        $this->assertSame('google', $account->provider);
        $this->assertMatchesRegularExpression('/^\d{9}$/', $account->provider_user_id);
        $this->assertSame('access-token', $account->access_token);
        $this->assertNull($account->refresh_token);
        $this->assertNull($account->token_expires_at);
    }

    public function test_social_account_tokens_are_encrypted_at_rest(): void
    {
        $account = SocialAccount::factory()->create(['refresh_token' => 'refresh-me']);

        $raw = DB::table('social_accounts')->where('id', $account->id)->first();

        $this->assertNotSame('access-token', $raw->access_token);
        $this->assertNotSame('refresh-me', $raw->refresh_token);
        $this->assertSame('access-token', Crypt::decryptString($raw->access_token));
        $this->assertSame('refresh-me', Crypt::decryptString($raw->refresh_token));
        $this->assertSame('refresh-me', $account->fresh()->refresh_token);
    }

    public function test_social_accounts_get_distinct_provider_ids(): void
    {
        $ids = SocialAccount::factory()->count(5)->create()->pluck('provider_user_id');

        $this->assertCount(5, $ids->unique());
    }
}
