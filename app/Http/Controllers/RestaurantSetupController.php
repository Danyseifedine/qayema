<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantSetupController extends Controller
{
    protected ImageOptimizationService $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Show the restaurant setup page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Allow step parameter to go back (explicit step request)
        $requestedStep = $request->query('step');

        if ($requestedStep !== null) {
            // User explicitly requested a step, allow it if valid
            $currentStep = (int) $requestedStep;
            if ($currentStep < 1 || $currentStep > 2) {
                $currentStep = 1;
            }
            // Allow going back to step 1 even if complete
            if ($currentStep == 2 && ! $user->isStep1Complete()) {
                $currentStep = 1;
            }
        } else {
            // Default behavior: show step 2 if step 1 is complete
            $currentStep = $user->isStep1Complete() ? 2 : 1;
        }

        if ($user->isStep2Complete()) {
            return redirect()->route('dashboard');
        }

        return view('restaurant-setup.index', [
            'user' => $user,
            'currentStep' => $currentStep,
        ]);
    }

    /**
     * Handle step 1 submission (restaurant info).
     */
    public function step1(Request $request): RedirectResponse
    {
        $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $user->update($request->only(['restaurant_name', 'phone', 'address']));

        return redirect()->route('restaurant-setup.index')->with('success', 'Step 1 completed! Now upload your logo and cover image.');
    }

    /**
     * Handle step 2 submission (images).
     */
    public function step2(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [];
        if (! $user->hasMedia('logo')) {
            $rules['logo'] = ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        } else {
            $rules['logo'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        }

        if (! $user->hasMedia('cover_image')) {
            $rules['cover_image'] = ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        } else {
            $rules['cover_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        }

        $request->validate($rules);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $optimizedPath = $this->imageService->optimizeLogo($request->file('logo'));
            $user->clearMediaCollection('logo');
            $user->addMedia($optimizedPath)
                ->usingName('logo')
                ->toMediaCollection('logo');
            @unlink($optimizedPath);
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $optimizedPath = $this->imageService->optimizeCoverImage($request->file('cover_image'));
            $user->clearMediaCollection('cover_image');
            $user->addMedia($optimizedPath)
                ->usingName('cover_image')
                ->toMediaCollection('cover_image');
            @unlink($optimizedPath);
        }

        if ($user->isRestaurantSetupComplete()) {
            return redirect()->route('dashboard')->with('success', 'Restaurant setup completed! You can now create your first menu.');
        }

        return redirect()->route('restaurant-setup.index')->with('error', 'Please upload both logo and cover image.');
    }
}
