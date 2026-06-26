@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('title', 'Privacy Policy')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'Ù‚Ø§Ù†ÙˆÙ†ÙŠ')
@section('headline_plain', 'Privacy')
@section('headline_italic', 'Policy')
@section('headline_ar', 'Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø®ØµÙˆØµÙŠØ©')
@section('updated', 'Last updated: May 9, 2025')
@section('updated_ar', 'Ø¢Ø®Ø± ØªØ­Ø¯ÙŠØ«: 9 Ù…Ø§ÙŠÙˆ 2025')

@section('toc')
    <li><a href="#information">Information we collect</a></li>
    <li><a href="#use">How we use your information</a></li>
    <li><a href="#sharing">Sharing your information</a></li>
    <li><a href="#storage">Data storage & security</a></li>
    <li><a href="#cookies">Cookies</a></li>
    <li><a href="#rights">Your rights</a></li>
    <li><a href="#children">Children's privacy</a></li>
    <li><a href="#changes">Changes to this policy</a></li>
    <li><a href="#contact">Contact us</a></li>
@endsection

@section('toc_ar')
    <li><a href="#information-ar">Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„ØªÙŠ Ù†Ø¬Ù…Ø¹Ù‡Ø§</a></li>
    <li><a href="#use-ar">ÙƒÙŠÙ Ù†Ø³ØªØ®Ø¯Ù… Ù…Ø¹Ù„ÙˆÙ…Ø§ØªÙƒ</a></li>
    <li><a href="#sharing-ar">Ù…Ø´Ø§Ø±ÙƒØ© Ù…Ø¹Ù„ÙˆÙ…Ø§ØªÙƒ</a></li>
    <li><a href="#storage-ar">ØªØ®Ø²ÙŠÙ† Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ÙˆØ§Ù„Ø£Ù…Ø§Ù†</a></li>
    <li><a href="#cookies-ar">Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</a></li>
    <li><a href="#rights-ar">Ø­Ù‚ÙˆÙ‚Ùƒ</a></li>
    <li><a href="#children-ar">Ø®ØµÙˆØµÙŠØ© Ø§Ù„Ø£Ø·ÙØ§Ù„</a></li>
    <li><a href="#changes-ar">Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„Ø³ÙŠØ§Ø³Ø©</a></li>
    <li><a href="#contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</a></li>
@endsection

@section('legal_body')

@unless ($isAr)

<div class="legal-highlight">
    <p>This Privacy Policy explains how Qayema, operated by Lebify Group, collects, uses, and protects information about you when you use our service at qayema.com. By using Qayema, you agree to the practices described here.</p>
</div>

<h2 id="information">Information we collect</h2>

<h3>Account information</h3>
<p>When you register for a Qayema account (via email or Google), we collect your name, email address, and authentication credentials. We do not store your Google password.</p>

<h3>Restaurant & menu data</h3>
<p>To provide the service, we store the restaurant information, categories, dishes, prices, images, and settings you add to your account. This data belongs to you.</p>

<h3>Usage & analytics data</h3>
<p>We collect information about how you use the dashboard, including pages visited, actions taken, and session duration. For public menus, we collect anonymised visitor statistics â€” session identifiers, device type, browser, operating system, time on page, and whether the visit came from a QR code scan. We do not collect names or personal details of your guests.</p>

<h3>Technical data</h3>
<p>We automatically receive standard server log data, including IP addresses, request timestamps, and HTTP headers. This is used for security, debugging, and infrastructure monitoring.</p>

<h3>Payment data</h3>
<p>Qayema does not directly handle payment card information. Payments are processed by third-party providers (such as Stripe) who are responsible for the security of your financial data.</p>

<h2 id="use">How we use your information</h2>

<ul>
    <li>To create and maintain your account and restaurant profile</li>
    <li>To deliver the Qayema dashboard and public menu features</li>
    <li>To provide you with visitor analytics for your menu</li>
    <li>To send transactional emails (account confirmation, password reset)</li>
    <li>To detect and prevent fraud, abuse, and security incidents</li>
    <li>To improve and develop our service based on usage patterns</li>
    <li>To comply with legal obligations</li>
</ul>

<p>We do not sell your data. We do not use your data for advertising purposes.</p>

<h2 id="sharing">Sharing your information</h2>

<p>We share your information only in the following circumstances:</p>

<ul>
    <li><strong>Service providers:</strong> Infrastructure partners (hosting, databases, email delivery, image storage) that process data on our behalf under strict data processing agreements.</li>
    <li><strong>AI services:</strong> When you use the AI Menu Scanner, images you upload are sent to Google's Gemini API for processing. No personally identifiable information is included.</li>
    <li><strong>Legal requirements:</strong> If required by law, court order, or to protect the rights and safety of Qayema or others.</li>
    <li><strong>Business transfers:</strong> In connection with a merger, acquisition, or sale of assets, with appropriate confidentiality obligations.</li>
</ul>

<p>We never sell, rent, or trade your personal data to third parties for their own marketing purposes.</p>

<h2 id="storage">Data storage & security</h2>

<p>Your data is stored on secure servers. We apply industry-standard security measures including encryption in transit (HTTPS/TLS), encrypted storage for sensitive fields, access controls, and regular security reviews.</p>
<p>No method of transmission over the internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security. In the event of a data breach that affects your rights, we will notify you in accordance with applicable law.</p>
<p>We retain your data for as long as your account is active. When you delete your account, your data is permanently removed from our systems within 30 days, except where retention is required by law.</p>

<h2 id="cookies">Cookies</h2>

<p>We use cookies and similar technologies to operate the service. For full details, please read our <a href="{{ route('cookies') }}">Cookie Policy</a>.</p>

<h2 id="rights">Your rights</h2>

<p>Depending on your location, you may have the following rights regarding your personal data:</p>

<ul>
    <li><strong>Access:</strong> Request a copy of the personal data we hold about you.</li>
    <li><strong>Correction:</strong> Ask us to correct inaccurate or incomplete data.</li>
    <li><strong>Deletion:</strong> Request deletion of your account and associated data.</li>
    <li><strong>Portability:</strong> Receive your data in a machine-readable format.</li>
    <li><strong>Objection:</strong> Object to certain types of processing.</li>
    <li><strong>Restriction:</strong> Request that we limit how we use your data.</li>
</ul>

<p>To exercise any of these rights, contact us at <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>. We will respond within 30 days.</p>

<h2 id="children">Children's privacy</h2>

<p>Qayema is intended for restaurant owners and operators and is not directed at children under the age of 16. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us and we will delete it promptly.</p>

<h2 id="changes">Changes to this policy</h2>

<p>We may update this Privacy Policy from time to time. When we make material changes, we will notify you by email or by displaying a notice in your dashboard at least 14 days before the changes take effect. Continued use of Qayema after that date constitutes acceptance of the updated policy.</p>

<h2 id="contact">Contact us</h2>

<p>If you have questions or concerns about this Privacy Policy or our data practices, please contact us:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a></li>
    <li><strong>Company:</strong> Lebify Group</li>
    <li><strong>Location:</strong> Barja, Lebanon</li>
</ul>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>ØªØ´Ø±Ø­ Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø®ØµÙˆØµÙŠØ© Ù‡Ø°Ù‡ ÙƒÙŠÙ ØªØ¬Ù…Ø¹ Ù‚Ø§Ø¦Ù…Ø©ØŒ Ø§Ù„ØªÙŠ ØªØ´ØºÙ‘Ù„Ù‡Ø§ Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠØŒ Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø¹Ù†Ùƒ ÙˆØªØ³ØªØ®Ø¯Ù…Ù‡Ø§ ÙˆØªØ­Ù…ÙŠÙ‡Ø§ Ø¹Ù†Ø¯ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ø®Ø¯Ù…ØªÙ†Ø§ Ø¹Ù„Ù‰ qayema.com. Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù‚Ø§Ø¦Ù…Ø©ØŒ ÙØ¥Ù†Ùƒ ØªÙˆØ§ÙÙ‚ Ø¹Ù„Ù‰ Ø§Ù„Ù…Ù…Ø§Ø±Ø³Ø§Øª Ø§Ù„Ù…ÙˆØ¶Ø­Ø© Ù‡Ù†Ø§.</p>
</div>

<h2 id="information-ar">Ø§Ù„Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„ØªÙŠ Ù†Ø¬Ù…Ø¹Ù‡Ø§</h2>

<h3>Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ø­Ø³Ø§Ø¨</h3>
<p>Ø¹Ù†Ø¯ Ø§Ù„ØªØ³Ø¬ÙŠÙ„ ÙÙŠ Ø­Ø³Ø§Ø¨ Ù‚Ø§Ø¦Ù…Ø© (Ø¹Ø¨Ø± Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ Ø£Ùˆ Google)ØŒ Ù†Ø¬Ù…Ø¹ Ø§Ø³Ù…Ùƒ ÙˆØ¹Ù†ÙˆØ§Ù† Ø¨Ø±ÙŠØ¯Ùƒ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ ÙˆØ¨ÙŠØ§Ù†Ø§Øª Ø§Ø¹ØªÙ…Ø§Ø¯ Ø§Ù„Ù…ØµØ§Ø¯Ù‚Ø©. Ù†Ø­Ù† Ù„Ø§ Ù†Ø­ØªÙØ¸ Ø¨ÙƒÙ„Ù…Ø© Ù…Ø±ÙˆØ± Google Ø§Ù„Ø®Ø§ØµØ© Ø¨Ùƒ.</p>

<h3>Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ù…Ø·Ø¹Ù… ÙˆØ§Ù„Ù‚Ø§Ø¦Ù…Ø©</h3>
<p>Ù„ØªÙ‚Ø¯ÙŠÙ… Ø§Ù„Ø®Ø¯Ù…Ø©ØŒ Ù†Ø­ØªÙØ¸ Ø¨Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ù…Ø·Ø¹Ù… ÙˆØ§Ù„ÙØ¦Ø§Øª ÙˆØ§Ù„Ø£Ø·Ø¨Ø§Ù‚ ÙˆØ§Ù„Ø£Ø³Ø¹Ø§Ø± ÙˆØ§Ù„ØµÙˆØ± ÙˆØ§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„ØªÙŠ ØªØ¶ÙŠÙÙ‡Ø§ Ø¥Ù„Ù‰ Ø­Ø³Ø§Ø¨Ùƒ. Ù‡Ø°Ù‡ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ù…Ù„Ùƒ Ù„Ùƒ.</p>

<h3>Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù… ÙˆØ§Ù„ØªØ­Ù„ÙŠÙ„Ø§Øª</h3>
<p>Ù†Ø¬Ù…Ø¹ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø­ÙˆÙ„ ÙƒÙŠÙÙŠØ© Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù„ÙˆØ­Ø© Ø§Ù„ØªØ­ÙƒÙ…ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ Ø§Ù„ØµÙØ­Ø§Øª Ø§Ù„ØªÙŠ ØªØ²ÙˆØ±Ù‡Ø§ ÙˆØ§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª Ø§Ù„ØªÙŠ ØªØªØ®Ø°Ù‡Ø§ ÙˆÙ…Ø¯Ø© Ø§Ù„Ø¬Ù„Ø³Ø©. Ø¨Ø§Ù„Ù†Ø³Ø¨Ø© Ù„Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„Ø¹Ø§Ù…Ø©ØŒ Ù†Ø¬Ù…Ø¹ Ø¥Ø­ØµØ§Ø¡Ø§Øª Ø²ÙˆØ§Ø± Ù…Ø¬Ù‡ÙˆÙ„Ø© Ø§Ù„Ù‡ÙˆÙŠØ© ØªØ´Ù…Ù„: Ù…Ø¹Ø±Ù‘ÙØ§Øª Ø§Ù„Ø¬Ù„Ø³Ø© ÙˆÙ†ÙˆØ¹ Ø§Ù„Ø¬Ù‡Ø§Ø² ÙˆØ§Ù„Ù…ØªØµÙØ­ ÙˆÙ†Ø¸Ø§Ù… Ø§Ù„ØªØ´ØºÙŠÙ„ ÙˆØ§Ù„ÙˆÙ‚Øª Ø§Ù„Ù…Ù‚Ø¶ÙŠ ÙÙŠ Ø§Ù„ØµÙØ­Ø© ÙˆÙ…Ø§ Ø¥Ø°Ø§ ÙƒØ§Ù†Øª Ø§Ù„Ø²ÙŠØ§Ø±Ø© Ù…Ù† Ø±Ù…Ø² QR. Ù†Ø­Ù† Ù„Ø§ Ù†Ø¬Ù…Ø¹ Ø£Ø³Ù…Ø§Ø¡ Ø£Ùˆ ØªÙØ§ØµÙŠÙ„ Ø´Ø®ØµÙŠØ© Ù„Ø¶ÙŠÙˆÙÙƒ.</p>

<h3>Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„ØªÙ‚Ù†ÙŠØ©</h3>
<p>Ù†ØªÙ„Ù‚Ù‰ ØªÙ„Ù‚Ø§Ø¦ÙŠÙ‹Ø§ Ø¨ÙŠØ§Ù†Ø§Øª Ø³Ø¬Ù„ Ø§Ù„Ø®Ø§Ø¯Ù… Ø§Ù„Ù‚ÙŠØ§Ø³ÙŠØ©ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ Ø¹Ù†Ø§ÙˆÙŠÙ† IP ÙˆØ·ÙˆØ§Ø¨Ø¹ ÙˆÙ‚Øª Ø§Ù„Ø·Ù„Ø¨Ø§Øª ÙˆØªØ±ÙˆÙŠØ³Ø§Øª HTTP. ØªÙØ³ØªØ®Ø¯Ù… Ù‡Ø°Ù‡ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ù„Ø£ØºØ±Ø§Ø¶ Ø§Ù„Ø£Ù…Ø§Ù† ÙˆØ§Ù„ØªØµØ­ÙŠØ­ ÙˆÙ…Ø±Ø§Ù‚Ø¨Ø© Ø§Ù„Ø¨Ù†ÙŠØ© Ø§Ù„ØªØ­ØªÙŠØ©.</p>

<h3>Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø¯ÙØ¹</h3>
<p>Ù„Ø§ ØªØªØ¹Ø§Ù…Ù„ Ù‚Ø§Ø¦Ù…Ø© Ù…Ø¨Ø§Ø´Ø±Ø©Ù‹ Ù…Ø¹ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø¨Ø·Ø§Ù‚Ø§Øª Ø§Ù„Ø¯ÙØ¹. ØªØªÙ… Ù…Ø¹Ø§Ù„Ø¬Ø© Ø§Ù„Ù…Ø¯ÙÙˆØ¹Ø§Øª Ø¹Ø¨Ø± Ù…Ø²ÙˆØ¯ÙŠ Ø®Ø¯Ù…Ø© Ø®Ø§Ø±Ø¬ÙŠÙŠÙ† (Ù…Ø«Ù„ Stripe) Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„ÙŠÙ† Ø¹Ù† Ø£Ù…Ø§Ù† Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ø§Ù„Ù…Ø§Ù„ÙŠØ©.</p>

<h2 id="use-ar">ÙƒÙŠÙ Ù†Ø³ØªØ®Ø¯Ù… Ù…Ø¹Ù„ÙˆÙ…Ø§ØªÙƒ</h2>

<ul>
    <li>Ù„Ø¥Ù†Ø´Ø§Ø¡ Ø­Ø³Ø§Ø¨Ùƒ ÙˆÙ…Ù„Ù Ù…Ø·Ø¹Ù…Ùƒ ÙˆØ§Ù„Ø­ÙØ§Ø¸ Ø¹Ù„ÙŠÙ‡Ù…Ø§</li>
    <li>Ù„ØªÙ‚Ø¯ÙŠÙ… Ù„ÙˆØ­Ø© ØªØ­ÙƒÙ… Ù‚Ø§Ø¦Ù…Ø© ÙˆÙ…ÙŠØ²Ø§Øª Ø§Ù„Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ø¹Ø§Ù…Ø©</li>
    <li>Ù„ØªØ²ÙˆÙŠØ¯Ùƒ Ø¨Ø¥Ø­ØµØ§Ø¡Ø§Øª Ø§Ù„Ø²ÙˆØ§Ø± Ù„Ù‚Ø§Ø¦Ù…ØªÙƒ</li>
    <li>Ù„Ø¥Ø±Ø³Ø§Ù„ Ø±Ø³Ø§Ø¦Ù„ Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ Ø§Ù„ØªØ¹Ø§Ù…Ù„ÙŠØ© (ØªØ£ÙƒÙŠØ¯ Ø§Ù„Ø­Ø³Ø§Ø¨ØŒ Ø¥Ø¹Ø§Ø¯Ø© ØªØ¹ÙŠÙŠÙ† ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ±)</li>
    <li>Ù„Ù„ÙƒØ´Ù Ø¹Ù† Ø§Ù„Ø§Ø­ØªÙŠØ§Ù„ ÙˆØ§Ù„Ø¥Ø³Ø§Ø¡Ø© ÙˆØ§Ù„Ø­ÙˆØ§Ø¯Ø« Ø§Ù„Ø£Ù…Ù†ÙŠØ© ÙˆÙ…Ù†Ø¹Ù‡Ø§</li>
    <li>Ù„ØªØ­Ø³ÙŠÙ† Ø®Ø¯Ù…ØªÙ†Ø§ ÙˆØªØ·ÙˆÙŠØ±Ù‡Ø§ Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø£Ù†Ù…Ø§Ø· Ø§Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù…</li>
    <li>Ù„Ù„Ø§Ù…ØªØ«Ø§Ù„ Ù„Ù„Ø§Ù„ØªØ²Ø§Ù…Ø§Øª Ø§Ù„Ù‚Ø§Ù†ÙˆÙ†ÙŠØ©</li>
</ul>

<p>Ù†Ø­Ù† Ù„Ø§ Ù†Ø¨ÙŠØ¹ Ø¨ÙŠØ§Ù†Ø§ØªÙƒ. Ù†Ø­Ù† Ù„Ø§ Ù†Ø³ØªØ®Ø¯Ù… Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ù„Ø£ØºØ±Ø§Ø¶ Ø¥Ø¹Ù„Ø§Ù†ÙŠØ©.</p>

<h2 id="sharing-ar">Ù…Ø´Ø§Ø±ÙƒØ© Ù…Ø¹Ù„ÙˆÙ…Ø§ØªÙƒ</h2>

<p>Ù†Ø´Ø§Ø±Ùƒ Ù…Ø¹Ù„ÙˆÙ…Ø§ØªÙƒ ÙÙ‚Ø· ÙÙŠ Ø§Ù„Ø­Ø§Ù„Ø§Øª Ø§Ù„ØªØ§Ù„ÙŠØ©:</p>

<ul>
    <li><strong>Ù…Ø²ÙˆØ¯Ùˆ Ø§Ù„Ø®Ø¯Ù…Ø©:</strong> Ø´Ø±ÙƒØ§Ø¡ Ø§Ù„Ø¨Ù†ÙŠØ© Ø§Ù„ØªØ­ØªÙŠØ© (Ø§Ù„Ø§Ø³ØªØ¶Ø§ÙØ© ÙˆÙ‚ÙˆØ§Ø¹Ø¯ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ÙˆØªØ³Ù„ÙŠÙ… Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ ÙˆØªØ®Ø²ÙŠÙ† Ø§Ù„ØµÙˆØ±) Ø§Ù„Ø°ÙŠÙ† ÙŠØ¹Ø§Ù„Ø¬ÙˆÙ† Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ù†ÙŠØ§Ø¨Ø©Ù‹ Ø¹Ù†Ø§ ÙˆÙÙ‚ Ø§ØªÙØ§Ù‚ÙŠØ§Øª Ù…Ø¹Ø§Ù„Ø¬Ø© Ø¨ÙŠØ§Ù†Ø§Øª ØµØ§Ø±Ù…Ø©.</li>
    <li><strong>Ø®Ø¯Ù…Ø§Øª Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ:</strong> Ø¹Ù†Ø¯ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù…Ø§Ø³Ø­ Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø¨Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠØŒ ØªÙØ±Ø³Ù„ Ø§Ù„ØµÙˆØ± Ø§Ù„ØªÙŠ ØªØ±ÙØ¹Ù‡Ø§ Ø¥Ù„Ù‰ Google Gemini API Ù„Ù„Ù…Ø¹Ø§Ù„Ø¬Ø©. Ù„Ø§ ØªÙØ¯Ø±Ø¬ Ø£ÙŠ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª ØªØ¹Ø±ÙŠÙ Ø´Ø®ØµÙŠØ©.</li>
    <li><strong>Ø§Ù„Ù…ØªØ·Ù„Ø¨Ø§Øª Ø§Ù„Ù‚Ø§Ù†ÙˆÙ†ÙŠØ©:</strong> Ø¥Ø°Ø§ Ø·ÙÙ„Ø¨ Ø°Ù„Ùƒ Ø¨Ù…ÙˆØ¬Ø¨ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø£Ùˆ Ø£Ù…Ø± Ù‚Ø¶Ø§Ø¦ÙŠ Ø£Ùˆ Ù„Ø­Ù…Ø§ÙŠØ© Ø­Ù‚ÙˆÙ‚ ÙˆØ³Ù„Ø§Ù…Ø© Ù‚Ø§Ø¦Ù…Ø© Ø£Ùˆ Ø§Ù„Ø¢Ø®Ø±ÙŠÙ†.</li>
    <li><strong>Ø§Ù„ØªØ­ÙˆÙŠÙ„Ø§Øª Ø§Ù„ØªØ¬Ø§Ø±ÙŠØ©:</strong> ÙÙŠ Ø³ÙŠØ§Ù‚ Ø§Ù„Ø§Ù†Ø¯Ù…Ø§Ø¬ Ø£Ùˆ Ø§Ù„Ø§Ø³ØªØ­ÙˆØ§Ø° Ø£Ùˆ Ø¨ÙŠØ¹ Ø§Ù„Ø£ØµÙˆÙ„ØŒ Ù…Ø¹ Ø§Ù„ØªØ²Ø§Ù…Ø§Øª Ø³Ø±ÙŠØ© Ù…Ù†Ø§Ø³Ø¨Ø©.</li>
</ul>

<p>Ù†Ø­Ù† Ù„Ø§ Ù†Ø¨ÙŠØ¹ Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ø§Ù„Ø´Ø®ØµÙŠØ© Ø£Ùˆ Ù†Ø¤Ø¬Ø±Ù‡Ø§ Ø£Ùˆ Ù†ØªØ§Ø¬Ø± Ø¨Ù‡Ø§ Ù…Ø¹ Ø£Ø·Ø±Ø§Ù Ø«Ø§Ù„Ø«Ø© Ù„Ø£ØºØ±Ø§Ø¶Ù‡Ù… Ø§Ù„ØªØ³ÙˆÙŠÙ‚ÙŠØ© Ø§Ù„Ø®Ø§ØµØ©.</p>

<h2 id="storage-ar">ØªØ®Ø²ÙŠÙ† Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ÙˆØ§Ù„Ø£Ù…Ø§Ù†</h2>

<p>ÙŠØªÙ… ØªØ®Ø²ÙŠÙ† Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ø¹Ù„Ù‰ Ø®ÙˆØ§Ø¯Ù… Ø¢Ù…Ù†Ø©. Ù†Ø·Ø¨Ù‘Ù‚ Ù…Ø¹Ø§ÙŠÙŠØ± Ø£Ù…Ø§Ù† ØµÙ†Ø§Ø¹ÙŠØ© ØªØ´Ù…Ù„ Ø§Ù„ØªØ´ÙÙŠØ± Ø£Ø«Ù†Ø§Ø¡ Ø§Ù„Ù†Ù‚Ù„ (HTTPS/TLS) ÙˆØ§Ù„ØªØ´ÙÙŠØ± Ù„Ù„Ø­Ù‚ÙˆÙ„ Ø§Ù„Ø­Ø³Ø§Ø³Ø© ÙˆØ¶ÙˆØ§Ø¨Ø· Ø§Ù„ÙˆØµÙˆÙ„ ÙˆÙ…Ø±Ø§Ø¬Ø¹Ø§Øª Ø§Ù„Ø£Ù…Ø§Ù† Ø§Ù„Ù…Ù†ØªØ¸Ù…Ø©.</p>
<p>Ù„Ø§ ØªÙˆØ¬Ø¯ Ø·Ø±ÙŠÙ‚Ø© Ù†Ù‚Ù„ Ø¹Ø¨Ø± Ø§Ù„Ø¥Ù†ØªØ±Ù†Øª Ø¢Ù…Ù†Ø© Ø¨Ù†Ø³Ø¨Ø© 100%. Ø¨ÙŠÙ†Ù…Ø§ Ù†Ø³Ø¹Ù‰ Ø¬Ø§Ù‡Ø¯ÙŠÙ† Ù„Ø­Ù…Ø§ÙŠØ© Ø¨ÙŠØ§Ù†Ø§ØªÙƒØŒ Ù„Ø§ ÙŠÙ…ÙƒÙ†Ù†Ø§ Ø¶Ù…Ø§Ù† Ø§Ù„Ø£Ù…Ø§Ù† Ø§Ù„Ù…Ø·Ù„Ù‚. ÙÙŠ Ø­Ø§Ù„ Ø­Ø¯ÙˆØ« Ø§Ø®ØªØ±Ø§Ù‚ Ù„Ù„Ø¨ÙŠØ§Ù†Ø§Øª ÙŠØ¤Ø«Ø± Ø¹Ù„Ù‰ Ø­Ù‚ÙˆÙ‚ÙƒØŒ Ø³Ù†ÙØ®Ø·Ø±Ùƒ ÙˆÙÙ‚Ù‹Ø§ Ù„Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡.</p>
<p>Ù†Ø­ØªÙØ¸ Ø¨Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ø·Ø§Ù„Ù…Ø§ Ø­Ø³Ø§Ø¨Ùƒ Ù†Ø´Ø·. Ø¹Ù†Ø¯ Ø­Ø°Ù Ø­Ø³Ø§Ø¨ÙƒØŒ ØªÙØ²Ø§Ù„ Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ù†Ù‡Ø§Ø¦ÙŠÙ‹Ø§ Ù…Ù† Ø£Ù†Ø¸Ù…ØªÙ†Ø§ Ø®Ù„Ø§Ù„ 30 ÙŠÙˆÙ…Ù‹Ø§ØŒ Ø¥Ù„Ø§ Ø¥Ø°Ø§ ÙƒØ§Ù† Ø§Ù„Ø§Ø­ØªÙØ§Ø¸ Ø¨Ù‡Ø§ Ù…Ø·Ù„ÙˆØ¨Ù‹Ø§ Ù‚Ø§Ù†ÙˆÙ†Ù‹Ø§.</p>

<h2 id="cookies-ar">Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</h2>

<p>Ù†Ø³ØªØ®Ø¯Ù… Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø· ÙˆØ§Ù„ØªÙ‚Ù†ÙŠØ§Øª Ø§Ù„Ù…Ù…Ø§Ø«Ù„Ø© Ù„ØªØ´ØºÙŠÙ„ Ø§Ù„Ø®Ø¯Ù…Ø©. Ù„Ù…Ø²ÙŠØ¯ Ù…Ù† Ø§Ù„ØªÙØ§ØµÙŠÙ„ØŒ ÙŠØ±Ø¬Ù‰ Ù‚Ø±Ø§Ø¡Ø© <a href="{{ route('cookies') }}">Ø³ÙŠØ§Ø³Ø© Ù…Ù„ÙØ§Øª ØªØ¹Ø±ÙŠÙ Ø§Ù„Ø§Ø±ØªØ¨Ø§Ø·</a>.</p>

<h2 id="rights-ar">Ø­Ù‚ÙˆÙ‚Ùƒ</h2>

<p>Ø¨Ø­Ø³Ø¨ Ù…ÙˆÙ‚Ø¹ÙƒØŒ Ù‚Ø¯ ØªØªÙ…ØªØ¹ Ø¨Ø§Ù„Ø­Ù‚ÙˆÙ‚ Ø§Ù„ØªØ§Ù„ÙŠØ© ÙÙŠÙ…Ø§ ÙŠØ®Øµ Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ø§Ù„Ø´Ø®ØµÙŠØ©:</p>

<ul>
    <li><strong>Ø§Ù„ÙˆØµÙˆÙ„:</strong> Ø·Ù„Ø¨ Ù†Ø³Ø®Ø© Ù…Ù† Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø´Ø®ØµÙŠØ© Ø§Ù„ØªÙŠ Ù†Ø­ØªÙØ¸ Ø¨Ù‡Ø§ Ø¹Ù†Ùƒ.</li>
    <li><strong>Ø§Ù„ØªØµØ­ÙŠØ­:</strong> Ø·Ù„Ø¨ ØªØµØ­ÙŠØ­ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ØºÙŠØ± Ø§Ù„Ø¯Ù‚ÙŠÙ‚Ø© Ø£Ùˆ ØºÙŠØ± Ø§Ù„Ù…ÙƒØªÙ…Ù„Ø©.</li>
    <li><strong>Ø§Ù„Ø­Ø°Ù:</strong> Ø·Ù„Ø¨ Ø­Ø°Ù Ø­Ø³Ø§Ø¨Ùƒ ÙˆØ§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ù‡.</li>
    <li><strong>Ù‚Ø§Ø¨Ù„ÙŠØ© Ø§Ù„Ù†Ù‚Ù„:</strong> Ø§Ù„Ø­ØµÙˆÙ„ Ø¹Ù„Ù‰ Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ø¨ØªÙ†Ø³ÙŠÙ‚ Ù‚Ø§Ø¨Ù„ Ù„Ù„Ù‚Ø±Ø§Ø¡Ø© Ø¢Ù„ÙŠÙ‹Ø§.</li>
    <li><strong>Ø§Ù„Ø§Ø¹ØªØ±Ø§Ø¶:</strong> Ø§Ù„Ø§Ø¹ØªØ±Ø§Ø¶ Ø¹Ù„Ù‰ Ø£Ù†ÙˆØ§Ø¹ Ù…Ø¹ÙŠÙ†Ø© Ù…Ù† Ø§Ù„Ù…Ø¹Ø§Ù„Ø¬Ø©.</li>
    <li><strong>Ø§Ù„ØªÙ‚ÙŠÙŠØ¯:</strong> Ø·Ù„Ø¨ ØªØ­Ø¯ÙŠØ¯ ÙƒÙŠÙÙŠØ© Ø§Ø³ØªØ®Ø¯Ø§Ù…Ù†Ø§ Ù„Ø¨ÙŠØ§Ù†Ø§ØªÙƒ.</li>
</ul>

<p>Ù„Ù…Ù…Ø§Ø±Ø³Ø© Ø£ÙŠ Ù…Ù† Ù‡Ø°Ù‡ Ø§Ù„Ø­Ù‚ÙˆÙ‚ØŒ ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ Ø¹Ù„Ù‰ <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>. Ø³Ù†Ø±Ø¯Ù‘ Ø®Ù„Ø§Ù„ 30 ÙŠÙˆÙ…Ù‹Ø§.</p>

<h2 id="children-ar">Ø®ØµÙˆØµÙŠØ© Ø§Ù„Ø£Ø·ÙØ§Ù„</h2>

<p>Ù‚Ø§Ø¦Ù…Ø© Ù…ÙˆØ¬Ù‘Ù‡Ø© Ù„Ø£ØµØ­Ø§Ø¨ Ø§Ù„Ù…Ø·Ø§Ø¹Ù… ÙˆØ§Ù„Ù…Ø´ØºÙ‘Ù„ÙŠÙ† ÙˆÙ„ÙŠØ³Øª Ù…ÙˆØ¬Ù‘Ù‡Ø© Ù„Ù„Ø£Ø·ÙØ§Ù„ Ø¯ÙˆÙ† Ø³Ù† 16 Ø¹Ø§Ù…Ù‹Ø§. Ù†Ø­Ù† Ù„Ø§ Ù†Ø¬Ù…Ø¹ Ø¹Ù† Ù‚ØµØ¯ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø´Ø®ØµÙŠØ© Ù…Ù† Ø§Ù„Ø£Ø·ÙØ§Ù„. Ø¥Ø°Ø§ ÙƒÙ†Øª ØªØ¹ØªÙ‚Ø¯ Ø£Ù† Ø·ÙÙ„Ø§Ù‹ Ù‚Ø¯ Ø²ÙˆÙ‘Ø¯Ù†Ø§ Ø¨Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø´Ø®ØµÙŠØ©ØŒ ÙÙŠØ±Ø¬Ù‰ Ø§Ù„ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ ÙˆØ³Ù†Ø­Ø°ÙÙ‡Ø§ ÙÙˆØ±Ù‹Ø§.</p>

<h2 id="changes-ar">Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„Ø³ÙŠØ§Ø³Ø©</h2>

<p>Ù‚Ø¯ Ù†Ø­Ø¯Ù‘Ø« Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø®ØµÙˆØµÙŠØ© Ù‡Ø°Ù‡ Ù…Ù† ÙˆÙ‚Øª Ù„Ø¢Ø®Ø±. Ø¹Ù†Ø¯ Ø¥Ø¬Ø±Ø§Ø¡ ØªØºÙŠÙŠØ±Ø§Øª Ø¬ÙˆÙ‡Ø±ÙŠØ©ØŒ Ø³Ù†ÙØ®Ø·Ø±Ùƒ Ø¹Ø¨Ø± Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ Ø£Ùˆ Ø¨Ø¹Ø±Ø¶ Ø¥Ø´Ø¹Ø§Ø± ÙÙŠ Ù„ÙˆØ­Ø© ØªØ­ÙƒÙ…Ùƒ Ù‚Ø¨Ù„ 14 ÙŠÙˆÙ…Ù‹Ø§ Ø¹Ù„Ù‰ Ø§Ù„Ø£Ù‚Ù„ Ù…Ù† Ø³Ø±ÙŠØ§Ù† Ø§Ù„ØªØºÙŠÙŠØ±Ø§Øª. ÙŠÙØ¹Ø¯Ù‘ Ø§Ø³ØªÙ…Ø±Ø§Ø± Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù‚Ø§Ø¦Ù…Ø© Ø¨Ø¹Ø¯ Ø°Ù„Ùƒ Ø§Ù„ØªØ§Ø±ÙŠØ® Ù‚Ø¨ÙˆÙ„Ø§Ù‹ Ù„Ù„Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ù…Ø­Ø¯Ù‘Ø«Ø©.</p>

<h2 id="contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</h2>

<p>Ø¥Ø°Ø§ ÙƒØ§Ù†Øª Ù„Ø¯ÙŠÙƒ Ø£Ø³Ø¦Ù„Ø© Ø£Ùˆ Ù…Ø®Ø§ÙˆÙ Ø¨Ø´Ø£Ù† Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø®ØµÙˆØµÙŠØ© Ù‡Ø°Ù‡ Ø£Ùˆ Ù…Ù…Ø§Ø±Ø³Ø§ØªÙ†Ø§ Ø§Ù„Ù…ØªØ¹Ù„Ù‚Ø© Ø¨Ø§Ù„Ø¨ÙŠØ§Ù†Ø§ØªØŒ ÙŠØ±Ø¬Ù‰ Ø§Ù„ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§:</p>
<ul>
    <li><strong>Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ:</strong> <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a></li>
    <li><strong>Ø§Ù„Ø´Ø±ÙƒØ©:</strong> Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠ</li>
    <li><strong>Ø§Ù„Ù…ÙˆÙ‚Ø¹:</strong> Ø¨Ø±Ø¬Ø§ØŒ Ù„Ø¨Ù†Ø§Ù†</li>
</ul>

@endif

@endsection
