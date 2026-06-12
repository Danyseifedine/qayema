<?php

namespace App\Http\Requests;

use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Http\FormRequest;

class SocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $link = $this->route('socialLink');

        return $link instanceof RestaurantSocialLink
            ? (bool) $this->user()?->can('update', $link)
            : (bool) $this->user()?->can('create', RestaurantSocialLink::class);
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:instagram,x,facebook,tiktok,whatsapp'],
            'url' => ['required', 'url', 'max:255', 'regex:/^https?:\/\//i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.required' => 'Please choose a platform.',
            'url.required' => 'Please enter the link URL.',
            'url.url' => 'Please enter a valid URL.',
            'url.regex' => 'Only http or https links are allowed.',
        ];
    }
}
