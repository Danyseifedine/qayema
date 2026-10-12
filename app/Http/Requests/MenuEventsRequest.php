<?php

namespace App\Http\Requests;

use App\Enums\MenuEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuEventsRequest extends FormRequest
{
    /** The public menu is open to guests; abuse is handled by the limiter. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape only. Whether a dish or category belongs to this restaurant is
     * settled in MenuEventRecorder, which drops what does not. A batch holds
     * the page's visit, its actions, or both.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'visitor' => ['nullable', 'string', 'regex:/^[a-f0-9]{32}$/'],
            'visit' => ['sometimes', 'array:locale,qr'],
            'visit.locale' => ['nullable', 'string', 'max:10'],
            'visit.qr' => ['sometimes', 'boolean'],
            'events' => ['required_without:visit', 'array', 'max:25'],
            'events.*.type' => ['required', 'string', Rule::enum(MenuEventType::class)],
            'events.*.dish_id' => ['nullable', 'integer', 'min:1'],
            'events.*.category_id' => ['nullable', 'integer', 'min:1'],
            'events.*.value' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * The guest's anonymous id: the one their browser keeps (menu-track.js),
     * or, from a browser that sent none, a one-way hash of its address and
     * user agent, never stored as they are.
     */
    public function visitor(): string
    {
        return $this->validated('visitor') ?? md5($this->ip().$this->userAgent());
    }
}
