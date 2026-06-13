<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('portal.contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);

            return back()
                ->withInput()
                ->withErrors(['rate_limit' => "You've reached the 3-message daily limit. Try again in {$hours} hour(s)."]);
        }

        $contact = ContactMessage::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'message' => $request->string('message'),
            'ip_address' => $request->ip(),
        ]);

        Mail::to(config('services.contact.recipient'))->send(new ContactMessageReceived($contact));

        // Decay hits after 24 hours
        RateLimiter::hit($key, 86400);

        return back()->with('success', true);
    }
}
