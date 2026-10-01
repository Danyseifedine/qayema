{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
{{-- Each public page lists its language versions (and English as the
     default), the way Google pairs translations. Built in SeoController. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
    <url>
        <loc>{{ $page['url'] }}</loc>
@foreach ($page['alternates'] as $locale => $href)
        <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ $href }}"/>
@endforeach
@if ($page['default'])
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $page['default'] }}"/>
@endif
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod'] }}</lastmod>
@endif
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
</urlset>
