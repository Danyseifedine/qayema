@extends('portal.layout.master.master')

@php
    $isAr = app()->getLocale() === 'ar';
    $pageTitle = trim($__env->yieldContent('title'));
    $seoTitle = $pageTitle !== '' ? $pageTitle.' — Qayema' : null;
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/pages/legal.css') }}?v={{ @filemtime(public_path('portal/css/pages/legal.css')) ?: '1' }}">
@endpush

@section('content')
  {{-- Hero --}}
  <section class="legal-hero">
    <div class="wrap">
      <div class="legal-hero-eyebrow"><span class="dot"></span>@yield($isAr ? 'eyebrow_ar' : 'eyebrow')</div>
      <h1>
        @if ($isAr)
          @yield('headline_ar')
        @else
          @yield('headline_plain') <em>@yield('headline_italic')</em>
        @endif
      </h1>
      <p class="legal-updated">@yield($isAr ? 'updated_ar' : 'updated')</p>
    </div>
  </section>

  {{-- Body --}}
  <section class="legal-body">
    <div class="wrap">
      <div class="legal-layout">
        <aside class="legal-toc">
          <p class="legal-toc-title">{{ $isAr ? 'المحتويات' : 'On this page' }}</p>
          <ul>@yield($isAr ? 'toc_ar' : 'toc')</ul>
        </aside>
        <article class="legal-content">
          @yield('legal_body')
        </article>
      </div>
    </div>
  </section>
@endsection
