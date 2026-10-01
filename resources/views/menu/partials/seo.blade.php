{{-- A menu's search and sharing tags, built by App\Services\Menu\MenuSeo.
     Every menu design includes this in its <head>. --}}
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
@if (count($seo['alternates']) > 1)
    @foreach ($seo['alternates'] as $code => $href)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $seo['default'] }}">
@endif

<meta property="og:type" content="restaurant.restaurant">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:locale" content="{{ $seo['og_locale'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">

@if ($seo['schema'])
    <script type="application/ld+json">{!! $seo['schema'] !!}</script>
@endif
