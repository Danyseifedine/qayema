@extends('legal.layout')

@section('title', 'Terms of Service')
@section('eyebrow', 'Legal')
@section('eyebrow_ar', 'قانوني')
@section('headline_plain', 'Terms of')
@section('headline_italic', 'Service')
@section('headline_ar', 'شروط الخدمة')
@section('updated', 'Last updated: May 9, 2025')
@section('updated_ar', 'آخر تحديث: 9 مايو 2025')

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
    <li><a href="#acceptance-ar">قبول الشروط</a></li>
    <li><a href="#service-ar">الخدمة</a></li>
    <li><a href="#account-ar">حسابك</a></li>
    <li><a href="#acceptable-ar">الاستخدام المقبول</a></li>
    <li><a href="#content-ar">محتواك</a></li>
    <li><a href="#ai-ar">ماسح القوائم بالذكاء الاصطناعي</a></li>
    <li><a href="#payment-ar">الباقات والدفع</a></li>
    <li><a href="#ip-ar">الملكية الفكرية</a></li>
    <li><a href="#termination-ar">إنهاء الخدمة</a></li>
    <li><a href="#disclaimer-ar">إخلاء المسؤولية</a></li>
    <li><a href="#liability-ar">تحديد المسؤولية</a></li>
    <li><a href="#governing-ar">القانون الحاكم</a></li>
    <li><a href="#contact-ar">تواصل معنا</a></li>
@endsection

@section('content')

<div x-show="!isAr">

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

</div>

<div x-show="isAr">

<div class="legal-highlight">
    <p>تحكم شروط الخدمة هذه ("الشروط") استخدامك لقائمة، التي تشغّلها مجموعة ليبيفاي ("نحن" أو "لنا"). بإنشائك حسابًا أو استخدامك لقائمة، فإنك توافق على هذه الشروط. إذا لم توافق، فلا تستخدم الخدمة.</p>
</div>

<h2 id="acceptance-ar">قبول الشروط</h2>

<p>بالوصول إلى قائمة أو استخدامها، فإنك تقرّ بأنك بلغت من العمر 18 عامًا على الأقل ولديك الصلاحية القانونية للموافقة على هذه الشروط بالنيابة عن نفسك أو مؤسستك. يُعدّ استخدام الخدمة قبولاً لهذه الشروط و<a href="{{ route('privacy') }}">سياسة الخصوصية</a>.</p>

<h2 id="service-ar">الخدمة</h2>

<p>قائمة هي منصة قوائم رقمية لأصحاب المطاعم ومشغّليها. تشمل الخدمة:</p>
<ul>
    <li>لوحة تحكم إلكترونية لإنشاء القوائم الرقمية وإدارتها</li>
    <li>صفحة قائمة عامة يمكن لضيوفك الوصول إليها عبر رمز QR أو رابط مباشر</li>
    <li>إحصاءات وتحليلات الزوار لقائمتك</li>
    <li>ماسح قوائم بالذكاء الاصطناعي لاستيراد القوائم الورقية</li>
    <li>أدوات إنشاء رمز QR وتخصيصه</li>
    <li>إدارة الروابط الاجتماعية وتكامل طلبات WhatsApp</li>
</ul>

<p>نحتفظ بالحق في تعديل أي جزء من الخدمة أو تعليقه أو إيقافه في أي وقت مع إشعار معقول. لن نكون مسؤولين تجاهك أو تجاه أي طرف ثالث عن أي تعديل أو تعليق أو إيقاف للخدمة.</p>

<h2 id="account-ar">حسابك</h2>

<p>أنت مسؤول عن الحفاظ على أمان بيانات اعتماد حسابك. يجب ألا تشارك كلمة مرورك أو تسمح للآخرين بالوصول إلى حسابك. أنت مسؤول عن جميع الأنشطة التي تجري تحت حسابك.</p>
<p>يجب تقديم معلومات دقيقة وكاملة عند التسجيل. إذا تغيّرت معلوماتك، يجب تحديثها فورًا. قد نعلّق الحسابات التي تحتوي على معلومات غير دقيقة أو التي نعتقد بشكل معقول أنها تُستخدم بطريقة احتيالية أو نوقفها.</p>
<p>يُسمح لك بإنشاء حساب واحد فقط لكل عنوان بريد إلكتروني ما لم نأذن صراحةً بغير ذلك.</p>

<h2 id="acceptable-ar">الاستخدام المقبول</h2>

<p>توافق على عدم استخدام قائمة من أجل:</p>
<ul>
    <li>انتهاك أي قانون أو لائحة معمول بها</li>
    <li>رفع أو نقل أو عرض محتوى غير قانوني أو ضار أو تشهيري أو فاحش أو احتيالي</li>
    <li>انتحال شخصية أي فرد أو كيان</li>
    <li>التدخل في الخدمة أو بنيتها التحتية أو تعطيلها</li>
    <li>محاولة الوصول غير المصرّح به إلى أي جزء من الخدمة</li>
    <li>استخدام أدوات آلية لاستخراج البيانات أو الزحف عليها أو حصادها من الخدمة</li>
    <li>إعادة بيع الخدمة أو ترخيصها من الباطن أو تسييلها بأي شكل آخر دون موافقتنا الكتابية</li>
    <li>استخدام الخدمة لأي غرض آخر غير تشغيل قائمة مطعمك الرقمية</li>
</ul>

<p>قد نحقّق في الانتهاكات المشتبه بها ونتخذ الإجراء المناسب، بما في ذلك تعليق الحساب أو إنهاؤه.</p>

<h2 id="content-ar">محتواك</h2>

<p>تحتفظ بملكية جميع المحتوى الذي ترفعه إلى قائمة، بما في ذلك أسماء الأطباق والأوصاف والأسعار والصور ومعلومات المطعم ("محتواك"). برفع المحتوى، تمنحنا ترخيصًا غير حصري وعالمي وبدون مقابل لتخزين محتواك وعرضه وتسليمه فقط لأغراض تشغيل الخدمة.</p>
<p>أنت وحدك المسؤول عن محتواك. تُقرّ بأنك تمتلك جميع الحقوق اللازمة لرفع محتواك واستخدامه على قائمة. نحن لا نؤيد محتواك ولا نتحمل أي مسؤولية عنه.</p>
<p>قد نزيل أي محتوى ينتهك هذه الشروط أو القانون المعمول به، دون إشعار.</p>

<h2 id="ai-ar">ماسح القوائم بالذكاء الاصطناعي</h2>

<p>يستخدم ماسح القوائم بالذكاء الاصطناعي واجهة برمجة تطبيقات Gemini من Google لمعالجة صور القوائم الورقية. باستخدام هذه الميزة، فإنك تُقرّ بأن:</p>
<ul>
    <li>الصور التي ترفعها تُرسل إلى خوادم Google للمعالجة</li>
    <li>لديك الحق في رفع المحتوى الموجود في الصور ومعالجته</li>
    <li>النتائج التي يولّدها الذكاء الاصطناعي قد تحتوي على أخطاء ويجب مراجعتها قبل الاستيراد</li>
    <li>الميزة محدودة بـ 3 عمليات مسح يوميًا لكل حساب</li>
    <li>لا نضمن دقة البيانات المستخرجة</li>
</ul>

<h2 id="payment-ar">الباقات والدفع</h2>

<p>تقدّم قائمة باقة مجانية للبدء. تتوفر الباقات المدفوعة عبر صفحة الأسعار. باشتراكك في باقة مدفوعة:</p>
<ul>
    <li>تأذن لنا بخصم المبلغ من طريقة الدفع الخاصة بك على أساس متكرر</li>
    <li>الرسوم غير قابلة للاسترداد إلا ما يقتضيه القانون المعمول به</li>
    <li>في حال فشل الدفع، قد نخفّض مستوى حسابك أو نعلّقه</li>
    <li>قد نغيّر الأسعار مع إشعار مسبق مدته 30 يومًا</li>
</ul>

<h2 id="ip-ar">الملكية الفكرية</h2>

<p>قائمة وجميع مكوّناتها، بما في ذلك البرنامج والتصميم والعلامات التجارية والمحتوى الذي أنشأناه، مملوكة لمجموعة ليبيفاي ومحمية بموجب قوانين الملكية الفكرية. لا تمنحك هذه الشروط أي حق في استخدام علاماتنا التجارية أو أسمائنا التجارية أو هويتنا البصرية.</p>
<p>لا يجوز لك نسخ أي جزء من خدمتنا أو برنامجها أو تعديله أو توزيعه أو بيعه أو تأجيره، ولا يجوز لك عكس هندسته أو محاولة استخراج الكود المصدري، إلا إذا أجاز لك ذلك القانون المعمول به أو حصلت على إذن كتابي منا.</p>

<h2 id="termination-ar">إنهاء الخدمة</h2>

<p>يمكنك حذف حسابك في أي وقت من إعدادات ملفك الشخصي. عند الحذف، ستُزال بياناتك نهائيًا خلال 30 يومًا.</p>
<p>قد نعلّق حسابك أو نوقفه فورًا إذا انتهكت هذه الشروط أو مارست نشاطًا احتياليًا أو إذا طُلب منا ذلك قانونًا. سنبذل جهودًا معقولة لإخطارك، إلا في الحالات التي تستوجب اتخاذ إجراء فوري لأسباب أمنية أو قانونية.</p>
<p>عند الإنهاء، يتوقف حقك في استخدام الخدمة فورًا. ستستمر في السريان الأقسام التي بطبيعتها يجب أن تبقى سارية بعد الإنهاء.</p>

<h2 id="disclaimer-ar">إخلاء المسؤولية</h2>

<p>تُقدَّم الخدمة "كما هي" و"حسب التوفر" دون ضمانات من أي نوع، سواء صريحة أو ضمنية، بما في ذلك على سبيل المثال لا الحصر ضمانات القابلية للتسويق أو الملاءمة لغرض معين أو عدم الانتهاك.</p>
<p>لا نضمن أن الخدمة ستكون غير منقطعة أو خالية من الأخطاء أو آمنة. لسنا مسؤولين عن دقة المحتوى الذي يولّده الذكاء الاصطناعي أو إحصاءات الزوار أو أي تكاملات مع أطراف ثالثة.</p>

<h2 id="liability-ar">تحديد المسؤولية</h2>

<p>إلى أقصى حد يسمح به القانون المعمول به، لن تكون مجموعة ليبيفاي مسؤولة عن أي أضرار غير مباشرة أو عرضية أو خاصة أو تبعية أو عقابية، بما في ذلك خسارة الأرباح أو البيانات أو الأعمال، الناجمة عن استخدامك لقائمة أو المرتبطة به.</p>
<p>تقتصر مسؤوليتنا الإجمالية عن أي مطالبة ناشئة عن هذه الشروط أو استخدامك للخدمة على المبلغ الذي دفعته لنا في الـ 12 شهرًا السابقة للمطالبة، أو 50 دولارًا أمريكيًا، أيهما أكبر.</p>

<h2 id="governing-ar">القانون الحاكم</h2>

<p>تخضع هذه الشروط لقوانين لبنان. يتم حل أي نزاعات في المحاكم اللبنانية، ما لم تقتضِ قوانين حماية المستهلك الإلزامية في ولايتك القضائية خلاف ذلك.</p>

<h2 id="contact-ar">تواصل معنا</h2>

<p>للاستفسار عن هذه الشروط، تواصل معنا على <a href="mailto:dany.a.seifeddine@gmail.com">dany.a.seifeddine@gmail.com</a>.</p>

</div>

@endsection
