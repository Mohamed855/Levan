@extends('site.layouts.main')

@section('title', 'لوت LOT - السياسات')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/front-page-help-center.scss')

@endsection

@section('content')

<main class="main relative">
    <!-- Hero section -->
    <section class="site-page-hero">
        <div class="container">
            <div class="scroll-revealed mx-auto max-w-[780px] text-center">
                <span class="hero-eyebrow">
                    <i class="ti ti-shield-check"></i>
                    الخصوصية والشروط
                </span>
                <h1 class="site-page-hero-title">
                    سياسات استخدام نظام لوت
                </h1>

                <p class="site-page-hero-desc">
                    تهدف هذه السياسات إلى تنظيم العلاقة بين شركة إتمام لتقنية نظم المعلومات ممثلة في نظام
                    لوت وعملائها، وضمان حماية حقوق الطرفين، بما يتوافق مع الأنظمة المرعية في المملكة العربية السعودية.
                </p>
            </div>
        </div>
    </section>


    <!-- Contact section -->
    <section id="">
        <div class="container">
            <div class="row">

                <div class="col-12">
                    <div class="scroll-revealed  py-8 sm:py-12 px-6 sm:px-10 z-10 relative">
                        <div class="text-center max-w-[550px] mx-auto mb-">

                            <p class="font-medium text-sm">
                                الإصدار: 2026 – جميع الحقوق محفوظة لشركة إتمام لتقنية نظم المعلومات
                            </p>
                        </div>

                        <!-- المقدمة -->
                        <div class="mb-5 p-4  bg-opacity-10 rounded">
                            <h5 class="fw-bold mb-3"></h5>
                            <p class="mb-6 font-medium text-sm">
                                تهدف هذه السياسات إلى تنظيم العلاقة بين شركة إتمام لتقنية نظم المعلومات ممثلة في نظام
                                لوت وعملائها من
                                شركات مكاتب وأقسام التحصيل، وضمان حماية حقوق الطرفين، بما يتوافق مع القوانين والأنظمة
                                المرعية في المملكة
                                العربية السعودية، وخاصةً نظام حماية البيانات الشخصية ونظام مكافحة الجرائم المعلوماتية
                                وأنظمة حماية
                                المستهلك.
                            </p>
                        </div>

                        <!-- أولاً: سياسة الخصوصية وحماية البيانات -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                أولاً: سياسة الخصوصية وحماية البيانات
                            </h4>


                            <div class="font-medium text-sm">
                                <div class="font-medium text-sm">
                                    <h5 class="fw-semibold mb-3">1. البيانات التي يتم جمعها</h5>
                                    <p class="mb-2">يقوم لوت بجمع ومعالجة البيانات التالية فقط لأغراض تشغيل الخدمة:
                                    </p>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الاسم
                                            الكامل
                                            للمستخدم أو الممثل القانوني.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>رقم
                                            الجوال
                                            الموثّق.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>البريد
                                            الإلكتروني المخصص للتواصل.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>بيانات
                                            الدفع
                                            الصادرة عن مزود خدمة الدفع الإلكتروني المعتمد.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>المستندات
                                            الرسمية لتأكيد النشاط التجاري أو المهني، مثل: السجل التجاري، السجل الضريبي،
                                            التراخيص المهنية، وأي مستندات داعمة أخرى.</li>
                                    </ul>
                                </div>

                                <div class="font-medium text-sm">
                                    <h5 class="fw-semibold mb-3">2. الغرض من جمع البيانات</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>التحقق من
                                            هوية العميل.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>تقديم
                                            الخدمات
                                            محل التعاقد.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>التواصل
                                            بشأن
                                            المسائل الفنية أو المالية أو القانونية.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الامتثال
                                            للالتزامات القانونية والتنظيمية.</li>
                                    </ul>
                                </div>

                                <div class="font-medium text-sm">
                                    <h5 class="fw-semibold mb-3">3. تخزين ومعالجة البيانات</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يتم تخزين
                                            جميع البيانات على خوادم سحابية خارج المملكة العربية السعودية، مزودة بأنظمة
                                            أمان
                                            معتمدة عالميًا.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>تطبق
                                            بروتوكولات تشفير قوية على البيانات أثناء النقل (TLS/SSL) وأثناء التخزين
                                            (AES-256) أو ما يعادلها.</li>
                                    </ul>
                                </div>

                                <div class="font-medium text-sm">
                                    <h5 class="fw-semibold mb-3">4. مشاركة البيانات</h5>
                                    <p class="mb-2">لا يتم مشاركة البيانات مع أي طرف ثالث إلا في الحالات التالية:</p>
                                    <ol class="ps-4 mx-3">
                                        <li class="mb-2">بناءً على طلب رسمي صادر من جهة أمنية مختصة.</li>
                                        <li class="mb-2">الامتثال لأوامر قضائية أو إدارية ملزمة.</li>
                                    </ol>
                                </div>

                                <div class="font-medium text-sm">
                                    <h5 class="fw-semibold mb-3">5. مدة الاحتفاظ بالبيانات</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يتم
                                            الاحتفاظ
                                            ببيانات العميل لمدة (30) يومًا من تاريخ انتهاء التعاقد.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>بعد
                                            انقضاء
                                            هذه المدة، يتم حذف البيانات أو إخفاء هويتها نهائيًا بطريقة غير قابلة
                                            للاسترجاع.
                                        </li>
                                    </ul>
                                </div>

                                <div class="font-medium text-sm">
                                    <h5 class="fw-semibold mb-3">6. حقوق العميل</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يحق
                                            للعميل
                                            طلب نسخة من بياناته.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يحق
                                            للعميل
                                            طلب تصحيح بياناته أو حذفها وفقًا للقوانين النافذة.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- ثانيًا: سياسة الدعم الفني -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                ثانيًا: سياسة الدعم الفني
                            </h4>

                            <div class="mx-5">
                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">1. قنوات الدعم الفني</h5>
                                    <p class="mb-2">يوفر لوت الدعم الفني من خلال:</p>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>نظام
                                            التذاكر
                                            الإلكتروني.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>المحادثة
                                            الفورية (Chat).</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>البريد
                                            الإلكتروني الرسمي.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الاتصال
                                            الهاتفي عبر الرقم المخصص.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">2. أوقات الاستجابة</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الطلبات
                                            الفنية: استجابة أولية خلال ساعتين عمل، ومعالجة المشكلة وفق أولويتها الفنية.
                                        </li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الطلبات
                                            الطارئة: معالجة فورية أو في أقرب وقت عملي متاح.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- ثالثًا: سياسة التعامل مع الشكاوى -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                ثالثًا: سياسة التعامل مع الشكاوى
                            </h4>

                            <div class="mx-5">
                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">1. قنوات استقبال الشكاوى</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>نظام
                                            التذاكر
                                            الإلكتروني.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>المحادثة
                                            الفورية.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>البريد
                                            الإلكتروني.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الاتصال
                                            الهاتفي.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">2. زمن المعالجة</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>الرد
                                            المبدئي على الشكوى خلال أربع (4) ساعات عمل.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>إغلاق
                                            الشكوى خلال اثنتين وسبعين (72) ساعة عمل كحد أقصى.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">3. التصعيد الإداري</h5>
                                    <p class="text-dark lh-lg">
                                        في حال عدم معالجة الشكوى ضمن المدة المحددة، يتم تصعيدها تلقائيًا إلى الإدارة
                                        الإشرافية العليا، مع إشعار العميل بذلك كتابيًا.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- رابعًا: سياسة الحماية من الاحتيال -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                رابعًا: سياسة الحماية من الاحتيال
                            </h4>

                            <div class="mx-5">
                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">1. آليات المراقبة</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يعتمد
                                            لوت
                                            أنظمة تحليل آلي لرصد المعاملات والسلوكيات غير المعتادة أو التي تحمل مؤشرات
                                            خطر.
                                        </li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">2. إجراءات عند الاشتباه</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>إيقاف
                                            الحساب مؤقتًا لحين انتهاء التحقق.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>طلب
                                            مستندات
                                            إضافية لإثبات الهوية أو صحة المعاملات.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">3. الالتزام القانوني</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>في حال
                                            ثبوت
                                            أي نشاط احتيالي، يلتزم لوت بإبلاغ الجهات المختصة، مع تقديم جميع الأدلة
                                            الرقمية
                                            المتاحة.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- خامسًا: سياسة المعالجة الأمنية -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                خامسًا: سياسة المعالجة الأمنية
                            </h4>

                            <div class="mx-5">
                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">1. التدابير التقنية</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>تشفير
                                            البيانات أثناء النقل والتخزين.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>نسخ
                                            احتياطي
                                            دوري للبيانات لضمان استمرارية الخدمة.</li>
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>تحديثات
                                            أمنية منتظمة لسد الثغرات.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">2. إدارة الحوادث الأمنية</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>في حال
                                            وقوع
                                            خرق أمني، يتم إخطار العملاء المتأثرين خلال مدة لا تتجاوز 72 ساعة من اكتشاف
                                            الحادثة، مع توضيح نطاق الخرق والإجراءات التصحيحية.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- سادسًا: سياسة الضمان والاسترجاع -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                سادسًا: سياسة الضمان والاسترجاع
                            </h4>

                            <div class="mx-5">
                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">1. حالات الاسترجاع</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يحق
                                            للعميل
                                            طلب استرجاع المبلغ المدفوع إذا كان سبب الطلب وجود خلل في الخدمة، ولم يتم
                                            إصلاحه
                                            خلال مدة 72 ساعة من الإبلاغ.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">2. مدة تقديم الطلب</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يجب
                                            تقديم
                                            طلب الاسترجاع خلال سبعة (7) أيام من تاريخ الدفع.</li>
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h5 class="fw-semibold mb-3">3. قيمة الاسترجاع</h5>
                                    <ul class="list-unstyled ps-3">
                                        <li class="mb-2"><i class="ti ti-point-filled text-primary me-2"></i>يتم رد
                                            المبلغ كاملًا دون خصومات، مع اتباع نفس وسيلة الدفع المستخدمة في السداد.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- سابعًا: أحكام عامة -->
                        <div class="font-medium text-sm">
                            <h4 class="mb-2 block text-3xl font-semibold text-primary">
                                سابعًا: أحكام عامة
                            </h4>

                            <div class="mx-5">

                                <ul class="list-unstyled ps-3">
                                    <li class="mb-3"><i class="ti ti-point-filled text-primary me-2"></i>تحتكم هذه
                                        السياسات لأنظمة المملكة العربية السعودية، وأي نزاع ينشأ يتم حله أمام الجهات
                                        القضائية
                                        المختصة في مدينة الرياض.</li>
                                    <li class="mb-3"><i class="ti ti-point-filled text-primary me-2"></i>تحتفظ شركة
                                        لوت
                                        بحق تعديل هذه السياسات في أي وقت، على أن يتم إشعار العملاء بالتعديلات قبل
                                        سريانها.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>



            </div>
        </div>
    </section>


    {{--
    <!-- About section -->
    <section id="" class="section-area">
        <div class="container">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <div class="w-full">
                    <figure class="scroll-revealed max-w-[480px] mx-auto">
                        <img src="{{ asset('static/site/images/about-img.png') }}" alt="About image"
                            class="rounded-xl" />
                    </figure>
                </div>

                <div class="w-full m-auto">
                    <div class="scroll-revealed">
                        <h6 class="mb-2 block text-7xl font-semibold text-primary">
                            لوت LOT
                        </h6>
                        <p class="font-bold text-3xl mb-6">
                            المنصة ذكية لإدارة عمليات التحصيل
                        </p>
                    </div>

                    <div class="font-semibold mt-4">
                        <p>
                            منصة ذكية لإدارة عمليات التحصيل هدفها تمكين الجهات من ضبط العمليات و رفع أداء افرق التحصيل
                            عبر لوحات متابعة فورية و تقارير تحليلية و أرشفة للمستندات

                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}
</main>



@endsection
