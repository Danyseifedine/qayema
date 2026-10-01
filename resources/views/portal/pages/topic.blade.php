{{-- A page built around what owners search for (a QR menu in Lebanon, a
     digital menu for cafés), from lang/{locale}/pages.php under $topic. --}}
@extends('portal.layout.master.master')
@use('App\Support\PortalUrl')

@php
    $page = __("pages.{$topic}");
    $seoTitle = $page['seo']['title'];
    $seoDescription = $page['seo']['description'];
    $seoSchema = [app(\App\Services\Portal\StructuredData::class)->faq($page['faq'])];
    $owner = auth()->user();
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/pages/content.css') }}?v={{ @filemtime(public_path('portal/css/pages/content.css')) ?: '1' }}">
@endpush

@section('content')
  <header class="page-hero">
    <div class="wrap">
      <div class="eyebrow"><span class="bar"></span><span class="mono-label">{{ $page['eyebrow'] }}</span></div>
      <h1 class="display">{{ $page['title'] }} <span class="gold-text">{{ $page['title_gold'] }}</span></h1>
      <p class="lede">{{ $page['intro'] }}</p>
      <div class="actions">
        <a class="btn btn-gold" data-magnetic href="{{ $owner?->afterLoginUrl() ?? route('register') }}">{{ __('pages.cta.button') }}</a>
        <a class="btn btn-line" href="{{ PortalUrl::to('pricing') }}">{{ __('pages.cta.secondary') }}</a>
      </div>
    </div>
  </header>

  <section class="page-body">
    <div class="wrap">
      @include('portal.partials.prose', ['sections' => $page['sections']])
    </div>
  </section>

  @include('portal.partials.faq', ['items' => $page['faq'], 'title' => __('pages.faq_title'), 'titleGold' => null])

  <section class="related">
    <div class="wrap">
      <h2>{{ __('pages.related') }}</h2>
      @include('portal.partials.guide-cards', ['guides' => array_slice(PortalUrl::GUIDES, 0, 2)])
    </div>
  </section>

  @include('portal.partials.cta')
@endsection
