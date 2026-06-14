<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TempUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'context' => ['nullable', 'string', 'in:logo,cover_image,dish,category,generic'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please choose an image to upload.',
            'file.image' => 'The upload must be an image.',
            'file.mimes' => 'Images must be JPEG, PNG, or WebP.',
            'file.max' => 'Images must not exceed 10 MB.',
            'context.in' => 'Invalid upload context.',
        ];
    }
}
