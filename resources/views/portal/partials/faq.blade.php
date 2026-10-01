{{-- A list of questions that open on tap ($items: [{q, a}]), under a title
     ($title, $titleGold). Each page that shows one also carries it as
     FAQPage structured data. --}}
<section class="sec" id="faq" style="padding-top:0;">
  <div class="wrap">
    <div class="sec-head reveal" style="text-align:center;max-width:640px;margin-inline:auto;">
      <div class="eyebrow" style="justify-content:center;"><span class="bar"></span><span class="mono-label">{{ $eyebrow ?? __('portal.faq.eyebrow') }}</span></div>
      <h2 class="display"><span>{{ $title }}</span>@if (! empty($titleGold)) <span class="gold-text">{{ $titleGold }}</span>@endif</h2>
    </div>
    <div class="faq" id="faqList">
      @foreach ($items as $item)
        <div class="faq-item">
          <button class="faq-q" type="button"><span>{{ $item['q'] }}</span><span class="pm"></span></button>
          <div class="faq-a"><div class="inner">{{ $item['a'] }}</div></div>
        </div>
      @endforeach
    </div>
  </div>
</section>
