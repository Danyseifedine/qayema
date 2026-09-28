<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\UpdateAppearanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The corners of App\Http\Requests\UpdateAppearanceRequest that the endpoint
 * itself cannot reach (the rest is covered by Tests\Feature\Api\AppearanceTest).
 */
class UpdateAppearanceRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function requestFor(?User $user, array $input): UpdateAppearanceRequest
    {
        $request = UpdateAppearanceRequest::create('/api/appearance', 'PUT', $input);
        $request->setContainer($this->app);
        $request->setUserResolver(fn (): ?User => $user);

        return $request;
    }

    /** Over HTTP, no restaurant stops at authorize() before any rule runs. */
    public function test_a_user_without_a_restaurant_is_refused_before_validation(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->putJson(route('api.appearance.update'), ['settings' => ['nope' => 1], 'fonts' => 'x'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'This action is unauthorized.', 'code' => 'forbidden']);
    }

    public function test_it_does_not_authorize_a_user_without_a_restaurant_or_a_design(): void
    {
        $this->assertFalse($this->requestFor($this->userWithoutRestaurant(), [])->authorize());
        $this->assertFalse($this->requestFor($this->owner()->user, [])->authorize());
        $this->assertFalse($this->requestFor(null, [])->authorize());
        $this->assertTrue($this->requestFor($this->published()->user, [])->authorize());
    }

    /**
     * Should the check ever run without a restaurant, the after-hook adds
     * nothing rather than failing on a null: the undeclared-key guard needs a
     * design to compare against.
     */
    public function test_the_undeclared_key_guard_is_a_no_op_without_a_restaurant(): void
    {
        $request = $this->requestFor($this->userWithoutRestaurant(), ['settings' => ['anything' => '#000000']]);

        $this->assertSame(['settings' => ['sometimes', 'array'], 'fonts' => ['sometimes', 'array']], $request->rules());

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->passes());
        $this->assertSame([], $validator->errors()->all());
    }

    /** With a restaurant, the same hook names the key the design does not declare. */
    public function test_the_undeclared_key_guard_names_the_key_with_a_restaurant(): void
    {
        $request = $this->requestFor($this->published()->user, ['settings' => ['anything' => '#000000']]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertSame(['settings.anything' => ['This template has no anything setting.']], $validator->errors()->toArray());
    }
}
