@props(['isRtl' => false])
<aside class="brand-col">

    <div class="brand-top">
        <div class="session-meta">
            <span class="live"></span>
            <span>{{ __('auth.brand.session_meta') }}</span>
        </div>
    </div>

    <div class="annot a1">
        <div class="lbl">
            <span class="pip">●</span>
            <span>{{ __('auth.brand.live_label') }}</span>
        </div>
        <div class="body">{!! __('auth.brand.live_body') !!}</div>
    </div>

    <div class="annot a2">
        <div class="lbl">
            <span>{{ __('auth.brand.scan_label') }}</span>
        </div>
        <div class="body" style="font-family:var(--font-display);font-style:italic;font-size:22px;color:var(--accent-soft)">
            31<span style="font-size:14px;opacity:.7">s</span>
            <span style="font-size:13px;color:var(--muted-dark);font-style:normal;margin-left:6px">↓ 42%</span>
        </div>
    </div>

    <div class="quote-block">
        <div class="qmark">"</div>
        <p class="quote" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">{!! __('auth.brand.quote') !!}</p>
    </div>

    <div class="brand-foot">
        <div>
            <div class="k">{{ __('auth.brand.restaurants') }}</div>
            <div class="v">240<span class="u">+</span></div>
        </div>
        <div>
            <div class="k">{{ __('auth.brand.languages') }}</div>
            <div class="v">10</div>
        </div>
        <div>
            <div class="k">{{ __('auth.brand.uptime') }}</div>
            <div class="v"><span class="it">‹</span>99.99<span class="u">%</span></div>
        </div>
    </div>

</aside>
