@extends('portal.legal.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Privacy')
@section('headline_italic', 'Policy')
@section('headline_ar', 'سياسة الخصوصية')
@section('updated', 'Last updated: October 3, 2026')
@section('updated_ar', 'آخر تحديث: 3 أكتوبر 2026')

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

@section('legal_body')

@unless ($isAr)

<div class="legal-highlight">
    <p>This Privacy Policy explains how Qayema, operated by Lebify Group, collects, uses, and protects information about you when you use our service at qayema.com. By using Qayema, you agree to the practices described here.</p>
</div>

<h2 id="information">Information we collect</h2>

<h3>Account information</h3>
<p>Accounts are created by signing in with Google. We receive and store your name, your email address, your Google account identifier, the link to your Google profile picture, and the sign-in tokens Google issues, which we store encrypted. We never see or store your Google password. If you set a password to sign in with your email address, we store only a one-way hash of it.</p>

<h3>Restaurant & menu data</h3>
<p>To provide the service, we store the restaurant details you enter (such as its name, description, phone number, map location link, opening hours, time zone, currency and social links), your categories, dishes, prices, images, and design and QR code settings. This data belongs to you, and what you publish is shown to anyone who opens your menu. Images you upload are converted to the WebP format and stored on Cloudflare R2; the original file you uploaded is not kept.</p>

<h3>Contact messages & package requests</h3>
<p>When you use our contact form or ask for a package from your dashboard, we store your name, email address, your message and the IP address it was sent from (used to limit each address to three messages a day). For a package request we also store your account and the package you asked for. An email with the message is sent to our team.</p>

<h3>Guests on public menus</h3>
<p>We do not track how you use the dashboard. When a guest opens a public menu, we record one entry per menu view with: a session identifier (the browser session's ID or, when there is no session, a one-way hash of the IP address and browser; the IP address itself is not stored in these entries), the device type (mobile, tablet or desktop), the browser name, the operating system, the menu language, whether the visit came through the menu's QR code, and the time.</p>
<p>We also record some of the actions guests take on a menu, tied to the same session identifier: adding a dish to the cart, opening a category, search terms (in a normalised form) and searches that found nothing, taps on the WhatsApp, map, call and social links, and switching language. The restaurant's owner sees these records as statistics. Both kinds of records are deleted automatically after 6 months. Owners previewing their own menu are not recorded. These records hold no names, phone numbers or other contact details; the only guest contact details we keep are those a guest types into an order placed in the menu (see Orders).</p>

<h3>Orders</h3>
<p>On menus whose package includes ordering, the restaurant takes orders in one of two ways. Either way, when a guest places an order we store the dishes ordered (name, price and quantity), the total, the currency, an optional note written by the guest, and the time.</p>
<ul>
    <li><strong>On WhatsApp:</strong> we do not ask for the guest's name, phone number or address. The guest is sent to WhatsApp with the order written out; that message goes from the guest to the restaurant through WhatsApp (operated by Meta) and is not processed by us.</li>
    <li><strong>In the menu:</strong> the guest also gives their name, a phone number, whether they want delivery or pickup and, for a delivery, an address. They may also share their device's location by tapping "Use my current location"; the browser asks first, and the location is only sent if they agree. To fill in the street for them, our server asks OpenStreetMap's address service for the street at that location; it receives the location, not who the guest is. Only that restaurant's owner sees these details, in the dashboard, so they can call the guest and bring the order. Opening the location on a map sends it to Google Maps.</li>
</ul>
<p>After ordering in the menu, the guest gets a private link to follow the order (sent, accepted, on its way or ready). The page shows what they ordered, their address and the order's status, and only someone with that link can open it.</p>
<p>We delete a guest's name, phone number, address and location from an order 90 days after it was placed. The rest of the order stays as part of the restaurant's records.</p>

<h3>Technical data</h3>
<p>Like any website, our servers receive the IP address, browser details and time of each request, which we use for security, debugging, and keeping the service running. We keep a session record for each browser that visits, including guests on a menu, holding its IP address, browser user agent and last activity time; it is removed after a year without activity. An IP address that sends abusive traffic can be blocked automatically for a period, and we keep the blocked address, the reason and when the block ends.</p>

<h3>Payments</h3>
<p>Qayema does not take payments. Paid packages are arranged and paid for directly with Lebify Group, so no payment details are entered into or stored by Qayema.</p>

<h2 id="use">How we use your information</h2>

<ul>
    <li>To create and maintain your account and restaurant profile</li>
    <li>To deliver the Qayema dashboard and public menu features</li>
    <li>To show you your menu's visitor statistics and orders</li>
    <li>To handle package requests and contact messages, and to switch packages on and off</li>
    <li>To send service emails, such as password reset links</li>
    <li>To give you support: our administrators can view your account and restaurant and, when needed, sign in as you to help</li>
    <li>To detect and prevent fraud, abuse, and security incidents, for example with rate limits and by blocking abusive IP addresses</li>
    <li>To improve and develop our service</li>
    <li>To comply with legal obligations</li>
</ul>

<p>We do not sell your data. We do not use your data for advertising purposes.</p>

<h2 id="sharing">Sharing your information</h2>

<p>We share your information only in the following circumstances:</p>

<ul>
    <li><strong>The restaurant's owner:</strong> Visit statistics, guest actions and orders from a menu are shown to that restaurant's owner.</li>
    <li><strong>Service providers:</strong> The providers that run the service for us: hosting and database, Cloudflare R2 (image storage), an email delivery service, and Grafana Labs (Grafana Cloud, on servers in the EU), which receives technical records of how the service runs: request timings, errors and log messages, with an account's internal ID where one is signed in. These records are designed to leave out names, email addresses and menu content, although an error message can occasionally quote the data involved in the error. They are kept by Grafana for a limited period and used only to keep the service running well.</li>
    <li><strong>Google:</strong> Sign-in with Google; Google reCAPTCHA, which checks for automated abuse on the sign-in and contact pages where it is enabled; and Google Fonts, which serves the typefaces on our website, dashboard and menus, so Google receives your IP address and browser details when a page loads them.</li>
    <li><strong>OpenStreetMap:</strong> Menus that show the restaurant's location load a map from OpenStreetMap, which receives the guest's IP address and the page address.</li>
    <li><strong>jsDelivr:</strong> Some scripts on our website pages are loaded from the jsDelivr content delivery network, which receives your IP address.</li>
    <li><strong>WhatsApp:</strong> When a guest taps a WhatsApp link or sends an order on WhatsApp, the conversation happens in WhatsApp (operated by Meta) under its own terms and privacy policy.</li>
    <li><strong>Legal requirements:</strong> If required by law, court order, or to protect the rights and safety of Qayema or others.</li>
    <li><strong>Business transfers:</strong> In connection with a merger, acquisition, or sale of assets, with appropriate confidentiality obligations.</li>
</ul>

<p>We never sell, rent, or trade your personal data to third parties for their own marketing purposes.</p>

<h2 id="storage">Data storage & security</h2>

<p>Your data is stored on our servers and, for images, on Cloudflare R2. We use encryption in transit (HTTPS/TLS), store passwords only as one-way hashes, store Google sign-in tokens encrypted, and apply access controls, rate limits and automatic blocking of abusive traffic.</p>
<p>No method of transmission over the internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security. In the event of a data breach that affects your rights, we will notify you in accordance with applicable law.</p>
<p>We retain your data for as long as your account exists. Records of menu views and guest actions are deleted after 6 months. When you ask us to delete your account, your data is permanently removed from our systems within 30 days, except where retention is required by law.</p>

<h2 id="cookies">Cookies</h2>

<p>We use a small number of cookies and browser storage entries to operate the service, and no advertising or analytics cookies. For full details, please read our <a href="{{ \App\Support\PortalUrl::to('cookies') }}">Cookie Policy</a>.</p>

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

<p>To exercise any of these rights, contact us at <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a>. We will respond within 30 days.</p>

<h2 id="children">Children's privacy</h2>

<p>Qayema is intended for restaurant owners and operators and is not directed at children under the age of 16. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us and we will delete it promptly.</p>

<h2 id="changes">Changes to this policy</h2>

<p>We may update this Privacy Policy from time to time. When we make material changes, we will notify you by email or by displaying a notice in your dashboard at least 14 days before the changes take effect. Continued use of Qayema after that date constitutes acceptance of the updated policy.</p>

<h2 id="contact">Contact us</h2>

<p>If you have questions or concerns about this Privacy Policy or our data practices, please contact us:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a></li>
    <li><strong>Company:</strong> Lebify Group</li>
    <li><strong>Location:</strong> Barja, Lebanon</li>
</ul>

@endunless

@if ($isAr)

<div class="legal-highlight">
    <p>تشرح سياسة الخصوصية هذه كيف تجمع Qayema، التي تشغّلها مجموعة ليبيفاي، المعلومات عنك وتستخدمها وتحميها عند استخدامك لخدمتنا على qayema.com. باستخدامك لـQayema، فإنك توافق على الممارسات الموضحة هنا.</p>
</div>

<h2 id="information-ar">المعلومات التي نجمعها</h2>

<h3>معلومات الحساب</h3>
<p>تُنشأ الحسابات بتسجيل الدخول عبر Google. نتلقى ونحتفظ باسمك وعنوان بريدك الإلكتروني ومعرّف حسابك في Google ورابط صورة ملفك الشخصي في Google ورموز تسجيل الدخول التي تصدرها Google، ونخزّن هذه الرموز مشفّرة. لا نطّلع على كلمة مرور Google الخاصة بك ولا نحتفظ بها. إذا عيّنت كلمة مرور لتسجيل الدخول ببريدك الإلكتروني، فإننا نحتفظ فقط بتجزئة أحادية الاتجاه لها.</p>

<h3>بيانات المطعم والقائمة</h3>
<p>لتقديم الخدمة، نحتفظ بتفاصيل المطعم التي تدخلها (مثل اسمه ووصفه ورقم هاتفه ورابط موقعه على الخريطة وساعات العمل والمنطقة الزمنية والعملة والروابط الاجتماعية)، وبالفئات والأطباق والأسعار والصور وإعدادات التصميم ورمز QR. هذه البيانات ملك لك، وما تنشره يظهر لكل من يفتح قائمتك. تُحوَّل الصور التي ترفعها إلى صيغة WebP وتُخزَّن على Cloudflare R2، ولا يُحتفظ بالملف الأصلي الذي رفعته.</p>

<h3>رسائل التواصل وطلبات الباقات</h3>
<p>عند استخدامك نموذج التواصل أو طلبك باقة من لوحة تحكمك، نحتفظ باسمك وعنوان بريدك الإلكتروني ورسالتك وعنوان IP الذي أُرسلت منه (ويُستخدم لحصر كل عنوان في ثلاث رسائل يوميًا). وفي طلب الباقة نحتفظ أيضًا بحسابك والباقة التي طلبتها. تُرسل رسالة بريد إلكتروني تتضمن الرسالة إلى فريقنا.</p>

<h3>ضيوف القوائم العامة</h3>
<p>لا نتتبّع كيفية استخدامك للوحة التحكم. عندما يفتح ضيف قائمة عامة، نسجّل إدخالًا واحدًا لكل مشاهدة للقائمة يتضمن: معرّف جلسة (معرّف جلسة المتصفح، أو عند عدم وجود جلسة، تجزئة أحادية الاتجاه لعنوان IP والمتصفح؛ ولا يُخزَّن عنوان IP نفسه في هذه الإدخالات)، ونوع الجهاز (هاتف أو جهاز لوحي أو حاسوب)، واسم المتصفح، ونظام التشغيل، ولغة القائمة، وما إذا جاءت الزيارة عبر رمز QR الخاص بالقائمة، ووقت الزيارة.</p>
<p>ونسجّل أيضًا بعض ما يفعله الضيوف في القائمة، مرتبطًا بمعرّف الجلسة نفسه: إضافة طبق إلى السلة، وفتح فئة، وكلمات البحث (بصيغة موحّدة) وعمليات البحث التي لم تجد نتيجة، والنقر على روابط WhatsApp والخريطة والاتصال ومواقع التواصل، وتبديل اللغة. يرى صاحب المطعم هذه السجلات على شكل إحصاءات. ويُحذف النوعان تلقائيًا بعد 6 أشهر. لا تُسجَّل معاينة أصحاب المطاعم لقوائمهم. ولا تتضمن هذه السجلات أسماء أو أرقام هواتف أو أي بيانات تواصل؛ فبيانات التواصل الوحيدة التي نحتفظ بها هي ما يكتبه الضيف في طلب يرسله من داخل القائمة (انظر الطلبات).</p>

<h3>الطلبات</h3>
<p>في القوائم التي تتضمن باقتها استقبال الطلبات، يستقبل المطعم الطلبات بإحدى طريقتين. وفي الحالتين، عندما يرسل ضيف طلبًا نحتفظ بالأطباق المطلوبة (الاسم والسعر والكمية) والمجموع والعملة وملاحظة اختيارية يكتبها الضيف ووقت الطلب.</p>
<ul>
    <li><strong>عبر WhatsApp:</strong> لا نطلب اسم الضيف أو رقم هاتفه أو عنوانه. يُحوَّل الضيف إلى WhatsApp مع نص الطلب؛ وتنتقل تلك الرسالة من الضيف إلى المطعم عبر WhatsApp (الذي تشغّله Meta) ولا نعالجها نحن.</li>
    <li><strong>من داخل القائمة:</strong> يضيف الضيف أيضًا اسمه ورقم هاتف، ويختار التوصيل أو الاستلام، ويكتب عنوانًا في حال التوصيل. ويمكنه أيضًا مشاركة موقع جهازه بالضغط على "استخدم موقعي الحالي"؛ يطلب المتصفح الإذن أولًا، ولا يُرسَل الموقع إلا بموافقته. ولملء اسم الشارع عنه، يسأل خادمنا خدمة العناوين في OpenStreetMap عن الشارع في ذلك الموقع؛ فتتلقى الموقع دون معرفة هوية الضيف. لا يرى هذه البيانات إلا صاحب ذلك المطعم، في لوحة التحكم، ليتصل بالضيف ويوصل الطلب. وفتح الموقع على الخريطة يرسله إلى خرائط Google.</li>
</ul>
<p>بعد الطلب من داخل القائمة، يحصل الضيف على رابط خاص لمتابعة طلبه (أُرسل، قُبل، في الطريق أو جاهز). تعرض الصفحة ما طلبه وعنوانه وحالة الطلب، ولا يفتحها إلا من لديه هذا الرابط.</p>
<p>نحذف اسم الضيف ورقم هاتفه وعنوانه وموقعه من الطلب بعد 90 يومًا من إرساله. ويبقى باقي الطلب ضمن سجلات المطعم.</p>

<h3>البيانات التقنية</h3>
<p>كما في أي موقع إلكتروني، تتلقى خوادمنا عنوان IP وتفاصيل المتصفح ووقت كل طلب، ونستخدمها لأغراض الأمان والتصحيح والحفاظ على عمل الخدمة. ونحتفظ بسجل جلسة لكل متصفح يزورنا، بما في ذلك ضيوف القوائم، يتضمن عنوان IP ووكيل المستخدم للمتصفح ووقت آخر نشاط، ويُحذف بعد سنة دون نشاط. وقد يُحظر تلقائيًا لفترة محددة عنوان IP يرسل حركة مسيئة، ونحتفظ بالعنوان المحظور وسبب الحظر وموعد انتهائه.</p>

<h3>المدفوعات</h3>
<p>لا تتقاضى Qayema أي مدفوعات. يُتفق على الباقات المدفوعة وتُدفع مباشرةً مع مجموعة ليبيفاي، لذلك لا تُدخل أي بيانات دفع في Qayema ولا تحتفظ بها.</p>

<h2 id="use-ar">كيف نستخدم معلوماتك</h2>

<ul>
    <li>لإنشاء حسابك وملف مطعمك والحفاظ عليهما</li>
    <li>لتقديم لوحة تحكم Qayema وميزات القائمة العامة</li>
    <li>لعرض إحصاءات زوار قائمتك وطلباتها عليك</li>
    <li>لمعالجة طلبات الباقات ورسائل التواصل، ولتفعيل الباقات وإيقافها</li>
    <li>لإرسال رسائل البريد الإلكتروني الخاصة بالخدمة، مثل روابط إعادة تعيين كلمة المرور</li>
    <li>لتقديم الدعم لك: يمكن لمسؤولينا الاطلاع على حسابك ومطعمك، وتسجيل الدخول بحسابك عند الحاجة لمساعدتك</li>
    <li>للكشف عن الاحتيال والإساءة والحوادث الأمنية ومنعها، مثل تحديد عدد الطلبات وحظر عناوين IP المسيئة</li>
    <li>لتحسين خدمتنا وتطويرها</li>
    <li>للامتثال للالتزامات القانونية</li>
</ul>

<p>نحن لا نبيع بياناتك. نحن لا نستخدم بياناتك لأغراض إعلانية.</p>

<h2 id="sharing-ar">مشاركة معلوماتك</h2>

<p>نشارك معلوماتك فقط في الحالات التالية:</p>

<ul>
    <li><strong>صاحب المطعم:</strong> تُعرض إحصاءات الزيارات وما يفعله الضيوف والطلبات الخاصة بقائمة ما على صاحب ذلك المطعم.</li>
    <li><strong>مزودو الخدمة:</strong> الجهات التي تشغّل الخدمة لصالحنا: الاستضافة وقاعدة البيانات، وCloudflare R2 (تخزين الصور)، وخدمة لتسليم البريد الإلكتروني، وGrafana Labs (خدمة Grafana Cloud على خوادم في الاتحاد الأوروبي) التي تتلقى سجلات تقنية عن عمل الخدمة: أوقات الطلبات والأخطاء ورسائل السجل، مع المعرّف الداخلي للحساب حين يكون المستخدم مسجّلاً دخوله. صُمّمت هذه السجلات لتخلو من الأسماء وعناوين البريد الإلكتروني ومحتوى القوائم، وإن كانت رسالة خطأ قد تتضمن أحياناً البيانات المتعلقة بذلك الخطأ. تحتفظ بها Grafana لفترة محدودة، ولا تُستخدم إلا لضمان حسن عمل الخدمة.</li>
    <li><strong>Google:</strong> تسجيل الدخول عبر Google؛ وGoogle reCAPTCHA الذي يتحقق من الإساءة الآلية في صفحتي تسجيل الدخول والتواصل حين يكون مفعّلًا؛ وخطوط Google التي تقدّم الخطوط المستخدمة في موقعنا ولوحة التحكم والقوائم، فتتلقى Google عنوان IP وتفاصيل متصفحك عند تحميل الصفحة لها.</li>
    <li><strong>OpenStreetMap:</strong> القوائم التي تعرض موقع المطعم تحمّل خريطة من OpenStreetMap، التي تتلقى عنوان IP الخاص بالضيف وعنوان الصفحة.</li>
    <li><strong>jsDelivr:</strong> تُحمَّل بعض البرامج النصية في صفحات موقعنا من شبكة توصيل المحتوى jsDelivr، التي تتلقى عنوان IP الخاص بك.</li>
    <li><strong>WhatsApp:</strong> عندما ينقر ضيف على رابط WhatsApp أو يرسل طلبًا عبره، تجري المحادثة داخل WhatsApp (الذي تشغّله Meta) وفق شروطه وسياسة خصوصيته.</li>
    <li><strong>المتطلبات القانونية:</strong> إذا طُلب ذلك بموجب القانون أو أمر قضائي أو لحماية حقوق وسلامة Qayema أو الآخرين.</li>
    <li><strong>التحويلات التجارية:</strong> في سياق الاندماج أو الاستحواذ أو بيع الأصول، مع التزامات سرية مناسبة.</li>
</ul>

<p>نحن لا نبيع بياناتك الشخصية أو نؤجرها أو نتاجر بها مع أطراف ثالثة لأغراضهم التسويقية الخاصة.</p>

<h2 id="storage-ar">تخزين البيانات والأمان</h2>

<p>تُخزَّن بياناتك على خوادمنا، وتُخزَّن الصور على Cloudflare R2. نستخدم التشفير أثناء النقل (HTTPS/TLS)، ونحتفظ بكلمات المرور على شكل تجزئة أحادية الاتجاه فقط، ونخزّن رموز تسجيل الدخول عبر Google مشفّرة، ونطبّق ضوابط الوصول وتحديد عدد الطلبات والحظر التلقائي للحركة المسيئة.</p>
<p>لا توجد طريقة نقل عبر الإنترنت آمنة بنسبة 100%. بينما نسعى جاهدين لحماية بياناتك، لا يمكننا ضمان الأمان المطلق. في حال حدوث اختراق للبيانات يؤثر على حقوقك، سنُخطرك وفقًا للقانون المعمول به.</p>
<p>نحتفظ ببياناتك طالما حسابك موجود. تُحذف سجلات مشاهدات القوائم وما يفعله الضيوف بعد 6 أشهر. عندما تطلب منا حذف حسابك، تُزال بياناتك نهائيًا من أنظمتنا خلال 30 يومًا، إلا إذا كان الاحتفاظ بها مطلوبًا قانونًا.</p>

<h2 id="cookies-ar">ملفات تعريف الارتباط</h2>

<p>نستخدم عددًا قليلًا من ملفات تعريف الارتباط وإدخالات التخزين في المتصفح لتشغيل الخدمة، ولا نستخدم أي ملفات ارتباط إعلانية أو تحليلية. لمزيد من التفاصيل، يرجى قراءة <a href="{{ \App\Support\PortalUrl::to('cookies') }}">سياسة ملفات تعريف الارتباط</a>.</p>

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

<p>لممارسة أي من هذه الحقوق، تواصل معنا على <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a>. سنردّ خلال 30 يومًا.</p>

<h2 id="children-ar">خصوصية الأطفال</h2>

<p>Qayema موجّهة لأصحاب المطاعم والمشغّلين وليست موجّهة للأطفال دون سن 16 عامًا. نحن لا نجمع عن قصد معلومات شخصية من الأطفال. إذا كنت تعتقد أن طفلاً قد زوّدنا بمعلومات شخصية، فيرجى التواصل معنا وسنحذفها فورًا.</p>

<h2 id="changes-ar">التغييرات على هذه السياسة</h2>

<p>قد نحدّث سياسة الخصوصية هذه من وقت لآخر. عند إجراء تغييرات جوهرية، سنُخطرك عبر البريد الإلكتروني أو بعرض إشعار في لوحة تحكمك قبل 14 يومًا على الأقل من سريان التغييرات. يُعدّ استمرار استخدامك لـQayema بعد ذلك التاريخ قبولاً للسياسة المحدّثة.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>إذا كانت لديك أسئلة أو مخاوف بشأن سياسة الخصوصية هذه أو ممارساتنا المتعلقة بالبيانات، يرجى التواصل معنا:</p>
<ul>
    <li><strong>البريد الإلكتروني:</strong> <a href="mailto:{{ config('seo.organization.contact.email') }}">{{ config('seo.organization.contact.email') }}</a></li>
    <li><strong>الشركة:</strong> مجموعة ليبيفاي</li>
    <li><strong>الموقع:</strong> برجا، لبنان</li>
</ul>

@endif

@endsection
