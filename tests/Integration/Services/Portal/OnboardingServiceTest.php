<?php

namespace Tests\Integration\Services\Portal;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Media\MediaService;
use App\Services\Portal\OnboardingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * OnboardingService called directly, one step at a time.
 */
class OnboardingServiceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function tearDown(): void
    {
        $temp = app(MediaService::class)->tempRoot();

        foreach (glob($temp.'/*/*.webp') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function service(): OnboardingService
    {
        return app(OnboardingService::class);
    }

    /** Park a real image in the user's temp area, as an upload would. */
    private function parkImage(User $user, string $key): string
    {
        $path = app(MediaService::class)->tempPath($user->id, $key);
        @mkdir(dirname($path), 0755, true);
        imagewebp(imagecreatetruecolor(20, 20), $path);

        return $path;
    }

    public function test_step_one_creates_the_restaurant_in_english_with_arabic_second(): void
    {
        $user = $this->userWithoutRestaurant();

        $this->service()->saveIdentity($user, 'Olive Tree', 'olive-tree', 'ar');

        $restaurant = $user->fresh()->restaurant;
        $this->assertNotNull($restaurant);
        $this->assertSame(['en' => 'Olive Tree'], $restaurant->getTranslations('name'));
        $this->assertSame('olive-tree', $restaurant->slug);
        $this->assertSame('ar', $restaurant->second_locale);
        $this->assertSame('ar', $restaurant->default_locale);
        $this->assertSame(Package::default()->id, $restaurant->package_id);
    }

    public function test_step_one_opens_in_english_for_anything_else(): void
    {
        foreach (['fr', null, ''] as $index => $locale) {
            $user = $this->userWithoutRestaurant();

            $this->service()->saveIdentity($user, 'Place '.$index, 'place-'.$index, $locale);

            $this->assertSame('en', $user->fresh()->restaurant->default_locale, var_export($locale, true));
        }
    }

    public function test_step_one_again_updates_the_same_restaurant_and_keeps_other_languages(): void
    {
        $user = $this->userWithoutRestaurant();
        $this->service()->saveIdentity($user, 'Olive', 'olive', 'en');
        $restaurant = $user->fresh()->restaurant;
        $restaurant->setTranslation('name', 'ar', 'زيتون')->save();

        $this->service()->saveIdentity($user->fresh(), 'Olive Bar', 'olive-bar', 'en');

        $this->assertSame(1, Restaurant::query()->where('user_id', $user->id)->count());
        $restaurant = $restaurant->fresh();
        $this->assertSame(['en' => 'Olive Bar', 'ar' => 'زيتون'], $restaurant->getTranslations('name'));
        $this->assertSame('olive-bar', $restaurant->slug);
    }

    public function test_step_one_again_keeps_arabic_as_the_opening_language(): void
    {
        $user = $this->userWithoutRestaurant();
        $this->service()->saveIdentity($user, 'Olive', 'olive', 'ar');

        // A refresh or going back resubmits the very same answers. Free has
        // no multiple_languages, which must not turn the choice into English.
        $this->service()->saveIdentity($user->fresh(), 'Olive', 'olive', 'ar');

        $this->assertSame('ar', $user->fresh()->restaurant->default_locale);
    }

    public function test_step_one_again_refuses_a_language_the_restaurant_does_not_have(): void
    {
        $user = $this->userWithoutRestaurant();
        $this->service()->saveIdentity($user, 'Olive', 'olive', 'ar');
        $user->fresh()->restaurant->update(['second_locale' => null]);

        $this->service()->saveIdentity($user->fresh(), 'Olive', 'olive', 'ar');

        $this->assertSame('en', $user->fresh()->restaurant->default_locale);
    }

    public function test_step_two_saves_the_contact_details(): void
    {
        $restaurant = $this->owner(['country_code' => 'LB', 'phone' => '70000000', 'currency' => 'USD']);

        $this->service()->saveContact($restaurant, 'AE', '501234567', 'AED');

        $this->assertSame(
            ['country_code' => 'AE', 'phone' => '501234567', 'currency' => 'AED'],
            $restaurant->fresh()->only('country_code', 'phone', 'currency'),
        );

        $this->service()->saveContact($restaurant, null, '501234567', 'AED');

        $this->assertNull($restaurant->fresh()->country_code);
    }

    public function test_step_three_promotes_both_uploads_into_their_collections(): void
    {
        $restaurant = $this->owner();
        $user = $restaurant->user;
        $logo = $this->parkImage($user, 'logo-key');
        $cover = $this->parkImage($user, 'cover-key');

        $this->service()->saveBranding($user, $restaurant, 'logo-key', 'cover-key');

        $restaurant = $restaurant->fresh();
        $this->assertCount(1, $restaurant->getMedia('logo'));
        $this->assertCount(1, $restaurant->getMedia('cover_image'));
        $this->assertSame('logo', $restaurant->getFirstMedia('logo')->name);
        $this->assertSame('cover-image', $restaurant->getFirstMedia('cover_image')->name);
        $this->assertFileDoesNotExist($logo, 'The temp file is moved, not copied.');
        $this->assertFileDoesNotExist($cover);
    }

    public function test_step_three_replaces_rather_than_adds(): void
    {
        $restaurant = $this->owner();
        $user = $restaurant->user;
        $this->parkImage($user, 'first');
        $this->service()->saveBranding($user, $restaurant, 'first', null);
        $firstId = $restaurant->fresh()->getFirstMedia('logo')->id;

        $this->parkImage($user, 'second');
        $this->service()->saveBranding($user, $restaurant->fresh(), 'second', null);

        $media = $restaurant->fresh()->getMedia('logo');
        $this->assertCount(1, $media);
        $this->assertNotSame($firstId, $media->first()->id);
    }

    public function test_step_three_skips_missing_keys_and_someone_elses_upload(): void
    {
        $restaurant = $this->owner();
        $stranger = $this->userWithoutRestaurant();
        $this->parkImage($stranger, 'theirs');

        $this->service()->saveBranding($restaurant->user, $restaurant, 'theirs', 'never-uploaded');
        $this->service()->saveBranding($restaurant->user, $restaurant, null, '');

        $this->assertCount(0, $restaurant->fresh()->getMedia('logo'));
        $this->assertCount(0, $restaurant->fresh()->getMedia('cover_image'));
        $this->assertFileExists(app(MediaService::class)->tempPath($stranger->id, 'theirs'));
    }

    public function test_finishing_marks_the_user_done_and_welcomes_them_once(): void
    {
        Mail::fake();
        config(['mail.welcome' => true]);
        $this->travelTo(CarbonImmutable::parse('2026-09-28 10:00:00', 'UTC'));
        $restaurant = $this->owner();
        $user = $restaurant->user;
        $user->forceFill(['onboarding_step' => 2, 'onboarding_completed_at' => null])->save();

        $this->service()->complete($user, $restaurant);

        $user = $user->fresh();
        $this->assertSame(User::ONBOARDING_STEPS, $user->onboarding_step);
        $this->assertSame('2026-09-28 10:00:00', $user->onboarding_completed_at->format('Y-m-d H:i:s'));
        Mail::assertQueued(WelcomeRestaurantOwner::class, fn (WelcomeRestaurantOwner $mail): bool => $mail->hasTo($user->email));
        Mail::assertQueuedCount(1);

        $this->travel(1)->day();
        $this->service()->complete($user, $restaurant);

        Mail::assertQueuedCount(1);
        $this->assertSame('2026-09-28 10:00:00', $user->fresh()->onboarding_completed_at->format('Y-m-d H:i:s'), 'The first finish is kept.');
    }

    public function test_no_welcome_email_while_it_is_switched_off(): void
    {
        // Off by default (MAIL_WELCOME); finishing still completes onboarding.
        Mail::fake();
        $restaurant = $this->owner();
        $user = $restaurant->user;
        $user->forceFill(['onboarding_step' => 2, 'onboarding_completed_at' => null])->save();

        $this->service()->complete($user, $restaurant);

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
        Mail::assertNothingOutgoing();
    }

    public function test_finishing_leaves_the_restaurant_without_a_template(): void
    {
        Mail::fake();
        $restaurant = $this->owner();

        $this->service()->complete($restaurant->user, $restaurant);

        $this->assertNull($restaurant->fresh()->template_id);
    }
}
