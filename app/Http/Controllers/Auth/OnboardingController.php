<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdvanceOnboardingRequest;
use App\Models\User;
use App\Rules\AvailableSlug;
use App\Services\Portal\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const TOTAL_STEPS = User::ONBOARDING_STEPS;

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasCompletedOnboarding()) {
            // An owner who is already set up belongs in the dashboard, not on
            // the marketing landing page. `afterLoginUrl()` is the same rule
            // login uses, so both paths agree on where a finished owner goes.
            return redirect()->away($request->user()->afterLoginUrl());
        }

        $restaurant = $request->user()->restaurant;

        return view('portal.auth.onboarding', [
            'step' => $request->user()->currentOnboardingStep(),
            'totalSteps' => self::TOTAL_STEPS,
            'restaurant' => $restaurant,
        ]);
    }

    public function checkSlug(Request $request): JsonResponse
    {
        try {
            $slug = Str::slug((string) $request->query('slug', ''));

            if (strlen($slug) < 2) {
                return response()->json(['available' => false]);
            }

            $ownId = $request->user()?->restaurant?->id;

            return response()->json(['available' => AvailableSlug::isFree($slug, $ownId)]);
        } catch (\Throwable) {
            return response()->json(['available' => false]);
        }
    }

    public function advance(AdvanceOnboardingRequest $request, OnboardingService $onboarding): JsonResponse
    {
        $user = $request->user()->load('restaurant');
        $dbStep = $user->onboarding_step ?? 0;
        $current = $request->step();

        // Every step after the first edits the restaurant created in step 1.
        if ($current > 0 && ! $user->restaurant) {
            return response()->json(['step' => 1], 200);
        }

        $next = $current + 1;
        $validated = $request->validated();

        switch ($current) {
            case 0: // Step 1: restaurant name + slug + the language the menu is written in
                $onboarding->saveIdentity($user, $validated['name'], $validated['slug'], $validated['main_locale'] ?? null);
                break;

            case 1: // Step 2: country code + phone + currency
                $onboarding->saveContact($user->restaurant, $validated['country_code'] ?? null, $validated['phone'], $validated['currency'], $validated['timezone'] ?? null);
                $onboarding->saveSecondCurrency($user->restaurant, $validated['second_currency'] ?? null, isset($validated['rate']) ? (string) $validated['rate'] : null);
                break;

            case 2: // Step 3: branding (logo required, cover image optional)
                $onboarding->saveBranding($user, $user->restaurant, $validated['logo_key'] ?? null, $validated['cover_image_key'] ?? null);
                $onboarding->complete($user, $user->restaurant);

                return response()->json([
                    'completed' => true,
                    'redirect' => config('app.dashboard_url'),
                ]);

        }

        // Track the furthest step reached; never move the saved pointer backwards.
        $user->update(['onboarding_step' => max($dbStep, $next)]);

        // Advance to the step right after the one processed, capped at the total.
        return response()->json(['step' => min($current + 2, self::TOTAL_STEPS)]);
    }
}
