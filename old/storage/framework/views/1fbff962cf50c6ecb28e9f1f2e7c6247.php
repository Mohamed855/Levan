<?php $__env->startSection('title', 'لوت LOT - المنصة المتقدمة لإدارة المزادات والأصول'); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/scss/shared/package-card.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/form-validation.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    toastr.error(<?php echo json_encode($error, 15, 512) ?>);
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            });
        </script>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <main class="site-main">
        <!-- 1. Hero Section -->
        <section id="home" class="hero-section">
            <div class="container">
                <div class="hero-grid">
                    <!-- Content Column -->
                    <div class="hero-content scroll-revealed">
                        <span class="hero-eyebrow">
                            <i class="ti ti-sparkles"></i>
                            المنصة الرقمية الرائدة
                        </span>

                        <h1 class="hero-title">
                            منصة لوت لإدارة <span class="hero-title-highlight">المزادات والأصول</span>
                        </h1>

                        <p class="hero-desc">
                            حل رقمي متكامل مصمم خصيصاً لتمكين الشركات والجهات من ضبط وإدارة المزادات والتحصيلات بثقة وكفاءة
                            استثنائية.
                        </p>

                        <div class="hero-actions">
                            <a href="<?php echo e($plans->isNotEmpty() ? route('site.plans.checkout', $plans->first()->id) : '#pricing'); ?>"
                                class="btn-hero-primary" role="button">
                                <span>طلب اشتراك</span>
                                <i class="ti ti-arrow-left"></i>
                            </a>

                            <a href="<?php echo e(route('site.trial')); ?>" class="btn-hero-outline" role="button">
                                <span>تجربة مجانية</span>
                                <i class="ti ti-device-laptop"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Media Column -->
                    <div class="hero-media scroll-revealed">
                        <figure>
                            <img src="<?php echo e(asset('static/site/images/hero.png')); ?>" alt="لوت LOT منصة المزادات والأصول">
                        </figure>

                        <img src="<?php echo e(asset('static/site/images/dots.svg')); ?>" alt=""
                            class="hero-dots hero-dots--bottom-left">
                        <img src="<?php echo e(asset('static/site/images/dots.svg')); ?>" alt=""
                            class="hero-dots hero-dots--top-right">
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Services Section -->
        <section id="services" class="section-area">
            <div class="container">
                <?php if (isset($component)) { $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web.section-header','data' => ['title' => 'أبرز مميزات منصة لوت','subtitle' => 'خدمات متكاملة','desc' => 'نقدّم حزمة متطورة من الأدوات الذكية المصممة لرفع كفاءة أعمالك وضبط العمليات بدقة متناهية.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web.section-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'أبرز مميزات منصة لوت','subtitle' => 'خدمات متكاملة','desc' => 'نقدّم حزمة متطورة من الأدوات الذكية المصممة لرفع كفاءة أعمالك وضبط العمليات بدقة متناهية.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $attributes = $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $component = $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>

                <div class="services-grid">
                    <!-- Feature 1 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-layout-dashboard"></i>
                        </div>
                        <h3 class="service-title">لوحة القيادة الذكية</h3>
                        <p class="service-desc">
                            نظرة شاملة ولحظية على مؤشرات الأداء الحيوية، وحالة المزادات، وسير عمليات التحصيل والمهام
                            الجارية.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-arrows-shuffle"></i>
                        </div>
                        <h3 class="service-title">توزيع ذكي وعادل</h3>
                        <p class="service-desc">
                            آليات مؤتمتة لتوزيع المحافظ والمهام بين فرق العمل حسب المبالغ أو الكثافة لضمان أعلى مستويات
                            الإنتاجية.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-chart-bar"></i>
                        </div>
                        <h3 class="service-title">لوحة التميز التنافسية</h3>
                        <p class="service-desc">
                            شاشات تقدم لحظية تعزز الشفافية والتحفيز التنافسي الإيجابي بين فرق العمل التنفيذية.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-stack-front"></i>
                        </div>
                        <h3 class="service-title">إدارة شاملة للعمليات</h3>
                        <p class="service-desc">
                            إدارة مرنة للمحافظ والعملاء والأصول وصلاحيات المستخدمين بأعلى معايير الحوكمة والأمان.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-headset"></i>
                        </div>
                        <h3 class="service-title">دعم فني استشاري 24/7</h3>
                        <p class="service-desc">
                            فريق تقني واستشاري متخصص على مدار الساعة لمرافقتك في تخصيص وتشغيل المنصة بكل سلاسة.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-trending-up"></i>
                        </div>
                        <h3 class="service-title">تقارير تحليلية وأرشفة</h3>
                        <p class="service-desc">
                            تقارير مالية وتشغيلية تفصيلية جاهزة للتصدير مع نظام أرشفة موثق يحفظ كامل المستندات والوثائق.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Pricing Section -->
        <section id="pricing" class="section-area section-area--alt">
            <div class="container">
                <?php if (isset($component)) { $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web.section-header','data' => ['title' => 'باقات مرنة تناسب طموحك','subtitle' => 'الباقات والأسعار','desc' => 'نوفر باقات اشتراك مدروسة وشفافة تمنحك كافة الإمكانات لتوسيع أعمالك وإدارة مزاداتك بأمان.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web.section-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'باقات مرنة تناسب طموحك','subtitle' => 'الباقات والأسعار','desc' => 'نوفر باقات اشتراك مدروسة وشفافة تمنحك كافة الإمكانات لتوسيع أعمالك وإدارة مزاداتك بأمان.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $attributes = $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $component = $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>

                <div class="pricing-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="scroll-revealed">
                            <?php if (isset($component)) { $__componentOriginal24c860cd468d7f062c4ced7e057f483a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal24c860cd468d7f062c4ced7e057f483a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.package-card','data' => ['plan' => $plan,'actionUrl' => route('site.plans.checkout', $plan->id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.package-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['plan' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plan),'action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('site.plans.checkout', $plan->id))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal24c860cd468d7f062c4ced7e057f483a)): ?>
<?php $attributes = $__attributesOriginal24c860cd468d7f062c4ced7e057f483a; ?>
<?php unset($__attributesOriginal24c860cd468d7f062c4ced7e057f483a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal24c860cd468d7f062c4ced7e057f483a)): ?>
<?php $component = $__componentOriginal24c860cd468d7f062c4ced7e057f483a; ?>
<?php unset($__componentOriginal24c860cd468d7f062c4ced7e057f483a); ?>
<?php endif; ?>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 4. Call to Action Banner -->
        <section id="call-action" class="cta-section">
            <div class="container">
                <div class="cta-content scroll-revealed">
                    <h2 class="cta-title">
                        تحكّم في مزاداتك وتوقف عن إدارة الأصول يدوياً
                    </h2>
                    <p class="cta-desc">
                        مع لوت، كل إجراء منظم في مساره الصحيح، وكل مستند تحت عينك، مع متابعة دقيقة لمراحل العمل من البداية
                        وحتى الإسناد.
                        مزادك يبدأ مع لوت.
                    </p>
                    <a href="<?php echo e(route('site.trial')); ?>" class="btn-cta" role="button">
                        <span>ابدأ تجربتك المجانية الآن</span>
                        <i class="ti ti-arrow-left"></i>
                    </a>
                </div>
            </div>
        </section>

        

        <!-- 8. FAQ Section -->
        <section id="faq" class="section-area section-area--alt">
            <div class="container">
                <?php if (isset($component)) { $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web.section-header','data' => ['title' => 'الأسئلة الشائعة','subtitle' => 'إجابات واضحة','desc' => 'إليك أهم الأسئلة التي يطرحها عملاؤنا، لمساعدتك في التعرف على إمكانات النظام بكل وضوح.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web.section-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الأسئلة الشائعة','subtitle' => 'إجابات واضحة','desc' => 'إليك أهم الأسئلة التي يطرحها عملاؤنا، لمساعدتك في التعرف على إمكانات النظام بكل وضوح.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $attributes = $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $component = $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>

                <div class="faq-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $faqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap"><i class="ti ti-help-circle"></i></div>
                        <div class="faq-body"><h3 class="faq-question"><?php echo e($faq->question); ?></h3><p class="faq-answer"><?php echo e($faq->answer); ?></p></div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="d-none" aria-hidden="true">
                    <!-- Q1 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل تتقاضون رسوماً مقابل التحديثات والدعم الفني؟</h3>
                            <p class="faq-answer">
                                لا، جميع التحديثات الدورية والتحسينات المستمرة وخدمات الدعم الفني مشمولة بالكامل ضمن
                                الاشتراك دون أي تكاليف خفية.
                            </p>
                        </div>
                    </div>

                    <!-- Q2 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل أحتاج إلى شراء ترخيص لكل مستخدم؟</h3>
                            <p class="faq-answer">
                                يعتمد عدد تراخيص المستخدمين على الباقة المختارة، مع إمكانية الترقية وإضافة مستخدمين جدد بكل
                                سهولة في أي وقت.
                            </p>
                        </div>
                    </div>

                    <!-- Q3 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل يدعم النظام الربط والتكامل مع الأنظمة المحاسبية؟</h3>
                            <p class="faq-answer">
                                نعم، تدعم منصة لوت الربط والتكامل مع الأنظمة المحاسبية وأنظمة ERP الرائدة لتسهيل تبادل
                                البيانات بسلاسة وأمان.
                            </p>
                        </div>
                    </div>

                    <!-- Q4 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل يمكن تخصيص النظام ليتناسب مع سياسات شركتنا؟</h3>
                            <p class="faq-answer">
                                بالتأكيد، يتيح النظام تخصيص الصلاحيات، وهيكل فرق العمل، ونماذج التقارير، وطرق توزيع العمل
                                بما يتوافق مع لوائح وسياسات كل جهة.
                            </p>
                        </div>
                    </div>

                    <!-- Q5 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">ما مدى أمان وحماية البيانات في منصة لوت؟</h3>
                            <p class="faq-answer">
                                نطبق أعلى بروتوكولات التشفير والنسخ الاحتياطي السحابي المستمر وعزل بيانات كل منشأة لضمان
                                الامتثال التام لمعايير الأمن السيبراني.
                            </p>
                        </div>
                    </div>

                    <!-- Q6 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">كيف يتم نقل البيانات من أنظمتنا القديمة؟</h3>
                            <p class="faq-answer">
                                يوفر فريقنا التقني خدمة نقل وترحيل البيانات مجاناً عند الاشتراك، مع التحقق الدقيق لضمان
                                سلامة واكتمال السجلات.
                            </p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Contact Section -->
        <section id="contact" class="section-area">
            <div class="container">
                <?php if (isset($component)) { $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web.section-header','data' => ['title' => 'ابدأ محادثتك معنا اليوم','subtitle' => 'تواصل معنا','desc' => 'فريقنا المتخصص على أتم الاستعداد للإجابة على استفساراتك واستعراض مزايا المنصة المناسبة لعملك.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web.section-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'ابدأ محادثتك معنا اليوم','subtitle' => 'تواصل معنا','desc' => 'فريقنا المتخصص على أتم الاستعداد للإجابة على استفساراتك واستعراض مزايا المنصة المناسبة لعملك.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $attributes = $__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__attributesOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b)): ?>
<?php $component = $__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b; ?>
<?php unset($__componentOriginal64d3f97d1b6e495e5e4ad4258712db8b); ?>
<?php endif; ?>

                <div class="contact-layout">
                    <!-- Form Container -->
                    <?php if (isset($component)) { $__componentOriginald4cc62e7fa563392b8a7f956386a5125 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4cc62e7fa563392b8a7f956386a5125 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web.contact-form','data' => ['dataFormValidate' => true,'action' => route('site.contact.store'),'validationUrl' => route('site.form-validation'),'fields' => 'contact','title' => 'أرسل لنا استفسارك','subtitle' => 'يسعدنا تواصلك','desc' => 'املأ البيانات وسيقوم ممثلنا بالتواصل معك في أقرب فرصة.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web.contact-form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data-form-validate' => true,'action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('site.contact.store')),'validation-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('site.form-validation')),'fields' => 'contact','title' => 'أرسل لنا استفسارك','subtitle' => 'يسعدنا تواصلك','desc' => 'املأ البيانات وسيقوم ممثلنا بالتواصل معك في أقرب فرصة.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4cc62e7fa563392b8a7f956386a5125)): ?>
<?php $attributes = $__attributesOriginald4cc62e7fa563392b8a7f956386a5125; ?>
<?php unset($__attributesOriginald4cc62e7fa563392b8a7f956386a5125); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4cc62e7fa563392b8a7f956386a5125)): ?>
<?php $component = $__componentOriginald4cc62e7fa563392b8a7f956386a5125; ?>
<?php unset($__componentOriginald4cc62e7fa563392b8a7f956386a5125); ?>
<?php endif; ?>

                    <!-- Info Cards -->
                    <?php if (isset($component)) { $__componentOriginal10cc6f9fe0bc27a121fba68331b319ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal10cc6f9fe0bc27a121fba68331b319ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.web.contact-info','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('web.contact-info'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal10cc6f9fe0bc27a121fba68331b319ae)): ?>
<?php $attributes = $__attributesOriginal10cc6f9fe0bc27a121fba68331b319ae; ?>
<?php unset($__attributesOriginal10cc6f9fe0bc27a121fba68331b319ae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal10cc6f9fe0bc27a121fba68331b319ae)): ?>
<?php $component = $__componentOriginal10cc6f9fe0bc27a121fba68331b319ae; ?>
<?php unset($__componentOriginal10cc6f9fe0bc27a121fba68331b319ae); ?>
<?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 10. Map Section -->
        <?php ($googleMapsEmbedUrl = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('google_maps_embed_url')); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($googleMapsEmbedUrl): ?>
        <section id="map" class="map-section">
            <iframe
                src="<?php echo e($googleMapsEmbedUrl); ?>"
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="موقع لوت LOT">
            </iframe>
        </section>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </main>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('web.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/web/index.blade.php ENDPATH**/ ?>