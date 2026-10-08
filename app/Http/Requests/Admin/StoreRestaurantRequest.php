<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Rules\AvailableSlug;
use App\Rules\Username;
use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * A restaurant opened from the admin app: the owner's account (a username,
 * an email or both, and a password) and the restaurant's name, menu link and
 * package, made together.
 */
class StoreRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route already requires an admin.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // The link as the menu will use it; left empty, made from the name.
        $slug = $this->string('slug')->trim()->value() ?: $this->string('name')->value();

        $this->merge([
            'slug' => Str::slug($slug),
            'username' => User::normalizeUsername($this->input('username')),
            'email' => trim((string) $this->input('email')) ?: null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'min:2', 'max:255', new AvailableSlug],
            'owner_name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'required_without:email', 'string', 'max:30', new Username],
            'email' => ['nullable', 'required_without:username', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')],
            // Null runs forever.
            'months' => ['nullable', 'integer', Rule::in(PackageAssigner::DURATIONS)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required_without' => 'Give the owner a username or an email to sign in with.',
            'email.required_without' => 'Give the owner a username or an email to sign in with.',
        ];
    }
}
