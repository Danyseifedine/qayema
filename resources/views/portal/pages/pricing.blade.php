{{-- The packages on a page of their own: the cards from home (read live
     from the packages), how moving up works, and questions about paying. --}}
@extends('portal.layout.master.master')

@php
    $page = __('pages.pricing');
    $seoTitle = $page['seo']['title'];
    $seoDescription = $page['seo']['description'];
    $data = app(\App\Services\Portal\StructuredData::class);
    $seoSchema = [$data->pricing(), $data->faq($page['faq'])];
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

  @include('portal.partials.pricing', ['heading' => false])

  <section class="sec" id="upgrade" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label">{{ __('portal.upgrade.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.upgrade.title') }}</span> <span class="gold-text">{{ __('portal.upgrade.title_gold') }}</span></h2>
        <p>{{ __('portal.upgrade.sub') }}</p>
      </div>
      <div class="prob-grid" data-stagger>
        @foreach (__('portal.upgrade.steps') as $i => $step)
          <div class="prob-card">
            <div class="num display">0{{ $i + 1 }}</div>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['desc'] }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  @include('portal.partials.faq', ['items' => $page['faq'], 'title' => __('pages.faq_title'), 'titleGold' => null])

  @include('portal.partials.cta')
@endsection
