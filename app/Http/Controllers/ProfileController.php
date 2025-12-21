<?php

namespace App\Http\Controllers;

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
    protected ImageOptimizationService $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Name and email are disabled, so we don't update them
        // This method is kept for backward compatibility but doesn't do anything
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's restaurant information.
     */
    public function updateRestaurantInformation(RestaurantInformationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Update restaurant information
        $user->update($request->only(['restaurant_name', 'phone', 'address']));

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

        return Redirect::route('profile.edit')->with('status', 'restaurant-information-updated');
    }

    /**
     * Delete the user's account.
     */
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
}
