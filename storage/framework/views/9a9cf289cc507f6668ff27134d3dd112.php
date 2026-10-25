<?php $__env->startSection('title', 'معاينة مخرج المزاد — ' . ($output['template']['template_name'] ?? '')); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a class="btn btn-label-secondary" href="<?php echo e(tenant_route_name('auctions.show', $auction->id)); ?>">
        <i class="ti ti-arrow-right me-1"></i> العودة لتفاصيل المزاد
    </a>
    <span class="badge bg-label-primary fs-6 p-2">
        نوع المخرج: <?php echo e(match($type) { 'brochure' => 'الكتيب التسويقي', 'billboard' => 'اللوحة الإعلانية', 'social' => 'منشورات التواصل', default => $type }); ?>

    </span>
</div>

<div class="card shadow-sm">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0"><?php echo e($output['template']['template_name'] ?? 'القالب المعتمد'); ?></h5>
            <small class="text-muted font-monospace">إصدار <?php echo e($output['template']['version'] ?? 1); ?></small>
        </div>
    </div>
    <div class="card-body p-4 d-grid gap-4 bg-light">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($output['template']['schema']['pages'])): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $output['template']['schema']['pages']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div class="mx-auto position-relative overflow-hidden border rounded shadow-sm bg-white"
                     style="width:min(100%, <?php echo e($output['template']['schema']['canvas']['width'] ?? 794); ?>px);
                            aspect-ratio:<?php echo e(($output['template']['schema']['canvas']['width'] ?? 794)); ?>/<?php echo e(($output['template']['schema']['canvas']['height'] ?? 1123)); ?>;
                            background:<?php echo e($page['background']['color'] ?? '#ffffff'); ?>;
                            <?php if(!empty($page['background']['image'])): ?> background-image:url('<?php echo e(Storage::disk('tenant_public')->url($page['background']['image'])); ?>'); background-size:cover; <?php endif; ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $page['elements']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $style = $element['style'] ?? [];
                            $value = $element['type'] === 'text'
                                ? ($element['content'] ?? '')
                                : ($output['bindings'][$element['binding'] ?? ''] ?? '');
                        ?>
                        <div class="position-absolute overflow-hidden"
                             style="left:<?php echo e($element['x']); ?>px;
                                    top:<?php echo e($element['y']); ?>px;
                                    width:<?php echo e($element['width']); ?>px;
                                    height:<?php echo e($element['height']); ?>px;
                                    color:<?php echo e($style['color'] ?? '#173f35'); ?>;
                                    background:<?php echo e($style['background_color'] ?? 'transparent'); ?>;
                                    font-size:<?php echo e($style['font_size'] ?? 18); ?>px;
                                    font-weight:<?php echo e($style['font_weight'] ?? 'normal'); ?>;
                                    text-align:<?php echo e($style['text_align'] ?? 'right'); ?>;
                                    border-radius:<?php echo e($style['border_radius'] ?? 0); ?>px;
                                    padding: 4px;">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($element['type'] === 'image' && filled($value)): ?>
                                <img src="<?php echo e(Storage::disk('tenant_public')->url($value)); ?>" alt="" class="w-100 h-100 object-fit-cover">
                            <?php elseif($element['type'] === 'image'): ?>
                                <span class="d-flex h-100 align-items-center justify-content-center bg-light text-muted small border rounded">صورة</span>
                            <?php else: ?>
                                <?php echo e($value); ?>

                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning mb-0" role="alert">
                لا يتوفر مخطط تصميم محفوظ لهذا القالب.
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/tenant/auctions/preview.blade.php ENDPATH**/ ?>