<?php

namespace App\Http\Requests\Admin;

use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A restaurant put on another package from today, for some months or
 * forever (null months), and with it, when given, what it pays a month
 * (null: the package's price) and whether through Whish.
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
            'price_cents' => UpdateBillingRequest::priceRules(),
            'pays_with_whish' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The price and Whish sent with the change, only the ones sent: a
     * change without them leaves both as the package change makes them.
     *
     * @return array{price_cents?: int|null, pays_with_whish?: bool}
     */
    public function billing(): array
    {
        return [
            ...($this->has('price_cents') ? ['price_cents' => $this->validated('price_cents')] : []),
            ...($this->has('pays_with_whish') ? ['pays_with_whish' => $this->boolean('pays_with_whish')] : []),
        ];
    }
}
