{{-- Primary Meta Tags --}}
<title>{{ $fullTitle() }}</title>
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="author" content="{{ $author }}">

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $url }}">

{{-- Robots Meta --}}
<meta name="robots" content="index, follow">
<meta name="googlebot" content="index, follow">
<meta name="bingbot" content="index, follow">

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:secure_url" content="{{ $image }}">
<meta property="og:image:alt" content="Qayema by Lebify - Digital Menus for Restaurants">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="{{ $locale }}">

{{-- Facebook App ID --}}
@if ($facebookAppId)
    <meta property="fb:app_id" content="{{ $facebookAppId }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $url }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="Qayema by Lebify - Digital Menus for Restaurants">
@if ($twitterHandle)
    <meta name="twitter:site" content="{{ '@'.ltrim($twitterHandle, '@') }}">
    <meta name="twitter:creator" content="{{ '@'.ltrim($twitterHandle, '@') }}">
@endif

{{-- JSON-LD Schema --}}
@if ($schemaData)
    <script type="application/ld+json">
{!! $schemaData !!}
</script>
@endif

{{-- Preconnect for Performance --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

{{-- Favicon and Touch Icons --}}
<link rel="icon" type="image/x-icon" href="{{ asset('images/favicons/favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
<link rel="manifest" href="{{ asset('images/favicons/site.webmanifest') }}">
<link rel="icon" type="image/png" sizes="192x192"
    href="{{ asset('images/favicons/android-chrome-192x192.png') }}">
<link rel="icon" type="image/png" sizes="512x512"
    href="{{ asset('images/favicons/android-chrome-512x512.png') }}">
