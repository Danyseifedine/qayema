{{-- Title, description and what search engines may do with the page --}}
<title>{{ $fullTitle() }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<meta name="author" content="{{ config('seo.organization.name') }}">

{{-- The page's one address, and its twin in the other language --}}
<link rel="canonical" href="{{ $url }}">
@foreach ($alternates as $language => $href)
    <link rel="alternate" hreflang="{{ $language }}" href="{{ $href }}">
@endforeach
@if ($defaultAlternate())
    <link rel="alternate" hreflang="x-default" href="{{ $defaultAlternate() }}">
@endif

{{-- How a shared link looks (WhatsApp, Facebook, LinkedIn) --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $fullTitle() }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $imageAlt }}">
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach ($ogLocaleAlternates as $alternate)
    <meta property="og:locale:alternate" content="{{ $alternate }}">
@endforeach
@if ($facebookAppId)
    <meta property="fb:app_id" content="{{ $facebookAppId }}">
@endif

{{-- X (Twitter) --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle() }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $imageAlt }}">
@if ($twitterHandle)
    <meta name="twitter:site" content="{{ '@'.ltrim($twitterHandle, '@') }}">
    <meta name="twitter:creator" content="{{ '@'.ltrim($twitterHandle, '@') }}">
@endif

{{-- Structured data (schema.org) --}}
@foreach ($schemas as $schema)
    <script type="application/ld+json">{!! $schema !!}</script>
@endforeach

{{-- Fonts load sooner --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

{{-- Icons: the Q centered on black (search results crop it to a circle) --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('images/favicons/favicon.svg') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('images/favicons/favicon.ico') }}">
<link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/favicons/favicon-48x48.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicons/android-chrome-192x192.png') }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/favicons/android-chrome-512x512.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('images/favicons/site.webmanifest') }}">
<meta name="theme-color" content="#0b0b0c">
