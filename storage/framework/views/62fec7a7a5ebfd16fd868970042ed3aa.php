<?php
    $officeAddress = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('office_address');
    $phoneNumber = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('phone_number');
    $email = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('email');
    $phoneLink = preg_replace('/[^0-9+]/', '', (string) $phoneNumber);
?>
<div <?php echo e($attributes->merge(['class' => 'contact-info-list scroll-revealed'])); ?>>
    
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-phone"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">تواصل معنا</h4>
            <p class="info-value">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phoneNumber): ?><a href="tel:<?php echo e($phoneLink); ?>" dir="ltr"><?php echo e($phoneNumber); ?></a><?php else: ?> — <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </p>
            <p class="info-value">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($email): ?><a href="mailto:<?php echo e($email); ?>"><?php echo e($email); ?></a><?php else: ?> — <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </p>
        </div>
    </div>

    
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-map-pin"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">العنوان</h4>
            <p class="info-value">
                <?php echo e($officeAddress ?: '—'); ?>

            </p>
        </div>
    </div>

    
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-alarm"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">ساعات العمل</h4>
            <p class="info-value">متاح 24 ساعة / 7 أيام أسبوعياً</p>
            <p class="info-value">ساعات المكتب: 10:00 ص - 6:00 م</p>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\resources\views/components/web/contact-info.blade.php ENDPATH**/ ?>