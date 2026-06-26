@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('title', 'Refund Policy')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'Ù‚Ø§Ù†ÙˆÙ†ÙŠ')
@section('headline_plain', 'Refund')
@section('headline_italic', 'Policy')
@section('headline_ar', 'Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø§Ø³ØªØ±Ø¯Ø§Ø¯')
@section('updated', 'Last updated: June 1, 2026')
@section('updated_ar', 'Ø¢Ø®Ø± ØªØ­Ø¯ÙŠØ«: 1 ÙŠÙˆÙ†ÙŠÙˆ 2026')

@section('toc')
    <li><a href="#overview">Overview</a></li>
    <li><a href="#no-refunds">No refunds</a></li>
    <li><a href="#subscription">Subscription period</a></li>
    <li><a href="#cancellation">Cancellation</a></li>
    <li><a href="#new-account">Starting another account</a></li>
    <li><a href="#exceptions">Exceptions</a></li>
    <li><a href="#contact">Contact</a></li>
@endsection

@section('toc_ar')
    <li><a href="#overview-ar">Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø©</a></li>
    <li><a href="#no-refunds-ar">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø§Ø³ØªØ±Ø¯Ø§Ø¯Ø§Øª</a></li>
    <li><a href="#subscription-ar">Ù…Ø¯Ø© Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ</a></li>
    <li><a href="#cancellation-ar">Ø¥Ù„ØºØ§Ø¡ Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ</a></li>
    <li><a href="#new-account-ar">ÙØªØ­ Ø­Ø³Ø§Ø¨ Ø¢Ø®Ø±</a></li>
    <li><a href="#exceptions-ar">Ø§Ù„Ø§Ø³ØªØ«Ù†Ø§Ø¡Ø§Øª</a></li>
    <li><a href="#contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</a></li>
@endsection

@section('legal_body')

@unless ($isAr)

<div class="legal-highlight">
    <p>This Refund Policy explains how payments work for Qayema, operated by Lebify Group. In short: once you purchase a menu plan, the payment is non-refundable. You keep full access until your subscription period ends. By subscribing, you agree to this policy.</p>
</div>

<h2 id="overview">Overview</h2>

<p>Qayema is a digital service that you can use immediately after payment. Because access to the service and its features begins the moment your plan is activated, all purchases are considered final.</p>

<h2 id="no-refunds">No refunds</h2>

<p>Once you purchase a menu plan, <strong>the payment cannot be refunded</strong>. This applies to all plans and billing periods, whether or not you have started using the features included in your plan.</p>

<p>We do not provide partial or pro-rated refunds for time remaining on a subscription, for unused features, or for changing your mind after purchase.</p>

<h2 id="subscription">Subscription period</h2>

<p>When you pay for a plan, you receive access for the full subscription period you purchased. If you decide you no longer want the service, you must <strong>wait until the end of your current subscription period</strong> for it to finish. Your access remains active until that date, so you continue to get the full value of what you paid for.</p>

<h2 id="cancellation">Cancellation</h2>

<p>You may cancel at any time to stop future renewals. Cancelling prevents the next charge but does not refund the current period. Your menu and features stay available until the period you already paid for has ended.</p>

<h2 id="new-account">Starting another account</h2>

<p>If you wish to use a different plan or set up a separate menu before your current subscription ends, you are welcome to <strong>create another account</strong> and subscribe to it. The original subscription will still run until its end date and will not be refunded.</p>

<h2 id="exceptions">Exceptions</h2>

<p>The only exceptions to this policy are where a refund is required by applicable law, or where you were charged in error (for example, a duplicate charge for the same plan). In those cases, please contact us with your account details and we will review the request.</p>

<h2 id="contact">Contact</h2>

<p>If you have questions about this Refund Policy, please contact us at <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>ØªØ´Ø±Ø­ Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ù‡Ø°Ù‡ ÙƒÙŠÙÙŠØ© Ø¹Ù…Ù„ Ø§Ù„Ù…Ø¯ÙÙˆØ¹Ø§Øª ÙÙŠ Ù‚Ø§Ø¦Ù…Ø©ØŒ Ø§Ù„ØªÙŠ ØªØ´ØºÙ‘Ù„Ù‡Ø§ Ù…Ø¬Ù…ÙˆØ¹Ø© Ù„ÙŠØ¨ÙŠÙØ§ÙŠ. Ø¨Ø§Ø®ØªØµØ§Ø±: Ø¨Ù…Ø¬Ø±Ø¯ Ø´Ø±Ø§Ø¦Ùƒ Ù„Ø¨Ø§Ù‚Ø© Ø§Ù„Ù‚Ø§Ø¦Ù…Ø©ØŒ ÙŠÙƒÙˆÙ† Ø§Ù„Ø¯ÙØ¹ ØºÙŠØ± Ù‚Ø§Ø¨Ù„ Ù„Ù„Ø§Ø³ØªØ±Ø¯Ø§Ø¯. ØªØ­ØªÙØ¸ Ø¨ÙƒØ§Ù…Ù„ ØµÙ„Ø§Ø­ÙŠØ© Ø§Ù„ÙˆØµÙˆÙ„ Ø­ØªÙ‰ Ø§Ù†ØªÙ‡Ø§Ø¡ Ù…Ø¯Ø© Ø§Ø´ØªØ±Ø§ÙƒÙƒ. Ø¨Ø§Ø´ØªØ±Ø§ÙƒÙƒØŒ ÙØ¥Ù†Ùƒ ØªÙˆØ§ÙÙ‚ Ø¹Ù„Ù‰ Ù‡Ø°Ù‡ Ø§Ù„Ø³ÙŠØ§Ø³Ø©.</p>
</div>

<h2 id="overview-ar">Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø©</h2>

<p>Ù‚Ø§Ø¦Ù…Ø© Ø®Ø¯Ù…Ø© Ø±Ù‚Ù…ÙŠØ© ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ø³ØªØ®Ø¯Ø§Ù…Ù‡Ø§ ÙÙˆØ± Ø§Ù„Ø¯ÙØ¹. ÙˆÙ„Ø£Ù† Ø§Ù„ÙˆØµÙˆÙ„ Ø¥Ù„Ù‰ Ø§Ù„Ø®Ø¯Ù…Ø© ÙˆÙ…ÙŠØ²Ø§ØªÙ‡Ø§ ÙŠØ¨Ø¯Ø£ Ù„Ø­Ø¸Ø© ØªÙØ¹ÙŠÙ„ Ø¨Ø§Ù‚ØªÙƒØŒ ØªÙØ¹ØªØ¨Ø± Ø¬Ù…ÙŠØ¹ Ø¹Ù…Ù„ÙŠØ§Øª Ø§Ù„Ø´Ø±Ø§Ø¡ Ù†Ù‡Ø§Ø¦ÙŠØ©.</p>

<h2 id="no-refunds-ar">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø§Ø³ØªØ±Ø¯Ø§Ø¯Ø§Øª</h2>

<p>Ø¨Ù…Ø¬Ø±Ø¯ Ø´Ø±Ø§Ø¦Ùƒ Ù„Ø¨Ø§Ù‚Ø© Ø§Ù„Ù‚Ø§Ø¦Ù…Ø©ØŒ <strong>Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ø§Ù„Ù…Ø¨Ù„Øº Ø§Ù„Ù…Ø¯ÙÙˆØ¹</strong>. ÙŠÙ†Ø·Ø¨Ù‚ Ù‡Ø°Ø§ Ø¹Ù„Ù‰ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø¨Ø§Ù‚Ø§Øª ÙˆÙØªØ±Ø§Øª Ø§Ù„ÙÙˆØªØ±Ø©ØŒ Ø³ÙˆØ§Ø¡ Ø¨Ø¯Ø£Øª Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ù…ÙŠØ²Ø§Øª Ø§Ù„Ù…Ø¶Ù…Ù‘Ù†Ø© ÙÙŠ Ø¨Ø§Ù‚ØªÙƒ Ø£Ù… Ù„Ø§.</p>

<p>Ù†Ø­Ù† Ù„Ø§ Ù†Ù‚Ø¯Ù‘Ù… Ø§Ø³ØªØ±Ø¯Ø§Ø¯Ø§Øª Ø¬Ø²Ø¦ÙŠØ© Ø£Ùˆ ØªÙ†Ø§Ø³Ø¨ÙŠØ© Ù…Ù‚Ø§Ø¨Ù„ Ø§Ù„ÙˆÙ‚Øª Ø§Ù„Ù…ØªØ¨Ù‚ÙŠ Ù…Ù† Ø§Ù„Ø§Ø´ØªØ±Ø§ÙƒØŒ Ø£Ùˆ Ù…Ù‚Ø§Ø¨Ù„ Ù…ÙŠØ²Ø§Øª ØºÙŠØ± Ù…Ø³ØªØ®Ø¯Ù…Ø©ØŒ Ø£Ùˆ Ø¹Ù†Ø¯ ØªØºÙŠÙŠØ± Ø±Ø£ÙŠÙƒ Ø¨Ø¹Ø¯ Ø§Ù„Ø´Ø±Ø§Ø¡.</p>

<h2 id="subscription-ar">Ù…Ø¯Ø© Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ</h2>

<p>Ø¹Ù†Ø¯ Ø¯ÙØ¹Ùƒ Ù…Ù‚Ø§Ø¨Ù„ Ø¨Ø§Ù‚Ø©ØŒ ØªØ­ØµÙ„ Ø¹Ù„Ù‰ Ø§Ù„ÙˆØµÙˆÙ„ Ø·ÙˆØ§Ù„ Ù…Ø¯Ø© Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ Ø§Ù„ØªÙŠ Ø§Ø´ØªØ±ÙŠØªÙ‡Ø§. Ø¥Ø°Ø§ Ù‚Ø±Ø±Øª Ø£Ù†Ùƒ Ù„Ù… ØªØ¹Ø¯ ØªØ±ØºØ¨ ÙÙŠ Ø§Ù„Ø®Ø¯Ù…Ø©ØŒ ÙØ¹Ù„ÙŠÙƒ <strong>Ø§Ù„Ø§Ù†ØªØ¸Ø§Ø± Ø­ØªÙ‰ Ù†Ù‡Ø§ÙŠØ© Ù…Ø¯Ø© Ø§Ø´ØªØ±Ø§ÙƒÙƒ Ø§Ù„Ø­Ø§Ù„ÙŠØ©</strong> Ù„ØªÙ†ØªÙ‡ÙŠ. ÙŠØ¨Ù‚Ù‰ ÙˆØµÙˆÙ„Ùƒ ÙØ¹Ù‘Ø§Ù„Ø§Ù‹ Ø­ØªÙ‰ Ø°Ù„Ùƒ Ø§Ù„ØªØ§Ø±ÙŠØ®ØŒ Ø¨Ø­ÙŠØ« ØªØ³ØªÙ…Ø± ÙÙŠ Ø§Ù„Ø­ØµÙˆÙ„ Ø¹Ù„Ù‰ ÙƒØ§Ù…Ù„ Ù‚ÙŠÙ…Ø© Ù…Ø§ Ø¯ÙØ¹ØªÙ‡.</p>

<h2 id="cancellation-ar">Ø¥Ù„ØºØ§Ø¡ Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ</h2>

<p>ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ù„Ø¥Ù„ØºØ§Ø¡ ÙÙŠ Ø£ÙŠ ÙˆÙ‚Øª Ù„Ø¥ÙŠÙ‚Ø§Ù Ø§Ù„ØªØ¬Ø¯ÙŠØ¯Ø§Øª Ø§Ù„Ù…Ø³ØªÙ‚Ø¨Ù„ÙŠØ©. ÙŠÙ…Ù†Ø¹ Ø§Ù„Ø¥Ù„ØºØ§Ø¡ Ø§Ù„Ø±Ø³Ù… Ø§Ù„ØªØ§Ù„ÙŠ Ù„ÙƒÙ†Ù‡ Ù„Ø§ ÙŠØ³ØªØ±Ø¯ Ù…Ø¨Ù„Øº Ø§Ù„Ù…Ø¯Ø© Ø§Ù„Ø­Ø§Ù„ÙŠØ©. ØªØ¨Ù‚Ù‰ Ù‚Ø§Ø¦Ù…ØªÙƒ ÙˆÙ…ÙŠØ²Ø§ØªÙƒ Ù…ØªØ§Ø­Ø© Ø­ØªÙ‰ Ø§Ù†ØªÙ‡Ø§Ø¡ Ø§Ù„Ù…Ø¯Ø© Ø§Ù„ØªÙŠ Ø¯ÙØ¹Øª Ù…Ù‚Ø§Ø¨Ù„Ù‡Ø§ Ø¨Ø§Ù„ÙØ¹Ù„.</p>

<h2 id="new-account-ar">ÙØªØ­ Ø­Ø³Ø§Ø¨ Ø¢Ø®Ø±</h2>

<p>Ø¥Ø°Ø§ Ø±ØºØ¨Øª ÙÙŠ Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø¨Ø§Ù‚Ø© Ù…Ø®ØªÙ„ÙØ© Ø£Ùˆ Ø¥Ø¹Ø¯Ø§Ø¯ Ù‚Ø§Ø¦Ù…Ø© Ù…Ù†ÙØµÙ„Ø© Ù‚Ø¨Ù„ Ø§Ù†ØªÙ‡Ø§Ø¡ Ø§Ø´ØªØ±Ø§ÙƒÙƒ Ø§Ù„Ø­Ø§Ù„ÙŠØŒ ÙÙŠÙ…ÙƒÙ†Ùƒ <strong>Ø¥Ù†Ø´Ø§Ø¡ Ø­Ø³Ø§Ø¨ Ø¢Ø®Ø±</strong> ÙˆØ§Ù„Ø§Ø´ØªØ±Ø§Ùƒ ÙÙŠÙ‡. Ø³ÙŠØ³ØªÙ…Ø± Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ Ø§Ù„Ø£ØµÙ„ÙŠ Ø­ØªÙ‰ ØªØ§Ø±ÙŠØ® Ø§Ù†ØªÙ‡Ø§Ø¦Ù‡ ÙˆÙ„Ù† ÙŠÙØ³ØªØ±Ø¯.</p>

<h2 id="exceptions-ar">Ø§Ù„Ø§Ø³ØªØ«Ù†Ø§Ø¡Ø§Øª</h2>

<p>Ø§Ù„Ø§Ø³ØªØ«Ù†Ø§Ø¡Ø§Øª Ø§Ù„ÙˆØ­ÙŠØ¯Ø© Ù„Ù‡Ø°Ù‡ Ø§Ù„Ø³ÙŠØ§Ø³Ø© Ù‡ÙŠ Ø¹Ù†Ø¯Ù…Ø§ ÙŠÙƒÙˆÙ† Ø§Ù„Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ù…Ø·Ù„ÙˆØ¨Ù‹Ø§ Ø¨Ù…ÙˆØ¬Ø¨ Ø§Ù„Ù‚Ø§Ù†ÙˆÙ† Ø§Ù„Ù…Ø¹Ù…ÙˆÙ„ Ø¨Ù‡ØŒ Ø£Ùˆ Ø¹Ù†Ø¯ ÙØ±Ø¶ Ø±Ø³ÙˆÙ… Ø¹Ù„ÙŠÙƒ Ø¨Ø§Ù„Ø®Ø·Ø£ (Ù…Ø«Ù„ Ø±Ø³Ù… Ù…ÙƒØ±Ø± Ù„Ù†ÙØ³ Ø§Ù„Ø¨Ø§Ù‚Ø©). ÙÙŠ ØªÙ„Ùƒ Ø§Ù„Ø­Ø§Ù„Ø§ØªØŒ ÙŠØ±Ø¬Ù‰ Ø§Ù„ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ Ù…Ø¹ ØªÙØ§ØµÙŠÙ„ Ø­Ø³Ø§Ø¨Ùƒ ÙˆØ³Ù†Ø±Ø§Ø¬Ø¹ Ø§Ù„Ø·Ù„Ø¨.</p>

<h2 id="contact-ar">ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§</h2>

<p>Ø¥Ø°Ø§ ÙƒØ§Ù†Øª Ù„Ø¯ÙŠÙƒ Ø£Ø³Ø¦Ù„Ø© Ø­ÙˆÙ„ Ø³ÙŠØ§Ø³Ø© Ø§Ù„Ø§Ø³ØªØ±Ø¯Ø§Ø¯ Ù‡Ø°Ù‡ØŒ ÙŠØ±Ø¬Ù‰ Ø§Ù„ØªÙˆØ§ØµÙ„ Ù…Ø¹Ù†Ø§ Ø¹Ù„Ù‰ <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

@endif

@endsection
