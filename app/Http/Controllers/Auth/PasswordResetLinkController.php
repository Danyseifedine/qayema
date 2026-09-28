<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * "Forgot your password?": sends the reset link.
 *
 * The response is the same whether or not the address exists, so the form
 * can't be used to discover which emails have accounts.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        // RESET_THROTTLED is the only outcome worth telling the user about:
        // it means they just asked and should check their inbox, not resubmit.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.passwords.throttled')]);
        }

        return back()->with('status', __('auth.passwords.sent'));
    }
}
