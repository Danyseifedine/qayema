<?php

namespace App\Http\Controllers;

use App\Exceptions\TooManyContactMessages;
use App\Http\Requests\ContactRequest;
use App\Services\Contact\ContactService;
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
        // Honeypot: bots fill the hidden "hp_field" input. Pretend success and drop
        if (filled($request->input('hp_field'))) {
            return $request->expectsJson()
                ? response()->json(['message' => __('portal.contact.js.sent')])
                : back()->with('success', true);
        }

        try {
            $contacts->submit($request->safe()->only(['name', 'email', 'message']), (string) $request->ip());
        } catch (TooManyContactMessages $e) {
            // Locale is resolved server-side (portal.locale middleware), so the
            // message comes back already translated for both AJAX and no-JS paths.
            $message = __('portal.contact.js.rate_limit', ['hours' => $e->retryAfterHours]);

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'code' => 'too_many_requests', 'errors' => ['rate_limit' => [$message]]], 429)
                : back()->withInput()->withErrors(['rate_limit' => $message]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => __('portal.contact.js.sent')])
            : back()->with('success', true);
    }
}
