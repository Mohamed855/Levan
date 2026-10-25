<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'action',
    'validationUrl',
    'fields' => 'contact', // 'contact' or 'trial'
    'title' => null,
    'subtitle' => null,
    'desc' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'action',
    'validationUrl',
    'fields' => 'contact', // 'contact' or 'trial'
    'title' => null,
    'subtitle' => null,
    'desc' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="contact-form-container scroll-revealed">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($title || $subtitle || $desc): ?>
        <div class="contact-form-header">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subtitle): ?>
                <span class="section-eyebrow"><?php echo e($subtitle); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($title): ?>
                <h3 class="form-title"><?php echo e($title); ?></h3>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($desc): ?>
                <p class="form-subtitle"><?php echo e($desc); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <form id="form" method="POST" action="<?php echo e($action); ?>" autocomplete="off" data-form-validate
        data-validation-url="<?php echo e($validationUrl); ?>" novalidate>
        <?php echo csrf_field(); ?>

        <div class="row g-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fields === 'contact'): ?>
                
                <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.input','data' => ['id' => 'contact_customer_name','name' => 'customer_name','label' => 'الاسم','placeholder' => 'أدخل اسمك الكامل','minlength' => '3','required' => true,'col' => 'col-12 col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contact_customer_name','name' => 'customer_name','label' => 'الاسم','placeholder' => 'أدخل اسمك الكامل','minlength' => '3','required' => true,'col' => 'col-12 col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.input','data' => ['id' => 'contact_customer_email','name' => 'customer_email','type' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'example@domain.com','col' => 'col-12 col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contact_customer_email','name' => 'customer_email','type' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'example@domain.com','col' => 'col-12 col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.textarea','data' => ['id' => 'contact_message','name' => 'message','label' => 'الرسالة أو الاستفسار','placeholder' => 'اكتب استفسارك أو رسالتك بالتفصيل هنا...','rows' => '5','minlength' => '10','required' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contact_message','name' => 'message','label' => 'الرسالة أو الاستفسار','placeholder' => 'اكتب استفسارك أو رسالتك بالتفصيل هنا...','rows' => '5','minlength' => '10','required' => true,'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $attributes = $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $component = $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
            <?php else: ?>
                
                <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.input','data' => ['id' => 'trial_customer_name','name' => 'customer_name','label' => 'الاسم الكامل','placeholder' => 'أدخل اسمك الكامل أو اسم جهة العمل','required' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'trial_customer_name','name' => 'customer_name','label' => 'الاسم الكامل','placeholder' => 'أدخل اسمك الكامل أو اسم جهة العمل','required' => true,'col' => 'col-12']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.input','data' => ['id' => 'trial_customer_email','name' => 'customer_email','type' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'name@company.com','required' => true,'col' => 'col-12 col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'trial_customer_email','name' => 'customer_email','type' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'name@company.com','required' => true,'col' => 'col-12 col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.input','data' => ['id' => 'trial_customer_phone','name' => 'customer_phone','type' => 'tel','label' => 'رقم الهاتف','placeholder' => '05XXXXXXXX','dir' => 'ltr','dataPhoneFormat' => 'mobile','required' => true,'col' => 'col-12 col-md-6','class' => 'text-end']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'trial_customer_phone','name' => 'customer_phone','type' => 'tel','label' => 'رقم الهاتف','placeholder' => '05XXXXXXXX','dir' => 'ltr','data-phone-format' => 'mobile','required' => true,'col' => 'col-12 col-md-6','class' => 'text-end']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" data-validation-skip>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['recaptcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <div class="col-12">
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                </div>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="col-12 text-center pt-2">
                <button type="submit" id="submit-form" class="site-btn-submit w-100">
                    <span class="spinner-border hidden" role="status" aria-hidden="true"></span>
                    <span class="btn-text">
                        <?php echo e($fields === 'trial' ? 'طلب تجربتك المجانية' : 'إرسال الرسالة'); ?>

                    </span>
                    <i class="ti ti-arrow-left"></i>
                </button>
            </div>
        </div>
    </form>
</div>
<?php /**PATH D:\Levan\resources\views/components/web/contact-form.blade.php ENDPATH**/ ?>