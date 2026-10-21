<?php
    $tenantObj = tenant();
    $daysRemaining = null;
    $currentPeriod = null;

    if ($tenantObj && auth()->check()) {
        $subscription = cache()->remember(
            "tenant:{$tenantObj->id}:subscription",
            now()->addMinutes(5),
            fn() => \Stancl\Tenancy\Facades\Tenancy::central(fn() => $tenantObj->lastSubscription),
        );

        if ($subscription && $subscription->status === \App\Enums\Admin\Subscriptions\SubscriptionStatus::ACTIVE) {
            $currentPeriod = $subscription->currentPeriod;
            if ($currentPeriod) {
                $daysRemaining = app(
                    \App\Services\shared\Subscription\SubscriptionManager::class,
                )->calculateDaysRemaining($currentPeriod);
            }
        }
    }
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($daysRemaining !== null && $daysRemaining > 0 && $daysRemaining <= 30): ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($daysRemaining > 7): ?>
        
        <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
            <span class="alert-icon rounded">
                <i class="ti ti-bell-ringing ti-md"></i>
            </span>
            <div class="d-flex flex-column flex-grow-1">
                <h6 class="alert-heading mb-1 text-warning fw-bold">تنبيه باقتراب موعد تجديد الاشتراك</h6>
                <span>
                    ينتهي اشتراك الشركة الحالي خلال <strong><?php echo e($daysRemaining); ?></strong> يوماً (بتاريخ
                    <strong><?php echo e(\Carbon\Carbon::parse($currentPeriod->period_end)->format('Y-m-d')); ?></strong>). يرجى
                    التجديد لضمان استمرار عمل الخدمات دون انقطاع.
                </span>
            </div>
            <div class="text-nowrap">
                <a href="<?php echo e(tenant_route_name('subscriptions.index')); ?>" class="btn btn-warning">
                    <i class="ti ti-refresh me-1"></i> تجديد الاشتراك
                </a>
            </div>
            <button type="button" class="btn-close position-relative" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
    <?php else: ?>
        
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-4 shadow-sm border border-danger"
            role="alert">
            <span class="alert-icon rounded bg-danger text-white p-1">
                <i class="ti ti-alert-triangle ti-md"></i>
            </span>
            <div class="d-flex flex-column flex-grow-1">
                <h6 class="alert-heading mb-1 text-danger fw-bold">تحذير عاجل: اشتراك الشركة شارف على الانتهاء!</h6>
                <span>
                    متبقي على انتهاء الاشتراك <strong><?php echo e($daysRemaining); ?></strong>
                    <?php echo e($daysRemaining == 1 ? 'يوم واحد' : ($daysRemaining == 2 ? 'يومان' : 'أيام')); ?> فقط (بتاريخ
                    <strong><?php echo e(\Carbon\Carbon::parse($currentPeriod->period_end)->format('Y-m-d')); ?></strong>). سيتم
                    إيقاف النظام وتعليق وصول الموظفين فور انتهاء الصلاحية.
                </span>
            </div>
            <div class="text-nowrap">
                <a href="<?php echo e(tenant_route_name('subscriptions.index')); ?>" class="btn btn-danger">
                    <i class="ti ti-bolt me-1"></i> تجديد الاشتراك فوراً
                </a>
            </div>
            <button type="button" class="btn-close position-relative" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::is('*dashboard*')): ?>
            <div class="modal fade" id="subscriptionExpiringModal" data-bs-backdrop="static" data-bs-keyboard="false"
                tabindex="-1" aria-labelledby="subscriptionExpiringModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center p-3 shadow-lg border-danger">
                        <div class="modal-body">
                            <div class="d-flex flex-column align-items-center gap-5">
                                <div class="avatar avatar-xl">
                                    <span class="avatar-initial rounded-circle text-danger"><i
                                            class="ti ti-alert-triangle fs-2"></i></span>
                                </div>
                                <h4 class="modal-title fw-bold text-danger" id="subscriptionExpiringModalLabel">تحذير
                                    عاجل: اقتراب انتهاء الاشتراك</h4>
                                <p class="fs-6 mb-0">
                                    <span class="d-block mb-2">
                                        متبقي على انتهاء اشتراك الشركة <strong><?php echo e($daysRemaining); ?></strong>
                                        <?php echo e($daysRemaining == 1 ? 'يوم واحد' : ($daysRemaining == 2 ? 'يومان' : 'أيام')); ?>

                                        فقط (بتاريخ
                                        <strong><?php echo e(\Carbon\Carbon::parse($currentPeriod->period_end)->format('Y-m-d')); ?></strong>).
                                    </span>
                                    <span class="text-danger fw-semibold">سيتم إيقاف النظام وتعليق وصول الموظفين فور
                                        انتهاء الصلاحية.</span>
                                </p>
                                <div class="w-100 d-flex flex-column gap-2">
                                    <a href="<?php echo e(tenant_route_name('subscriptions.index')); ?>" class="btn btn-danger">
                                        <i class="ti ti-bolt me-1"></i> تجديد الاشتراك فوراً
                                    </a>
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">تذكيرى
                                        لاحقاً</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const reminderKey = 'subscription_reminder_postponed_until_<?php echo e($tenantObj->id); ?>';
                    const postponedUntil = localStorage.getItem(reminderKey);

                    // show modal again after 24 hours
                    if (!postponedUntil || Date.now() > parseInt(postponedUntil, 10)) {
                        var modalEl = document.getElementById('subscriptionExpiringModal');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            var modal = new bootstrap.Modal(modalEl);
                            modal.show();

                            modalEl.addEventListener('hidden.bs.modal', function() {
                                // 24 hours (24 * 60 * 60 * 1000 milliseconds)
                                const nextReminderTime = Date.now() + (24 * 60 * 60 * 1000);
                                localStorage.setItem(reminderKey, nextReminderTime.toString());
                            });
                        }
                    }
                });
            </script>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\Levan\old\resources\views/tenant/layouts/sections/subscription_alert.blade.php ENDPATH**/ ?>