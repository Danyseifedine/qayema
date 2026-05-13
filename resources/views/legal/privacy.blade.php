@extends('legal.layout')

@section('title', 'Privacy Policy')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Privacy')
@section('headline_italic', 'Policy')
@section('headline_ar', 'سياسة الخصوصية')
@section('updated', 'Last updated: May 9, 2025')
@section('updated_ar', 'آخر تحديث: 9 مايو 2025')

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
    <li><a href="#information-ar">المعلومات التي نجمعها</a></li>
    <li><a href="#use-ar">كيف نستخدم معلوماتك</a></li>
    <li><a href="#sharing-ar">مشاركة معلوماتك</a></li>
    <li><a href="#storage-ar">تخزين البيانات والأمان</a></li>
    <li><a href="#cookies-ar">ملفات تعريف الارتباط</a></li>
    <li><a href="#rights-ar">حقوقك</a></li>
    <li><a href="#children-ar">خصوصية الأطفال</a></li>
    <li><a href="#changes-ar">التغييرات على هذه السياسة</a></li>
    <li><a href="#contact-ar">تواصل معنا</a></li>
@endsection

@section('content')

<div x-show="!isAr">

<div class="legal-highlight">
    <p>This Privacy Policy explains how Qayema, operated by Lebify Group, collects, uses, and protects information about you when you use our service at qayema.com. By using Qayema, you agree to the practices described here.</p>
</div>

<h2 id="information">Information we collect</h2>

<h3>Account information</h3>
<p>When you register for a Qayema account (via email or Google), we collect your name, email address, and authentication credentials. We do not store your Google password.</p>

<h3>Restaurant & menu data</h3>
<p>To provide the service, we store the restaurant information, categories, dishes, prices, images, and settings you add to your account. This data belongs to you.</p>

<h3>Usage & analytics data</h3>
<p>We collect information about how you use the dashboard, including pages visited, actions taken, and session duration. For public menus, we collect anonymised visitor statistics — session identifiers, device type, browser, operating system, time on page, and whether the visit came from a QR code scan. We do not collect names or personal details of your guests.</p>

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

</div>

<div x-show="isAr">

<div class="legal-highlight">
    <p>تشرح سياسة الخصوصية هذه كيف تجمع قائمة، التي تشغّلها مجموعة ليبيفاي، المعلومات عنك وتستخدمها وتحميها عند استخدامك لخدمتنا على qayema.com. باستخدامك لقائمة، فإنك توافق على الممارسات الموضحة هنا.</p>
</div>

<h2 id="information-ar">المعلومات التي نجمعها</h2>

<h3>معلومات الحساب</h3>
<p>عند التسجيل في حساب قائمة (عبر البريد الإلكتروني أو Google)، نجمع اسمك وعنوان بريدك الإلكتروني وبيانات اعتماد المصادقة. نحن لا نحتفظ بكلمة مرور Google الخاصة بك.</p>

<h3>بيانات المطعم والقائمة</h3>
<p>لتقديم الخدمة، نحتفظ بمعلومات المطعم والفئات والأطباق والأسعار والصور والإعدادات التي تضيفها إلى حسابك. هذه البيانات ملك لك.</p>

<h3>بيانات الاستخدام والتحليلات</h3>
<p>نجمع معلومات حول كيفية استخدامك للوحة التحكم، بما في ذلك الصفحات التي تزورها والإجراءات التي تتخذها ومدة الجلسة. بالنسبة للقوائم العامة، نجمع إحصاءات زوار مجهولة الهوية تشمل: معرّفات الجلسة ونوع الجهاز والمتصفح ونظام التشغيل والوقت المقضي في الصفحة وما إذا كانت الزيارة من رمز QR. نحن لا نجمع أسماء أو تفاصيل شخصية لضيوفك.</p>

<h3>البيانات التقنية</h3>
<p>نتلقى تلقائيًا بيانات سجل الخادم القياسية، بما في ذلك عناوين IP وطوابع وقت الطلبات وترويسات HTTP. تُستخدم هذه البيانات لأغراض الأمان والتصحيح ومراقبة البنية التحتية.</p>

<h3>بيانات الدفع</h3>
<p>لا تتعامل قائمة مباشرةً مع معلومات بطاقات الدفع. تتم معالجة المدفوعات عبر مزودي خدمة خارجيين (مثل Stripe) المسؤولين عن أمان بياناتك المالية.</p>

<h2 id="use-ar">كيف نستخدم معلوماتك</h2>

<ul>
    <li>لإنشاء حسابك وملف مطعمك والحفاظ عليهما</li>
    <li>لتقديم لوحة تحكم قائمة وميزات القائمة العامة</li>
    <li>لتزويدك بإحصاءات الزوار لقائمتك</li>
    <li>لإرسال رسائل البريد الإلكتروني التعاملية (تأكيد الحساب، إعادة تعيين كلمة المرور)</li>
    <li>للكشف عن الاحتيال والإساءة والحوادث الأمنية ومنعها</li>
    <li>لتحسين خدمتنا وتطويرها بناءً على أنماط الاستخدام</li>
    <li>للامتثال للالتزامات القانونية</li>
</ul>

<p>نحن لا نبيع بياناتك. نحن لا نستخدم بياناتك لأغراض إعلانية.</p>

<h2 id="sharing-ar">مشاركة معلوماتك</h2>

<p>نشارك معلوماتك فقط في الحالات التالية:</p>

<ul>
    <li><strong>مزودو الخدمة:</strong> شركاء البنية التحتية (الاستضافة وقواعد البيانات وتسليم البريد الإلكتروني وتخزين الصور) الذين يعالجون البيانات نيابةً عنا وفق اتفاقيات معالجة بيانات صارمة.</li>
    <li><strong>خدمات الذكاء الاصطناعي:</strong> عند استخدامك لماسح القوائم بالذكاء الاصطناعي، تُرسل الصور التي ترفعها إلى Google Gemini API للمعالجة. لا تُدرج أي معلومات تعريف شخصية.</li>
    <li><strong>المتطلبات القانونية:</strong> إذا طُلب ذلك بموجب القانون أو أمر قضائي أو لحماية حقوق وسلامة قائمة أو الآخرين.</li>
    <li><strong>التحويلات التجارية:</strong> في سياق الاندماج أو الاستحواذ أو بيع الأصول، مع التزامات سرية مناسبة.</li>
</ul>

<p>نحن لا نبيع بياناتك الشخصية أو نؤجرها أو نتاجر بها مع أطراف ثالثة لأغراضهم التسويقية الخاصة.</p>

<h2 id="storage-ar">تخزين البيانات والأمان</h2>

<p>يتم تخزين بياناتك على خوادم آمنة. نطبّق معايير أمان صناعية تشمل التشفير أثناء النقل (HTTPS/TLS) والتشفير للحقول الحساسة وضوابط الوصول ومراجعات الأمان المنتظمة.</p>
<p>لا توجد طريقة نقل عبر الإنترنت آمنة بنسبة 100%. بينما نسعى جاهدين لحماية بياناتك، لا يمكننا ضمان الأمان المطلق. في حال حدوث اختراق للبيانات يؤثر على حقوقك، سنُخطرك وفقًا للقانون المعمول به.</p>
<p>نحتفظ ببياناتك طالما حسابك نشط. عند حذف حسابك، تُزال بياناتك نهائيًا من أنظمتنا خلال 30 يومًا، إلا إذا كان الاحتفاظ بها مطلوبًا قانونًا.</p>

<h2 id="cookies-ar">ملفات تعريف الارتباط</h2>

<p>نستخدم ملفات تعريف الارتباط والتقنيات المماثلة لتشغيل الخدمة. لمزيد من التفاصيل، يرجى قراءة <a href="{{ route('cookies') }}">سياسة ملفات تعريف الارتباط</a>.</p>

<h2 id="rights-ar">حقوقك</h2>

<p>بحسب موقعك، قد تتمتع بالحقوق التالية فيما يخص بياناتك الشخصية:</p>

<ul>
    <li><strong>الوصول:</strong> طلب نسخة من البيانات الشخصية التي نحتفظ بها عنك.</li>
    <li><strong>التصحيح:</strong> طلب تصحيح البيانات غير الدقيقة أو غير المكتملة.</li>
    <li><strong>الحذف:</strong> طلب حذف حسابك والبيانات المرتبطة به.</li>
    <li><strong>قابلية النقل:</strong> الحصول على بياناتك بتنسيق قابل للقراءة آليًا.</li>
    <li><strong>الاعتراض:</strong> الاعتراض على أنواع معينة من المعالجة.</li>
    <li><strong>التقييد:</strong> طلب تحديد كيفية استخدامنا لبياناتك.</li>
</ul>

<p>لممارسة أي من هذه الحقوق، تواصل معنا على <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>. سنردّ خلال 30 يومًا.</p>

<h2 id="children-ar">خصوصية الأطفال</h2>

<p>قائمة موجّهة لأصحاب المطاعم والمشغّلين وليست موجّهة للأطفال دون سن 16 عامًا. نحن لا نجمع عن قصد معلومات شخصية من الأطفال. إذا كنت تعتقد أن طفلاً قد زوّدنا بمعلومات شخصية، فيرجى التواصل معنا وسنحذفها فورًا.</p>

<h2 id="changes-ar">التغييرات على هذه السياسة</h2>

<p>قد نحدّث سياسة الخصوصية هذه من وقت لآخر. عند إجراء تغييرات جوهرية، سنُخطرك عبر البريد الإلكتروني أو بعرض إشعار في لوحة تحكمك قبل 14 يومًا على الأقل من سريان التغييرات. يُعدّ استمرار استخدامك لقائمة بعد ذلك التاريخ قبولاً للسياسة المحدّثة.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>إذا كانت لديك أسئلة أو مخاوف بشأن سياسة الخصوصية هذه أو ممارساتنا المتعلقة بالبيانات، يرجى التواصل معنا:</p>
<ul>
    <li><strong>البريد الإلكتروني:</strong> <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a></li>
    <li><strong>الشركة:</strong> مجموعة ليبيفاي</li>
    <li><strong>الموقع:</strong> برجا، لبنان</li>
</ul>

</div>

@endsection
