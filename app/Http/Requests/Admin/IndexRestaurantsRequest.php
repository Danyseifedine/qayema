<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The admin app's restaurant list: a search and one of its filters.
 */
class IndexRestaurantsRequest extends FormRequest
{
    /** Every restaurant, newest first. */
    public const ALL = 'all';

    /** Opened in the last NEW_DAYS days, newest first. */
    public const NEW = 'new';

    public const NEW_DAYS = 7;

    /** Menus that guests can open. */
    public const ACTIVE = 'active';

    /** Menus switched off. */
    public const INACTIVE = 'inactive';

    /** A package in force with an end date, the soonest end first. */
    public const ENDING = 'ending';

    /** A package that has ended, the latest end first. */
    public const ENDED = 'ended';

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
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', Rule::in([self::ALL, self::NEW, self::ACTIVE, self::INACTIVE, self::ENDING, self::ENDED])],
        ];
    }

    public function filter(): string
    {
        return $this->string('filter')->value() ?: self::ALL;
    }

    public function search(): ?string
    {
        return trim($this->string('search')->value()) ?: null;
    }
}
