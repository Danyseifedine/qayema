{{-- Every guide, with a line on each. --}}
@extends('portal.layout.master.master')
@use('App\Support\PortalUrl')

@php
    $page = __('pages.guides');
    $seoTitle = $page['seo']['title'];
    $seoDescription = $page['seo']['description'];
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
    </div>
  </header>

  <section class="page-body">
    <div class="wrap">
      @include('portal.partials.guide-cards', ['guides' => PortalUrl::GUIDES])
    </div>
  </section>

  @include('portal.partials.cta')
@endsection
