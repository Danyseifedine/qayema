<?php

namespace App\Http\Requests;

use App\Services\Analytics\MenuStats;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', Rule::in(array_keys(MenuStats::RANGES))],
        ];
    }

    /** The range asked for, 30 days when none was. */
    public function range(): string
    {
        return (string) $this->query('range', '30d');
    }
}
