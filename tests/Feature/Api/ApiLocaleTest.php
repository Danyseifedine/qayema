<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The dashboard sends the language it is showing; the API answers in it.
 */
class ApiLocaleTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_errors_come_back_in_arabic_when_the_dashboard_is_in_arabic(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => '']], ['Accept-Language' => 'ar'])
            ->assertStatus(422)
            ->assertJsonFragment(['name.en' => ['اسم القسم مطلوب بالإنجليزية.']]);
    }

    public function test_laravels_own_rules_are_in_arabic_too(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => str_repeat('x', 256)]], ['Accept-Language' => 'ar'])
            ->assertStatus(422)
            ->assertJsonFragment(['name.en' => ['يجب ألا يتجاوز حقل الاسم 255 حرفاً.']]);
    }

    public function test_english_is_the_default(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => '']])
            ->assertStatus(422)
            ->assertJsonFragment(['name.en' => ['A category name is required in English.']]);
    }

    public function test_a_language_the_server_has_no_text_for_falls_back_to_english(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => '']], ['Accept-Language' => 'fr-FR,fr;q=0.9'])
            ->assertStatus(422)
            ->assertJsonFragment(['name.en' => ['A category name is required in English.']]);
    }

    public function test_a_guest_error_is_in_arabic_as_well(): void
    {
        $this->getJson(route('api.user'), ['Accept-Language' => 'ar'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'يجب تسجيل الدخول.');
    }
}
