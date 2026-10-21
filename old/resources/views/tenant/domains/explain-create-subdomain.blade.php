@extends('tenant.layouts.layoutMaster')

@section('title', 'شرح إنشاء نطاق فرعي (Subdomain)')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h2 class="mb-3">
                            <i class="ti ti-world ti-lg text-primary"></i>
                            دليل إنشاء نطاق فرعي (Subdomain)
                        </h2>
                        <p class="text-muted mb-0">اتبع الخطوات التالية لإضافة نطاق فرعي لموقعك بنجاح</p>
                    </div>
                </div>

                <!-- مقدمة -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="ti ti-info-circle ti-lg me-2"></i>
                            <div>
                                <h5 class="alert-heading mb-1">ما هو النطاق الفرعي (Subdomain)؟</h5>
                                <p class="mb-0">
                                    النطاق الفرعي هو جزء فرعي من موقعك الإلكتروني يعمل كموقع مستقل تمامًا.
                                    على سبيل المثال: إذا كان موقعك <code>example.com</code> يمكنك إنشاء
                                    <code>blog.example.com</code> أو <code>store.example.com</code>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات مهمة -->
                <div class="card mb-4 border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            معلومات مهمة قبل البدء
                        </h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="ti ti-check text-success me-2"></i>
                                        النطاق الفرعي مجاني ولا يحتاج لحجز منفصل
                                    </li>
                                    <li class="mb-2">
                                        <i class="ti ti-check text-success me-2"></i>
                                        يعمل كموقع مستقل عن الموقع الرئيسي
                                    </li>
                                    <li class="mb-2">
                                        <i class="ti ti-check text-success me-2"></i>
                                        يمكن تثبيت تطبيقات منفصلة عليه
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-primary mb-0">
                                    <h6 class="mb-2"><i class="ti ti-server me-1"></i> عنوان IP الخاص بنا:</h6>
                                    <code class="fs-5">{{ env('SERVER_IP', '135.181.215.105') }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الخطوات -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="ti ti-list-numbers me-2"></i>
                            خطوات إنشاء النطاق الفرعي
                        </h4>
                    </div>
                    <div class="card-body">

                        <!-- الخطوة 1 -->
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary rounded-pill me-3 fs-5"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">1</span>
                                <h5 class="mb-0">تسجيل الدخول إلى لوحة التحكم للدومين</h5>
                            </div>
                            <div class="ps-5">
                                <p class="text-muted mb-3">قم بالدخول إلى لوحة التحكم الخاصة بالدومين (مثل Namecheap،
                                    GoDaddy، Cloudflare، أو أي مزود آخر)</p>
                                <div class="alert alert-light border">
                                    <p class="mb-2"><strong>أمثلة روابط لوحات التحكم الشهيرة:</strong></p>
                                    <ul class="mb-0">
                                        <li><strong>Namecheap:</strong> <code>https://ap.www.namecheap.com/</code></li>
                                        <li><strong>GoDaddy:</strong> <code>https://dcc.godaddy.com/</code></li>
                                        <li><strong>Cloudflare:</strong> <code>https://dash.cloudflare.com/</code></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- الخطوة 2 -->
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary rounded-pill me-3 fs-5"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">2</span>
                                <h5 class="mb-0">الانتقال إلى إدارة DNS</h5>
                            </div>
                            <div class="ps-5">
                                <p class="text-muted mb-3">ابحث عن قسم إدارة DNS أو DNS Management في لوحة التحكم</p>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="card bg-label-primary">
                                            <div class="card-body text-center">
                                                <i class="ti ti-settings ti-lg mb-2"></i>
                                                <p class="mb-0 small">ابحث عن "DNS Management"</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-label-primary">
                                            <div class="card-body text-center">
                                                <i class="ti ti-server ti-lg mb-2"></i>
                                                <p class="mb-0 small">أو "Advanced DNS"</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-label-primary">
                                            <div class="card-body text-center">
                                                <i class="ti ti-file-settings ti-lg mb-2"></i>
                                                <p class="mb-0 small">أو "Manage DNS"</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- الخطوة 3 -->
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary rounded-pill me-3 fs-5"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">3</span>
                                <h5 class="mb-0">إضافة سجل A Record للنطاق الفرعي</h5>
                            </div>
                            <div class="ps-5">
                                <p class="text-muted mb-3">قم بإضافة سجل جديد بالقيم التالية:</p>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="25%">الحقل</th>
                                                <th width="40%">القيمة المطلوبة</th>
                                                <th width="35%">مثال</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Type (النوع)</strong></td>
                                                <td><code class="text-primary">A</code></td>
                                                <td>A Record</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Host/Name</strong></td>
                                                <td>اسم النطاق الفرعي الذي تريده</td>
                                                <td><code class="text-success">blog</code> أو <code
                                                        class="text-success">store</code> أو <code
                                                        class="text-success">app</code></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Value/Points to</strong></td>
                                                <td><code
                                                        class="text-danger">{{ env('SERVER_IP', '135.181.215.105') }}</code>
                                                </td>
                                                <td>عنوان IP الخاص بنا</td>
                                            </tr>
                                            <tr>
                                                <td><strong>TTL</strong></td>
                                                <td><code>Automatic</code> أو <code>14400</code></td>
                                                <td>اتركه على الوضع الافتراضي</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-warning mt-3">
                                    <i class="ti ti-clock me-2"></i>
                                    <strong>ملاحظة:</strong> قد يستغرق تفعيل السجل من 5 دقائق إلى 48 ساعة حسب مزود الدومين
                                </div>
                            </div>
                        </div>

                        <!-- الخطوة 4 -->
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary rounded-pill me-3 fs-5"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">4</span>
                                <h5 class="mb-0">حفظ التغييرات</h5>
                            </div>
                            <div class="ps-5">
                                <p class="text-muted mb-3">اضغط على زر "Save" أو "Add Record" لحفظ السجل الجديد</p>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" disabled>
                                        <i class="ti ti-device-floppy me-1"></i>
                                        Save Changes
                                    </button>
                                    <button type="button" class="btn btn-primary" disabled>
                                        <i class="ti ti-plus me-1"></i>
                                        Add Record
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- الخطوة 5 -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-success rounded-pill me-3 fs-5"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">5</span>
                                <h5 class="mb-0">التحقق من تفعيل النطاق الفرعي</h5>
                            </div>
                            <div class="ps-5">
                                <p class="text-muted mb-3">بعد انتظار فترة التفعيل، قم بزيارة النطاق الفرعي في المتصفح</p>
                                <div class="alert alert-success">
                                    <i class="ti ti-circle-check me-2"></i>
                                    إذا ظهر موقعك أو صفحة ترحيبية، فهذا يعني أن النطاق الفرعي تم تفعيله بنجاح!
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- أمثلة عملية -->
                <div class="card mb-4 bg-label-info">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <i class="ti ti-bulb me-2"></i>
                            أمثلة عملية
                        </h5>
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="text-primary">مثال 1: إنشاء مدونة</h6>
                                        <ul class="list-unstyled small mb-0">
                                            <li><strong>Host:</strong> <code>blog</code></li>
                                            <li><strong>Type:</strong> <code>A</code></li>
                                            <li><strong>Value:</strong>
                                                <code>{{ env('SERVER_IP', '135.181.215.105') }}</code>
                                            </li>
                                            <li><strong>النتيجة:</strong> <code
                                                    class="text-success">blog.yourdomain.com</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="text-primary">مثال 2: إنشاء متجر</h6>
                                        <ul class="list-unstyled small mb-0">
                                            <li><strong>Host:</strong> <code>store</code></li>
                                            <li><strong>Type:</strong> <code>A</code></li>
                                            <li><strong>Value:</strong>
                                                <code>{{ env('SERVER_IP', '135.181.215.105') }}</code>
                                            </li>
                                            <li><strong>النتيجة:</strong> <code
                                                    class="text-success">store.yourdomain.com</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الأسئلة الشائعة -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti ti-help me-2"></i>
                            الأسئلة الشائعة
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseOne">
                                        <i class="ti ti-clock-hour-4 me-2"></i>
                                        كم يستغرق تفعيل النطاق الفرعي؟
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        عادةً يستغرق التفعيل من 5 دقائق إلى ساعة واحدة، لكن في بعض الحالات قد يصل إلى 48
                                        ساعة حسب مزود الدومين وانتشار DNS.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseTwo">
                                        <i class="ti ti-currency-dollar me-2"></i>
                                        هل النطاق الفرعي مجاني؟
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        نعم، النطاق الفرعي مجاني تمامًا ولا يحتاج لدفع أي رسوم إضافية. يمكنك إنشاء عدد غير
                                        محدود من النطاقات الفرعية.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseThree">
                                        <i class="ti ti-help-circle me-2"></i>
                                        ماذا لو لم يعمل النطاق الفرعي؟
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <ul class="mb-0">
                                            <li>تأكد من إدخال عنوان IP الصحيح:
                                                <code>{{ env('SERVER_IP', '135.181.215.105') }}</code>
                                            </li>
                                            <li>انتظر 24-48 ساعة لانتشار DNS</li>
                                            <li>تحقق من عدم وجود أخطاء إملائية في اسم النطاق الفرعي</li>
                                            <li>تواصل مع الدعم الفني إذا استمرت المشكلة</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- إنشاء نطاق فرعي تلقائياً -->
                <div class="card mb-4 border-success">
                    <div class="card-header bg-label-success">
                        <h5 class="mb-0">
                            <i class="ti ti-rocket me-2"></i>
                            إنشاء نطاق فرعي تلقائياً
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            لا تريد القيام بالخطوات يدوياً؟ يمكنك إنشاء النطاق الفرعي تلقائياً من خلال النموذج أدناه
                        </p>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <i class="ti ti-circle-check me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <i class="ti ti-alert-triangle me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('subdomain_url'))
                            <div class="alert alert-info alert-dismissible" role="alert">
                                <i class="ti ti-link me-2"></i>
                                <strong>رابط النطاق الفرعي:</strong>
                                <a href="{{ session('subdomain_url') }}" target="_blank" class="alert-link">
                                    {{ session('subdomain_url') }}
                                </a>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ tenant_route_name('domains.subdomain') }}" method="POST">
                            @csrf
                            <div class="row g-4 align-items-end">
                                <x-form.controls.input name="subdomain" id="subdomain" label="اسم النطاق الفرعي"
                                    placeholder="مثال: blog أو store أو app"
                                    help="استخدم حروف إنجليزية صغيرة وأرقام وشرطة (-) فقط" required col="col-md-6"
                                    :input-group="true">
                                    <span class="input-group-text">.levan.com</span>
                                </x-form.controls.input>
                                <div class="col-md-6 mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-plus me-1"></i>
                                        إنشاء النطاق الفرعي
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            <small>
                                سيتم إنشاء النطاق الفرعي فوراً وسيكون جاهزاً للاستخدام خلال دقائق
                            </small>
                        </div>
                    </div>
                </div>

                <!-- روابط مساعدة -->
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="mb-3">
                            <i class="ti ti-lifebuoy me-2"></i>
                            هل تحتاج مساعدة إضافية؟
                        </h5>
                        <p class="text-muted mb-3">فريق الدعم الفني جاهز لمساعدتك في أي وقت</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="#" class="btn btn-primary">
                                <i class="ti ti-message-circle me-1"></i>
                                تواصل مع الدعم
                            </a>
                            <a href="{{ tenant_route_name('dashboard') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-right me-1"></i>
                                العودة للوحة التحكم
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
