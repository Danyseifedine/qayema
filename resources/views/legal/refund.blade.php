@extends('legal.layout')

@section('title', 'Refund Policy')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Refund')
@section('headline_italic', 'Policy')
@section('headline_ar', 'سياسة الاسترداد')
@section('updated', 'Last updated: June 1, 2026')
@section('updated_ar', 'آخر تحديث: 1 يونيو 2026')

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
    <li><a href="#overview-ar">نظرة عامة</a></li>
    <li><a href="#no-refunds-ar">لا توجد استردادات</a></li>
    <li><a href="#subscription-ar">مدة الاشتراك</a></li>
    <li><a href="#cancellation-ar">إلغاء الاشتراك</a></li>
    <li><a href="#new-account-ar">فتح حساب آخر</a></li>
    <li><a href="#exceptions-ar">الاستثناءات</a></li>
    <li><a href="#contact-ar">تواصل معنا</a></li>
@endsection

@section('content')

<div x-show="!isAr">

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

</div>

<div x-show="isAr">

<div class="legal-highlight">
    <p>تشرح سياسة الاسترداد هذه كيفية عمل المدفوعات في قائمة، التي تشغّلها مجموعة ليبيفاي. باختصار: بمجرد شرائك لباقة القائمة، يكون الدفع غير قابل للاسترداد. تحتفظ بكامل صلاحية الوصول حتى انتهاء مدة اشتراكك. باشتراكك، فإنك توافق على هذه السياسة.</p>
</div>

<h2 id="overview-ar">نظرة عامة</h2>

<p>قائمة خدمة رقمية يمكنك استخدامها فور الدفع. ولأن الوصول إلى الخدمة وميزاتها يبدأ لحظة تفعيل باقتك، تُعتبر جميع عمليات الشراء نهائية.</p>

<h2 id="no-refunds-ar">لا توجد استردادات</h2>

<p>بمجرد شرائك لباقة القائمة، <strong>لا يمكن استرداد المبلغ المدفوع</strong>. ينطبق هذا على جميع الباقات وفترات الفوترة، سواء بدأت باستخدام الميزات المضمّنة في باقتك أم لا.</p>

<p>نحن لا نقدّم استردادات جزئية أو تناسبية مقابل الوقت المتبقي من الاشتراك، أو مقابل ميزات غير مستخدمة، أو عند تغيير رأيك بعد الشراء.</p>

<h2 id="subscription-ar">مدة الاشتراك</h2>

<p>عند دفعك مقابل باقة، تحصل على الوصول طوال مدة الاشتراك التي اشتريتها. إذا قررت أنك لم تعد ترغب في الخدمة، فعليك <strong>الانتظار حتى نهاية مدة اشتراكك الحالية</strong> لتنتهي. يبقى وصولك فعّالاً حتى ذلك التاريخ، بحيث تستمر في الحصول على كامل قيمة ما دفعته.</p>

<h2 id="cancellation-ar">إلغاء الاشتراك</h2>

<p>يمكنك الإلغاء في أي وقت لإيقاف التجديدات المستقبلية. يمنع الإلغاء الرسم التالي لكنه لا يسترد مبلغ المدة الحالية. تبقى قائمتك وميزاتك متاحة حتى انتهاء المدة التي دفعت مقابلها بالفعل.</p>

<h2 id="new-account-ar">فتح حساب آخر</h2>

<p>إذا رغبت في استخدام باقة مختلفة أو إعداد قائمة منفصلة قبل انتهاء اشتراكك الحالي، فيمكنك <strong>إنشاء حساب آخر</strong> والاشتراك فيه. سيستمر الاشتراك الأصلي حتى تاريخ انتهائه ولن يُسترد.</p>

<h2 id="exceptions-ar">الاستثناءات</h2>

<p>الاستثناءات الوحيدة لهذه السياسة هي عندما يكون الاسترداد مطلوبًا بموجب القانون المعمول به، أو عند فرض رسوم عليك بالخطأ (مثل رسم مكرر لنفس الباقة). في تلك الحالات، يرجى التواصل معنا مع تفاصيل حسابك وسنراجع الطلب.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>إذا كانت لديك أسئلة حول سياسة الاسترداد هذه، يرجى التواصل معنا على <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

</div>

@endsection
