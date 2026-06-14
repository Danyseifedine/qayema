@extends('portal.layout.master.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/pages/contact.css') }}?v={{ @filemtime(public_path('portal/css/pages/contact.css')) ?: '1' }}">
@endpush

@section('content')
  <section class="sec ctc">
    <div class="wrap">
      <div class="ctc-grid">

        {{-- Left: info --}}
        <div>
          <div class="ctc-eyebrow"><span class="bar"></span><span class="mono-label">{{ __('portal.contact.eyebrow') }}</span></div>
          <h1 class="ctc-headline">{!! __('portal.contact.headline') !!}</h1>
          <p class="ctc-sub">{{ __('portal.contact.sub') }}</p>

          <div class="ctc-info">
            <div class="ctc-info-item">
              <div class="ctc-info-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1.5" y="3.5" width="13" height="9" rx="1.5"/><path d="M1.5 5l6.5 4.5L14.5 5"/></svg>
              </div>
              <div>
                <p class="ctc-info-label">{{ __('portal.contact.email_label') }}</p>
                <p class="ctc-info-value"><a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a></p>
              </div>
            </div>
            <div class="ctc-info-item">
              <div class="ctc-info-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 1.5a4.5 4.5 0 0 1 4.5 4.5c0 3-4.5 8.5-4.5 8.5S3.5 9 3.5 6A4.5 4.5 0 0 1 8 1.5Z"/><circle cx="8" cy="6" r="1.5"/></svg>
              </div>
              <div>
                <p class="ctc-info-label">{{ __('portal.contact.location_label') }}</p>
                <p class="ctc-info-value">{{ __('portal.contact.location_value') }}</p>
              </div>
            </div>
            <div class="ctc-info-item">
              <div class="ctc-info-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v3.75l2.5 1.5"/></svg>
              </div>
              <div>
                <p class="ctc-info-label">{{ __('portal.contact.response_label') }}</p>
                <p class="ctc-info-value">{{ __('portal.contact.response_value') }}</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Right: form --}}
        <div class="ctc-form-wrap">
          <div class="ctc-form-card">

            {{-- Success state: revealed by the server after a no-JS redirect, or by JS after an AJAX submit --}}
            <div class="ctc-success" id="ctcSuccess" @unless (session('success')) hidden @endunless>
              <div class="ctc-success-ring">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M6 14l5.5 5.5L22 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <h3>{{ __('portal.contact.success_title') }}</h3>
              <p>{{ __('portal.contact.success_body') }}</p>
              <a href="{{ route('contact') }}" class="ctc-success-back">
                <span>{{ __('portal.contact.success_back') }}</span>
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </a>
            </div>

            {{-- Form state --}}
            <div id="ctcFormState" @if (session('success')) hidden @endif>
              <p class="ctc-form-title">{{ __('portal.contact.form_title') }}</p>
              <p class="ctc-form-sub">{{ __('portal.contact.form_sub') }}</p>

              <div class="ctc-rate-banner" id="ctcBanner" @unless ($errors->has('rate_limit')) hidden @endunless>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6.5"/><path d="M8 5v3.5M8 11h.01"/></svg>
                <span class="msg">{{ $errors->first('rate_limit') }}</span>
              </div>

              <form id="contactForm" method="POST" action="{{ route('contact.store') }}" novalidate>
                @csrf

                {{-- Honeypot: hidden from real users, bots fill it. Submissions with it set are
                     silently dropped. The field is named non-semantically and opts out of
                     password managers so browser autofill can't trip it for real visitors. --}}
                <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
                  <label>Leave this field empty<input type="text" name="hp_field" tabindex="-1"
                         autocomplete="off" data-lpignore="true" data-1p-ignore data-form-type="other" value=""></label>
                </div>

                @if (config('services.recaptcha.enabled'))
                  <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @endif

                <div class="ctc-row">
                  {{-- Name --}}
                  <div class="ctc-field">
                    <label class="ctc-label" for="name">{{ __('portal.contact.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name"
                           class="ctc-input @error('name') is-error @enderror"
                           placeholder="{{ __('portal.contact.name_placeholder') }}">
                    <p class="ctc-error" data-error-for="name">@error('name'){{ $message }}@enderror</p>
                  </div>

                  {{-- Email --}}
                  <div class="ctc-field">
                    <label class="ctc-label" for="email">{{ __('portal.contact.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                           class="ctc-input @error('email') is-error @enderror"
                           placeholder="{{ __('portal.contact.email_placeholder') }}">
                    <p class="ctc-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                  </div>
                </div>

                {{-- Message --}}
                <div class="ctc-field">
                  <label class="ctc-label" for="message">{{ __('portal.contact.message') }}</label>
                  <textarea id="message" name="message" maxlength="2000"
                            class="ctc-textarea @error('message') is-error @enderror"
                            placeholder="{{ __('portal.contact.message_placeholder') }}">{{ old('message') }}</textarea>
                  <div class="ctc-field-foot">
                    <p class="ctc-error" data-error-for="message" style="margin:0">@error('message'){{ $message }}@enderror</p>
                    <span class="ctc-char-count" id="ctcCount">0 / 2000</span>
                  </div>
                </div>

                <button type="submit" class="ctc-btn" id="ctcSubmit">
                  <span class="label">{{ __('portal.contact.submit') }}</span>
                </button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('contactForm');
  if (!form) return;

  var count   = document.getElementById('ctcCount');
  var message = document.getElementById('message');
  var btn     = document.getElementById('ctcSubmit');
  var btnLbl  = btn.querySelector('.label');
  var banner  = document.getElementById('ctcBanner');
  var fields  = ['name', 'email', 'message'];

  // validation + UI strings, rendered server-side in the current locale
  var MSG = @json(__('portal.contact.js'));

  function input(n) { return form.querySelector('[name="' + n + '"]'); }
  function errorEl(n) { return form.querySelector('[data-error-for="' + n + '"]'); }

  function validate(n) {
    var v = (input(n).value || '').trim();
    if (n === 'name')    { if (!v) return MSG.name_required;    if (v.length > 100) return MSG.name_max; }
    if (n === 'email')   { if (!v) return MSG.email_required;   if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return MSG.email_invalid; }
    if (n === 'message') { if (!v) return MSG.message_required; if (v.length < 10) return MSG.message_min; if (v.length > 2000) return MSG.message_max; }
    return null;
  }

  function showError(n, msg) {
    errorEl(n).textContent = msg || '';
    input(n).classList.toggle('is-error', !!msg);
  }

  fields.forEach(function (n) {
    input(n).addEventListener('blur', function () { showError(n, validate(n)); });
    input(n).addEventListener('input', function () { if (errorEl(n).textContent) showError(n, validate(n)); });
  });

  if (message && count) {
    var updateCount = function () {
      var n = message.value.length;
      count.textContent = n + ' / 2000';
      count.classList.toggle('over', n > 2000);
    };
    message.addEventListener('input', updateCount);
    updateCount();
  }

  function setBanner(msg) {
    if (!banner) return;
    banner.querySelector('.msg').textContent = msg;
    banner.hidden = false;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var ok = true;
    fields.forEach(function (n) { var m = validate(n); showError(n, m); if (m) ok = false; });
    if (!ok) {
      var firstBad = form.querySelector('.ctc-input.is-error, .ctc-textarea.is-error');
      if (firstBad) { firstBad.focus(); }
      return;
    }

    if (banner) { banner.hidden = true; }
    btn.disabled = true;
    var prev = btnLbl.textContent;
    btnLbl.textContent = MSG.sending;

    var restore = function () { btn.disabled = false; btnLbl.textContent = prev; };

    fetch(form.action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: new FormData(form)
    }).then(function (res) {
      if (res.ok) {
        document.getElementById('ctcFormState').hidden = true;
        document.getElementById('ctcSuccess').hidden = false;
        return;
      }
      return res.json().then(function (data) {
        restore();
        var errs = (data && data.errors) || {};
        fields.forEach(function (n) { if (errs[n]) showError(n, errs[n][0]); });
        if (errs.rate_limit) { setBanner(errs.rate_limit[0]); }              // server-localized
        if (errs['g-recaptcha-response']) { setBanner(errs['g-recaptcha-response'][0]); }
        if (!errs.name && !errs.email && !errs.message && !errs.rate_limit && !errs['g-recaptcha-response']) {
          setBanner(MSG.fail);
        }
      }).catch(function () { restore(); setBanner(MSG.fail); });
    }).catch(function () {
      restore();
      setBanner(MSG.fail);
    });
  });
})();
</script>
@if (config('services.recaptcha.enabled'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
  (function () {
    const siteKey = @json(config('services.recaptcha.site_key'));
    function refreshCaptchaToken() {
      grecaptcha.ready(function () {
        grecaptcha.execute(siteKey, { action: 'contact' }).then(function (token) {
          const field = document.getElementById('g-recaptcha-response');
          if (field) { field.value = token; }
        });
      });
    }
    refreshCaptchaToken();
    setInterval(refreshCaptchaToken, 110000);
  })();
</script>
@endif
@endpush
