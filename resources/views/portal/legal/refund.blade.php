@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Refund')
@section('headline_italic', 'Policy')
@section('headline_ar', 'سياسة الاسترداد')
@section('updated', 'Last updated: September 28, 2026')
@section('updated_ar', 'آخر تحديث: 28 سبتمبر 2026')

@section('toc')
    <li><a href="#overview">Overview</a></li>
    <li><a href="#no-refunds">No refunds</a></li>
    <li><a href="#subscription">Package period</a></li>
    <li><a href="#cancellation">Ending a package early</a></li>
    <li><a href="#new-account">Starting another account</a></li>
    <li><a href="#exceptions">Exceptions</a></li>
    <li><a href="#contact">Contact</a></li>
@endsection

@section('toc_ar')
    <li><a href="#overview-ar">نظرة عامة</a></li>
    <li><a href="#no-refunds-ar">لا توجد استردادات</a></li>
    <li><a href="#subscription-ar">مدة الباقة</a></li>
    <li><a href="#cancellation-ar">إنهاء الباقة مبكرًا</a></li>
    <li><a href="#new-account-ar">فتح حساب آخر</a></li>
    <li><a href="#exceptions-ar">الاستثناءات</a></li>
    <li><a href="#contact-ar">تواصل معنا</a></li>
@endsection

@section('legal_body')

@unless ($isAr)

<div class="legal-highlight">
    <p>This Refund Policy explains how payments for paid packages work on Qayema, operated by Lebify Group. In short: the Free package costs nothing. The Pro, Premium and Custom packages are paid for directly with us for an agreed period; once paid, the payment is non-refundable, and you keep the package until that period ends. By paying for a package, you agree to this policy.</p>
</div>

<h2 id="overview">Overview</h2>

<p>Qayema does not take payments inside the service and never charges you automatically. When you ask for a paid package, we agree the price, the period and the way you will pay with you directly, and then switch the package on for your restaurant. Because the package's features are available from the moment it is switched on, every payment for a package is final.</p>

<h2 id="no-refunds">No refunds</h2>

<p>Once you have paid for a package, <strong>the payment cannot be refunded</strong>. This applies to every paid package and period, whether or not you have started using the features included in your package.</p>

<p>We do not provide partial or pro-rated refunds for time remaining in a package period, for unused features, or for changing your mind after paying.</p>

<h2 id="subscription">Package period</h2>

<p>When you pay for a package, it stays on for the full period agreed with you, or with no end date if that is what was agreed. If you decide you no longer want it, you do not need to do anything: <strong>nothing is charged again</strong>, and when the period ends your restaurant returns to the Free package automatically. Your menu and everything you made stay in place; content over the Free limits stays on your menu, and features that Free does not include stop working while their settings are kept.</p>

<h2 id="cancellation">Ending a package early</h2>

<p>You may ask us at any time to end your package before its period is over, for example to go back to the Free package. Ending a package early does not refund the time left in the period. If you want a different package before your period ends, ask us: the change and its price are agreed with you directly.</p>

<h2 id="new-account">Starting another account</h2>

<p>Each account holds one restaurant menu. If you want a separate menu, for example for another branch, you are welcome to <strong>create another account</strong>; a package for it is agreed separately. A package paid for one restaurant stays with that restaurant until its end date and is not refunded because another account was opened.</p>

<h2 id="exceptions">Exceptions</h2>

<p>The only exceptions to this policy are where a refund is required by applicable law, or where you were charged in error (for example, paying twice for the same package and period). In those cases, please contact us with your account details and we will review the request.</p>

<h2 id="contact">Contact</h2>

<p>If you have questions about this Refund Policy, please contact us at <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a>.</p>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>تشرح سياسة الاسترداد هذه كيفية عمل مدفوعات الباقات المدفوعة في Qayema، التي تشغّلها مجموعة ليبيفاي. باختصار: الباقة المجانية بلا مقابل. أما باقات برو ومميّز ومخصّص فتُدفع مباشرةً لنا مقابل مدة متفق عليها؛ وبعد الدفع لا يمكن استرداد المبلغ، وتبقى الباقة مفعّلة لك حتى نهاية تلك المدة. بدفعك مقابل باقة، فإنك توافق على هذه السياسة.</p>
</div>

<h2 id="overview-ar">نظرة عامة</h2>

<p>لا تتقاضى Qayema أي مدفوعات داخل الخدمة ولا تخصم منك أي مبلغ تلقائيًا أبدًا. عندما تطلب باقة مدفوعة، نتفق معك مباشرةً على السعر والمدة وطريقة الدفع، ثم نفعّل الباقة لمطعمك. ولأن ميزات الباقة تصبح متاحة لحظة تفعيلها، يُعتبر كل دفع مقابل باقة نهائيًا.</p>

<h2 id="no-refunds-ar">لا توجد استردادات</h2>

<p>بمجرد دفعك مقابل باقة، <strong>لا يمكن استرداد المبلغ المدفوع</strong>. ينطبق هذا على جميع الباقات المدفوعة ومددها، سواء بدأت باستخدام الميزات المضمّنة في باقتك أم لا.</p>

<p>نحن لا نقدّم استردادات جزئية أو تناسبية مقابل الوقت المتبقي من مدة الباقة، أو مقابل ميزات غير مستخدمة، أو عند تغيير رأيك بعد الدفع.</p>

<h2 id="subscription-ar">مدة الباقة</h2>

<p>عند دفعك مقابل باقة، تبقى مفعّلة طوال المدة المتفق عليها معك، أو دون تاريخ انتهاء إذا كان هذا ما اتُّفق عليه. إذا قررت أنك لم تعد ترغب فيها، فلا حاجة لأي إجراء منك: <strong>لا يُخصم منك أي مبلغ مجددًا</strong>، وعند انتهاء المدة يعود مطعمك تلقائيًا إلى الباقة المجانية. تبقى قائمتك وكل ما أنشأته في مكانه؛ يبقى المحتوى الذي يتجاوز حدود الباقة المجانية في قائمتك، والميزات التي لا تتضمنها الباقة المجانية تتوقف عن العمل مع الاحتفاظ بإعداداتها.</p>

<h2 id="cancellation-ar">إنهاء الباقة مبكرًا</h2>

<p>يمكنك أن تطلب منا في أي وقت إنهاء باقتك قبل انتهاء مدتها، مثلًا للعودة إلى الباقة المجانية. لا يترتب على إنهاء الباقة مبكرًا استرداد مقابل الوقت المتبقي من المدة. وإذا رغبت في باقة مختلفة قبل انتهاء مدتك، فاطلب ذلك منا: يُتفق معك مباشرةً على التغيير وسعره.</p>

<h2 id="new-account-ar">فتح حساب آخر</h2>

<p>يضم كل حساب قائمة مطعم واحدة. إذا رغبت في قائمة منفصلة، مثلًا لفرع آخر، فيمكنك <strong>إنشاء حساب آخر</strong>، ويُتفق على باقته بشكل منفصل. تبقى الباقة المدفوعة لمطعم ما مرتبطة بذلك المطعم حتى تاريخ انتهائها، ولا تُسترد بسبب فتح حساب آخر.</p>

<h2 id="exceptions-ar">الاستثناءات</h2>

<p>الاستثناءات الوحيدة لهذه السياسة هي عندما يكون الاسترداد مطلوبًا بموجب القانون المعمول به، أو عند فرض رسوم عليك بالخطأ (مثل الدفع مرتين مقابل الباقة نفسها والمدة نفسها). في تلك الحالات، يرجى التواصل معنا مع تفاصيل حسابك وسنراجع الطلب.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>إذا كانت لديك أسئلة حول سياسة الاسترداد هذه، يرجى التواصل معنا على <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a>.</p>

@endif

@endsection
