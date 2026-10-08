<?php

namespace App\Http\Requests\Admin;

use App\Models\DeviceToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The admin app's phone, to get notifications on: the address Firebase gave
 * it, and whether it is Android or iOS.
 */
class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route already requires an admin.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(DeviceToken::PLATFORMS)],
        ];
    }
}
