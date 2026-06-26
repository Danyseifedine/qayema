@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('title', 'Terms of Service')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'Ù‚Ø§Ù†ÙˆÙ†ÙŠ')
@section('headline_plain', 'Terms of')
@section('headline_italic', 'Service')
@section('headline_ar', 'Ø´Ø±ÙˆØ· Ø§Ù„Ø®Ø¯Ù…Ø©')
@section('updated', 'Last updated: May 9, 2025')
@section('updated_ar', 'Ø¢Ø®Ø± ØªØ­Ø¯ÙŠØ«: 9 Ù…Ø§ÙŠÙˆ 2025')

@section('toc')
    <li><a href="#acceptance">Acceptance of terms</a></li>
    <li><a href="#service">The service</a></li>
    <li><a href="#account">Your account</a></li>
    <li><a href="#acceptable">Acceptable use</a></li>
    <li><a href="#content">Your content</a></li>
    <li><a href="#ai">AI Menu Scanner</a></li>
    <li><a href="#payment">Plans & payment</a></li>
    <li><a href="#ip">Intellectual property</a></li>
    <li><a href="#termination">Termination</a></li>
    <li><a href="#disclaimer">Disclaimers</a></li>
    <li><a href="#liability">Limitation of liability</a></li>
    <li><a href="#governing">Governing law</a></li>
    <li><a href="#contact">Contact</a></li>
@endsection

@section('toc_ar')
    <li><a href="#acceptance-ar">Ù‚Ø¨ÙˆÙ„ Ø§Ù„Ø´Ø±ÙˆØ·</a></li>
    <li><a href="#service-ar">Ø§Ù„Ø®Ø¯Ù…Ø©</a></li>
    <li><a href="#account-ar">Ø­Ø³Ø§Ø¨Ùƒ</a></li>
    <li><a href="#acceptable-ar">Ø§Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ù…Ù‚Ø¨ÙˆÙ„</a></li>
    <li><a href="#content-ar">Ù…Ø­ØªÙˆØ§Ùƒ</a></li>
    <li><a href="#ai-ar">Ù…Ø§Ø³Ø­ Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø¨Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ</a></li>
    <li><a href="#payment-ar">Ø§Ù„Ø¨Ø§Ù‚Ø§Øª ÙˆØ§Ù„Ø¯ÙØ¹</a></li>
    <li><a href="#ip-ar">Ø§Ù„Ù…Ù„ÙƒÙŠØ© Ø§Ù„ÙÙƒØ±ÙŠØ©</a></li>
    <li><a href="#termination-ar">Ø¥Ù†Ù‡Ø§Ø¡ Ø§Ù„Ø®Ø¯Ù…Ø©</a></li>
    <li><a href="#disclaimer-ar">Ø¥Ø®Ù„Ø§Ø¡ Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„ÙŠØ©</a></li>
    <li><a href="#liability-ar">ØªØ­Ø¯ÙŠØ¯ Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„ÙŠØ©</a></li>
    <li><a href="#governing-ar">Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ø­Ø§ÙƒÙ…</a></li>
    <li><a href="#contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</a></li>
@endsection

@section('legal_body')

@unless ($isAr)

<div class="legal-highlight">
    <p>These Terms of Service ("Terms") govern your use of Qayema, operated by Lebify Group ("we," "us," "our"). By creating an account or using Qayema, you agree to these Terms. If you do not agree, do not use the service.</p>
</div>

<h2 id="acceptance">Acceptance of terms</h2>

<p>By accessing or using Qayema, you represent that you are at least 18 years old and have the legal authority to agree to these Terms on behalf of yourself or your organisation. Use of the service constitutes acceptance of these Terms and our <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>

<h2 id="service">The service</h2>

<p>Qayema is a digital menu platform for restaurant owners and operators. The service includes:</p>
<ul>
    <li>A web-based dashboard for creating and managing digital menus</li>
    <li>A public menu page accessible to your guests via QR code or direct link</li>
    <li>Visitor analytics and statistics for your menu</li>
    <li>An AI-powered menu scanner for importing paper menus</li>
    <li>QR code generation and customisation tools</li>
    <li>Social link management and WhatsApp ordering integration</li>
</ul>

<p>We reserve the right to modify, suspend, or discontinue any part of the service at any time with reasonable notice. We will not be liable to you or any third party for any modification, suspension, or discontinuation of the service.</p>

<h2 id="account">Your account</h2>

<p>You are responsible for maintaining the security of your account credentials. You must not share your password or allow others to access your account. You are responsible for all activity that occurs under your account.</p>
<p>You must provide accurate and complete information when registering. If your information changes, you must update it promptly. We may suspend or terminate accounts that contain inaccurate information or that we reasonably believe are being used fraudulently.</p>
<p>You may only create one account per email address unless expressly permitted by us.</p>

<h2 id="acceptable">Acceptable use</h2>

<p>You agree not to use Qayema to:</p>
<ul>
    <li>Violate any applicable law or regulation</li>
    <li>Upload, transmit, or display content that is unlawful, harmful, defamatory, obscene, or fraudulent</li>
    <li>Impersonate any person or entity</li>
    <li>Interfere with or disrupt the service or its infrastructure</li>
    <li>Attempt to gain unauthorised access to any part of the service</li>
    <li>Use automated tools to scrape, crawl, or harvest data from the service</li>
    <li>Resell, sublicense, or otherwise commercialise the service without our written consent</li>
    <li>Use the service for any purpose other than operating your restaurant's digital menu</li>
</ul>

<p>We may investigate suspected violations and take appropriate action, including account suspension or termination.</p>

<h2 id="content">Your content</h2>

<p>You retain ownership of all content you upload to Qayema, including dish names, descriptions, prices, images, and restaurant information ("Your Content"). By uploading content, you grant us a non-exclusive, worldwide, royalty-free licence to store, display, and deliver Your Content solely for the purpose of operating the service.</p>
<p>You are solely responsible for Your Content. You represent that you have all rights necessary to upload and use Your Content on Qayema. We do not endorse and take no responsibility for Your Content.</p>
<p>We may remove any content that violates these Terms or applicable law, without notice.</p>

<h2 id="ai">AI Menu Scanner</h2>

<p>The AI Menu Scanner uses Google's Gemini API to process images of paper menus. By using this feature, you acknowledge that:</p>
<ul>
    <li>Images you upload are transmitted to Google's servers for processing</li>
    <li>You have the right to upload and process the content in the images</li>
    <li>AI-generated results may contain errors and should be reviewed before importing</li>
    <li>The feature is limited to 3 scans per day per account</li>
    <li>We do not guarantee accuracy of extracted data</li>
</ul>

<h2 id="payment">Plans & payment</h2>

<p>Qayema offers a free starter plan. Paid plans, if available, are described on the pricing page. By subscribing to a paid plan:</p>
<ul>
    <li>You authorise us to charge your payment method on a recurring basis</li>
    <li>Fees are non-refundable except as required by applicable law</li>
    <li>If payment fails, we may downgrade or suspend your account</li>
    <li>We may change pricing with 30 days' advance notice</li>
</ul>

<h2 id="ip">Intellectual property</h2>

<p>Qayema and all its components, including the software, design, trademarks, and content created by us, are owned by Lebify Group and protected by intellectual property laws. Nothing in these Terms grants you any right to use our trademarks, trade names, or branding.</p>
<p>You may not copy, modify, distribute, sell, or lease any part of our service or its software, nor may you reverse-engineer or attempt to extract the source code, unless applicable law permits this or you have our written permission.</p>

<h2 id="termination">Termination</h2>

<p>You may delete your account at any time from your profile settings. Upon deletion, your data will be permanently removed within 30 days.</p>
<p>We may suspend or terminate your account immediately if you violate these Terms, engage in fraudulent activity, or if we are required to do so by law. We will make reasonable efforts to notify you, except where immediate action is required for security or legal reasons.</p>
<p>Upon termination, your right to use the service ends immediately. Sections of these Terms that by their nature should survive termination will continue to apply.</p>

<h2 id="disclaimer">Disclaimers</h2>

<p>The service is provided "as is" and "as available" without warranties of any kind, either express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, or non-infringement.</p>
<p>We do not warrant that the service will be uninterrupted, error-free, or secure. We are not responsible for the accuracy of AI-generated content, visitor statistics, or any third-party integrations.</p>

<h2 id="liability">Limitation of liability</h2>

<p>To the maximum extent permitted by applicable law, Lebify Group shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, data, or business, arising out of or relating to your use of Qayema.</p>
<p>Our total liability for any claim arising from these Terms or your use of the service shall not exceed the amount you paid us in the 12 months preceding the claim, or $50 USD, whichever is greater.</p>

<h2 id="governing">Governing law</h2>

<p>These Terms are governed by the laws of Lebanon. Any disputes shall be resolved in the courts of Lebanon, unless otherwise required by mandatory consumer protection laws in your jurisdiction.</p>

<h2 id="contact">Contact</h2>

<p>For questions about these Terms, contact us at <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>ØªØ­ÙƒÙ… Ø´Ø±ÙˆØ· Ø§Ù„Ø®Ø¯Ù…Ø© Ù‡Ø°Ù‡ ("Ø§Ù„Ø´Ø±ÙˆØ·") Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù‚Ø§Ø¦Ù…Ø©ØŒ Ø§Ù„ØªÙŠ ØªØ´ØºÙ‘Ù„Ù‡Ø§ Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠ ("Ù†Ø­Ù†" Ø£Ùˆ "Ù„Ù†Ø§"). Ø¨Ø¥Ù†Ø´Ø§Ø¦Ùƒ Ø­Ø³Ø§Ø¨Ù‹Ø§ Ø£Ùˆ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù‚Ø§Ø¦Ù…Ø©ØŒ ÙØ¥Ù†Ùƒ ØªÙˆØ§ÙÙ‚ Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ·. Ø¥Ø°Ø§ Ù„Ù… ØªÙˆØ§ÙÙ‚ØŒ ÙÙ„Ø§ ØªØ³ØªØ®Ø¯Ù… Ø§Ù„Ø®Ø¯Ù…Ø©.</p>
</div>

<h2 id="acceptance-ar">Ù‚Ø¨ÙˆÙ„ Ø§Ù„Ø´Ø±ÙˆØ·</h2>

<p>Ø¨Ø§Ù„ÙˆØµÙˆÙ„ Ø¥Ù„Ù‰ Ù‚Ø§Ø¦Ù…Ø© Ø£Ùˆ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ù‡Ø§ØŒ ÙØ¥Ù†Ùƒ ØªÙ‚Ø±Ù‘ Ø¨Ø£Ù†Ùƒ Ø¨Ù„ØºØª Ù…Ù† Ø§Ù„Ø¹Ù…Ø± 18 Ø¹Ø§Ù…Ù‹Ø§ Ø¹Ù„Ù‰ Ø§Ù„Ø£Ù‚Ù„ ÙˆÙ„Ø¯ÙŠÙƒ Ø§Ù„ØµÙ„Ø§Ø­ÙŠØ© Ø§Ù„Ù‚Ø§Ù†ÙˆÙ†ÙŠØ© Ù„Ù„Ù…ÙˆØ§ÙÙ‚Ø© Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ø¨Ø§Ù„Ù†ÙŠØ§Ø¨Ø© Ø¹Ù† Ù†ÙØ³Ùƒ Ø£Ùˆ Ù…Ø¤Ø³Ø³ØªÙƒ. ÙŠÙØ¹Ø¯Ù‘ Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ø®Ø¯Ù…Ø© Ù‚Ø¨ÙˆÙ„Ø§Ù‹ Ù„Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ùˆ<a href="{{ route('privacy') }}">Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø®ØµÙˆØµÙŠØ©</a>.</p>

<h2 id="service-ar">Ø§Ù„Ø®Ø¯Ù…Ø©</h2>

<p>Ù‚Ø§Ø¦Ù…Ø© Ù‡ÙŠ Ù…Ù†ØµØ© Ù‚ÙˆØ§Ø¦Ù… Ø±Ù‚Ù…ÙŠØ© Ù„Ø£ØµØ­Ø§Ø¨ Ø§Ù„Ù…Ø·Ø§Ø¹Ù… ÙˆÙ…Ø´ØºÙ‘Ù„ÙŠÙ‡Ø§. ØªØ´Ù…Ù„ Ø§Ù„Ø®Ø¯Ù…Ø©:</p>
<ul>
    <li>Ù„ÙˆØ­Ø© ØªØ­ÙƒÙ… Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠØ© Ù„Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„Ø±Ù‚Ù…ÙŠØ© ÙˆØ¥Ø¯Ø§Ø±ØªÙ‡Ø§</li>
    <li>ØµÙØ­Ø© Ù‚Ø§Ø¦Ù…Ø© Ø¹Ø§Ù…Ø© ÙŠÙ…ÙƒÙ† Ù„Ø¶ÙŠÙˆÙÙƒ Ø§Ù„ÙˆØµÙˆÙ„ Ø¥Ù„ÙŠÙ‡Ø§ Ø¹Ø¨Ø± Ø±Ù…Ø² QR Ø£Ùˆ Ø±Ø§Ø¨Ø· Ù…Ø¨Ø§Ø´Ø±</li>
    <li>Ø¥Ø­ØµØ§Ø¡Ø§Øª ÙˆØªØ­Ù„ÙŠÙ„Ø§Øª Ø§Ù„Ø²ÙˆØ§Ø± Ù„Ù‚Ø§Ø¦Ù…ØªÙƒ</li>
    <li>Ù…Ø§Ø³Ø­ Ù‚ÙˆØ§Ø¦Ù… Ø¨Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ Ù„Ø§Ø³ØªÙŠØ±Ø§Ø¯ Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„ÙˆØ±Ù‚ÙŠØ©</li>
    <li>Ø£Ø¯ÙˆØ§Øª Ø¥Ù†Ø´Ø§Ø¡ Ø±Ù…Ø² QR ÙˆØªØ®ØµÙŠØµÙ‡</li>
    <li>Ø¥Ø¯Ø§Ø±Ø© Ø§Ù„Ø±ÙˆØ§Ø¨Ø· Ø§Ù„Ø§Ø¬ØªÙ…Ø§Ø¹ÙŠØ© ÙˆØªÙƒØ§Ù…Ù„ Ø·Ù„Ø¨Ø§Øª WhatsApp</li>
</ul>

<p>Ù†Ø­ØªÙØ¸ Ø¨Ø§Ù„Ø­Ù‚ ÙÙŠ ØªØ¹Ø¯ÙŠÙ„ Ø£ÙŠ Ø¬Ø²Ø¡ Ù…Ù† Ø§Ù„Ø®Ø¯Ù…Ø© Ø£Ùˆ ØªØ¹Ù„ÙŠÙ‚Ù‡ Ø£Ùˆ Ø¥ÙŠÙ‚Ø§ÙÙ‡ ÙÙŠ Ø£ÙŠ ÙˆÙ‚Øª Ù…Ø¹ Ø¥Ø´Ø¹Ø§Ø± Ù…Ø¹Ù‚ÙˆÙ„. Ù„Ù† Ù†ÙƒÙˆÙ† Ù…Ø³Ø¤ÙˆÙ„ÙŠÙ† ØªØ¬Ø§Ù‡Ùƒ Ø£Ùˆ ØªØ¬Ø§Ù‡ Ø£ÙŠ Ø·Ø±Ù Ø«Ø§Ù„Ø« Ø¹Ù† Ø£ÙŠ ØªØ¹Ø¯ÙŠÙ„ Ø£Ùˆ ØªØ¹Ù„ÙŠÙ‚ Ø£Ùˆ Ø¥ÙŠÙ‚Ø§Ù Ù„Ù„Ø®Ø¯Ù…Ø©.</p>

<h2 id="account-ar">Ø­Ø³Ø§Ø¨Ùƒ</h2>

<p>Ø£Ù†Øª Ù…Ø³Ø¤ÙˆÙ„ Ø¹Ù† Ø§Ù„Ø­ÙØ§Ø¸ Ø¹Ù„Ù‰ Ø£Ù…Ø§Ù† Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ø¹ØªÙ…Ø§Ø¯ Ø­Ø³Ø§Ø¨Ùƒ. ÙŠØ¬Ø¨ Ø£Ù„Ø§ ØªØ´Ø§Ø±Ùƒ ÙƒÙ„Ù…Ø© Ù…Ø±ÙˆØ±Ùƒ Ø£Ùˆ ØªØ³Ù…Ø­ Ù„Ù„Ø¢Ø®Ø±ÙŠÙ† Ø¨Ø§Ù„ÙˆØµÙˆÙ„ Ø¥Ù„Ù‰ Ø­Ø³Ø§Ø¨Ùƒ. Ø£Ù†Øª Ù…Ø³Ø¤ÙˆÙ„ Ø¹Ù† Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø£Ù†Ø´Ø·Ø© Ø§Ù„ØªÙŠ ØªØ¬Ø±ÙŠ ØªØ­Øª Ø­Ø³Ø§Ø¨Ùƒ.</p>
<p>ÙŠØ¬Ø¨ ØªÙ‚Ø¯ÙŠÙ… Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø¯Ù‚ÙŠÙ‚Ø© ÙˆÙƒØ§Ù…Ù„Ø© Ø¹Ù†Ø¯ Ø§Ù„ØªØ³Ø¬ÙŠÙ„. Ø¥Ø°Ø§ ØªØºÙŠÙ‘Ø±Øª Ù…Ø¹Ù„ÙˆÙ…Ø§ØªÙƒØŒ ÙŠØ¬Ø¨ ØªØ­Ø¯ÙŠØ«Ù‡Ø§ ÙÙˆØ±Ù‹Ø§. Ù‚Ø¯ Ù†Ø¹Ù„Ù‘Ù‚ Ø§Ù„Ø­Ø³Ø§Ø¨Ø§Øª Ø§Ù„ØªÙŠ ØªØ­ØªÙˆÙŠ Ø¹Ù„Ù‰ Ù…Ø¹Ù„ÙˆÙ…Ø§Øª ØºÙŠØ± Ø¯Ù‚ÙŠÙ‚Ø© Ø£Ùˆ Ø§Ù„ØªÙŠ Ù†Ø¹ØªÙ‚Ø¯ Ø¨Ø´ÙƒÙ„ Ù…Ø¹Ù‚ÙˆÙ„ Ø£Ù†Ù‡Ø§ ØªÙØ³ØªØ®Ø¯Ù… Ø¨Ø·Ø±ÙŠÙ‚Ø© Ø§Ø­ØªÙŠØ§Ù„ÙŠØ© Ø£Ùˆ Ù†ÙˆÙ‚ÙÙ‡Ø§.</p>
<p>ÙŠÙØ³Ù…Ø­ Ù„Ùƒ Ø¨Ø¥Ù†Ø´Ø§Ø¡ Ø­Ø³Ø§Ø¨ ÙˆØ§Ø­Ø¯ ÙÙ‚Ø· Ù„ÙƒÙ„ Ø¹Ù†ÙˆØ§Ù† Ø¨Ø±ÙŠØ¯ Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ Ù…Ø§ Ù„Ù… Ù†Ø£Ø°Ù† ØµØ±Ø§Ø­Ø©Ù‹ Ø¨ØºÙŠØ± Ø°Ù„Ùƒ.</p>

<h2 id="acceptable-ar">Ø§Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ù…Ù‚Ø¨ÙˆÙ„</h2>

<p>ØªÙˆØ§ÙÙ‚ Ø¹Ù„Ù‰ Ø¹Ø¯Ù… Ø§Ø³ØªØ®Ø¯Ø§Ù… Ù‚Ø§Ø¦Ù…Ø© Ù…Ù† Ø£Ø¬Ù„:</p>
<ul>
    <li>Ø§Ù†ØªÙ‡Ø§Ùƒ Ø£ÙŠ Ù‚Ø§Ù†ÙˆÙ† Ø£Ùˆ Ù„Ø§Ø¦Ø­Ø© Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡Ø§</li>
    <li>Ø±ÙØ¹ Ø£Ùˆ Ù†Ù‚Ù„ Ø£Ùˆ Ø¹Ø±Ø¶ Ù…Ø­ØªÙˆÙ‰ ØºÙŠØ± Ù‚Ø§Ù†ÙˆÙ†ÙŠ Ø£Ùˆ Ø¶Ø§Ø± Ø£Ùˆ ØªØ´Ù‡ÙŠØ±ÙŠ Ø£Ùˆ ÙØ§Ø­Ø´ Ø£Ùˆ Ø§Ø­ØªÙŠØ§Ù„ÙŠ</li>
    <li>Ø§Ù†ØªØ­Ø§Ù„ Ø´Ø®ØµÙŠØ© Ø£ÙŠ ÙØ±Ø¯ Ø£Ùˆ ÙƒÙŠØ§Ù†</li>
    <li>Ø§Ù„ØªØ¯Ø®Ù„ ÙÙŠ Ø§Ù„Ø®Ø¯Ù…Ø© Ø£Ùˆ Ø¨Ù†ÙŠØªÙ‡Ø§ Ø§Ù„ØªØ­ØªÙŠØ© Ø£Ùˆ ØªØ¹Ø·ÙŠÙ„Ù‡Ø§</li>
    <li>Ù…Ø­Ø§ÙˆÙ„Ø© Ø§Ù„ÙˆØµÙˆÙ„ ØºÙŠØ± Ø§Ù„Ù…ØµØ±Ù‘Ø­ Ø¨Ù‡ Ø¥Ù„Ù‰ Ø£ÙŠ Ø¬Ø²Ø¡ Ù…Ù† Ø§Ù„Ø®Ø¯Ù…Ø©</li>
    <li>Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø£Ø¯ÙˆØ§Øª Ø¢Ù„ÙŠØ© Ù„Ø§Ø³ØªØ®Ø±Ø§Ø¬ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø£Ùˆ Ø§Ù„Ø²Ø­Ù Ø¹Ù„ÙŠÙ‡Ø§ Ø£Ùˆ Ø­ØµØ§Ø¯Ù‡Ø§ Ù…Ù† Ø§Ù„Ø®Ø¯Ù…Ø©</li>
    <li>Ø¥Ø¹Ø§Ø¯Ø© Ø¨ÙŠØ¹ Ø§Ù„Ø®Ø¯Ù…Ø© Ø£Ùˆ ØªØ±Ø®ÙŠØµÙ‡Ø§ Ù…Ù† Ø§Ù„Ø¨Ø§Ø·Ù† Ø£Ùˆ ØªØ³ÙŠÙŠÙ„Ù‡Ø§ Ø¨Ø£ÙŠ Ø´ÙƒÙ„ Ø¢Ø®Ø± Ø¯ÙˆÙ† Ù…ÙˆØ§ÙÙ‚ØªÙ†Ø§ Ø§Ù„ÙƒØªØ§Ø¨ÙŠØ©</li>
    <li>Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ø®Ø¯Ù…Ø© Ù„Ø£ÙŠ ØºØ±Ø¶ Ø¢Ø®Ø± ØºÙŠØ± ØªØ´ØºÙŠÙ„ Ù‚Ø§Ø¦Ù…Ø© Ù…Ø·Ø¹Ù…Ùƒ Ø§Ù„Ø±Ù‚Ù…ÙŠØ©</li>
</ul>

<p>Ù‚Ø¯ Ù†Ø­Ù‚Ù‘Ù‚ ÙÙŠ Ø§Ù„Ø§Ù†ØªÙ‡Ø§ÙƒØ§Øª Ø§Ù„Ù…Ø´ØªØ¨Ù‡ Ø¨Ù‡Ø§ ÙˆÙ†ØªØ®Ø° Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡ Ø§Ù„Ù…Ù†Ø§Ø³Ø¨ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ ØªØ¹Ù„ÙŠÙ‚ Ø§Ù„Ø­Ø³Ø§Ø¨ Ø£Ùˆ Ø¥Ù†Ù‡Ø§Ø¤Ù‡.</p>

<h2 id="content-ar">Ù…Ø­ØªÙˆØ§Ùƒ</h2>

<p>ØªØ­ØªÙØ¸ Ø¨Ù…Ù„ÙƒÙŠØ© Ø¬Ù…ÙŠØ¹ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ Ø§Ù„Ø°ÙŠ ØªØ±ÙØ¹Ù‡ Ø¥Ù„Ù‰ Ù‚Ø§Ø¦Ù…Ø©ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ Ø£Ø³Ù…Ø§Ø¡ Ø§Ù„Ø£Ø·Ø¨Ø§Ù‚ ÙˆØ§Ù„Ø£ÙˆØµØ§Ù ÙˆØ§Ù„Ø£Ø³Ø¹Ø§Ø± ÙˆØ§Ù„ØµÙˆØ± ÙˆÙ…Ø¹Ù„ÙˆÙ…Ø§Øª Ø§Ù„Ù…Ø·Ø¹Ù… ("Ù…Ø­ØªÙˆØ§Ùƒ"). Ø¨Ø±ÙØ¹ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ØŒ ØªÙ…Ù†Ø­Ù†Ø§ ØªØ±Ø®ÙŠØµÙ‹Ø§ ØºÙŠØ± Ø­ØµØ±ÙŠ ÙˆØ¹Ø§Ù„Ù…ÙŠ ÙˆØ¨Ø¯ÙˆÙ† Ù…Ù‚Ø§Ø¨Ù„ Ù„ØªØ®Ø²ÙŠÙ† Ù…Ø­ØªÙˆØ§Ùƒ ÙˆØ¹Ø±Ø¶Ù‡ ÙˆØªØ³Ù„ÙŠÙ…Ù‡ ÙÙ‚Ø· Ù„Ø£ØºØ±Ø§Ø¶ ØªØ´ØºÙŠÙ„ Ø§Ù„Ø®Ø¯Ù…Ø©.</p>
<p>Ø£Ù†Øª ÙˆØ­Ø¯Ùƒ Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„ Ø¹Ù† Ù…Ø­ØªÙˆØ§Ùƒ. ØªÙÙ‚Ø±Ù‘ Ø¨Ø£Ù†Ùƒ ØªÙ…ØªÙ„Ùƒ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø­Ù‚ÙˆÙ‚ Ø§Ù„Ù„Ø§Ø²Ù…Ø© Ù„Ø±ÙØ¹ Ù…Ø­ØªÙˆØ§Ùƒ ÙˆØ§Ø³ØªØ®Ø¯Ø§Ù…Ù‡ Ø¹Ù„Ù‰ Ù‚Ø§Ø¦Ù…Ø©. Ù†Ø­Ù† Ù„Ø§ Ù†Ø¤ÙŠØ¯ Ù…Ø­ØªÙˆØ§Ùƒ ÙˆÙ„Ø§ Ù†ØªØ­Ù…Ù„ Ø£ÙŠ Ù…Ø³Ø¤ÙˆÙ„ÙŠØ© Ø¹Ù†Ù‡.</p>
<p>Ù‚Ø¯ Ù†Ø²ÙŠÙ„ Ø£ÙŠ Ù…Ø­ØªÙˆÙ‰ ÙŠÙ†ØªÙ‡Ùƒ Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ø£Ùˆ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡ØŒ Ø¯ÙˆÙ† Ø¥Ø´Ø¹Ø§Ø±.</p>

<h2 id="ai-ar">Ù…Ø§Ø³Ø­ Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø¨Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ</h2>

<p>ÙŠØ³ØªØ®Ø¯Ù… Ù…Ø§Ø³Ø­ Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø¨Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ ÙˆØ§Ø¬Ù‡Ø© Ø¨Ø±Ù…Ø¬Ø© ØªØ·Ø¨ÙŠÙ‚Ø§Øª Gemini Ù…Ù† Google Ù„Ù…Ø¹Ø§Ù„Ø¬Ø© ØµÙˆØ± Ø§Ù„Ù‚ÙˆØ§Ø¦Ù… Ø§Ù„ÙˆØ±Ù‚ÙŠØ©. Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù… Ù‡Ø°Ù‡ Ø§Ù„Ù…ÙŠØ²Ø©ØŒ ÙØ¥Ù†Ùƒ ØªÙÙ‚Ø±Ù‘ Ø¨Ø£Ù†:</p>
<ul>
    <li>Ø§Ù„ØµÙˆØ± Ø§Ù„ØªÙŠ ØªØ±ÙØ¹Ù‡Ø§ ØªÙØ±Ø³Ù„ Ø¥Ù„Ù‰ Ø®ÙˆØ§Ø¯Ù… Google Ù„Ù„Ù…Ø¹Ø§Ù„Ø¬Ø©</li>
    <li>Ù„Ø¯ÙŠÙƒ Ø§Ù„Ø­Ù‚ ÙÙŠ Ø±ÙØ¹ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ Ø§Ù„Ù…ÙˆØ¬ÙˆØ¯ ÙÙŠ Ø§Ù„ØµÙˆØ± ÙˆÙ…Ø¹Ø§Ù„Ø¬ØªÙ‡</li>
    <li>Ø§Ù„Ù†ØªØ§Ø¦Ø¬ Ø§Ù„ØªÙŠ ÙŠÙˆÙ„Ù‘Ø¯Ù‡Ø§ Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ Ù‚Ø¯ ØªØ­ØªÙˆÙŠ Ø¹Ù„Ù‰ Ø£Ø®Ø·Ø§Ø¡ ÙˆÙŠØ¬Ø¨ Ù…Ø±Ø§Ø¬Ø¹ØªÙ‡Ø§ Ù‚Ø¨Ù„ Ø§Ù„Ø§Ø³ØªÙŠØ±Ø§Ø¯</li>
    <li>Ø§Ù„Ù…ÙŠØ²Ø© Ù…Ø­Ø¯ÙˆØ¯Ø© Ø¨Ù€ 3 Ø¹Ù…Ù„ÙŠØ§Øª Ù…Ø³Ø­ ÙŠÙˆÙ…ÙŠÙ‹Ø§ Ù„ÙƒÙ„ Ø­Ø³Ø§Ø¨</li>
    <li>Ù„Ø§ Ù†Ø¶Ù…Ù† Ø¯Ù‚Ø© Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ù…Ø³ØªØ®Ø±Ø¬Ø©</li>
</ul>

<h2 id="payment-ar">Ø§Ù„Ø¨Ø§Ù‚Ø§Øª ÙˆØ§Ù„Ø¯ÙØ¹</h2>

<p>ØªÙ‚Ø¯Ù‘Ù… Ù‚Ø§Ø¦Ù…Ø© Ø¨Ø§Ù‚Ø© Ù…Ø¬Ø§Ù†ÙŠØ© Ù„Ù„Ø¨Ø¯Ø¡. ØªØªÙˆÙØ± Ø§Ù„Ø¨Ø§Ù‚Ø§Øª Ø§Ù„Ù…Ø¯ÙÙˆØ¹Ø© Ø¹Ø¨Ø± ØµÙØ­Ø© Ø§Ù„Ø£Ø³Ø¹Ø§Ø±. Ø¨Ø§Ø´ØªØ±Ø§ÙƒÙƒ ÙÙŠ Ø¨Ø§Ù‚Ø© Ù…Ø¯ÙÙˆØ¹Ø©:</p>
<ul>
    <li>ØªØ£Ø°Ù† Ù„Ù†Ø§ Ø¨Ø®ØµÙ… Ø§Ù„Ù…Ø¨Ù„Øº Ù…Ù† Ø·Ø±ÙŠÙ‚Ø© Ø§Ù„Ø¯ÙØ¹ Ø§Ù„Ø®Ø§ØµØ© Ø¨Ùƒ Ø¹Ù„Ù‰ Ø£Ø³Ø§Ø³ Ù…ØªÙƒØ±Ø±</li>
    <li>Ø§Ù„Ø±Ø³ÙˆÙ… ØºÙŠØ± Ù‚Ø§Ø¨Ù„Ø© Ù„Ù„Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ø¥Ù„Ø§ Ù…Ø§ ÙŠÙ‚ØªØ¶ÙŠÙ‡ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡</li>
    <li>ÙÙŠ Ø­Ø§Ù„ ÙØ´Ù„ Ø§Ù„Ø¯ÙØ¹ØŒ Ù‚Ø¯ Ù†Ø®ÙÙ‘Ø¶ Ù…Ø³ØªÙˆÙ‰ Ø­Ø³Ø§Ø¨Ùƒ Ø£Ùˆ Ù†Ø¹Ù„Ù‘Ù‚Ù‡</li>
    <li>Ù‚Ø¯ Ù†ØºÙŠÙ‘Ø± Ø§Ù„Ø£Ø³Ø¹Ø§Ø± Ù…Ø¹ Ø¥Ø´Ø¹Ø§Ø± Ù…Ø³Ø¨Ù‚ Ù…Ø¯ØªÙ‡ 30 ÙŠÙˆÙ…Ù‹Ø§</li>
</ul>

<h2 id="ip-ar">Ø§Ù„Ù…Ù„ÙƒÙŠØ© Ø§Ù„ÙÙƒØ±ÙŠØ©</h2>

<p>Ù‚Ø§Ø¦Ù…Ø© ÙˆØ¬Ù…ÙŠØ¹ Ù…ÙƒÙˆÙ‘Ù†Ø§ØªÙ‡Ø§ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬ ÙˆØ§Ù„ØªØµÙ…ÙŠÙ… ÙˆØ§Ù„Ø¹Ù„Ø§Ù…Ø§Øª Ø§Ù„ØªØ¬Ø§Ø±ÙŠØ© ÙˆØ§Ù„Ù…Ø­ØªÙˆÙ‰ Ø§Ù„Ø°ÙŠ Ø£Ù†Ø´Ø£Ù†Ø§Ù‡ØŒ Ù…Ù…Ù„ÙˆÙƒØ© Ù„Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠ ÙˆÙ…Ø­Ù…ÙŠØ© Ø¨Ù…ÙˆØ¬Ø¨ Ù‚ÙˆØ§Ù†ÙŠÙ† Ø§Ù„Ù…Ù„ÙƒÙŠØ© Ø§Ù„ÙÙƒØ±ÙŠØ©. Ù„Ø§ ØªÙ…Ù†Ø­Ùƒ Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ø£ÙŠ Ø­Ù‚ ÙÙŠ Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø¹Ù„Ø§Ù…Ø§ØªÙ†Ø§ Ø§Ù„ØªØ¬Ø§Ø±ÙŠØ© Ø£Ùˆ Ø£Ø³Ù…Ø§Ø¦Ù†Ø§ Ø§Ù„ØªØ¬Ø§Ø±ÙŠØ© Ø£Ùˆ Ù‡ÙˆÙŠØªÙ†Ø§ Ø§Ù„Ø¨ØµØ±ÙŠØ©.</p>
<p>Ù„Ø§ ÙŠØ¬ÙˆØ² Ù„Ùƒ Ù†Ø³Ø® Ø£ÙŠ Ø¬Ø²Ø¡ Ù…Ù† Ø®Ø¯Ù…ØªÙ†Ø§ Ø£Ùˆ Ø¨Ø±Ù†Ø§Ù…Ø¬Ù‡Ø§ Ø£Ùˆ ØªØ¹Ø¯ÙŠÙ„Ù‡ Ø£Ùˆ ØªÙˆØ²ÙŠØ¹Ù‡ Ø£Ùˆ Ø¨ÙŠØ¹Ù‡ Ø£Ùˆ ØªØ£Ø¬ÙŠØ±Ù‡ØŒ ÙˆÙ„Ø§ ÙŠØ¬ÙˆØ² Ù„Ùƒ Ø¹ÙƒØ³ Ù‡Ù†Ø¯Ø³ØªÙ‡ Ø£Ùˆ Ù…Ø­Ø§ÙˆÙ„Ø© Ø§Ø³ØªØ®Ø±Ø§Ø¬ Ø§Ù„ÙƒÙˆØ¯ Ø§Ù„Ù…ØµØ¯Ø±ÙŠØŒ Ø¥Ù„Ø§ Ø¥Ø°Ø§ Ø£Ø¬Ø§Ø² Ù„Ùƒ Ø°Ù„Ùƒ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡ Ø£Ùˆ Ø­ØµÙ„Øª Ø¹Ù„Ù‰ Ø¥Ø°Ù† ÙƒØªØ§Ø¨ÙŠ Ù…Ù†Ø§.</p>

<h2 id="termination-ar">Ø¥Ù†Ù‡Ø§Ø¡ Ø§Ù„Ø®Ø¯Ù…Ø©</h2>

<p>ÙŠÙ…ÙƒÙ†Ùƒ Ø­Ø°Ù Ø­Ø³Ø§Ø¨Ùƒ ÙÙŠ Ø£ÙŠ ÙˆÙ‚Øª Ù…Ù† Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ù…Ù„ÙÙƒ Ø§Ù„Ø´Ø®ØµÙŠ. Ø¹Ù†Ø¯ Ø§Ù„Ø­Ø°ÙØŒ Ø³ØªÙØ²Ø§Ù„ Ø¨ÙŠØ§Ù†Ø§ØªÙƒ Ù†Ù‡Ø§Ø¦ÙŠÙ‹Ø§ Ø®Ù„Ø§Ù„ 30 ÙŠÙˆÙ…Ù‹Ø§.</p>
<p>Ù‚Ø¯ Ù†Ø¹Ù„Ù‘Ù‚ Ø­Ø³Ø§Ø¨Ùƒ Ø£Ùˆ Ù†ÙˆÙ‚ÙÙ‡ ÙÙˆØ±Ù‹Ø§ Ø¥Ø°Ø§ Ø§Ù†ØªÙ‡ÙƒØª Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ø£Ùˆ Ù…Ø§Ø±Ø³Øª Ù†Ø´Ø§Ø·Ù‹Ø§ Ø§Ø­ØªÙŠØ§Ù„ÙŠÙ‹Ø§ Ø£Ùˆ Ø¥Ø°Ø§ Ø·ÙÙ„Ø¨ Ù…Ù†Ø§ Ø°Ù„Ùƒ Ù‚Ø§Ù†ÙˆÙ†Ù‹Ø§. Ø³Ù†Ø¨Ø°Ù„ Ø¬Ù‡ÙˆØ¯Ù‹Ø§ Ù…Ø¹Ù‚ÙˆÙ„Ø© Ù„Ø¥Ø®Ø·Ø§Ø±ÙƒØŒ Ø¥Ù„Ø§ ÙÙŠ Ø§Ù„Ø­Ø§Ù„Ø§Øª Ø§Ù„ØªÙŠ ØªØ³ØªÙˆØ¬Ø¨ Ø§ØªØ®Ø§Ø° Ø¥Ø¬Ø±Ø§Ø¡ ÙÙˆØ±ÙŠ Ù„Ø£Ø³Ø¨Ø§Ø¨ Ø£Ù…Ù†ÙŠØ© Ø£Ùˆ Ù‚Ø§Ù†ÙˆÙ†ÙŠØ©.</p>
<p>Ø¹Ù†Ø¯ Ø§Ù„Ø¥Ù†Ù‡Ø§Ø¡ØŒ ÙŠØªÙˆÙ‚Ù Ø­Ù‚Ùƒ ÙÙŠ Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ø®Ø¯Ù…Ø© ÙÙˆØ±Ù‹Ø§. Ø³ØªØ³ØªÙ…Ø± ÙÙŠ Ø§Ù„Ø³Ø±ÙŠØ§Ù† Ø§Ù„Ø£Ù‚Ø³Ø§Ù… Ø§Ù„ØªÙŠ Ø¨Ø·Ø¨ÙŠØ¹ØªÙ‡Ø§ ÙŠØ¬Ø¨ Ø£Ù† ØªØ¨Ù‚Ù‰ Ø³Ø§Ø±ÙŠØ© Ø¨Ø¹Ø¯ Ø§Ù„Ø¥Ù†Ù‡Ø§Ø¡.</p>

<h2 id="disclaimer-ar">Ø¥Ø®Ù„Ø§Ø¡ Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„ÙŠØ©</h2>

<p>ØªÙÙ‚Ø¯ÙŽÙ‘Ù… Ø§Ù„Ø®Ø¯Ù…Ø© "ÙƒÙ…Ø§ Ù‡ÙŠ" Ùˆ"Ø­Ø³Ø¨ Ø§Ù„ØªÙˆÙØ±" Ø¯ÙˆÙ† Ø¶Ù…Ø§Ù†Ø§Øª Ù…Ù† Ø£ÙŠ Ù†ÙˆØ¹ØŒ Ø³ÙˆØ§Ø¡ ØµØ±ÙŠØ­Ø© Ø£Ùˆ Ø¶Ù…Ù†ÙŠØ©ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ Ø¹Ù„Ù‰ Ø³Ø¨ÙŠÙ„ Ø§Ù„Ù…Ø«Ø§Ù„ Ù„Ø§ Ø§Ù„Ø­ØµØ± Ø¶Ù…Ø§Ù†Ø§Øª Ø§Ù„Ù‚Ø§Ø¨Ù„ÙŠØ© Ù„Ù„ØªØ³ÙˆÙŠÙ‚ Ø£Ùˆ Ø§Ù„Ù…Ù„Ø§Ø¡Ù…Ø© Ù„ØºØ±Ø¶ Ù…Ø¹ÙŠÙ† Ø£Ùˆ Ø¹Ø¯Ù… Ø§Ù„Ø§Ù†ØªÙ‡Ø§Ùƒ.</p>
<p>Ù„Ø§ Ù†Ø¶Ù…Ù† Ø£Ù† Ø§Ù„Ø®Ø¯Ù…Ø© Ø³ØªÙƒÙˆÙ† ØºÙŠØ± Ù…Ù†Ù‚Ø·Ø¹Ø© Ø£Ùˆ Ø®Ø§Ù„ÙŠØ© Ù…Ù† Ø§Ù„Ø£Ø®Ø·Ø§Ø¡ Ø£Ùˆ Ø¢Ù…Ù†Ø©. Ù„Ø³Ù†Ø§ Ù…Ø³Ø¤ÙˆÙ„ÙŠÙ† Ø¹Ù† Ø¯Ù‚Ø© Ø§Ù„Ù…Ø­ØªÙˆÙ‰ Ø§Ù„Ø°ÙŠ ÙŠÙˆÙ„Ù‘Ø¯Ù‡ Ø§Ù„Ø°ÙƒØ§Ø¡ Ø§Ù„Ø§ØµØ·Ù†Ø§Ø¹ÙŠ Ø£Ùˆ Ø¥Ø­ØµØ§Ø¡Ø§Øª Ø§Ù„Ø²ÙˆØ§Ø± Ø£Ùˆ Ø£ÙŠ ØªÙƒØ§Ù…Ù„Ø§Øª Ù…Ø¹ Ø£Ø·Ø±Ø§Ù Ø«Ø§Ù„Ø«Ø©.</p>

<h2 id="liability-ar">ØªØ­Ø¯ÙŠØ¯ Ø§Ù„Ù…Ø³Ø¤ÙˆÙ„ÙŠØ©</h2>

<p>Ø¥Ù„Ù‰ Ø£Ù‚ØµÙ‰ Ø­Ø¯ ÙŠØ³Ù…Ø­ Ø¨Ù‡ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡ØŒ Ù„Ù† ØªÙƒÙˆÙ† Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠ Ù…Ø³Ø¤ÙˆÙ„Ø© Ø¹Ù† Ø£ÙŠ Ø£Ø¶Ø±Ø§Ø± ØºÙŠØ± Ù…Ø¨Ø§Ø´Ø±Ø© Ø£Ùˆ Ø¹Ø±Ø¶ÙŠØ© Ø£Ùˆ Ø®Ø§ØµØ© Ø£Ùˆ ØªØ¨Ø¹ÙŠØ© Ø£Ùˆ Ø¹Ù‚Ø§Ø¨ÙŠØ©ØŒ Ø¨Ù…Ø§ ÙÙŠ Ø°Ù„Ùƒ Ø®Ø³Ø§Ø±Ø© Ø§Ù„Ø£Ø±Ø¨Ø§Ø­ Ø£Ùˆ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø£Ùˆ Ø§Ù„Ø£Ø¹Ù…Ø§Ù„ØŒ Ø§Ù„Ù†Ø§Ø¬Ù…Ø© Ø¹Ù† Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù‚Ø§Ø¦Ù…Ø© Ø£Ùˆ Ø§Ù„Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ù‡.</p>
<p>ØªÙ‚ØªØµØ± Ù…Ø³Ø¤ÙˆÙ„ÙŠØªÙ†Ø§ Ø§Ù„Ø¥Ø¬Ù…Ø§Ù„ÙŠØ© Ø¹Ù† Ø£ÙŠ Ù…Ø·Ø§Ù„Ø¨Ø© Ù†Ø§Ø´Ø¦Ø© Ø¹Ù† Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ø£Ùˆ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ùƒ Ù„Ù„Ø®Ø¯Ù…Ø© Ø¹Ù„Ù‰ Ø§Ù„Ù…Ø¨Ù„Øº Ø§Ù„Ø°ÙŠ Ø¯ÙØ¹ØªÙ‡ Ù„Ù†Ø§ ÙÙŠ Ø§Ù„Ù€ 12 Ø´Ù‡Ø±Ù‹Ø§ Ø§Ù„Ø³Ø§Ø¨Ù‚Ø© Ù„Ù„Ù…Ø·Ø§Ù„Ø¨Ø©ØŒ Ø£Ùˆ 50 Ø¯ÙˆÙ„Ø§Ø±Ù‹Ø§ Ø£Ù…Ø±ÙŠÙƒÙŠÙ‹Ø§ØŒ Ø£ÙŠÙ‡Ù…Ø§ Ø£ÙƒØ¨Ø±.</p>

<h2 id="governing-ar">Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ø­Ø§ÙƒÙ…</h2>

<p>ØªØ®Ø¶Ø¹ Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ· Ù„Ù‚ÙˆØ§Ù†ÙŠÙ† Ù„Ø¨Ù†Ø§Ù†. ÙŠØªÙ… Ø­Ù„ Ø£ÙŠ Ù†Ø²Ø§Ø¹Ø§Øª ÙÙŠ Ø§Ù„Ù…Ø­Ø§ÙƒÙ… Ø§Ù„Ù„Ø¨Ù†Ø§Ù†ÙŠØ©ØŒ Ù…Ø§ Ù„Ù… ØªÙ‚ØªØ¶Ù Ù‚ÙˆØ§Ù†ÙŠÙ† Ø­Ù…Ø§ÙŠØ© Ø§Ù„Ù…Ø³ØªÙ‡Ù„Ùƒ Ø§Ù„Ø¥Ù„Ø²Ø§Ù…ÙŠØ© ÙÙŠ ÙˆÙ„Ø§ÙŠØªÙƒ Ø§Ù„Ù‚Ø¶Ø§Ø¦ÙŠØ© Ø®Ù„Ø§Ù Ø°Ù„Ùƒ.</p>

<h2 id="contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</h2>

<p>Ù„Ù„Ø§Ø³ØªÙØ³Ø§Ø± Ø¹Ù† Ù‡Ø°Ù‡ Ø§Ù„Ø´Ø±ÙˆØ·ØŒ ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ Ø¹Ù„Ù‰ <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

@endif

@endsection
