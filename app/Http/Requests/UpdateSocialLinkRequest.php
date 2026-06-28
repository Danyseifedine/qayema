<?php

namespace App\Http\Requests;

use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocialLinkRequest extends FormRequest
{
    /**
     * Authorization is enforced by the policy in the controller against the
     * resolved link, so the request itself only validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;

        return [
            'platform' => [
                'required',
                'string',
                Rule::in(RestaurantSocialLink::PLATFORMS),
                Rule::unique('restaurant_social_links', 'platform')
                    ->where('restaurant_id', $restaurantId)
                    ->ignore($this->route('socialLink')),
            ],
            'url' => ['required', 'string', 'url:http,https', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.unique' => __('You already have a link for this platform.'),
        ];
    }
}
