<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\RestaurantInformationRequest;
use App\Services\ImageOptimizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ImageOptimizationService $imageService) {}

    public function edit(Request $request): View
    {
        return view('menu-owner.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateRestaurantInformation(RestaurantInformationRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update($request->only(['restaurant_name', 'phone', 'address']));

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

        return Redirect::route('profile.edit')->with('status', 'restaurant-information-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function cleanupTemp(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
