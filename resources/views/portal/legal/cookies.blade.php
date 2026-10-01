@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Cookie')
@section('headline_italic', 'Policy')
@section('headline_ar', 'سياسة ملفات الارتباط')
@section('updated', 'Last updated: September 28, 2026')
@section('updated_ar', 'آخر تحديث: 28 سبتمبر 2026')

@section('toc')
    <li><a href="#what">What are cookies</a></li>
    <li><a href="#types">Cookies we use</a></li>
    <li><a href="#essential">Essential cookies</a></li>
    <li><a href="#functional">Functional storage</a></li>
    <li><a href="#analytics">Analytics cookies</a></li>
    <li><a href="#third">Third-party cookies</a></li>
    <li><a href="#control">Controlling cookies</a></li>
    <li><a href="#changes">Changes</a></li>
    <li><a href="#contact">Contact</a></li>
@endsection

@section('toc_ar')
    <li><a href="#what-ar">ما هي ملفات تعريف الارتباط</a></li>
    <li><a href="#types-ar">ملفات الارتباط التي نستخدمها</a></li>
    <li><a href="#essential-ar">ملفات الارتباط الأساسية</a></li>
    <li><a href="#functional-ar">التخزين الوظيفي</a></li>
    <li><a href="#analytics-ar">ملفات الارتباط التحليلية</a></li>
    <li><a href="#third-ar">ملفات الارتباط من الأطراف الثالثة</a></li>
    <li><a href="#control-ar">التحكم في ملفات الارتباط</a></li>
    <li><a href="#changes-ar">التغييرات</a></li>
    <li><a href="#contact-ar">تواصل معنا</a></li>
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

<p>We use two categories of cookies and browser storage, and no analytics or advertising cookies:</p>
<ul>
    <li><strong>Essential cookies</strong>: required for the service to function. Cannot be disabled.</li>
    <li><strong>Functional storage</strong>: remembers your preferences (such as the dashboard language) and a guest's cart on a menu.</li>
    <li><strong>Analytics cookies</strong>: we use none. Menu statistics are recorded on our server, as described below.</li>
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
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema-session</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Keeps you signed in and holds temporary data such as your chosen website language. On a public menu, it links a guest's visit and actions together for the menu's statistics and protects orders</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Up to 1 year</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">XSRF-TOKEN</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Protects against cross-site request forgery attacks</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Up to 1 year</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">remember_web_…</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Keeps you signed in even after your session ends when "Keep me signed in" is ticked (it is by default) or when you sign in with Google</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">400 days</td>
        </tr>
    </tbody>
</table>

<h2 id="functional">Functional storage</h2>

<p>Functional storage allows the service to remember choices you make, such as your preferred language and theme, and keeps a guest's cart on a menu. Apart from your website language, which is kept inside the session, these entries live in your browser's local storage and are never sent to our servers on their own. Clearing them may affect your experience.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Name</th>
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Purpose</th>
            <th style="text-align:left;padding:8px 12px;font-weight:600;color:var(--muted)">Duration</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">owner_locale</code> (inside the session)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Remembers the language you chose on the website and sign-in pages</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">As long as the session</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema-theme</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Remembers the light or dark theme you chose on the website</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Until you clear it</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema.dashboard.theme.v1</code>, <code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema.dashboard.locale.v1</code>, <code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema.dashboard.sidebar.collapsed.v1</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Remember your dashboard theme, language and whether the sidebar is collapsed</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Until you clear it</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema-cart-…</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">On menus that take orders, keeps the guest's cart so a reload does not lose it; emptied when the order is placed</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">Until you clear it</td>
        </tr>
    </tbody>
</table>

<h2 id="analytics">Analytics cookies</h2>

<p>We do not use analytics cookies or third-party analytics tools, and we do not track how you use the dashboard. Nothing here is used to follow anyone across websites.</p>

<p>On public menu pages, statistics are recorded on our server rather than in a separate cookie. Each menu view is recorded with the session identifier from the session cookie above (or, without a session, a one-way hash of the IP address and browser; the IP address itself is not stored), the device type, browser name, operating system, menu language, whether the visit came through the menu's QR code, and the time. Some guest actions are recorded with the same identifier: adding a dish to the cart, opening a category, searches (including ones that found nothing), taps on the WhatsApp, map, call and social links, and switching language. The restaurant's owner sees these records as statistics; they are deleted after 6 months, and owners previewing their own menu are not recorded. See our <a href="{{ \App\Support\PortalUrl::to('privacy') }}">Privacy Policy</a> for details.</p>

<h2 id="third">Third-party cookies</h2>

<p>Some features of Qayema load third-party services, which may set their own cookies or log request data:</p>

<ul>
    <li><strong>Google Fonts:</strong> Used to load the typefaces on the website, the dashboard and menus. Google may log request metadata. See <a href="https://policies.google.com/privacy" target="_blank">Google's Privacy Policy</a>.</li>
    <li><strong>Google sign-in:</strong> If you sign in with Google, Google sets authentication cookies. See <a href="https://policies.google.com/privacy" target="_blank">Google's Privacy Policy</a>.</li>
    <li><strong>Google reCAPTCHA:</strong> Where it is enabled, the sign-in and contact pages load reCAPTCHA to tell people from automated abuse, and Google may set cookies for it. See <a href="https://policies.google.com/privacy" target="_blank">Google's Privacy Policy</a>.</li>
    <li><strong>OpenStreetMap:</strong> Menus that show the restaurant's location load a map from OpenStreetMap. See <a href="https://osmfoundation.org/wiki/Privacy_Policy" target="_blank">the OpenStreetMap Foundation's Privacy Policy</a>.</li>
    <li><strong>jsDelivr:</strong> Some scripts on our website pages are loaded from the jsDelivr content delivery network, which may log request data.</li>
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

<p>You can also clear localStorage from your browser's settings or Developer Tools (Application tab) to remove the stored theme, dashboard preferences and menu carts.</p>

<h2 id="changes">Changes to this policy</h2>

<p>We may update this Cookie Policy to reflect changes in our practices or applicable law. When we make significant changes, we will update the "Last updated" date at the top of this page. Continued use of Qayema after changes constitutes acceptance of the updated policy.</p>

<h2 id="contact">Contact</h2>

<p>If you have questions about our use of cookies, please contact us at <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a>.</p>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>تشرح سياسة ملفات تعريف الارتباط هذه كيف تستخدم Qayema، التي تشغّلها مجموعة ليبيفاي، ملفات تعريف الارتباط والتقنيات المماثلة على qayema.com وصفحات القوائم العامة التي نستضيفها للمطاعم. باستخدامك لخدمتنا، فإنك توافق على استخدام ملفات تعريف الارتباط كما هو موضح هنا.</p>
</div>

<h2 id="what-ar">ما هي ملفات تعريف الارتباط</h2>

<p>ملفات تعريف الارتباط هي ملفات نصية صغيرة تُوضع على جهازك عند زيارتك موقعًا إلكترونيًا. تُستخدم على نطاق واسع لجعل المواقع تعمل بكفاءة وتذكّر تفضيلاتك وتزويد أصحاب المواقع بالمعلومات.</p>
<p>تشمل التقنيات المماثلة التخزين المحلي وتخزين الجلسة وعلامات البكسل. حين نشير إلى "ملفات تعريف الارتباط" في هذه السياسة، فإننا نقصد جميع هذه التقنيات.</p>

<h2 id="types-ar">ملفات الارتباط التي نستخدمها</h2>

<p>نستخدم فئتين من ملفات تعريف الارتباط والتخزين في المتصفح، ولا نستخدم أي ملفات ارتباط تحليلية أو إعلانية:</p>
<ul>
    <li><strong>ملفات الارتباط الأساسية</strong>: ضرورية لعمل الخدمة. لا يمكن تعطيلها.</li>
    <li><strong>التخزين الوظيفي</strong>: يتذكر تفضيلاتك (مثل لغة لوحة التحكم) وسلة الضيف في القائمة.</li>
    <li><strong>ملفات الارتباط التحليلية</strong>: لا نستخدم أيًا منها. تُسجَّل إحصاءات القوائم على خادمنا كما هو موضح أدناه.</li>
</ul>

<h2 id="essential-ar">ملفات الارتباط الأساسية</h2>

<p>هذه الملفات ضرورية تمامًا لتشغيل الخدمة. بدونها لن تعمل ميزات مثل تسجيل الدخول والحفاظ على جلستك والحماية من هجمات طلب التزوير عبر المواقع (CSRF). لا يمكنك إلغاء الاشتراك في هذه الملفات.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">ملف الارتباط</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">الغرض</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">المدة</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema-session</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يُبقيك مسجّل الدخول ويخزّن بيانات مؤقتة مثل لغة الموقع التي اخترتها. وفي القائمة العامة، يربط زيارة الضيف وما يفعله ببعضهما لإحصاءات القائمة ويحمي الطلبات</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">حتى سنة واحدة</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">XSRF-TOKEN</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يحمي من هجمات طلب التزوير عبر المواقع</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">حتى سنة واحدة</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">remember_web_…</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يُبقيك مسجّل الدخول حتى بعد انتهاء جلستك عند تفعيل خيار "ابقني متصلاً" (وهو مفعّل افتراضيًا) أو عند تسجيل الدخول عبر Google</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">400 يوم</td>
        </tr>
    </tbody>
</table>

<h2 id="functional-ar">التخزين الوظيفي</h2>

<p>يتيح التخزين الوظيفي للخدمة تذكّر الخيارات التي تتخذها، مثل اللغة والمظهر المفضّلين لديك، ويحفظ سلة الضيف في القائمة. باستثناء لغة الموقع التي تُحفظ داخل الجلسة، تُخزَّن هذه الإدخالات في التخزين المحلي لمتصفحك ولا تُرسل وحدها إلى خوادمنا. قد يؤثر مسحها على تجربتك.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">الاسم</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">الغرض</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">المدة</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">owner_locale</code> (داخل الجلسة)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يتذكر اللغة التي اخترتها في الموقع وصفحات تسجيل الدخول</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">طوال مدة الجلسة</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema-theme</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يتذكر المظهر الفاتح أو الداكن الذي اخترته في الموقع</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">حتى تمسحه</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema.dashboard.theme.v1</code>، <code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema.dashboard.locale.v1</code>، <code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema.dashboard.sidebar.collapsed.v1</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">تتذكر مظهر لوحة التحكم ولغتها وما إذا كان الشريط الجانبي مطويًا</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">حتى تمسحها</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema-cart-…</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">في القوائم التي تستقبل الطلبات، يحفظ سلة الضيف كي لا تضيع عند إعادة تحميل الصفحة، ويُفرَّغ عند إرسال الطلب</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">حتى تمسحه</td>
        </tr>
    </tbody>
</table>

<h2 id="analytics-ar">ملفات الارتباط التحليلية</h2>

<p>لا نستخدم ملفات ارتباط تحليلية أو أدوات تحليل من أطراف ثالثة، ولا نتتبّع كيفية استخدامك للوحة التحكم. ولا يُستخدم أي مما سبق لتتبّع أحد عبر المواقع.</p>

<p>في صفحات القوائم العامة، تُسجَّل الإحصاءات على خادمنا وليس في ملف ارتباط منفصل. تُسجَّل كل مشاهدة للقائمة مع معرّف الجلسة المأخوذ من ملف ارتباط الجلسة أعلاه (أو، عند عدم وجود جلسة، تجزئة أحادية الاتجاه لعنوان IP والمتصفح؛ ولا يُخزَّن عنوان IP نفسه)، ونوع الجهاز واسم المتصفح ونظام التشغيل ولغة القائمة وما إذا جاءت الزيارة عبر رمز QR الخاص بالقائمة ووقتها. وتُسجَّل بعض أفعال الضيوف بالمعرّف نفسه: إضافة طبق إلى السلة، وفتح فئة، وعمليات البحث (بما فيها التي لم تجد نتيجة)، والنقر على روابط WhatsApp والخريطة والاتصال ومواقع التواصل، وتبديل اللغة. يرى صاحب المطعم هذه السجلات على شكل إحصاءات، وتُحذف بعد 6 أشهر، ولا تُسجَّل معاينة أصحاب المطاعم لقوائمهم. راجع <a href="{{ \App\Support\PortalUrl::to('privacy') }}">سياسة الخصوصية</a> لمزيد من التفاصيل.</p>

<h2 id="third-ar">ملفات الارتباط من الأطراف الثالثة</h2>

<p>بعض ميزات Qayema تحمّل خدمات من أطراف ثالثة قد تضع ملفات الارتباط الخاصة بها أو تسجّل بيانات الطلبات:</p>

<ul>
    <li><strong>خطوط Google:</strong> تُستخدم لتحميل الخطوط المعروضة في الموقع ولوحة التحكم والقوائم. قد تسجّل Google بيانات تعريفية للطلبات. راجع <a href="https://policies.google.com/privacy" target="_blank">سياسة خصوصية Google</a>.</li>
    <li><strong>تسجيل الدخول عبر Google:</strong> إذا سجّلت دخولك باستخدام Google، تضع Google ملفات ارتباط للمصادقة. راجع <a href="https://policies.google.com/privacy" target="_blank">سياسة خصوصية Google</a>.</li>
    <li><strong>Google reCAPTCHA:</strong> حين يكون مفعّلًا، تحمّل صفحتا تسجيل الدخول والتواصل reCAPTCHA للتمييز بين الأشخاص والإساءة الآلية، وقد تضع Google ملفات ارتباط لذلك. راجع <a href="https://policies.google.com/privacy" target="_blank">سياسة خصوصية Google</a>.</li>
    <li><strong>OpenStreetMap:</strong> القوائم التي تعرض موقع المطعم تحمّل خريطة من OpenStreetMap. راجع <a href="https://osmfoundation.org/wiki/Privacy_Policy" target="_blank">سياسة خصوصية مؤسسة OpenStreetMap</a>.</li>
    <li><strong>jsDelivr:</strong> تُحمَّل بعض البرامج النصية في صفحات موقعنا من شبكة توصيل المحتوى jsDelivr، التي قد تسجّل بيانات الطلبات.</li>
</ul>

<p>نحن لا نستخدم ملفات ارتباط إعلانية أو بكسلات تتبع من أطراف ثالثة.</p>

<h2 id="control-ar">التحكم في ملفات تعريف الارتباط</h2>

<p>يمكنك التحكم في ملفات تعريف الارتباط وحذفها من خلال إعدادات متصفحك. يُرجى ملاحظة أن تعطيل ملفات الارتباط الأساسية سيمنعك من تسجيل الدخول أو استخدام لوحة التحكم.</p>

<p>تعليمات المتصفحات لإدارة ملفات تعريف الارتباط:</p>
<ul>
    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/en-us/guide/safari/sfri11471/mac" target="_blank">Apple Safari</a></li>
    <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank">Microsoft Edge</a></li>
</ul>

<p>يمكنك أيضًا مسح التخزين المحلي من إعدادات متصفحك أو أدوات المطوّرين فيه (علامة تبويب التطبيقات) لإزالة المظهر المحفوظ وتفضيلات لوحة التحكم وسلال القوائم.</p>

<h2 id="changes-ar">التغييرات على هذه السياسة</h2>

<p>قد نحدّث سياسة ملفات تعريف الارتباط هذه لتعكس التغييرات في ممارساتنا أو القانون المعمول به. عند إجراء تغييرات جوهرية، سنحدّث تاريخ "آخر تحديث" في أعلى هذه الصفحة. يُعدّ استمرار استخدامك لـQayema بعد التغييرات قبولاً للسياسة المحدّثة.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>إذا كانت لديك أسئلة حول استخدامنا لملفات تعريف الارتباط، يرجى التواصل معنا على <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a>.</p>

@endif

@endsection
