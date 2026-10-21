<?php $__env->startSection('title', 'تفاصيل المزاد - ' . $auction->name); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-gavel me-2 text-primary"></i><?php echo e($auction->name); ?></h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge <?php echo e($auction->status_badge_class); ?>"><?php echo e($auction->status_label); ?></span>
                <span class="badge bg-label-info"><?php echo e($auction->type_label); ?></span>
                <span class="badge bg-secondary"><?php echo e($auction->city); ?></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('auctions.edit')): ?>
                <a href="<?php echo e(tenant_route_name('auctions.edit', $auction->id)); ?>" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل المزاد
                </a>
            <?php endif; ?>
            <a href="<?php echo e(tenant_route_name('auctions.index')); ?>" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للمزادات
            </a>
        </div>
    </div>

    <div class="row">
        
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <img src="<?php echo e($auction->cover_image ? tasset($auction->cover_image) : asset('assets/img/illustrations/page-pricing-enterprise.png')); ?>"
                            alt="<?php echo e($auction->name); ?>" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="text-center"><?php echo e($auction->name); ?></h5>
                    <p class="text-muted small"><?php echo e($auction->description ?? 'لا يوجد وصف مضاف.'); ?></p>

                    <ul class="list-unstyled border-top pt-3">
                        <li class="mb-2"><span class="fw-bold me-1">الإسناد المرتبط:</span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($auction->assignment): ?>
                                <a href="<?php echo e(tenant_route_name('assignments.show', $auction->assignment_id)); ?>"
                                    class="text-primary fw-medium">
                                    <?php echo e($auction->assignment->name); ?> (<?php echo e($auction->assignment->assignment_number); ?>)
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </li>
                        <li class="mb-2"><span class="fw-bold me-1">الجهة المشرفة:</span>
                            <span><?php echo e($auction->supervising_entity ?? '-'); ?></span></li>
                        <li class="mb-2"><span class="fw-bold me-1">منصة المزاد:</span>
                            <span><?php echo e($auction->platform_name ?? '-'); ?></span></li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($auction->platform_url): ?>
                            <li class="mb-2"><span class="fw-bold me-1">رابط المزاد:</span>
                                <a href="<?php echo e($auction->platform_url); ?>" target="_blank" class="text-primary">زيارة المنصة <i
                                        class="ti ti-external-link"></i></a>
                            </li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <li class="mb-2"><span class="fw-bold me-1">تاريخ البداية:</span>
                            <span><?php echo e($auction->start_date?->format('Y-m-d')); ?> <?php echo e($auction->start_time); ?></span></li>
                        <li class="mb-2"><span class="fw-bold me-1">تاريخ النهاية:</span>
                            <span><?php echo e($auction->end_date?->format('Y-m-d')); ?> <?php echo e($auction->end_time); ?></span></li>
                    </ul>
                </div>
            </div>

            
            <?php
                $hasTemplates = !empty($auction->template_versions) || !empty($auction->template_snapshot) || $auction->brochure_template_id || $auction->billboard_template_id || $auction->social_template_id;
                $hasBrochure = !empty($auction->template_versions['brochure']) || !empty($auction->template_snapshot['brochure']) || $auction->brochure_template_id;
                $hasBillboard = !empty($auction->template_versions['billboard']) || !empty($auction->template_snapshot['billboard']) || $auction->billboard_template_id;
                $hasSocial = !empty($auction->template_versions['social']) || !empty($auction->template_snapshot['social']) || $auction->social_template_id;
            ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasTemplates): ?>
                <div class="card mb-4 shadow-sm border-primary">
                    <div class="card-header bg-label-primary py-3">
                        <h6 class="card-title mb-0 text-primary fw-bold">
                            <i class="ti ti-palette me-1"></i>مخرجات ومعاينة القوالب المعتمدة
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="d-grid gap-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasBrochure): ?>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e(tenant_route_name('auctions.preview', ['auction' => $auction->id, 'type' => 'brochure'])); ?>"
                                       class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-between" target="_blank">
                                        <span><i class="ti ti-eye me-2"></i>معاينة كتيب البروشور</span>
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($auction->brochure_template_id): ?>
                                        <a href="<?php echo e(tenant_route_name('auctions.brochure', $auction->id)); ?>"
                                           class="btn btn-outline-primary" target="_blank" title="تحميل PDF">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasBillboard): ?>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e(tenant_route_name('auctions.preview', ['auction' => $auction->id, 'type' => 'billboard'])); ?>"
                                       class="btn btn-warning flex-grow-1 d-flex align-items-center justify-content-between" target="_blank">
                                        <span><i class="ti ti-eye me-2"></i>معاينة اللوحة الإعلانية</span>
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($auction->billboard_template_id): ?>
                                        <a href="<?php echo e(tenant_route_name('auctions.billboard', $auction->id)); ?>"
                                           class="btn btn-outline-warning" target="_blank" title="تحميل PDF">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasSocial): ?>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e(tenant_route_name('auctions.preview', ['auction' => $auction->id, 'type' => 'social'])); ?>"
                                       class="btn btn-info flex-grow-1 d-flex align-items-center justify-content-between" target="_blank">
                                        <span><i class="ti ti-eye me-2"></i>معاينة منشورات التواصل</span>
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($auction->social_template_id): ?>
                                        <a href="<?php echo e(tenant_route_name('auctions.social', $auction->id)); ?>"
                                           class="btn btn-outline-info" target="_blank" title="تحميل PDF">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($auction->attachments->isNotEmpty()): ?>
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0">وثائق ومرفقات المزاد</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $auction->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <li class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        <i class="ti ti-file text-primary fs-5" aria-hidden="true"></i>
                                        <span class="text-truncate" title="<?php echo e($attachment->file_name); ?>"><?php echo e($attachment->file_name); ?></span>
                                    </div>
                                    <a href="<?php echo e(tasset($attachment->file_path)); ?>" target="_blank"
                                        class="btn btn-sm btn-icon btn-label-primary" title="تنزيل <?php echo e($attachment->file_name); ?>">
                                        <i class="ti ti-download" aria-hidden="true"></i>
                                    </a>
                                </li>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="col-xl-8 col-lg-7 col-md-7">
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">العقارات المدرجة في المزاد (<?php echo e($auction->properties->count()); ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم العقار</th>
                                <th>اسم العقار</th>
                                <th>النوع</th>
                                <th>المساحة</th>
                                <th>القيمة التقديرية</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $auction->properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><code><?php echo e($prop->property_number); ?></code></td>
                                    <td>
                                        <a href="<?php echo e(tenant_route_name('properties.show', $prop->id)); ?>"
                                            class="fw-medium text-body">
                                            <?php echo e($prop->name); ?>

                                        </a>
                                    </td>
                                    <td><span class="badge bg-label-info"><?php echo e($prop->type_label); ?></span></td>
                                    <td><?php echo e(number_format((float) $prop->area)); ?> م²</td>
                                    <td><?php echo e(number_format((float) $prop->estimated_value)); ?> ر.س</td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لم يتم إدراج عقارات في هذا المزاد
                                        بعد.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/tenant/auctions/show.blade.php ENDPATH**/ ?>