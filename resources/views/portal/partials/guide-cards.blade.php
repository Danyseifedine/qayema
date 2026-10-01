{{-- A card per guide in $guides (their keys in PortalUrl::GUIDES). --}}
@use('App\Support\PortalUrl')
<div class="guide-grid">
  @foreach ($guides as $guide)
    <a class="guide-card" href="{{ PortalUrl::to('guide', null, ['guide' => $guide]) }}">
      <span class="mins">{{ trans_choice('pages.guides.minutes', __("pages.articles.{$guide}.minutes")) }}</span>
      <h3>{{ __("pages.articles.{$guide}.title") }}</h3>
      <p>{{ __("pages.articles.{$guide}.summary") }}</p>
      <span class="more">{{ __('pages.guides.read') }}</span>
    </a>
  @endforeach
</div>
