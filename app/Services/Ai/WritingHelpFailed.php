<?php

namespace App\Services\Ai;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** The writing help could not get an answer; the owner can try again. */
class WritingHelpFailed extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => __('Writing help is not available right now. Try again in a moment.'),
            'code' => 'writing_help_failed',
        ], 503);
    }
}
