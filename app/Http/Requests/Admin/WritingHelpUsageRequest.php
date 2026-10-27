<?php

namespace App\Http\Requests\Admin;

use App\Services\Ai\WritingHelpLog;
use App\Services\Push\AdminAlerts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The admin app's AI page: today, the last WritingHelpLog::DAYS days, or
 * this month so far. Days are the admins' own, in Lebanon.
 */
class WritingHelpUsageRequest extends FormRequest
{
    public const TODAY = 'today';

    public const LAST_DAYS = '30d';

    /** From the 1st of this month. */
    public const MONTH = 'month';

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
            'range' => ['nullable', Rule::in([self::TODAY, self::LAST_DAYS, self::MONTH])],
        ];
    }

    public function range(): string
    {
        return $this->string('range')->value() ?: self::TODAY;
    }

    /** The range's first day, a calendar day in Lebanon. */
    public function since(): string
    {
        $today = now(AdminAlerts::TIMEZONE);

        return match ($this->range()) {
            self::MONTH => $today->startOfMonth()->toDateString(),
            self::LAST_DAYS => $today->subDays(WritingHelpLog::DAYS - 1)->toDateString(),
            default => $today->toDateString(),
        };
    }
}
