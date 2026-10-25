@extends('tenant.layouts.layoutMaster')

@section('title', 'شرح ربط الدومين')

@section('page-style')
    @vite(['resources/assets/scss/tenant/pages/domain-explain.scss'])
@endsection

@section('content')
    <!-- Hero Section -->
    <div class="guide-hero">
        <div class="container">
            <i class="ti ti-plug-connected animate__animated animate__bounceIn"></i>
            <h1 class="animate__animated animate__fadeInDown" style="color:white">دليل ربط ونقل الدومين</h1>
            <p class="animate__animated animate__fadeInUp">اتبع هذه الخطوات البسيطة لربط دومينك بالسيرفر أو نقله من مزود آخر
            </p>
        </div>
    </div>

    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Quick Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="info-box">
                    <i class="ti ti-info-circle"></i>
                    <strong>ما هو ربط الدومين؟</strong>
                    <p class="mb-0 mt-2">ربط الدومين هو عملية توجيه اسم النطاق الذي اشتريته إلى السيرفر الذي يستضيف موقعك،
                        مما يسمح للزوار بالوصول إلى موقعك عن طريق كتابة اسم الدومين بدلاً من عنوان IP.</p>
                </div>
            </div>
        </div>

        <!-- General Info Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="ti ti-file-text"></i>
                        </div>
                        <h4 class="info-card-title">شروط ربط الدومين</h4>
                    </div>
                    <div class="info-card-content">
                        <ul>
                            <li>أن يكون الدومين مسجلاً باسمك</li>
                            <li>الوصول إلى لوحة تحكم الدومين</li>
                            <li>معرفة Name Servers الخاصة بالسيرفر</li>
                            <li>الدومين غير محظور أو منتهي</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="ti ti-clock"></i>
                        </div>
                        <h4 class="info-card-title">مدة التفعيل</h4>
                    </div>
                    <div class="info-card-content">
                        <span class="badge-custom badge-success" style="color:black">سريع: 2-8 ساعات</span>
                        <span class="badge-custom badge-warning" style="color:black">عادي: 24 ساعة</span>
                        <span class="badge-custom badge-info" style="color:black">أقصى: 48 ساعة</span>
                        <p class="mt-3 mb-0" style="color:black">تعتمد المدة على مزود الدومين والسيرفرات العالمية</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="ti ti-alert-triangle"></i>
                        </div>
                        <h4 class="info-card-title">ملاحظات مهمة</h4>
                    </div>
                    <div class="info-card-content">
                        <ul>
                            <li>احتفظ بنسخة احتياطية من موقعك</li>
                            <li>لا تحذف الاستضافة القديمة قبل اكتمال النقل</li>
                            <li>تحقق من تاريخ انتهاء الدومين</li>
                            <li>تأكد من صحة بيانات التواصل</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: ربط الدومين -->
        <h3 class="mb-4" style="color: var(--primary-color); font-weight: 700; font-size: 1.4rem;">
            <i class="ti ti-link me-2"></i>خطوات ربط الدومين
        </h3>

        <!-- Step 1 -->
        <div class="step-card animate__animated animate__fadeInRight">
            <div class="step-number">1</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-login"></i>
                </div>
                <h3 class="step-title">تسجيل الدخول</h3>
            </div>
            <div class="step-content">
                <p>سجل دخولك إلى حساب الشركة التي اشتريت منها الدومين (GoDaddy, Namecheap, إلخ).</p>

                <div class="timeline" style="height:220px">
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <strong style="margin-right: 19px;">افتح موقع الشركة</strong>
                        <p class="text-muted">انتقل إلى موقع مزود الدومين</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <strong style="margin-right: 19px;">اضغط تسجيل الدخول</strong>
                        <p class="text-muted">عادة في أعلى الصفحة</p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <strong style="margin-right: 19px;">أدخل البيانات</strong>
                        <p class="text-muted">البريد وكلمة المرور</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="step-card animate__animated animate__fadeInLeft">
            <div class="step-number">2</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-world"></i>
                </div>
                <h3 class="step-title">إعدادات الدومين</h3>
            </div>
            <div class="step-content">
                <p>من لوحة التحكم، اختر قسم "النطاقات" أو "Domains"، ثم اختر الدومين واضغط "إدارة" أو "Manage".</p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="step-card animate__animated animate__fadeInRight">
            <div class="step-number">3</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-server"></i>
                </div>
                <h3 class="step-title">تعديل Name Servers</h3>
            </div>
            <div class="step-content">
                <p>ابحث عن "Nameservers" أو "DNS Management" واختر "Custom DNS"، ثم أدخل Name Servers التالية:</p>

                <div class="info-box success">
                    <i class="ti ti-check-circle"></i>
                    <strong>Name Servers الخاصة بنا:</strong>
                </div>

                <table class="ns-table">
                    <thead>
                        <tr>
                            <th>الوصف</th>
                            <th>القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Name Server 1</strong></td>
                            <td>
                                <code>ns1.levan.com</code>
                                <button type="button" class="btn btn-sm btn-outline-primary me-2"
                                    onclick="copyToClipboard('ns1.levan.com')">
                                    <i class="ti ti-copy"></i> نسخ
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Name Server 2</strong></td>
                            <td>
                                <code>ns2.levan.com</code>
                                <button type="button" class="btn btn-sm btn-outline-primary me-2"
                                    onclick="copyToClipboard('ns2.levan.com')">
                                    <i class="ti ti-copy"></i> نسخ
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- <div class="code-box">
                <button type="button" class="copy-btn" onclick="copyToClipboard('ns1.levan.com\nns2.levan.com')">
                    <i class="ti ti-copy"></i> نسخ الكل
                </button>
                <div style="margin-top: 25px;">
                    <code>Nameserver 1: ns1.levan.com</code><br>
                    <code>Nameserver 2: ns2.levan.com</code>
                </div>
            </div> --}}
            </div>
        </div>

        <!-- Step 4 -->
        <div class="step-card animate__animated animate__fadeInLeft">
            <div class="step-number">4</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-device-floppy"></i>
                </div>
                <h3 class="step-title">حفظ التغييرات</h3>
            </div>
            <div class="step-content">
                <p>اضغط "حفظ" أو "Save" أو "Update Nameservers" وانتظر رسالة التأكيد.</p>
                <div class="info-box success">
                    <i class="ti ti-check"></i>
                    <strong>تم!</strong> إذا ظهرت رسالة نجاح، فقد أتممت العملية بنجاح.
                </div>
            </div>
        </div>

        <!-- Section: نقل الدومين -->
        <h3 class="mb-4 mt-5" style="color: var(--primary-color); font-weight: 700; font-size: 1.4rem;">
            <i class="ti ti-transfer me-2"></i>خطوات نقل الدومين
        </h3>

        <div class="row mb-4">
            <div class="col-12">
                <div class="info-box info">
                    <i class="ti ti-info-circle"></i>
                    <strong>ما هو نقل الدومين؟</strong>
                    <p class="mb-0 mt-2">نقل الدومين (Domain Transfer) هو عملية نقل ملكية وإدارة الدومين من شركة تسجيل إلى
                        أخرى مع الاحتفاظ بنفس اسم الدومين.[web:34][web:35]</p>
                </div>
            </div>
        </div>

        <!-- Transfer Step 1 -->
        <div class="step-card animate__animated animate__fadeInRight">
            <div class="step-number">1</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-lock-open"></i>
                </div>
                <h3 class="step-title">فك قفل الدومين</h3>
            </div>
            <div class="step-content">
                <p>الدومينات مقفلة افتراضياً لحمايتها. لنقل الدومين يجب فك القفل أولاً.[web:36][web:39]</p>

                <h5>خطوات فك القفل:</h5>
                <ol>
                    <li>سجل دخول إلى حساب الدومين الحالي</li>
                    <li>اذهب إلى إعدادات الدومين</li>
                    <li>ابحث عن "Registrar Lock" أو "Domain Lock"</li>
                    <li>اضغط "Disable" أو "Unlock Domain"</li>
                </ol>

                <div class="info-box warning">
                    <i class="ti ti-alert-triangle"></i>
                    <strong>مهم:</strong> الدومين يجب أن يكون مسجلاً منذ 60 يوماً على الأقل قبل نقله.[web:34]
                </div>
            </div>
        </div>

        <!-- Transfer Step 2 -->
        <div class="step-card animate__animated animate__fadeInLeft">
            <div class="step-number">2</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-key"></i>
                </div>
                <h3 class="step-title">الحصول على كود التحويل (EPP/Auth Code)</h3>
            </div>
            <div class="step-content">
                <p>كود التحويل (Authorization Code أو EPP Code) هو رمز أمان مكون من أحرف وأرقام مطلوب لإتمام عملية
                    النقل.[web:35][web:38]</p>

                <h5>كيفية الحصول على الكود:</h5>
                <ol>
                    <li>من لوحة تحكم الدومين الحالية</li>
                    <li>اختر الدومين المراد نقله</li>
                    <li>ابحث عن "Transfer Out" أو "Get Auth Code"</li>
                    <li>اضغط على الزر واحتفظ بالكود</li>
                </ol>

                <div class="info-box info">
                    <i class="ti ti-bulb"></i>
                    <strong>ملاحظة:</strong> قد يتم إرسال الكود إلى بريدك الإلكتروني المسجل.[web:38]
                </div>
            </div>
        </div>

        <!-- Transfer Step 3 -->
        <div class="step-card animate__animated animate__fadeInRight">
            <div class="step-number">3</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-send"></i>
                </div>
                <h3 class="step-title">طلب النقل</h3>
            </div>
            <div class="step-content">
                <p>تواصل معنا وقدم المعلومات التالية لبدء عملية النقل:</p>

                <ul>
                    <li><strong>اسم الدومين:</strong> الدومين الذي تريد نقله</li>
                    <li><strong>كود التحويل (EPP/Auth Code):</strong> الكود الذي حصلت عليه</li>
                    <li><strong>البريد الإلكتروني:</strong> بريدك المسجل لدى الشركة الحالية</li>
                </ul>

                <div class="info-box success">
                    <i class="ti ti-check"></i>
                    <strong>سنقوم نحن بـ:</strong> إرسال طلب النقل إلى الشركة الحالية نيابة عنك.
                </div>
            </div>
        </div>

        <!-- Transfer Step 4 -->
        <div class="step-card animate__animated animate__fadeInLeft">
            <div class="step-number">4</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-mail-opened"></i>
                </div>
                <h3 class="step-title">تأكيد النقل</h3>
            </div>
            <div class="step-content">
                <p>ستصلك رسالة بريد إلكتروني من الشركة الحالية للتأكيد على طلب النقل.[web:35]</p>

                <h5>ما يجب فعله:</h5>
                <ol>
                    <li>افتح البريد الإلكتروني المرسل</li>
                    <li>اضغط على رابط التأكيد أو زر "Approve Transfer"</li>
                    <li>قد يطلب منك تسجيل الدخول للتأكيد</li>
                </ol>

                <div class="info-box warning">
                    <i class="ti ti-clock"></i>
                    <strong>المدة الزمنية:</strong> عملية النقل تستغرق من 5 إلى 7 أيام للدومينات العادية (.com, .net,
                    .org).[web:34]
                </div>
            </div>
        </div>

        <!-- Transfer Step 5 -->
        <div class="step-card animate__animated animate__fadeInRight">
            <div class="step-number">5</div>
            <div class="step-header">
                <div class="step-icon">
                    <i class="ti ti-circle-check"></i>
                </div>
                <h3 class="step-title">اكتمال النقل</h3>
            </div>
            <div class="step-content">
                <p>بعد التأكيد، ستتم عملية النقل تلقائياً وستصلك رسالة تأكيد باكتمال النقل.</p>

                <div class="info-box success">
                    <i class="ti ti-check-circle"></i>
                    <strong>تمت العملية بنجاح!</strong> الدومين الآن مسجل لدينا ويمكنك إدارته من لوحة التحكم الخاصة بك.
                </div>

                <h5 class="mt-3">بعد اكتمال النقل:</h5>
                <ul>
                    <li>تحقق من إعدادات DNS</li>
                    <li>تأكد من عمل الموقع بشكل صحيح</li>
                    <li>حدّث معلومات التواصل إذا لزم الأمر</li>
                </ul>
            </div>
        </div>

        <!-- Common Issues -->
        <div class="row mt-4">
            <div class="col-12">
                <h3 class="mb-3" style="color: var(--primary-color); font-weight: 700; font-size: 1.3rem;">
                    <i class="ti ti-alert-circle me-2"></i>المشاكل الشائعة وحلولها
                </h3>

                <div class="accordion" id="troubleshootingAccordion">
                    <div class="accordion-item" style="border-radius: 10px; overflow: hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#issue1">
                                الموقع لا يعمل بعد 48 ساعة
                            </button>
                        </h2>
                        <div id="issue1" class="accordion-collapse collapse show"
                            data-bs-parent="#troubleshootingAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li>تأكد من إدخال Name Servers بشكل صحيح</li>
                                    <li>تحقق من عدم وجود مسافات زائدة</li>
                                    <li>امسح الكاش من المتصفح</li>
                                    <li>تواصل مع الدعم الفني</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item" style="border-radius: 10px; overflow: hidden; margin-top: 0.75rem;">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#issue2">
                                رفض طلب نقل الدومين
                            </button>
                        </h2>
                        <div id="issue2" class="accordion-collapse collapse"
                            data-bs-parent="#troubleshootingAccordion">
                            <div class="accordion-body">
                                <p>الأسباب المحتملة:[web:36]</p>
                                <ul>
                                    <li>الدومين مازال مقفلاً (Domain Lock)</li>
                                    <li>كود التحويل (EPP) غير صحيح</li>
                                    <li>الدومين مسجل منذ أقل من 60 يوماً</li>
                                    <li>البريد الإلكتروني غير صحيح</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item" style="border-radius: 10px; overflow: hidden; margin-top: 0.75rem;">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#issue3">
                                لم أستلم بريد تأكيد النقل
                            </button>
                        </h2>
                        <div id="issue3" class="accordion-collapse collapse"
                            data-bs-parent="#troubleshootingAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li>تحقق من مجلد البريد المزعج (Spam)</li>
                                    <li>تأكد من صحة البريد الإلكتروني المسجل</li>
                                    <li>انتظر 24 ساعة ثم تواصل مع الدعم</li>
                                    <li>تحقق من إعدادات تصفية البريد</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item" style="border-radius: 10px; overflow: hidden; margin-top: 0.75rem;">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#issue4">
                                لا أجد خيار Custom Nameservers
                            </button>
                        </h2>
                        <div id="issue4" class="accordion-collapse collapse"
                            data-bs-parent="#troubleshootingAccordion">
                            <div class="accordion-body">
                                <p>قد يكون بأسماء مختلفة:</p>
                                <ul>
                                    <li>"Change Nameservers"</li>
                                    <li>"DNS Management"</li>
                                    <li>"Custom DNS"</li>
                                    <li>"Use custom nameservers"</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need Help Box -->
        <div class="need-help-box animate__animated">
            <i class="ti ti-headset"></i>
            <h3>هل تحتاج مساعدة؟</h3>
            <p>فريق الدعم الفني جاهز لمساعدتك في ربط أو نقل الدومين</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="{{ tenant_route_name('tickets.index') }}" class="btn" style="color:white">
                    <i class="ti ti-message-circle" style="font-size: 39px;"></i>
                    تواصل مع الدعم
                </a>
                <a href="{{ tenant_route_name('domains.faq') }}" class="btn"
                    style="background: transparent;  color: white;">
                    <i class="ti ti-help-circle" style="font-size: 39px;"></i>
                    الأسئلة الشائعة
                </a>
            </div>
        </div>
    </div>

@endsection

@section('page-script')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                const toast = document.createElement('div');
                toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-5 alert alert-success';
                toast.style.zIndex = '9999';
                toast.style.fontSize = '0.9rem';
                toast.innerHTML = '<i class="ti ti-check me-2"></i>تم النسخ بنجاح!';
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 2000);
            });
        }
    </script>
@endsection
