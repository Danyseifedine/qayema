{{-- Primary Meta Tags --}}
<title>{{ $fullTitle() }}</title>
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">
@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}">
@endif
@if ($author)
    <meta name="author" content="{{ $author }}">
@endif

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $canonical }}">

{{-- Robots Meta --}}
<meta name="robots" content="{{ $robotsContent() }}">
<meta name="googlebot" content="{{ $robotsContent() }}">
<meta name="bingbot" content="{{ $robotsContent() }}">

{{-- Alternate Languages (hreflang) --}}
@if ($hreflang)
    @foreach ($hreflang as $lang => $langUrl)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $langUrl }}">
    @endforeach
@endif

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:secure_url" content="{{ $image }}">
<meta property="og:image:alt" content="{{ $imageAlt }}">
@if ($imageWidth && $imageHeight)
    <meta property="og:image:width" content="{{ $imageWidth }}">
    <meta property="og:image:height" content="{{ $imageHeight }}">
@endif
<meta property="og:locale" content="{{ $locale }}">
@if ($alternateLocales)
    @foreach ($alternateLocales as $altLocale)
        <meta property="og:locale:alternate" content="{{ $altLocale }}">
    @endforeach
@endif

{{-- Article Specific --}}
@if ($type === 'article')
    @if ($publishedTime)
        <meta property="article:published_time" content="{{ $publishedTime }}">
    @endif
    @if ($modifiedTime)
        <meta property="article:modified_time" content="{{ $modifiedTime }}">
    @endif
    @if ($author)
        <meta property="article:author" content="{{ $author }}">
    @endif
    @if ($section)
        <meta property="article:section" content="{{ $section }}">
    @endif
    @if ($tags)
        @foreach ($tags as $tag)
            <meta property="article:tag" content="{{ $tag }}">
        @endforeach
    @endif
@endif

{{-- Product Specific --}}
@if ($type === 'product' && $price)
    <meta property="product:price:amount" content="{{ $price }}">
    <meta property="product:price:currency" content="{{ $currency }}">
@endif

{{-- Facebook App ID --}}
@if ($facebookAppId)
    <meta property="fb:app_id" content="{{ $facebookAppId }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:url" content="{{ $url }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $imageAlt }}">
@if ($twitterSite)
    <meta name="twitter:site" content="@{{ $twitterSite }}">
@endif
@if ($twitterCreator)
    <meta name="twitter:creator" content="@{{ $twitterCreator }}">
@endif

{{-- Video Meta (if video) --}}
@if ($videoUrl)
    <meta property="og:video" content="{{ $videoUrl }}">
    <meta property="og:video:secure_url" content="{{ $videoUrl }}">
    @if ($videoDuration)
        <meta property="og:video:duration" content="{{ $videoDuration }}">
    @endif
    <meta name="twitter:player" content="{{ $videoUrl }}">
@endif

{{-- Additional Meta Tags --}}
@if ($additionalMeta)
    @foreach ($additionalMeta as $name => $content)
        <meta name="{{ $name }}" content="{{ $content }}">
    @endforeach
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
<link rel="dns-prefetch" href="//www.google-analytics.com">

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
