<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An error an owner's dashboard hit (its `reportError`): what it said, where,
 * and in which release; `network` is requests that got no answer (blocked
 * on the way, or the phone offline), sent once one gets through. The dashboard keeps it short; anything longer is
 * refused rather than logged.
 */
class ClientErrorRequest extends FormRequest
{
    /** A page that broke may have lost its session; the limiter keeps it small. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
            'stack' => ['nullable', 'string', 'max:4000'],
            'source' => ['required', 'string', Rule::in(['render', 'error', 'rejection', 'network'])],
            'page' => ['nullable', 'string', 'max:300'],
            'release' => ['nullable', 'string', 'max:40'],
        ];
    }
}
