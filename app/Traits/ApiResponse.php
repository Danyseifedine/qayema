<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Shared JSON envelope so every controller responds with the same shape:
 *   success → { "success": true,  "message"?: string, "data"?: mixed }
 *   error   → { "success": false, "message"?: string, "errors"?: { field: string[] } }
 *
 * The `errors` shape mirrors Laravel's native validation errors, so the same
 * client code handles both manual and auto-validation failures.
 */
trait ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    protected function success(?array $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], static fn ($value): bool => $value !== null), $status);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     * @param  array<string, mixed>|null  $data  machine-readable extras (e.g. retry timing) for the client to localize
     */
    protected function error(?string $message = null, array $errors = [], int $status = 422, ?array $data = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors !== [] ? $errors : null,
            'data' => $data,
        ], static fn ($value): bool => $value !== null), $status);
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    protected function created(?array $data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
