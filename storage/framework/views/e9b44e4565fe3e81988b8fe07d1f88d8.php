<?php
    use Illuminate\Support\Facades\Route;
    $configData = App\Helpers\Helpers::appClasses();
    use App\Helpers\Helpers;
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($navbarFull)): ?>
        <div class="app-brand demo">
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="app-brand-link">
                <?php if (isset($component)) { $__componentOriginal317330454e2f73a7713e7f2849f4760c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal317330454e2f73a7713e7f2849f4760c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.partials.admin-logo','data' => ['height' => 32,'logoPath' => asset('static/brand/logos/logo-light.svg')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('partials.admin-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['height' => 32,'logo-path' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(asset('static/brand/logos/logo-light.svg'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal317330454e2f73a7713e7f2849f4760c)): ?>
<?php $attributes = $__attributesOriginal317330454e2f73a7713e7f2849f4760c; ?>
<?php unset($__attributesOriginal317330454e2f73a7713e7f2849f4760c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal317330454e2f73a7713e7f2849f4760c)): ?>
<?php $component = $__componentOriginal317330454e2f73a7713e7f2849f4760c; ?>
<?php unset($__componentOriginal317330454e2f73a7713e7f2849f4760c); ?>
<?php endif; ?>
                <span class="app-brand-text demo menu-text fw-bold">
                    <?php echo e(App\Helpers\Admin\Settings\AdminSettingsHelper::get('logo_text')); ?>

                </span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ti menu-toggle-icon d-none d-xl-block align-middle"></i>
                <i class="ti ti-x d-block d-xl-none ti-md align-middle"></i>
            </a>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">

        <li class="menu-header small">
            <span class="menu-header-text">الرئيسية</span>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dashboard.main')): ?>
            <li class="menu-item <?php if(Route::is('admin.dashboard')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
                    <div>لوحة القيادة</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.users.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.users.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-user-cog"></i>
                    <div>إدارة المستخدمين</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.roles.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.roles.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-shield-lock"></i>
                    <div>إدارة الأدوار والصلاحيات</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()?->can('clients.view') || auth()->user()?->can('companies.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.clients.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.clients.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-building"></i>
                    <div>إدارة العملاء</div>
                </a>
            </li>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <li class="menu-header small">
            <span class="menu-header-text">القوالب</span>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('auction_templates.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.auction-templates.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.auction-templates.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-template"></i>
                    <div>قوالب المزادات</div>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header small">
            <span class="menu-header-text">الباقات والعروض</span>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('plans.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.plans.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.plans.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-package"></i>
                    <div>إدارة الباقات</div>
                </a>
            </li>
        <?php endif; ?>


        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('coupons.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.coupons.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.coupons.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-ticket"></i>
                    <div>إدارة الكوبونات</div>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header small">
            <span class="menu-header-text">الإشتراكات والمالية</span>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('subscriptions.view')): ?>
            <?php
                $expiringCount = cache()->remember('admin:expiring_subscriptions_count', 60, function () {
                    return app(
                        \App\Services\Admin\SubscriptionManagement\SubscriptionManagementService::class,
                    )->getExpiringSoonCount();
                });
                $pendingCount = cache()->remember('admin:pending_subscriptions_count', 60, function () {
                    return \App\Models\Admin\Subscription\Subscription::where(
                        'status',
                        \App\Enums\Admin\Subscriptions\SubscriptionStatus::PENDING,
                    )->count();
                });
                $expiredCount = cache()->remember('admin:expired_subscriptions_count', 60, function () {
                    return \App\Models\Admin\Subscription\Subscription::where(
                        'status',
                        \App\Enums\Admin\Subscriptions\SubscriptionStatus::EXPIRED,
                    )->count();
                });
                $totalBadgeCount = $expiringCount + $pendingCount + $expiredCount;
            ?>
            <li class="menu-item <?php if(Route::is('admin.subscriptions.*') || Route::is('admin.subscriptionManage.*')): ?> active open <?php endif; ?>">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-file-certificate"></i>
                    <div>إدارة الاشتراكات</div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($totalBadgeCount > 0): ?>
                        <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($totalBadgeCount); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item <?php if(Route::is('admin.subscriptions.*')): ?> active <?php endif; ?>">
                        <a href="<?php echo e(route('admin.subscriptions.index')); ?>" class="menu-link">
                            <div>جميع الإشتراكات</div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingCount > 0): ?>
                                <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($pendingCount); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?php if(Route::is('admin.subscriptionManage.pending')): ?> active <?php endif; ?>">
                        <a href="<?php echo e(route('admin.subscriptionManage.pending')); ?>" class="menu-link">
                            <div>الطلبات</div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingCount > 0): ?>
                                <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($pendingCount); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?php if(Route::is('admin.subscriptionManage.expiring')): ?> active <?php endif; ?>">
                        <a href="<?php echo e(route('admin.subscriptionManage.expiring')); ?>" class="menu-link">
                            <div>تقارب على الانتهاء</div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiringCount > 0): ?>
                                <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($expiringCount); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?php if(Route::is('admin.subscriptionManage.expired')): ?> active <?php endif; ?>">
                        <a href="<?php echo e(route('admin.subscriptionManage.expired')); ?>" class="menu-link">
                            <div>الاشتراكات المنتهية</div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiredCount > 0): ?>
                                <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($expiredCount); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('financial_transactions.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.bills.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.bills.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-credit-card"></i>
                    <div>إدارة المعاملات المالية</div>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header small">
            <span class="menu-header-text">الدعم والطلبات</span>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('tickets.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.tickets.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.tickets.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-messages"></i>
                    <div>إدارة التذاكر</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('contact_requests.view')): ?>
            <?php
                $pendingContactRequestsCount = cache()->remember(
                    'admin:pending_contact_requests_count',
                    60,
                    function () {
                        return \App\Models\Admin\PublicRequests\ContactRequest::where(
                            'status',
                            \App\Enums\Admin\PublicRequests\ContactRequestStatus::PENDING,
                        )->count();
                    },
                );
            ?>
            <li class="menu-item <?php if(Route::is('admin.contact-requests.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.contact-requests.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-mail"></i>
                    <div>طلبات التواصل</div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingContactRequestsCount > 0): ?>
                        <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($pendingContactRequestsCount); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('trial_requests.view')): ?>
            <?php
                $pendingTrialRequestsCount = cache()->remember('admin:pending_trial_requests_count', 60, function () {
                    return \App\Models\Admin\PublicRequests\TrialRequest::where(
                        'status',
                        \App\Enums\Admin\PublicRequests\TrialRequestStatus::PENDING,
                    )->count();
                });
            ?>
            <li class="menu-item <?php if(Route::is('admin.trial-requests.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.trial-requests.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-clock"></i>
                    <div>طلبات التجربة</div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingTrialRequestsCount > 0): ?>
                        <div class="badge bg-label-warning rounded-pill ms-auto"><?php echo e($pendingTrialRequestsCount); ?></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </a>
            </li>
        <?php endif; ?>


        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['system.settings', 'bank_settings.view', 'domain_extension_prices.settings', 'faqs.view'])): ?>
            <li class="menu-header small">
                <span class="menu-header-text">إعدادات المنصة</span>
            </li>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('system.settings')): ?>
                <li class="menu-item <?php if(Route::is('admin.settings.index')): ?> active open <?php endif; ?>">
                    <a href="<?php echo e(route('admin.settings.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-settings"></i>
                        <div>الإعدادات العامة</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bank_settings.view')): ?>
                <li class="menu-item <?php if(Route::is('admin.settings.banks.*')): ?> active open <?php endif; ?>">
                    <a href="<?php echo e(route('admin.settings.banks.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-building-bank"></i>
                        <div>الحسابات البنكية</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('domain_extension_prices.settings')): ?>
                <li class="menu-item <?php if(Route::is('admin.settings.domains.*')): ?> active open <?php endif; ?>">
                    <a href="<?php echo e(route('admin.settings.domains.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-world"></i>
                        <div>إعدادات النطاقات</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('faqs.view')): ?>
                <li class="menu-item <?php if(Route::is('admin.faqs.*')): ?> active open <?php endif; ?>"><a
                        href="<?php echo e(route('admin.faqs.index')); ?>" class="menu-link"><i
                            class="menu-icon tf-icons ti ti-help"></i>
                        <div>الأسئلة الشائعة</div>
                    </a></li>
            <?php endif; ?>
        <?php endif; ?>

        <li class="menu-header small">
            <span class="menu-header-text">النشاطات</span>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_log.view')): ?>
            <li class="menu-item <?php if(Route::is('admin.activity-logs.*')): ?> active open <?php endif; ?>">
                <a href="<?php echo e(route('admin.activity-logs.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-history"></i>
                    <div>سجل النشاطات</div>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header small">
            <span class="menu-header-text">المحذوفات</span>
        </li>

        <li class="menu-item <?php if(Route::is('admin.archives.*')): ?> active open <?php endif; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons ti ti-trash"></i>
                <div>البيانات المحذوفة</div>
            </a>
            <ul class="menu-sub">

                <li class="menu-item <?php if(Route::is('admin.archives.index')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(route('admin.archives.index')); ?>" class="menu-link">
                        <div>المستخدمون</div>
                    </a>
                </li>

                <li class="menu-item <?php if(Route::is('admin.archives.plans.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(route('admin.archives.plans.index')); ?>" class="menu-link">
                        <div>الباقات</div>
                    </a>
                </li>

                <li class="menu-item <?php if(Route::is('admin.archives.coupons.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(route('admin.archives.coupons.index')); ?>" class="menu-link">
                        <div>الكوبونات</div>
                    </a>
                </li>

                <li class="menu-item <?php if(Route::is('admin.archives.tenants.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(route('admin.archives.tenants.index')); ?>" class="menu-link">
                        <div>العملاء المؤرشفون</div>
                    </a>
                </li>

                <li class="menu-item <?php if(Route::is('admin.archives.bills.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(route('admin.archives.bills.index')); ?>" class="menu-link">
                        <div>المعاملات المالية</div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
<?php /**PATH D:\Levan\resources\views/admin/layouts/sections/menu/verticalMenu.blade.php ENDPATH**/ ?>