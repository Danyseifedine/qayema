<?php

namespace App\Http\Requests;

use App\Services\Media\UploadLimits;
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
            // `dimensions` reads the image header (no full decode) so it rejects a
            // decompression bomb — a small file declaring huge pixel dimensions —
            // before the optimizer ever loads it into memory. 6000px is far above
            // what any preset needs (the widest is a 1920px cover).
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:max_width=6000,max_height=6000'],
            'context' => ['nullable', 'string', 'in:logo,cover_image,dish,generic'],
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
            'file.max' => UploadLimits::tooLargeMessage(),
            // PHP drops a file larger than `upload_max_filesize` before any
            // rule runs, and Laravel's built-in `uploaded` rule fires instead.
            // Without this the owner sees the framework's bare "The file
            // failed to upload." with no hint that size was the problem — and
            // the limit quoted has to be the one PHP is really enforcing.
            'file.uploaded' => UploadLimits::tooLargeMessage(),
            'file.dimensions' => 'Images must be at most 6000 × 6000 pixels.',
            'context.in' => 'Invalid upload context.',
        ];
    }
}
