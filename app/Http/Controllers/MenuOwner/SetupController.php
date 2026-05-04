<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantSetupStep1Request;
use App\Services\ImageOptimizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __construct(private readonly ImageOptimizationService $imageService) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isStep2Complete()) {
            return redirect()->route('dashboard');
        }

        $requestedStep = $request->query('step');

        if ($requestedStep !== null) {
            $currentStep = (int) $requestedStep;
            if ($currentStep < 1 || $currentStep > 2) {
                $currentStep = 1;
            }
            if ($currentStep === 2 && ! $user->isStep1Complete()) {
                $currentStep = 1;
            }
        } else {
            $currentStep = $user->isStep1Complete() ? 2 : 1;
        }

        return view('menu-owner.setup.index', [
            'user' => $user,
            'currentStep' => $currentStep,
        ]);
    }

    public function step1(RestaurantSetupStep1Request $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('restaurant-setup.index')
            ->with('success', 'Step 1 completed! Now upload your logo and cover image.');
    }

    public function step2(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'logo' => $user->hasMedia('logo')
                ? ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120']
                : ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'cover_image' => $user->hasMedia('cover_image')
                ? ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120']
                : ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];

        $request->validate($rules);

        if ($request->hasFile('logo')) {
            $optimizedPath = $this->imageService->optimizeLogo($request->file('logo'));
            $user->clearMediaCollection('logo');
            $user->addMedia($optimizedPath)->usingName('logo')->toMediaCollection('logo');
            $this->cleanupTemp($optimizedPath);
        }

        if ($request->hasFile('cover_image')) {
            $optimizedPath = $this->imageService->optimizeCoverImage($request->file('cover_image'));
            $user->clearMediaCollection('cover_image');
            $user->addMedia($optimizedPath)->usingName('cover_image')->toMediaCollection('cover_image');
            $this->cleanupTemp($optimizedPath);
        }

        if ($user->isRestaurantSetupComplete()) {
            return redirect()->route('dashboard')
                ->with('success', 'Restaurant setup completed! You can now create your first menu.');
        }

        return redirect()->route('restaurant-setup.index')
            ->with('error', 'Please upload both logo and cover image.');
    }

    private function cleanupTemp(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
