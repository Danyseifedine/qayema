@extends('legal.layout')

@section('title', 'Cookie Policy')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Cookie')
@section('headline_italic', 'Policy')
@section('headline_ar', 'سياسة ملفات الارتباط')
@section('updated', 'Last updated: May 9, 2025')
@section('updated_ar', 'آخر تحديث: 9 مايو 2025')

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
    <li><a href="#what-ar">ما هي ملفات تعريف الارتباط</a></li>
    <li><a href="#types-ar">ملفات الارتباط التي نستخدمها</a></li>
    <li><a href="#essential-ar">ملفات الارتباط الأساسية</a></li>
    <li><a href="#functional-ar">ملفات الارتباط الوظيفية</a></li>
    <li><a href="#analytics-ar">ملفات الارتباط التحليلية</a></li>
    <li><a href="#third-ar">ملفات الارتباط من الأطراف الثالثة</a></li>
    <li><a href="#control-ar">التحكم في ملفات الارتباط</a></li>
    <li><a href="#changes-ar">التغييرات</a></li>
    <li><a href="#contact-ar">تواصل معنا</a></li>
@endsection

@section('content')

<div x-show="!isAr">

<div class="legal-highlight">
    <p>This Cookie Policy explains how Qayema, operated by Lebify Group, uses cookies and similar tracking technologies on qayema.com and the public menu pages we host for restaurants. By using our service, you consent to the use of cookies as described here.</p>
</div>

<h2 id="what">What are cookies</h2>

<p>Cookies are small text files placed on your device when you visit a website. They are widely used to make websites work efficiently, to remember your preferences, and to provide information to site owners.</p>
<p>Similar technologies include local storage, session storage, and pixel tags. Where we refer to "cookies" in this policy, we include all these technologies.</p>

<h2 id="types">Cookies we use</h2>

<p>We use three categories of cookies:</p>
<ul>
    <li><strong>Essential cookies</strong> — required for the service to function. Cannot be disabled.</li>
    <li><strong>Functional cookies</strong> — remember your preferences (e.g. dashboard language).</li>
    <li><strong>Analytics cookies</strong> — help us understand how visitors use our service.</li>
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

</div>

<div x-show="isAr">

<div class="legal-highlight">
    <p>تشرح سياسة ملفات تعريف الارتباط هذه كيف تستخدم قائمة، التي تشغّلها مجموعة ليبيفاي، ملفات تعريف الارتباط والتقنيات المماثلة على qayema.com وصفحات القوائم العامة التي نستضيفها للمطاعم. باستخدامك لخدمتنا، فإنك توافق على استخدام ملفات تعريف الارتباط كما هو موضح هنا.</p>
</div>

<h2 id="what-ar">ما هي ملفات تعريف الارتباط</h2>

<p>ملفات تعريف الارتباط هي ملفات نصية صغيرة تُوضع على جهازك عند زيارتك موقعًا إلكترونيًا. تُستخدم على نطاق واسع لجعل المواقع تعمل بكفاءة وتذكّر تفضيلاتك وتزويد أصحاب المواقع بالمعلومات.</p>
<p>تشمل التقنيات المماثلة التخزين المحلي وتخزين الجلسة وعلامات البكسل. حين نشير إلى "ملفات تعريف الارتباط" في هذه السياسة، فإننا نقصد جميع هذه التقنيات.</p>

<h2 id="types-ar">ملفات الارتباط التي نستخدمها</h2>

<p>نستخدم ثلاث فئات من ملفات تعريف الارتباط:</p>
<ul>
    <li><strong>ملفات الارتباط الأساسية</strong> — ضرورية لعمل الخدمة. لا يمكن تعطيلها.</li>
    <li><strong>ملفات الارتباط الوظيفية</strong> — تتذكر تفضيلاتك (مثل لغة لوحة التحكم).</li>
    <li><strong>ملفات الارتباط التحليلية</strong> — تساعدنا على فهم كيفية تفاعل الزوار مع خدمتنا.</li>
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
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema_session</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يحافظ على جلسة تسجيل دخولك ويخزّن البيانات المؤقتة</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">جلسة (إغلاق المتصفح)</td>
        </tr>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">XSRF-TOKEN</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يحمي من هجمات طلب التزوير عبر المواقع</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">جلسة</td>
        </tr>
    </tbody>
</table>

<h2 id="functional-ar">ملفات الارتباط الوظيفية</h2>

<p>تتيح ملفات الارتباط الوظيفية للخدمة تذكّر الخيارات التي تتخذها، مثل لغة لوحة التحكم المفضّلة لديك. قد يؤثر تعطيل هذه الملفات على تجربتك.</p>

<table style="width:100%;border-collapse:collapse;font-size:13.5px;margin-bottom:16px">
    <thead>
        <tr style="border-bottom:1px solid rgba(15,15,16,.1)">
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">ملف الارتباط / التخزين</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">الغرض</th>
            <th style="text-align:right;padding:8px 12px;font-weight:600;color:var(--muted)">المدة</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid rgba(15,15,16,.06)">
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">owner_locale</code></td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يتذكر لغة لوحة التحكم المختارة</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">جلسة</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)"><code style="font-size:12px;background:rgba(15,15,16,.05);padding:2px 6px;border-radius:4px">qayema_lang</code> (localStorage)</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">يتذكر تفضيلات اللغة على الموقع العام</td>
            <td style="padding:8px 12px;color:rgba(15,15,16,.75)">دائم (سنة واحدة)</td>
        </tr>
    </tbody>
</table>

<h2 id="analytics-ar">ملفات الارتباط التحليلية</h2>

<p>نستخدم تحليلات خفيفة تراعي الخصوصية لفهم كيفية تفاعل الزوار مع لوحة التحكم وصفحات القوائم العامة. هذه البيانات مجهولة الهوية ومجمّعة ولا تُستخدم لتتبع الأفراد عبر المواقع.</p>

<p>بالنسبة لصفحات القوائم العامة المستضافة على قائمة، نسجّل معرّف الجلسة ونوع الجهاز والمتصفح ونظام التشغيل ومدة الزيارة لكل جلسة. هذه البيانات مرئية لصاحب المطعم كإحصاءات الزوار. نحن لا نربط هذه البيانات بأي معلومات تعريف شخصية.</p>

<h2 id="third-ar">ملفات الارتباط من الأطراف الثالثة</h2>

<p>بعض ميزات قائمة تتضمن خدمات من أطراف ثالثة قد تضع ملفات الارتباط الخاصة بها:</p>

<ul>
    <li><strong>خطوط Google:</strong> تُستخدم لتحميل الخطوط المعروضة على الموقع. قد تسجّل Google بيانات تعريفية للطلبات. راجع <a href="https://policies.google.com/privacy" target="_blank">سياسة خصوصية Google</a>.</li>
    <li><strong>Google OAuth:</strong> إذا سجّلت دخولك باستخدام Google، تضع Google ملفات ارتباط للمصادقة. راجع <a href="https://policies.google.com/privacy" target="_blank">سياسة خصوصية Google</a>.</li>
    <li><strong>معالج الدفع (Stripe):</strong> إذا اشتركت في باقة مدفوعة، يضع Stripe ملفات ارتباط للكشف عن الاحتيال. راجع <a href="https://stripe.com/privacy" target="_blank">سياسة خصوصية Stripe</a>.</li>
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

<p>يمكنك أيضًا مسح التخزين المحلي من أدوات المطوّرين في متصفحك (علامة تبويب التطبيقات) لإزالة التخزين الوظيفي الدائم.</p>

<h2 id="changes-ar">التغييرات على هذه السياسة</h2>

<p>قد نحدّث سياسة ملفات تعريف الارتباط هذه لتعكس التغييرات في ممارساتنا أو القانون المعمول به. عند إجراء تغييرات جوهرية، سنحدّث تاريخ "آخر تحديث" في أعلى هذه الصفحة. يُعدّ استمرار استخدامك لقائمة بعد التغييرات قبولاً للسياسة المحدّثة.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>إذا كانت لديك أسئلة حول استخدامنا لملفات تعريف الارتباط، يرجى التواصل معنا على <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

</div>

@endsection
