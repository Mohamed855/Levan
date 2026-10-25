<?php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $configData = App\Helpers\Helpers::appClasses();
    use App\Helpers\Helpers;
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($navbarFull)): ?>
        <div class="app-brand demo">
            <a href="<?php echo e(tenant_route_name('dashboard')); ?>" class="app-brand-link">
                <?php if (isset($component)) { $__componentOriginal82fdf6e4f5ef629b82b3f1c000053808 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal82fdf6e4f5ef629b82b3f1c000053808 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.partials.tenant-logo','data' => ['height' => 32,'logoPath' => asset('static/brand/logos/logo-light.svg')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('partials.tenant-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['height' => 32,'logo-path' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(asset('static/brand/logos/logo-light.svg'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal82fdf6e4f5ef629b82b3f1c000053808)): ?>
<?php $attributes = $__attributesOriginal82fdf6e4f5ef629b82b3f1c000053808; ?>
<?php unset($__attributesOriginal82fdf6e4f5ef629b82b3f1c000053808); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal82fdf6e4f5ef629b82b3f1c000053808)): ?>
<?php $component = $__componentOriginal82fdf6e4f5ef629b82b3f1c000053808; ?>
<?php unset($__componentOriginal82fdf6e4f5ef629b82b3f1c000053808); ?>
<?php endif; ?>
                <span class="app-brand-text demo menu-text fw-bold">
                    <?php echo e(App\Helpers\Tenants\Settings\SettingsHelper::get('logo_text')); ?>

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

        
        <li class="menu-item <?php if(Helpers::isActiveRoute('dashboard')): ?> active open <?php endif; ?>">
            <a href="<?php echo e(tenant_route_name('dashboard')); ?>" class="menu-link">
                <i class="menu-icon tf-icons ti ti-smart-home"></i>
                <div>لوحة القيادة</div>
            </a>
        </li>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['users.view', 'roles.view'])): ?>
            <li class="menu-header small">
                <span class="menu-header-text">الشركة وفريق العمل</span>
            </li>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('users.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('users.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-users"></i>
                        <div>المستخدمين</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('roles.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('roles.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-shield-lock"></i>
                        <div>الأدوار والصلاحيات</div>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['assignments.view', 'properties.view', 'auctions.view'])): ?>
            <li class="menu-header small">
                <span class="menu-header-text">المزادات والعقارات</span>
            </li>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('assignments.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('assignments.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('assignments.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-file-certificate"></i>
                        <div>الإسنادات</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('properties.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('properties.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('properties.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-building"></i>
                        <div>العقارات</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('auctions.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('auctions.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('auctions.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-gavel"></i>
                        <div>المزادات</div>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['subscriptions.view', 'bills.view', 'domain.view'])): ?>
            <li class="menu-header small">
                <span class="menu-header-text">الاشتراك والفوترة</span>
            </li>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('subscriptions.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('subscriptions.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('subscriptions.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-award"></i>
                        <div>الاشتراك والتراخيص</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bills.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('bills.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('bills.index')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-credit-card"></i>
                        <div>الفواتير</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('domain.view')): ?>
                <li class="menu-item <?php if(Helpers::isActiveRoute('domains.*')): ?> active <?php endif; ?>">
                    <a href="<?php echo e(tenant_route_name('domains.setting')); ?>" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-world"></i>
                        <div>إدارة النطاقات</div>
                    </a>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        
        <li class="menu-header small">
            <span class="menu-header-text">النظام والدعم</span>
        </li>

        <li class="menu-item <?php if(Helpers::isActiveRoute('tickets.*')): ?> active <?php endif; ?>">
            <a href="<?php echo e(tenant_route_name('tickets.index')); ?>" class="menu-link">
                <i class="menu-icon tf-icons ti ti-headset"></i>
                <div>الدعم الفني</div>
            </a>
        </li>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_log.view')): ?>
            <li class="menu-item <?php if(Helpers::isActiveRoute('activity-logs.*')): ?> active <?php endif; ?>">
                <a href="<?php echo e(tenant_route_name('activity-logs.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-activity"></i>
                    <div>سجل النشاطات</div>
                </a>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['user_archive.view', 'assignments_archive.view', 'properties_archive.view', 'auctions_archive.view'])): ?>
            <li class="menu-item <?php if(Helpers::isActiveRoute('archive.*')): ?> active open <?php endif; ?>">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-trash"></i>
                    <div>البيانات المحذوفة</div>
                </a>
                <ul class="menu-sub">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_archive.view')): ?>
                        <li class="menu-item <?php if(Helpers::isActiveRoute('archive.users.*')): ?> active <?php endif; ?>">
                            <a href="<?php echo e(tenant_route_name('archive.users.index')); ?>" class="menu-link">
                                <div>المستخدمون</div>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('assignments_archive.view')): ?>
                        <li class="menu-item <?php if(Helpers::isActiveRoute('archive.assignments.*')): ?> active <?php endif; ?>">
                            <a href="<?php echo e(tenant_route_name('archive.assignments.index')); ?>" class="menu-link">
                                <div>الإسنادات</div>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('properties_archive.view')): ?>
                        <li class="menu-item <?php if(Helpers::isActiveRoute('archive.properties.*')): ?> active <?php endif; ?>">
                            <a href="<?php echo e(tenant_route_name('archive.properties.index')); ?>" class="menu-link">
                                <div>العقارات</div>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('auctions_archive.view')): ?>
                        <li class="menu-item <?php if(Helpers::isActiveRoute('archive.auctions.*')): ?> active <?php endif; ?>">
                            <a href="<?php echo e(tenant_route_name('archive.auctions.index')); ?>" class="menu-link">
                                <div>المزادات</div>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('system.settings')): ?>
            <li class="menu-item <?php if(Helpers::isActiveRoute('settings.*')): ?> active <?php endif; ?>">
                <a href="<?php echo e(tenant_route_name('settings.index')); ?>" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-settings"></i>
                    <div>إعدادات الشركة</div>
                </a>
            </li>
        <?php endif; ?>

    </ul>
</aside>
<?php /**PATH D:\Levan\resources\views/tenant/layouts/sections/menu/verticalMenu.blade.php ENDPATH**/ ?>