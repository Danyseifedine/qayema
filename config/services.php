<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'contact' => [
        'recipient' => env('CONTACT_RECIPIENT_EMAIL'),
    ],

    // Writing help on the dashboard (App\Ai\Agents): translating menu text
    // and writing a category's description. Off until a provider is named
    // here and its key is set in config/ai.php's providers (ANTHROPIC_API_KEY,
    // OPENAI_API_KEY...). With no model, the provider's cheapest one is used.
    // Uses a day are set per package at /admin (Feature::WritingHelpLimit);
    // the server runs at most `at_once` at the same time
    // (App\Http\Middleware\PaceWritingHelp), so they never take every worker.
    'writing_help' => [
        'provider' => env('AI_PROVIDER'),
        'model' => env('AI_MODEL'),
        'at_once' => (int) env('AI_AT_ONCE', 10),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET'),
        'enabled' => filled(env('RECAPTCHA_SECRET')),
    ],

];
