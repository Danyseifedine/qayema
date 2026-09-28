<?php

namespace Tests\Feature\Requests;

use App\Enums\Feature;
use App\Services\Qr\QrStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** App\Http\Requests\QrSettingsRequest, through PUT /api/qr. */
class QrSettingsRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::QrStudio);
    }

    /** @return array<string, mixed> */
    private static function design(): array
    {
        return [
            'dot_style' => 'classy',
            'dot_color' => '#1F6FEB',
            'dot_gradient' => null,
            'gradient_type' => 'linear',
            'corner_style' => 'dot',
            'corner_color' => '#111418',
            'eye_style' => 'square',
            'eye_color' => '#000000',
            'background' => '#FFFFFF',
            'logo' => false,
            'logo_size' => 'small',
            'card_theme' => 'brand',
            'title' => null,
            'subtitle' => null,
            'cta' => null,
            'show_url' => true,
        ];
    }

    /** @return array<string, array{0: string}> */
    public static function requiredFields(): array
    {
        $fields = ['dot_style', 'dot_color', 'gradient_type', 'corner_style', 'corner_color', 'eye_style', 'eye_color', 'background', 'logo', 'logo_size', 'card_theme', 'show_url'];

        return array_combine($fields, array_map(fn (string $field): array => [$field], $fields));
    }

    /** @return array<string, array{0: string, 1: mixed, 2: string}> */
    public static function badValues(): array
    {
        return [
            'unknown dot style' => ['dot_style', 'hearts', 'The selected dot style is invalid.'],
            'dot colour without #' => ['dot_color', '1F6FEB', 'The dot color field format is invalid.'],
            'short dot colour' => ['dot_color', '#FFF', 'The dot color field format is invalid.'],
            'named dot colour' => ['dot_color', 'red', 'The dot color field format is invalid.'],
            'dot colour not a string' => ['dot_color', 123456, 'The dot color field must be a string.'],
            'gradient not hex' => ['dot_gradient', 'blue', 'The dot gradient field format is invalid.'],
            'gradient with alpha' => ['dot_gradient', '#1F6FEB80', 'The dot gradient field format is invalid.'],
            'unknown gradient type' => ['gradient_type', 'conic', 'The selected gradient type is invalid.'],
            'unknown corner style' => ['corner_style', 'rounded', 'The selected corner style is invalid.'],
            'corner colour not hex' => ['corner_color', '#GGGGGG', 'The corner color field format is invalid.'],
            'unknown eye style' => ['eye_style', 'extra-rounded', 'The selected eye style is invalid.'],
            'eye colour not hex' => ['eye_color', 'rgb(0,0,0)', 'The eye color field format is invalid.'],
            'background not hex' => ['background', 'transparent', 'The background field format is invalid.'],
            'logo not a boolean' => ['logo', 'yes', 'The logo field must be true or false.'],
            'logo size a number' => ['logo_size', 0.35, 'The selected logo size is invalid.'],
            'unknown logo size' => ['logo_size', 'huge', 'The selected logo size is invalid.'],
            'unknown card theme' => ['card_theme', 'sepia', 'The selected card theme is invalid.'],
            'title too long' => ['title', str_repeat('t', 61), 'The title field must not be greater than 60 characters.'],
            'title not a string' => ['title', ['Menu'], 'The title field must be a string.'],
            'subtitle too long' => ['subtitle', str_repeat('s', 81), 'The subtitle field must not be greater than 80 characters.'],
            'cta too long' => ['cta', str_repeat('c', 61), 'The cta field must not be greater than 60 characters.'],
            'show url not a boolean' => ['show_url', 'maybe', 'The show url field must be true or false.'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function test_each_required_field_must_be_sent(string $field): void
    {
        $restaurant = $this->owner();
        $body = self::design();
        unset($body[$field]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), $body)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field])
            ->assertJsonMissingValidationErrors(array_diff(array_keys(self::design()), [$field]));

        $this->assertNull($restaurant->refresh()->qr_settings);
    }

    #[DataProvider('badValues')]
    public function test_a_bad_value_is_refused(string $field, mixed $value, string $message): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), [...self::design(), $field => $value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);

        $this->assertNull($restaurant->refresh()->qr_settings);
    }

    public function test_every_allowed_shape_and_theme_is_accepted(): void
    {
        $restaurant = $this->owner();
        $user = $restaurant->user;

        foreach (QrStyle::DOT_STYLES as $style) {
            $this->actingAs($user)->putJson(route('api.qr.update'), [...self::design(), 'dot_style' => $style])->assertOk();
        }
        foreach (QrStyle::CORNER_STYLES as $style) {
            $this->actingAs($user)->putJson(route('api.qr.update'), [...self::design(), 'corner_style' => $style])->assertOk();
        }
        foreach (QrStyle::EYE_STYLES as $style) {
            $this->actingAs($user)->putJson(route('api.qr.update'), [...self::design(), 'eye_style' => $style])->assertOk();
        }
        foreach (QrStyle::GRADIENT_TYPES as $type) {
            $this->actingAs($user)->putJson(route('api.qr.update'), [...self::design(), 'gradient_type' => $type])->assertOk();
        }
        foreach (array_keys(QrStyle::LOGO_SIZES) as $size) {
            $this->actingAs($user)->putJson(route('api.qr.update'), [...self::design(), 'logo_size' => $size])->assertOk();
        }
        foreach (QrStyle::CARD_THEMES as $theme) {
            $this->actingAs($user)->putJson(route('api.qr.update'), [...self::design(), 'card_theme' => $theme])->assertOk()
                ->assertJsonPath('data.settings.card_theme', $theme);
        }
    }

    public function test_the_limits_themselves_are_accepted_and_stored(): void
    {
        $restaurant = $this->owner();
        $body = [
            ...self::design(),
            'dot_gradient' => '#abcdef',
            'logo' => true,
            'show_url' => false,
            'title' => str_repeat('t', 60),
            'subtitle' => str_repeat('s', 80),
            'cta' => str_repeat('c', 60),
        ];

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), $body)
            ->assertOk()
            ->assertJsonPath('data.settings.dot_gradient', '#abcdef')
            ->assertJsonPath('data.settings.title', str_repeat('t', 60));

        $this->assertSame($body, $restaurant->refresh()->qr_settings);
    }

    /** The `boolean` rule's own spellings of on and off all pass. */
    public function test_boolean_fields_accept_the_rules_spellings(): void
    {
        $restaurant = $this->owner();

        foreach ([true, false, 1, 0, '1', '0'] as $value) {
            $this->actingAs($restaurant->user)
                ->putJson(route('api.qr.update'), [...self::design(), 'logo' => $value, 'show_url' => $value])
                ->assertOk();
        }
    }

    /** Only the declared keys are kept; anything else in the body is dropped. */
    public function test_an_undeclared_key_is_not_stored(): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.qr.update'), [...self::design(), 'url' => 'https://evil.example'])
            ->assertOk();

        $this->assertArrayNotHasKey('url', $restaurant->refresh()->qr_settings);
        $this->assertSame(array_keys(self::design()), array_keys($restaurant->qr_settings));
    }

    /** The request authorizes everyone signed in; the controller then checks the restaurant. */
    public function test_a_user_without_a_restaurant_is_validated_first_then_refused(): void
    {
        $user = $this->userWithoutRestaurant();

        $this->actingAs($user)
            ->putJson(route('api.qr.update'), ['dot_style' => 'hearts'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dot_style');

        $this->actingAs($user)
            ->putJson(route('api.qr.update'), self::design())
            ->assertForbidden()
            ->assertExactJson(['message' => 'Create your restaurant before designing a QR code.', 'code' => 'forbidden']);
    }

    public function test_a_guest_gets_a_401(): void
    {
        $this->putJson(route('api.qr.update'), self::design())->assertUnauthorized();
    }
}
