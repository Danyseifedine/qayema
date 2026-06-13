@extends('portal.layout.master.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/pages/contact.css') }}">
@endpush

@section('content')
  <section class="sec ctc">
    <div class="wrap">
      <div class="ctc-grid">

        {{-- Left: info --}}
        <div>
          <div class="ctc-eyebrow"><span class="bar"></span><span class="mono-label" data-en="Company" data-ar="الشركة">Company</span></div>
          <h1 class="ctc-headline" data-en="Get in <span class='it'>Touch</span>" data-ar="تواصل <span class='it'>معنا</span>">Get in <span class="it">Touch</span></h1>
          <p class="ctc-sub" data-en="Have a question, a partnership idea, or just want to say hello? Drop us a message and we'll get back to you." data-ar="هل لديك سؤال أو فكرة شراكة أو تريد فقط أن تقول مرحباً؟ أرسل لنا رسالة وسنردّ عليك.">Have a question, a partnership idea, or just want to say hello? Drop us a message and we'll get back to you.</p>

          <div class="ctc-info">
            <div class="ctc-info-item">
              <div class="ctc-info-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1.5" y="3.5" width="13" height="9" rx="1.5"/><path d="M1.5 5l6.5 4.5L14.5 5"/></svg>
              </div>
              <div>
                <p class="ctc-info-label" data-en="Email" data-ar="البريد الإلكتروني">Email</p>
                <p class="ctc-info-value"><a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a></p>
              </div>
            </div>
            <div class="ctc-info-item">
              <div class="ctc-info-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 1.5a4.5 4.5 0 0 1 4.5 4.5c0 3-4.5 8.5-4.5 8.5S3.5 9 3.5 6A4.5 4.5 0 0 1 8 1.5Z"/><circle cx="8" cy="6" r="1.5"/></svg>
              </div>
              <div>
                <p class="ctc-info-label" data-en="Location" data-ar="الموقع">Location</p>
                <p class="ctc-info-value" data-en="Barja, Lebanon" data-ar="برجا، لبنان">Barja, Lebanon</p>
              </div>
            </div>
            <div class="ctc-info-item">
              <div class="ctc-info-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v3.75l2.5 1.5"/></svg>
              </div>
              <div>
                <p class="ctc-info-label" data-en="Response time" data-ar="وقت الرد">Response time</p>
                <p class="ctc-info-value" data-en="Usually within 24 hours" data-ar="عادةً خلال 24 ساعة">Usually within 24 hours</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Right: form --}}
        <div class="ctc-form-wrap">
          <div class="ctc-form-card">

            @if (session('success'))
              <div class="ctc-success">
                <div class="ctc-success-ring">
                  <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M6 14l5.5 5.5L22 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3 data-en="Message sent!" data-ar="تم الإرسال!">Message sent!</h3>
                <p data-en="Thanks for reaching out. We'll get back to you as soon as possible." data-ar="شكرًا للتواصل معنا. سنردّ عليك في أقرب وقت ممكن.">Thanks for reaching out. We'll get back to you as soon as possible.</p>
                <a href="{{ route('contact') }}" class="ctc-success-back">
                  <span data-en="Send another message" data-ar="إرسال رسالة أخرى">Send another message</span>
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
              </div>
            @else

              <p class="ctc-form-title" data-en="Send us a message" data-ar="أرسل لنا رسالة">Send us a message</p>
              <p class="ctc-form-sub" data-en="We read every message personally." data-ar="نقرأ كل رسالة شخصياً.">We read every message personally.</p>

              @if ($errors->has('rate_limit'))
                <div class="ctc-rate-banner">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6.5"/><path d="M8 5v3.5M8 11h.01"/></svg>
                  {{ $errors->first('rate_limit') }}
                </div>
              @endif

              <form method="POST" action="{{ route('contact.store') }}" novalidate>
                @csrf

                @if (config('services.recaptcha.enabled'))
                  <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @endif

                <div class="ctc-row">
                  {{-- Name --}}
                  <div class="ctc-field">
                    <label class="ctc-label" for="name" data-en="Name" data-ar="الاسم">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name"
                           class="ctc-input @error('name') is-error @enderror"
                           data-en-ph="John Smith" data-ar-ph="محمد أحمد" placeholder="John Smith">
                    @error('name')<p class="ctc-error">{{ $message }}</p>@enderror
                  </div>

                  {{-- Email --}}
                  <div class="ctc-field">
                    <label class="ctc-label" for="email" data-en="Email" data-ar="البريد الإلكتروني">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                           class="ctc-input @error('email') is-error @enderror"
                           data-en-ph="you@example.com" data-ar-ph="you@example.com" placeholder="you@example.com">
                    @error('email')<p class="ctc-error">{{ $message }}</p>@enderror
                  </div>
                </div>

                {{-- Message --}}
                <div class="ctc-field">
                  <label class="ctc-label" for="message" data-en="Message" data-ar="الرسالة">Message</label>
                  <textarea id="message" name="message" maxlength="2000"
                            class="ctc-textarea @error('message') is-error @enderror"
                            data-en-ph="Tell us what's on your mind..." data-ar-ph="أخبرنا بما يدور في ذهنك...">{{ old('message') }}</textarea>
                  <div class="ctc-field-foot">
                    @error('message')<p class="ctc-error" style="margin:0">{{ $message }}</p>@enderror
                    <span class="ctc-char-count" id="ctcCount">0 / 2000</span>
                  </div>
                </div>

                <button type="submit" class="ctc-btn">
                  <span data-en="Send message" data-ar="إرسال الرسالة">Send message</span>
                  <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2 7.5h11M9 3.5l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </form>
            @endif
          </div>
        </div>

      </div>
    </div>
  </section>
@endsection

@push('scripts')
<script>
  // live character counter (no framework dependency)
  (function () {
    var ta = document.getElementById('message'), c = document.getElementById('ctcCount');
    if (!ta || !c) return;
    var update = function () {
      var n = ta.value.length;
      c.textContent = n + ' / 2000';
      c.classList.toggle('over', n > 2000);
    };
    ta.addEventListener('input', update);
    update();
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
