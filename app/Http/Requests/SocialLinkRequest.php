<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:instagram,x,facebook,tiktok'],
            'url' => ['required', 'url', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $menu = $user->menus()->first();

            if ($menu && $this->isMethod('post')) {
                // Check if we're creating a new social link
                if ($menu->hasReachedSocialLinkLimit()) {
                    $validator->errors()->add(
                        'platform',
                        "You have reached the maximum limit of {$menu->social_link_limit} social links for this menu."
                    );
                }
            }
        });
    }
}
