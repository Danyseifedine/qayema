{{-- One guide, from lang/{locale}/pages.php under articles.$guide, as an
     Article for search engines. --}}
@extends('portal.layout.master.master')
@use('App\Support\PortalUrl')

@php
    $article = __("pages.articles.{$guide}");
    $seoTitle = $article['title'];
    $seoDescription = $article['description'];
    $seoSchema = [app(\App\Services\Portal\StructuredData::class)->article($guide)];
    $updated = \Illuminate\Support\Carbon::parse($article['published'])->locale(app()->getLocale())->translatedFormat('j F Y');
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/pages/content.css') }}?v={{ @filemtime(public_path('portal/css/pages/content.css')) ?: '1' }}">
@endpush

@section('content')
  <header class="page-hero">
    <div class="wrap">
      <nav class="crumbs" aria-label="breadcrumb">
        <a href="{{ PortalUrl::to('home') }}">Qayema</a>
        <span aria-hidden="true">/</span>
        <a href="{{ PortalUrl::to('guides') }}">{{ __('pages.guides.crumb') }}</a>
      </nav>
      <h1 class="display">{{ $article['title'] }}</h1>
      <p class="lede">{{ $article['summary'] }}</p>
      <p class="meta">
        <span><time datetime="{{ $article['published'] }}">{{ __('pages.guides.updated', ['date' => $updated]) }}</time></span>
        <span>{{ trans_choice('pages.guides.minutes', $article['minutes']) }}</span>
      </p>
    </div>
  </header>

  <section class="page-body">
    <div class="wrap">
      @include('portal.partials.prose', ['sections' => $article['sections']])
    </div>
  </section>

  <section class="related">
    <div class="wrap">
      <h2>{{ __('pages.related') }}</h2>
      @include('portal.partials.guide-cards', ['guides' => array_values(array_diff(PortalUrl::GUIDES, [$guide]))])
    </div>
  </section>

  @include('portal.partials.cta')
@endsection
