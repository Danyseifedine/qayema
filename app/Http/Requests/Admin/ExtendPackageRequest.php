<?php

namespace App\Http\Requests\Admin;

use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * More time on a restaurant's package: months added to its end (or to today
 * once it ended), or no end at all (null months).
 */
class ExtendPackageRequest extends FormRequest
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
            'months' => ['present', 'nullable', 'integer', Rule::in(PackageAssigner::DURATIONS)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Restaurant $restaurant */
                $restaurant = $this->route('restaurant');

                if ($restaurant->package_ends_at === null) {
                    $validator->errors()->add('months', 'This package runs forever: there is no end to move.');
                }
            },
        ];
    }
}
