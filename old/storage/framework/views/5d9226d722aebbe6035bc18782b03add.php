<?php $__env->startSection('title', 'الملف الشخصي للمستخدم'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/pages/page-profile.scss']); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/tenant/user-management/user/pages-profile.js']); ?>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('content'); ?>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">

                <div class="background-image-container"
                    style="position: relative; width: 100%; height: 100%; overflow: hidden;">
                    <img src="<?php echo e(asset('static/dashboard/backgrounds/cta-bg-secondary3.png')); ?>" alt="صورة الغلاف"
                        class="rounded-top" style="width: 100%; height: 143px !important; object-fit: cover;">
                </div>
                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-5">
                    <div class="profile-image-container mt-n5 mx-sm-0 mx-auto"
                        style="position: relative; display: inline-block;">
                        <img src="<?php echo e($user->image && Storage::disk('public')->exists($user->image) ? asset('storage/' . $user->image) : asset('static/brand/logos/logo.svg')); ?>"
                            alt="صورة المستخدم" class="d-block h-auto ms-0 ms-sm-6"
                            style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                    </div>
                    <div class="flex-grow-1 mt-3 mt-lg-5">
                        <div
                            class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                            <div class="user-profile-info">
                                <h5 class="mb-2 mt-lg-6 fw-bold"><?php echo e($user->name); ?></h5>
                                <ul
                                    class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($role)): ?>
                                        <li class="list-inline-item d-flex gap-2 align-items-center">
                                            <i class='ti ti-id-badge fs-5 text-primary'></i>
                                            <span class="fw-bold small">
                                                <?php echo e($role->name); ?>

                                            </span>
                                        </li>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($user->created_at)): ?>
                                        <li class="list-inline-item d-flex gap-2 align-items-center">
                                            <i class='ti ti-calendar-event fs-5 text-primary'></i>
                                            <span class="fw-bold small">
                                                تاريخ الإضافة
                                                <?php echo e(optional($user->created_at)->format('Y-m-d') ?? ''); ?>

                                            </span>
                                        </li>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                </ul>
                            </div>

                        </div>
                    </div>
                </div>

            </div>




            <div class="tab-pane fade show active" id="general_info_tab">
                <div class="row g-4 d-flex align-items-stretch">
                    <div class="col-xl-8">
                        <!-- معلومات عن الموظف -->
                        <div class="card mb-6">
                            <div class="card-header py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-list-details text-warning me-2"></i>
                                    البيانات الاساسية
                                </h6>
                            </div>

                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body row">
                                <div class="col-6">
                                    <small class="card-text text-primary fw-bold">معلومات الموظف</small>
                                    <ul class="list-unstyled my-3 py-1">
                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-user-circle  text-primary"></i>
                                            <small class="mx-2 fw-semibold">
                                                <?php echo e($user->name); ?></small>
                                        </li>

                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-id-badge  text-primary"></i>
                                            <small class="mx-2 fw-semibold"><?php echo e($role ? $role->name : 'غير متوفر'); ?></small>
                                        </li>

                                    </ul>
                                </div>

                                <div class="col-6">
                                    <small class="card-text text-primary fw-bold">التواصل</small>
                                    <ul class="list-unstyled my-3 py-1">
                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-phone text-primary"></i>
                                            <small class="mx-2 fw-semibold"><?php echo e($user->phone ?? 'غير محدد'); ?></small>
                                        </li>
                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-mail text-primary"></i>
                                            <small class="mx-2 fw-semibold"><?php echo e($user->email ?? 'غير محدد'); ?></small>
                                        </li>
                                    </ul>
                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4">
                        <!-- سجل النشاطات -->
                        <div class="card mb-6">
                            <div class="card-header d-flex justify-content-between py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-history text-warning me-2"></i>
                                    سجل النشاطات
                                </h6>
                                <h6 class="small mb-0">اخر 5 نشاطات</h6>

                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body pt-3 small fw-bold">
                                <ul class="timeline mb-0">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <li class="timeline-item">
                                            <span class="timeline-point bg-secondary"></span>
                                            <div class="timeline-event">
                                                <small class="timeline-title text-capitalize">
                                                    <?php echo e($activity->description); ?>

                                                </small>
                                                <small class="text-muted d-block">
                                                    <?php echo e($activity->created_at->diffForHumans()); ?>

                                                </small>
                                            </div>
                                        </li>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        <h6 class="mb-0 small text-center">لا توجد نشاطات</h6>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <!--/ سجل النشاطات -->
                    </div>
                </div>

                <!-- قسم الكوبونات -->
                <div class="row g-4 mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-ticket text-success me-2"></i>
                                    الكوبونات المُسندة
                                </h6>
                                <span class="badge bg-label-primary rounded-pill"><?php echo e($coupons->count()); ?> كوبون</span>
                            </div>

                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $coupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php
                                        $expiryDate = $coupon->expiry_date
                                            ? \Carbon\Carbon::parse($coupon->expiry_date)
                                            : null;
                                        $isExpired = $expiryDate && $expiryDate->isPast();
                                        $isExhausted =
                                            $coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit;
                                    ?>

                                    <div class="card mb-3 border border-2 <?php echo e($coupon->is_active && !$isExpired && !$isExhausted ? 'border-primary' : 'border-secondary'); ?>"
                                        style="border-radius: 0.75rem; overflow: hidden;">
                                        <div class="card-body p-4">
                                            <!-- الصف الأول: العنوان والحالة -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <h5 class="mb-0 fw-bold text-dark">
                                                            <?php echo e($coupon->name); ?>

                                                        </h5>
                                                        <i class="ti ti-discount-2 text-primary fs-4"></i>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="text-muted small">الكود:</span>
                                                        <code class="bg-secondary px-2 py-1 rounded fw-bold"
                                                            style="font-size: 14px;"><?php echo e($coupon->code); ?></code>
                                                        <i class="ti ti-copy text-primary cursor-pointer"
                                                            onclick="navigator.clipboard.writeText('<?php echo e($coupon->code); ?>')"
                                                            title="نسخ الكود"></i>
                                                    </div>
                                                </div>

                                                <div class="text-end">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$coupon->is_active): ?>
                                                        <span class="badge bg-secondary rounded-pill mb-1">
                                                            <i class="ti ti-circle-x me-1"></i>
                                                            غير نشط
                                                        </span>
                                                    <?php elseif($isExpired): ?>
                                                        <span class="badge bg-danger rounded-pill mb-1">
                                                            <i class="ti ti-alert-circle me-1"></i>
                                                            منتهي
                                                        </span>
                                                    <?php elseif($isExhausted): ?>
                                                        <span class="badge bg-warning rounded-pill mb-1">
                                                            <i class="ti ti-alert-triangle me-1"></i>
                                                            مستنفذ
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success rounded-pill mb-1">
                                                            <i class="ti ti-circle-check me-1"></i>
                                                            نشط
                                                        </span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>

                                            <hr class="my-3">

                                            <!-- الصف الثاني: المعلومات الأساسية -->
                                            <div class="row g-4">
                                                <!-- قيمة الخصم -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-percentage text-success fs-5"></i>
                                                        <small class="text-muted">قيمة الخصم</small>
                                                    </div>
                                                    <div class="fw-bold fs-5 text-success">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($coupon->discount_type === 'fixed'): ?>
                                                            <?php echo e(number_format($coupon->discount_value, 0)); ?> جنيه
                                                        <?php else: ?>
                                                            <?php echo e(number_format($coupon->discount_value, 0)); ?>%
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- حد الاستخدام -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-users text-info fs-5"></i>
                                                        <small class="text-muted">حد الاستخدام</small>
                                                    </div>
                                                    <div
                                                        class="fw-bold fs-5 <?php echo e($isExhausted ? 'text-danger' : 'text-info'); ?>">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($coupon->usage_limit): ?>
                                                            <?php echo e($coupon->used_count); ?> / <?php echo e($coupon->usage_limit); ?>

                                                            <i class="ti ti-user-check fs-6"></i>
                                                        <?php else: ?>
                                                            غير محدود
                                                            <i class="ti ti-infinity fs-6"></i>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- عدد مرات الاستخدام -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-chart-bar text-warning fs-5"></i>
                                                        <small class="text-muted">عدد مرات الاستخدام</small>
                                                    </div>
                                                    <div class="fw-bold fs-5 text-warning">
                                                        <?php echo e($coupon->used_count); ?>

                                                        <i class="ti ti-ticket fs-6"></i>
                                                    </div>
                                                </div>

                                                <!-- تاريخ الانتهاء -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i
                                                            class="ti ti-calendar-time <?php echo e($isExpired ? 'text-danger' : 'text-primary'); ?> fs-5"></i>
                                                        <small class="text-muted">تاريخ الانتهاء</small>
                                                    </div>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiryDate): ?>
                                                        <div
                                                            class="fw-bold fs-6 <?php echo e($isExpired ? 'text-danger' : 'text-primary'); ?>">
                                                            <?php echo e($expiryDate->locale('ar')->isoFormat('D MMMM YYYY')); ?>

                                                        </div>
                                                        <small class="text-muted">
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isExpired): ?>
                                                                منذ <?php echo e($expiryDate->locale('ar')->diffForHumans()); ?>

                                                            <?php else: ?>
                                                                <?php echo e($expiryDate->locale('ar')->diffForHumans()); ?>

                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <div class="fw-bold fs-6 text-success">
                                                            بدون تاريخ انتهاء
                                                            <i class="ti ti-infinity"></i>
                                                        </div>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($coupon->max_discount): ?>
                                                <div class="alert alert-info mt-3 mb-0 py-2" role="alert">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    <small>الحد الأقصى للخصم:
                                                        <strong><?php echo e(number_format($coupon->max_discount, 0)); ?></strong>
                                                        جنيه</small>
                                                </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                            <!-- التواريخ السفلية -->
                                            <div
                                                class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                                <small class="text-muted">
                                                    <i class="ti ti-calendar-plus me-1"></i>
                                                    تاريخ الإنشاء:
                                                    <span
                                                        class="fw-semibold"><?php echo e($coupon->created_at->locale('ar')->isoFormat('D MMMM YYYY')); ?></span>
                                                </small>

                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($coupon->updated_at && $coupon->updated_at->ne($coupon->created_at)): ?>
                                                    <small class="text-muted">
                                                        <i class="ti ti-refresh me-1"></i>
                                                        آخر تحديث:
                                                        <span
                                                            class="fw-semibold"><?php echo e($coupon->updated_at->locale('ar')->diffForHumans()); ?></span>
                                                    </small>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="ti ti-ticket-off text-muted"
                                                style="font-size: 80px; opacity: 0.3;"></i>
                                        </div>
                                        <h5 class="text-muted mb-2">لا توجد كوبونات</h5>
                                        <p class="text-muted small">لم يتم إسناد أي كوبونات لهذا المستخدم حتى الآن</p>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ قسم الكوبونات -->

            </div>



        </div>


    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/users/show.blade.php ENDPATH**/ ?>