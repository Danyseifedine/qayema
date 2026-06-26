@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('title', 'Cookie Policy')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'Ù‚Ø§Ù†ÙˆÙ†ÙŠ')
@section('headline_plain', 'Cookie')
@section('headline_italic', 'Policy')
@section('headline_ar', 'Ø³ÙŠØ§Ø³Ø© Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·')
@section('updated', 'Last updated: May 9, 2025')
@section('updated_ar', 'Ø¢Ø®Ø± ØªØ­Ø¯ÙŠØ«: 9 Ù…Ø§ÙŠÙˆ 2025')

@section('toc')
    <li><a href="#what">What are cookies</a></li>
    <li><a href="#types">Cookies we use</a></li>
    <li><a href="#essential">Essential cookies</a></li>
    <li><a href="#functional">Functional cookies</a></li>
    <li><a href="#analytics">Analytics cookies</a></li>
    <li><a href="#third">Third-party cookies</a></li>
    <li><a href="#control">Controlling cookies</a></li>
    <li><a href="#changes">Changes</a></li>
    <li><a href="#contact">Contact</a></li>
@endsection

@section('toc_ar')
    <li><a href="#what-ar">Ù…Ø§ Ù‡ÙŠ Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</a></li>
    <li><a href="#types-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ØªÙŠ Ù†Ø³ØªØ®Ø¯Ù…Ù‡Ø§</a></li>
    <li><a href="#essential-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ©</a></li>
    <li><a href="#functional-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ÙˆØ¸ÙŠÙÙŠØ©</a></li>
    <li><a href="#analytics-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ØªØ­Ù„ÙŠÙ„ÙŠØ©</a></li>
    <li><a href="#third-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ù…Ù† Ø§Ù„Ø£Ø·Ø±Ø§Ù Ø§Ù„Ø«Ø§Ù„Ø«Ø©</a></li>
    <li><a href="#control-ar">Ø§Ù„ØªØ­ÙƒÙ… ÙÙŠ Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</a></li>
    <li><a href="#changes-ar">Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª</a></li>
    <li><a href="#contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</a></li>
@endsection

@section('legal_body')

@unless ($isAr)

<div class="legal-highlight">
    <p>This Cookie Policy explains how Qayema, operated by Lebify Group, uses cookies and similar tracking technologies on qayema.com and the public menu pages we host for restaurants. By using our service, you consent to the use of cookies as described here.</p>
</div>

<h2 id="what">What are cookies</h2>

<p>Cookies are small text files placed on your device when you visit a website. They are widely used to make websites work efficiently, to remember your preferences, and to provide information to site owners.</p>
<p>Similar technologies include local storage, session storage, and pixel tags. Where we refer to "cookies" in this policy, we include all these technologies.</p>

<h2 id="types">Cookies we use</h2>

<p>We use three categories of cookies:</p>
<ul>
    <li><strong>Essential cookies</strong> â€” required for the service to function. Cannot be disabled.</li>
    <li><strong>Functional cookies</strong> â€” remember your preferences (e.g. dashboard language).</li>
    <li><strong>Analytics cookies</strong> â€” help us understand how visitors use our service.</li>
</ul>

<h2 id="essential">Essential cookies</h2>

<p>These cookies are strictly necessary for the service to operate. Without them, features such as logging in, maintaining your session, and protecting against cross-site request forgery (CSRF) would not work. You cannot opt out of these cookies.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Cookie</th>
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Purpose</th>
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Duration</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema_session</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Maintains your login session and stores temporary data</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Session (browser close)</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">XSRF-TOKEN</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Protects against cross-site request forgery attacks</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Session</td>
        </tr>
    </tbody>
</table>

<h2 id="functional">Functional cookies</h2>

<p>Functional cookies allow the service to remember choices you make, such as your preferred dashboard language. Disabling these cookies may affect your experience.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Cookie / Storage</th>
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Purpose</th>
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Duration</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">owner_locale</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Remembers your chosen dashboard language</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Session</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema_lang</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Remembers your language preference on the public website</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Persistent (1 year)</td>
        </tr>
    </tbody>
</table>

<h2 id="analytics">Analytics cookies</h2>

<p>We use lightweight, privacy-first analytics to understand how visitors interact with the dashboard and public menu pages. This data is anonymised and aggregated, and is not used to track individuals across websites.</p>

<p>For public menu pages hosted on Qayema, we record a session identifier, device type, browser, operating system, and visit duration per session. This data is visible to the restaurant owner as visitor statistics. We do not link this data to any personally identifiable information.</p>

<h2 id="third">Third-party cookies</h2>

<p>Some features of Qayema involve third-party services that may set their own cookies:</p>

<ul>
    <li><strong>Google Fonts:</strong> Used to load the typefaces displayed on the website. Google may log request metadata. See <a href="https://policies.google.com/privacy" target="_blank">Google's Privacy Policy</a>.</li>
    <li><strong>Google OAuth:</strong> If you sign in with Google, Google sets authentication cookies. See <a href="https://policies.google.com/privacy" target="_blank">Google's Privacy Policy</a>.</li>
    <li><strong>Payment processor (Stripe):</strong> If you subscribe to a paid plan, Stripe sets cookies for fraud detection. See <a href="https://stripe.com/privacy" target="_blank">Stripe's Privacy Policy</a>.</li>
</ul>

<p>We do not use advertising cookies or third-party tracking pixels.</p>

<h2 id="control">Controlling cookies</h2>

<p>You can control and delete cookies through your browser settings. Note that disabling essential cookies will prevent you from logging in or using the dashboard.</p>

<p>Browser instructions for managing cookies:</p>
<ul>
    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/en-us/guide/safari/sfri11471/mac" target="_blank">Apple Safari</a></li>
    <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank">Microsoft Edge</a></li>
</ul>

<p>You can also clear localStorage from your browser's Developer Tools (Application tab) to remove persistent functional storage.</p>

<h2 id="changes">Changes to this policy</h2>

<p>We may update this Cookie Policy to reflect changes in our practices or applicable law. When we make significant changes, we will update the "Last updated" date at the top of this page. Continued use of Qayema after changes constitutes acceptance of the updated policy.</p>

<h2 id="contact">Contact</h2>

<p>If you have questions about our use of cookies, please contact us at <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>ØªØ´Ø±Ø­ Ø³ÙŠØ§Ø³Ø© Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ù‡Ø°Ù‡ ÙƒÙŠÙ ØªØ³ØªØ®Ø¯Ù… Ù‚Ø§Ø¦Ù…Ø©ØŒ Ø§Ù„ØªÙŠ ØªØ´ØºÙ‘Ù„Ù‡Ø§ Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠØŒ Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· ÙˆØ§Ù„ØªÙ‚Ù†ÙŠØ§Øª Ø§Ù„Ù…Ù…Ø§Ø«Ù„Ø© Ø¹Ù„Ù‰ qayema.com ÙˆØµÙØ­Ø§Øª Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„Ø¹Ø§Ù…Ø© Ø§Ù„ØªÙŠ Ù†Ø³ØªØ¶ÙŠÙÙ‡Ø§ Ù„Ù„Ù…Ø·Ø§Ø¹Ù…. Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ø®Ø¯Ù…ØªÙ†Ø§ØŒ ÙØ¥Ù†Ùƒ ØªÙˆØ§ÙÙ‚ Ø¹Ù„Ù‰ Ø§Ø³ØªØ®Ø¯Ø§Ù… Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· ÙƒÙ…Ø§ Ù‡Ùˆ Ù…ÙˆØ¶Ø­ Ù‡Ù†Ø§.</p>
</div>

<h2 id="what-ar">Ù…Ø§ Ù‡ÙŠ Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</h2>

<p>Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ù‡ÙŠ Ù…Ù„ÙØ§Øª Ù†ØµÙŠØ© ØµØºÙŠØ±Ø© ØªÙÙˆØ¶Ø¹ Ø¹Ù„Ù‰ Ø¬Ù‡Ø§Ø²Ùƒ Ø¹Ù†Ø¯ Ø²ÙŠØ§Ø±ØªÙƒ Ù…ÙˆÙ‚Ø¹Ù‹Ø§ Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠÙ‹Ø§. ØªÙØ³ØªØ®Ø¯Ù… Ø¹Ù„Ù‰ Ù†Ø·Ø§Ù‚ ÙˆØ§Ø³Ø¹ Ù„Ø¬Ø¹Ù„ Ø§Ù„Ù…ÙˆØ§Ù‚Ø¹ ØªØ¹Ù…Ù„ Ø¨ÙƒÙØ§Ø¡Ø© ÙˆØªØ°ÙƒÙ‘Ø± ØªÙØ¶ÙŠÙ„Ø§ØªÙƒ ÙˆØªØ²ÙˆÙŠØ¯ Ø£ØµØ­Ø§Ø¨ Ø§Ù„Ù…ÙˆØ§Ù‚Ø¹ Ø¨Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª.</p>
<p>ØªØ´Ù…Ù„ Ø§Ù„ØªÙ‚Ù†ÙŠØ§Øª Ø§Ù„Ù…Ù…Ø§Ø«Ù„Ø© Ø§Ù„ØªØ®Ø²ÙŠÙ† Ø§Ù„Ù…Ø­Ù„ÙŠ ÙˆØªØ®Ø²ÙŠÙ† Ø§Ù„Ø¬Ù„Ø³Ø© ÙˆØ¹Ù„Ø§Ù…Ø§Øª Ø§Ù„Ø¨ÙƒØ³Ù„. Ø­ÙŠÙ† Ù†Ø´ÙŠØ± Ø¥Ù„Ù‰ "Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·" ÙÙŠ Ù‡Ø°Ù‡ Ø§Ù„Ø³ÙŠØ§Ø³Ø©ØŒ ÙØ¥Ù†Ù†Ø§ Ù†Ù‚ØµØ¯ Ø¬Ù…ÙŠØ¹ Ù‡Ø°Ù‡ Ø§Ù„ØªÙ‚Ù†ÙŠØ§Øª.</p>

<h2 id="types-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ØªÙŠ Ù†Ø³ØªØ®Ø¯Ù…Ù‡Ø§</h2>

<p>Ù†Ø³ØªØ®Ø¯Ù… Ø«Ù„Ø§Ø« ÙØ¦Ø§Øª Ù…Ù† Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·:</p>
<ul>
    <li><strong>Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ©</strong> â€” Ø¶Ø±ÙˆØ±ÙŠØ© Ù„Ø¹Ù…Ù„ Ø§Ù„Ø®Ø¯Ù…Ø©. Ù„Ø§ ÙŠÙ…ÙƒÙ† ØªØ¹Ø·ÙŠÙ„Ù‡Ø§.</li>
    <li><strong>Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ÙˆØ¸ÙŠÙÙŠØ©</strong> â€” ØªØªØ°ÙƒØ± ØªÙØ¶ÙŠÙ„Ø§ØªÙƒ (Ù…Ø«Ù„ Ù„ØºØ© Ù„ÙˆØ­Ø© Ø§Ù„ØªØ­ÙƒÙ…).</li>
    <li><strong>Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ØªØ­Ù„ÙŠÙ„ÙŠØ©</strong> â€” ØªØ³Ø§Ø¹Ø¯Ù†Ø§ Ø¹Ù„Ù‰ ÙÙ‡Ù… ÙƒÙŠÙÙŠØ© ØªÙØ§Ø¹Ù„ Ø§Ù„Ø²ÙˆØ§Ø± Ù…Ø¹ Ø®Ø¯Ù…ØªÙ†Ø§.</li>
</ul>

<h2 id="essential-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ©</h2>

<p>Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù„ÙØ§Øª Ø¶Ø±ÙˆØ±ÙŠØ© ØªÙ…Ø§Ù…Ù‹Ø§ Ù„ØªØ´ØºÙŠÙ„ Ø§Ù„Ø®Ø¯Ù…Ø©. Ø¨Ø¯ÙˆÙ†Ù‡Ø§ Ù„Ù† ØªØ¹Ù…Ù„ Ù…ÙŠØ²Ø§Øª Ù…Ø«Ù„ ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯Ø®ÙˆÙ„ ÙˆØ§Ù„Ø­ÙØ§Ø¸ Ø¹Ù„Ù‰ Ø¬Ù„Ø³ØªÙƒ ÙˆØ§Ù„Ø­Ù…Ø§ÙŠØ© Ù…Ù† Ù‡Ø¬Ù…Ø§Øª Ø·Ù„Ø¨ Ø§Ù„ØªØ²ÙˆÙŠØ± Ø¹Ø¨Ø± Ø§Ù„Ù…ÙˆØ§Ù‚Ø¹ (CSRF). Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ Ø¥Ù„ØºØ§Ø¡ Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ ÙÙŠ Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù„ÙØ§Øª.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">Ù…Ù„Ù Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">Ø§Ù„ØºØ±Ø¶</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">Ø§Ù„Ù…Ø¯Ø©</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema_session</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">ÙŠØ­Ø§ÙØ¸ Ø¹Ù„Ù‰ Ø¬Ù„Ø³Ø© ØªØ³Ø¬ÙŠÙ„ Ø¯Ø®ÙˆÙ„Ùƒ ÙˆÙŠØ®Ø²Ù‘Ù† Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ù…Ø¤Ù‚ØªØ©</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Ø¬Ù„Ø³Ø© (Ø¥ØºÙ„Ø§Ù‚ Ø§Ù„Ù…ØªØµÙØ­)</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">XSRF-TOKEN</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">ÙŠØ­Ù…ÙŠ Ù…Ù† Ù‡Ø¬Ù…Ø§Øª Ø·Ù„Ø¨ Ø§Ù„ØªØ²ÙˆÙŠØ± Ø¹Ø¨Ø± Ø§Ù„Ù…ÙˆØ§Ù‚Ø¹</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Ø¬Ù„Ø³Ø©</td>
        </tr>
    </tbody>
</table>

<h2 id="functional-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ÙˆØ¸ÙŠÙÙŠØ©</h2>

<p>ØªØªÙŠØ­ Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ÙˆØ¸ÙŠÙÙŠØ© Ù„Ù„Ø®Ø¯Ù…Ø© ØªØ°ÙƒÙ‘Ø± Ø§Ù„Ø®ÙŠØ§Ø±Ø§Øª Ø§Ù„ØªÙŠ ØªØªØ®Ø°Ù‡Ø§ØŒ Ù…Ø«Ù„ Ù„ØºØ© Ù„ÙˆØ­Ø© Ø§Ù„ØªØ­ÙƒÙ… Ø§Ù„Ù…ÙØ¶Ù‘Ù„Ø© Ù„Ø¯ÙŠÙƒ. Ù‚Ø¯ ÙŠØ¤Ø«Ø± ØªØ¹Ø·ÙŠÙ„ Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù„ÙØ§Øª Ø¹Ù„Ù‰ ØªØ¬Ø±Ø¨ØªÙƒ.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">Ù…Ù„Ù Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· / Ø§Ù„ØªØ®Ø²ÙŠÙ†</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">Ø§Ù„ØºØ±Ø¶</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">Ø§Ù„Ù…Ø¯Ø©</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">owner_locale</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">ÙŠØªØ°ÙƒØ± Ù„ØºØ© Ù„ÙˆØ­Ø© Ø§Ù„ØªØ­ÙƒÙ… Ø§Ù„Ù…Ø®ØªØ§Ø±Ø©</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Ø¬Ù„Ø³Ø©</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema_lang</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">ÙŠØªØ°ÙƒØ± ØªÙØ¶ÙŠÙ„Ø§Øª Ø§Ù„Ù„ØºØ© Ø¹Ù„Ù‰ Ø§Ù„Ù…ÙˆÙ‚Ø¹ Ø§Ù„Ø¹Ø§Ù…</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Ø¯Ø§Ø¦Ù… (Ø³Ù†Ø© ÙˆØ§Ø­Ø¯Ø©)</td>
        </tr>
    </tbody>
</table>

<h2 id="analytics-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„ØªØ­Ù„ÙŠÙ„ÙŠØ©</h2>

<p>Ù†Ø³ØªØ®Ø¯Ù… ØªØ­Ù„ÙŠÙ„Ø§Øª Ø®ÙÙŠÙØ© ØªØ±Ø§Ø¹ÙŠ Ø§Ù„Ø®ØµÙˆØµÙŠØ© Ù„ÙÙ‡Ù… ÙƒÙŠÙÙŠØ© ØªÙØ§Ø¹Ù„ Ø§Ù„Ø²ÙˆØ§Ø± Ù…Ø¹ Ù„ÙˆØ­Ø© Ø§Ù„ØªØ­ÙƒÙ… ÙˆØµÙØ­Ø§Øª Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„Ø¹Ø§Ù…Ø©. Ù‡Ø°Ù‡ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ù…Ø¬Ù‡ÙˆÙ„Ø© Ø§Ù„Ù‡ÙˆÙŠØ© ÙˆÙ…Ø¬Ù…Ù‘Ø¹Ø© ÙˆÙ„Ø§ ØªÙØ³ØªØ®Ø¯Ù… Ù„ØªØªØ¨Ø¹ Ø§Ù„Ø£ÙØ±Ø§Ø¯ Ø¹Ø¨Ø± Ø§Ù„Ù…ÙˆØ§Ù‚Ø¹.</p>

<p>Ø¨Ø§Ù„Ù†Ø³Ø¨Ø© Ù„ØµÙØ­Ø§Øª Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„Ø¹Ø§Ù…Ø© Ø§Ù„Ù…Ø³ØªØ¶Ø§ÙØ© Ø¹Ù„Ù‰ Ù‚Ø§Ø¦Ù…Ø©ØŒ Ù†Ø³Ø¬Ù‘Ù„ Ù…Ø¹Ø±Ù‘Ù Ø§Ù„Ø¬Ù„Ø³Ø© ÙˆÙ†ÙˆØ¹ Ø§Ù„Ø¬Ù‡Ø§Ø² ÙˆØ§Ù„Ù…ØªØµÙØ­ ÙˆÙ†Ø¸Ø§Ù… Ø§Ù„ØªØ´ØºÙŠÙ„ ÙˆÙ…Ø¯Ø© Ø§Ù„Ø²ÙŠØ§Ø±Ø© Ù„ÙƒÙ„ Ø¬Ù„Ø³Ø©. Ù‡Ø°Ù‡ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ù…Ø±Ø¦ÙŠØ© Ù„ØµØ§Ø­Ø¨ Ø§Ù„Ù…Ø·Ø¹Ù… ÙƒØ¥Ø­ØµØ§Ø¡Ø§Øª Ø§Ù„Ø²ÙˆØ§Ø±. Ù†Ø­Ù† Ù„Ø§ Ù†Ø±Ø¨Ø· Ù‡Ø°Ù‡ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø¨Ø£ÙŠ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª ØªØ¹Ø±ÙŠÙ Ø´Ø®ØµÙŠØ©.</p>

<h2 id="third-ar">Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ù…Ù† Ø§Ù„Ø£Ø·Ø±Ø§Ù Ø§Ù„Ø«Ø§Ù„Ø«Ø©</h2>

<p>Ø¨Ø¹Ø¶ Ù…ÙŠØ²Ø§Øª Ù‚Ø§Ø¦Ù…Ø© ØªØªØ¶Ù…Ù† Ø®Ø¯Ù…Ø§Øª Ù…Ù† Ø£Ø·Ø±Ø§Ù Ø«Ø§Ù„Ø«Ø© Ù‚Ø¯ ØªØ¶Ø¹ Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„Ø®Ø§ØµØ© Ø¨Ù‡Ø§:</p>

<ul>
    <li><strong>Ø®Ø·ÙˆØ· Google:</strong> ØªÙØ³ØªØ®Ø¯Ù… Ù„ØªØ­Ù…ÙŠÙ„ Ø§Ù„Ø®Ø·ÙˆØ· Ø§Ù„Ù…Ø¹Ø±ÙˆØ¶Ø© Ø¹Ù„Ù‰ Ø§Ù„Ù…ÙˆÙ‚Ø¹. Ù‚Ø¯ ØªØ³Ø¬Ù‘Ù„ Google Ø¨ÙŠØ§Ù†Ø§Øª ØªØ¹Ø±ÙŠÙÙŠØ© Ù„Ù„Ø·Ù„Ø¨Ø§Øª. Ø±Ø§Ø¬Ø¹ <a href="https://policies.google.com/privacy" target="_blank">Ø³ÙŠØ§Ø³Ø© Ø®ØµÙˆØµÙŠØ© Google</a>.</li>
    <li><strong>Google OAuth:</strong> Ø¥Ø°Ø§ Ø³Ø¬Ù‘Ù„Øª Ø¯Ø®ÙˆÙ„Ùƒ Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù… GoogleØŒ ØªØ¶Ø¹ Google Ù…Ù„ÙØ§Øª Ø§Ø±ØªØ¨Ø§Ø· Ù„Ù„Ù…ØµØ§Ø¯Ù‚Ø©. Ø±Ø§Ø¬Ø¹ <a href="https://policies.google.com/privacy" target="_blank">Ø³ÙŠØ§Ø³Ø© Ø®ØµÙˆØµÙŠØ© Google</a>.</li>
    <li><strong>Ù…Ø¹Ø§Ù„Ø¬ Ø§Ù„Ø¯ÙØ¹ (Stripe):</strong> Ø¥Ø°Ø§ Ø§Ø´ØªØ±ÙƒØª ÙÙŠ Ø¨Ø§Ù‚Ø© Ù…Ø¯ÙÙˆØ¹Ø©ØŒ ÙŠØ¶Ø¹ Stripe Ù…Ù„ÙØ§Øª Ø§Ø±ØªØ¨Ø§Ø· Ù„Ù„ÙƒØ´Ù Ø¹Ù† Ø§Ù„Ø§Ø­ØªÙŠØ§Ù„. Ø±Ø§Ø¬Ø¹ <a href="https://stripe.com/privacy" target="_blank">Ø³ÙŠØ§Ø³Ø© Ø®ØµÙˆØµÙŠØ© Stripe</a>.</li>
</ul>

<p>Ù†Ø­Ù† Ù„Ø§ Ù†Ø³ØªØ®Ø¯Ù… Ù…Ù„ÙØ§Øª Ø§Ø±ØªØ¨Ø§Ø· Ø¥Ø¹Ù„Ø§Ù†ÙŠØ© Ø£Ùˆ Ø¨ÙƒØ³Ù„Ø§Øª ØªØªØ¨Ø¹ Ù…Ù† Ø£Ø·Ø±Ø§Ù Ø«Ø§Ù„Ø«Ø©.</p>

<h2 id="control-ar">Ø§Ù„ØªØ­ÙƒÙ… ÙÙŠ Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</h2>

<p>ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ù„ØªØ­ÙƒÙ… ÙÙŠ Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· ÙˆØ­Ø°ÙÙ‡Ø§ Ù…Ù† Ø®Ù„Ø§Ù„ Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ù…ØªØµÙØ­Ùƒ. ÙŠÙØ±Ø¬Ù‰ Ù…Ù„Ø§Ø­Ø¸Ø© Ø£Ù† ØªØ¹Ø·ÙŠÙ„ Ù…Ù„ÙØ§Øª Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ø§Ù„Ø£Ø³Ø§Ø³ÙŠØ© Ø³ÙŠÙ…Ù†Ø¹Ùƒ Ù…Ù† ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¯Ø®ÙˆÙ„ Ø£Ùˆ Ø§Ø³ØªØ®Ø¯Ø§Ù… Ù„ÙˆØ­Ø© Ø§Ù„ØªØ­ÙƒÙ….</p>

<p>ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø§Ù„Ù…ØªØµÙØ­Ø§Øª Ù„Ø¥Ø¯Ø§Ø±Ø© Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·:</p>
<ul>
    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/en-us/guide/safari/sfri11471/mac" target="_blank">Apple Safari</a></li>
    <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank">Microsoft Edge</a></li>
</ul>

<p>ÙŠÙ…ÙƒÙ†Ùƒ Ø£ÙŠØ¶Ù‹Ø§ Ù…Ø³Ø­ Ø§Ù„ØªØ®Ø²ÙŠÙ† Ø§Ù„Ù…Ø­Ù„ÙŠ Ù…Ù† Ø£Ø¯ÙˆØ§Øª Ø§Ù„Ù…Ø·ÙˆÙ‘Ø±ÙŠÙ† ÙÙŠ Ù…ØªØµÙØ­Ùƒ (Ø¹Ù„Ø§Ù…Ø© ØªØ¨ÙˆÙŠØ¨ Ø§Ù„ØªØ·Ø¨ÙŠÙ‚Ø§Øª) Ù„Ø¥Ø²Ø§Ù„Ø© Ø§Ù„ØªØ®Ø²ÙŠÙ† Ø§Ù„ÙˆØ¸ÙŠÙÙŠ Ø§Ù„Ø¯Ø§Ø¦Ù….</p>

<h2 id="changes-ar">Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„Ø³ÙŠØ§Ø³Ø©</h2>

<p>Ù‚Ø¯ Ù†Ø­Ø¯Ù‘Ø« Ø³ÙŠØ§Ø³Ø© Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· Ù‡Ø°Ù‡ Ù„ØªØ¹ÙƒØ³ Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª ÙÙŠ Ù…Ù…Ø§Ø±Ø³Ø§ØªÙ†Ø§ Ø£Ùˆ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡. Ø¹Ù†Ø¯ Ø¥Ø¬Ø±Ø§Ø¡ ØªØºÙŠÙŠØ±Ø§Øª Ø¬ÙˆÙ‡Ø±ÙŠØ©ØŒ Ø³Ù†Ø­Ø¯Ù‘Ø« ØªØ§Ø±ÙŠØ® "Ø¢Ø®Ø± ØªØ­Ø¯ÙŠØ«" ÙÙŠ Ø£Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„ØµÙØ­Ø©. ÙŠÙØ¹Ø¯Ù‘ Ø§Ø³ØªÙ…Ø±Ø§Ø± Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù‚Ø§Ø¦Ù…Ø© Ø¨Ø¹Ø¯ Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª Ù‚Ø¨ÙˆÙ„Ø§Ù‹ Ù„Ù„Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ù…Ø­Ø¯Ù‘Ø«Ø©.</p>

<h2 id="contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</h2>

<p>Ø¥Ø°Ø§ ÙƒØ§Ù†Øª Ù„Ø¯ÙŠÙƒ Ø£Ø³Ø¦Ù„Ø© Ø­ÙˆÙ„ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ù†Ø§ Ù„Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·ØŒ ÙŠØ±Ø¬Ù‰ Ø§Ù„ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ Ø¹Ù„Ù‰ <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

@endif

@endsection
