<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The admin app's AI page: today, or the last WritingHelpLog::DAYS days.
 */
class WritingHelpUsageRequest extends FormRequest
{
    public const TODAY = 'today';

    public const MONTH = '30d';

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
            'range' => ['nullable', Rule::in([self::TODAY, self::MONTH])],
        ];
    }

    public function range(): string
    {
        return $this->string('range')->value() ?: self::TODAY;
    }
}
