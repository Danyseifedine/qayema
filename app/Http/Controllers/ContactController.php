<?php

namespace App\Http\Controllers;

use App\Exceptions\TooManyContactMessages;
use App\Http\Requests\ContactRequest;
use App\Services\Portal\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('portal.contact');
    }

    public function store(ContactRequest $request, ContactService $contacts): RedirectResponse|JsonResponse
    {
        // Honeypot: bots fill the hidden "website" field. Pretend success and drop
        if (filled($request->input('website'))) {
            return $request->expectsJson()
                ? $this->success(message: 'Your message has been sent.')
                : back()->with('success', true);
        }

        try {
            $contacts->submit($request->safe()->only(['name', 'email', 'message']), (string) $request->ip());
        } catch (TooManyContactMessages $e) {
            // Locale is resolved server-side (owner.locale middleware), so the
            // message comes back already translated for both AJAX and no-JS paths.
            $message = __('portal.contact.js.rate_limit', ['hours' => $e->retryAfterHours]);

            return $request->expectsJson()
                ? $this->error($message, ['rate_limit' => [$message]], 429)
                : back()->withInput()->withErrors(['rate_limit' => $message]);
        }

        return $request->expectsJson()
            ? $this->success(message: 'Your message has been sent.')
            : back()->with('success', true);
    }
}
