<?php

namespace App\Http\Requests\Admin;

use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A restaurant put on another package from today, for some months or
 * forever (null months).
 */
class ChangePackageRequest extends FormRequest
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
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')],
            'months' => ['nullable', 'integer', Rule::in(PackageAssigner::DURATIONS)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
