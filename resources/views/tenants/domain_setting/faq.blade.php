@extends('tenants.layouts.layoutMaster')

@section('title', 'الأسئلة الشائعة')

@section('vendor-style')
@vite('resources/assets/vendor/libs/bs-stepper/bs-stepper.scss')
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/front-page-help-center.scss'])
<style>
    .faq-header {
        background: linear-gradient(135deg, #282c3e 0%, #3d4152 100%);
        padding: 60px 0;
        text-align: center;
        color: white;
        margin-bottom: 40px;
    }
    
    .faq-header h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .faq-header p {
        font-size: 16px;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .faq-search-box {
        max-width: 600px;
        margin: 30px auto 0;
        position: relative;
    }
    
    .faq-search-box input {
        width: 100%;
        padding: 15px 50px 15px 20px;
        border-radius: 10px;
        border: none;
        font-size: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }
    
    .faq-search-box i {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #666;
    }
    
    .faq-categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 50px;
    }
    
    .faq-category-card {
        background: white;
        border-radius: 12px;
        padding: 8px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }
    
    .faq-category-card:hover {
        border-color: #282c3e;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(40, 44, 62, 0.15);
    }
    
    .faq-category-card.active {
        border-color: #282c3e;
        background: #f8f9fa;
    }
    
    .faq-category-card i {
        font-size: 48px;
        color: #282c3e;
        margin-bottom: 15px;
    }
    
    .faq-category-card h5 {
        font-size: 18px;
        font-weight: 600;
        color: #282c3e;
        margin-bottom: 8px;
    }
    
    .faq-category-card p {
        font-size: 13px;
        color: #6c757d;
        margin: 0;
    }
    
    .accordion-item {
        border: none;
        margin-bottom: 15px;
        border-radius: 10px !important;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .accordion-button {
        background: white;
        color: #282c3e;
        font-weight: 600;
        font-size: 16px;
        padding: 20px 25px;
        border: none;
    }
    
    .accordion-button:not(.collapsed) {
        background: #282c3e;
        color: white;
        box-shadow: none;
    }
    
    .accordion-button:focus {
        box-shadow: none;
        border: none;
    }
    
    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23282c3e'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    
    .accordion-button:not(.collapsed)::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    
    .accordion-body {
        padding: 25px;
        background: #f8f9fa;
        color: #495057;
        line-height: 1.8;
    }
    
    .contact-support-box {
        background: linear-gradient(135deg, #282c3e 0%, #3d4152 100%);
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        color: white;
        margin-top: 50px;
    }
    
    .contact-support-box h4 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .contact-support-box p {
        opacity: 0.9;
        margin-bottom: 25px;
    }
    
    .contact-support-box .btn {
        background: white;
        color: #282c3e;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .contact-support-box .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(255, 255, 255, 0.3);
    }
</style>
@endsection

@section('content')
<!-- Header -->
<div class="faq-header">
    <div class="container">
        <i class="ti ti-help-circle" style="font-size: 60px; margin-bottom: 20px;"></i>
        <h2 style="color:white">الأسئلة الشائعة</h2>
        <p>ابحث عن إجابات لأسئلتك الشائعة حول خدماتنا ومنتجاتنا</p>
        
        <!-- Search Box -->
        
    </div>
</div>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Categories -->
    <div class="faq-categories mb-5">
        <div class="faq-category-card active" data-category="all">
            <i class="ti ti-layout-grid"></i>
            <h5>الكل</h5>
            <p>جميع الأسئلة</p>
        </div>
        
        <div class="faq-category-card" data-category="domains">
            <i class="ti ti-world"></i>
            <h5>الدومينات</h5>
            <p>أسئلة عن الدومينات</p>
        </div>
        
        <div class="faq-category-card" data-category="hosting">
            <i class="ti ti-server"></i>
            <h5>الاستضافة</h5>
            <p>أسئلة عن الاستضافة</p>
        </div>
        
        <div class="faq-category-card" data-category="payment">
            <i class="ti ti-credit-card"></i>
            <h5>الدفع</h5>
            <p>أسئلة عن الدفع</p>
        </div>
        
        <div class="faq-category-card" data-category="technical">
            <i class="ti ti-settings"></i>
            <h5>الدعم التقني</h5>
            <p>أسئلة تقنية</p>
        </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="row">
        <div class="col-12">
            <!-- Domain Questions -->
            <div class="faq-section" data-category="domains">
                <h4 class="mb-4"><i class="ti ti-world me-2"></i>أسئلة عن الدومينات</h4>
                <div class="accordion" id="accordionDomains">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#domain1">
                                كيف أقوم بحجز دومين جديد؟
                            </button>
                        </h2>
                        <div id="domain1" class="accordion-collapse collapse show" data-bs-parent="#accordionDomains">
                            <div class="accordion-body">
                                يمكنك حجز دومين جديد من خلال الذهاب إلى قسم "إعدادات الدومين" ثم اختيار "حجز دومين" والبحث عن الدومين المطلوب. بعد التأكد من توفره، اتبع خطوات الدفع لإتمام عملية الحجز.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#domain2">
                                كيف يمكنني نقل دومين موجود؟
                            </button>
                        </h2>
                        <div id="domain2" class="accordion-collapse collapse" data-bs-parent="#accordionDomains">
                            <div class="accordion-body">
                                لنقل دومين موجود، اذهب إلى قسم "نقل دومين" وأدخل اسم الدومين. ستحتاج إلى تغيير Name Servers في لوحة تحكم الدومين الحالي إلى ns1.levan.com و ns2.levan.com. قد تستغرق العملية من 24 إلى 48 ساعة.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#domain3">
                                ما هي مدة صلاحية الدومين؟
                            </button>
                        </h2>
                        <div id="domain3" class="accordion-collapse collapse" data-bs-parent="#accordionDomains">
                            <div class="accordion-body">
                                مدة صلاحية الدومين عادة سنة واحدة من تاريخ التسجيل، ويمكنك تجديده قبل انتهاء الصلاحية. ننصح بتفعيل التجديد التلقائي لضمان عدم فقدان الدومين.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hosting Questions -->
            <div class="faq-section mt-5" data-category="hosting">
                <h4 class="mb-4"><i class="ti ti-server me-2"></i>أسئلة عن الاستضافة</h4>
                <div class="accordion" id="accordionHosting">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hosting1">
                                ما هي أنواع الاستضافة المتوفرة؟
                            </button>
                        </h2>
                        <div id="hosting1" class="accordion-collapse collapse" data-bs-parent="#accordionHosting">
                            <div class="accordion-body">
                                نوفر عدة أنواع من الاستضافة: الاستضافة المشتركة، الاستضافة السحابية، والخوادم الافتراضية الخاصة (VPS). كل نوع مناسب لاحتياجات مختلفة من حيث الحجم والأداء.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hosting2">
                                كيف أرفع ملفات موقعي؟
                            </button>
                        </h2>
                        <div id="hosting2" class="accordion-collapse collapse" data-bs-parent="#accordionHosting">
                            <div class="accordion-body">
                                يمكنك رفع ملفات موقعك من خلال لوحة تحكم cPanel باستخدام File Manager، أو عبر بروتوكول FTP باستخدام برامج مثل FileZilla. ستجد معلومات تسجيل الدخول في إعدادات الاستضافة.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hosting3">
                                هل يمكن ترقية الباقة لاحقاً؟
                            </button>
                        </h2>
                        <div id="hosting3" class="accordion-collapse collapse" data-bs-parent="#accordionHosting">
                            <div class="accordion-body">
                                نعم، يمكنك ترقية باقتك في أي وقت من خلال لوحة التحكم. سيتم احتساب الفرق في السعر بناءً على الفترة المتبقية من اشتراكك الحالي.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Questions -->
            <div class="faq-section mt-5" data-category="payment">
                <h4 class="mb-4"><i class="ti ti-credit-card me-2"></i>أسئلة عن الدفع</h4>
                <div class="accordion" id="accordionPayment">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment1">
                                ما هي طرق الدفع المتاحة؟
                            </button>
                        </h2>
                        <div id="payment1" class="accordion-collapse collapse" data-bs-parent="#accordionPayment">
                            <div class="accordion-body">
                                نقبل الدفع عبر بطاقات الائتمان (Visa, Mastercard, Mada)، التحويل البنكي، ومحافظ الدفع الإلكتروني. جميع المعاملات مؤمنة ومشفرة.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment2">
                                هل تتضمن الأسعار ضريبة القيمة المضافة؟
                            </button>
                        </h2>
                        <div id="payment2" class="accordion-collapse collapse" data-bs-parent="#accordionPayment">
                            <div class="accordion-body">
                                الأسعار المعروضة لا تشمل ضريبة القيمة المضافة (15%). سيتم إضافة الضريبة عند إتمام عملية الدفع وستظهر في الفاتورة النهائية.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment3">
                                هل يمكن استرداد المبالغ المدفوعة؟
                            </button>
                        </h2>
                        <div id="payment3" class="accordion-collapse collapse" data-bs-parent="#accordionPayment">
                            <div class="accordion-body">
                                نوفر سياسة استرداد خلال 30 يوماً من تاريخ الشراء للخدمات الجديدة. يرجى مراجعة شروط وأحكام الاسترداد أو التواصل مع الدعم الفني لمزيد من التفاصيل.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Support Questions -->
            <div class="faq-section mt-5" data-category="technical">
                <h4 class="mb-4"><i class="ti ti-settings me-2"></i>أسئلة الدعم التقني</h4>
                <div class="accordion" id="accordionTechnical">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tech1">
                                كيف أتواصل مع الدعم الفني؟
                            </button>
                        </h2>
                        <div id="tech1" class="accordion-collapse collapse" data-bs-parent="#accordionTechnical">
                            <div class="accordion-body">
                                يمكنك التواصل مع فريق الدعم الفني عبر نظام التذاكر في لوحة التحكم، أو عبر البريد الإلكتروني support@example.com، أو الهاتف خلال ساعات العمل الرسمية.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tech2">
                                كيف أقوم بإنشاء بريد إلكتروني مخصص؟
                            </button>
                        </h2>
                        <div id="tech2" class="accordion-collapse collapse" data-bs-parent="#accordionTechnical">
                            <div class="accordion-body">
                                من خلال لوحة تحكم cPanel، اذهب إلى قسم "Email Accounts" واختر "Create". أدخل اسم البريد وكلمة المرور المطلوبة، وسيتم إنشاء البريد فوراً.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tech3">
                                كيف أقوم بعمل نسخة احتياطية لموقعي؟
                            </button>
                        </h2>
                        <div id="tech3" class="accordion-collapse collapse" data-bs-parent="#accordionTechnical">
                            <div class="accordion-body">
                                نوفر نسخ احتياطية تلقائية يومية لجميع المواقع. يمكنك أيضاً إنشاء نسخة احتياطية يدوية من خلال cPanel > Backup > Generate Backup واختيار الملفات وقواعد البيانات المطلوبة.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Support Box -->
    <div class="contact-support-box">
        <i class="ti ti-headset" style="font-size: 60px; margin-bottom: 20px;"></i>
        <h4 style="color:white">لم تجد إجابة لسؤالك؟</h4>
        <p>فريق الدعم الفني متواجد لمساعدتك في أي وقت</p>
        <a href="{{ tenant_route_name('tickets.index') }}" class="btn">
            <i class="ti ti-message-circle me-2"></i>
            تواصل مع الدعم
        </a>
    </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category Filter
    const categoryCards = document.querySelectorAll('.faq-category-card');
    const faqSections = document.querySelectorAll('.faq-section');
    
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update active state
            categoryCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // Filter sections
            if (category === 'all') {
                faqSections.forEach(section => section.style.display = 'block');
            } else {
                faqSections.forEach(section => {
                    if (section.dataset.category === category) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
        });
    });
    
    // Search Functionality
    const searchInput = document.getElementById('faqSearch');
    const accordionButtons = document.querySelectorAll('.accordion-button');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        accordionButtons.forEach(button => {
            const questionText = button.textContent.toLowerCase();
            const accordionItem = button.closest('.accordion-item');
            
            if (questionText.includes(searchTerm)) {
                accordionItem.style.display = 'block';
            } else {
                accordionItem.style.display = 'none';
            }
        });
        
        // Show all sections when searching
        if (searchTerm) {
            faqSections.forEach(section => section.style.display = 'block');
        }
    });
});
</script>
@endsection
