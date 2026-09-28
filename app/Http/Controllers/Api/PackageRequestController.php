<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TooManyContactMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequestPackageRequest;
use App\Models\Package;
use App\Services\Contact\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * An owner asking to move to a paid package.
 *
 * Nothing is charged and nothing changes on the restaurant: the request lands
 * in the same inbox as the public contact form, and an admin assigns the
 * package by hand. It therefore shares that form's per-IP daily quota.
 */
class PackageRequestController extends Controller
{
    public function store(RequestPackageRequest $request, ContactService $contacts): JsonResponse|Response
    {
        $user = $request->user();
        // The rules already found it.
        $package = Package::query()->where('slug', $request->validated('package'))->sole();

        $message = trim((string) $request->validated('message'));

        try {
            $contacts->submit([
                'name' => $user->name,
                'email' => $user->email,
                'message' => $message !== ''
                    ? $message
                    : __('Package request: :package', ['package' => $package->getTranslation('name', 'en')]),
                'user_id' => $user->id,
                'package_id' => $package->id,
            ], (string) $request->ip());
        } catch (TooManyContactMessages $exception) {
            $message = __('You have already sent a few requests today. Try again in :hours hours.', [
                'hours' => $exception->retryAfterHours,
            ]);

            return response()->json([
                'message' => $message,
                'code' => 'too_many_requests',
                'retry_after' => $exception->retryAfterHours * 3600,
            ], 429);
        }

        return response()->noContent(201);
    }
}
