<?php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;

    $containerNav =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'wide'
            ? 'container-fluid'
            : 'container-xxl';
    $navbarDetached = $navbarDetached ?? '';
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($navbarDetached) && $navbarDetached == 'navbar-detached'): ?>
    <nav class="layout-navbar <?php echo e($containerNav); ?> navbar navbar-expand-xl <?php echo e($navbarDetached); ?> align-items-center bg-navbar-theme"
        id="layout-navbar">
    <?php else: ?>
        <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
            <div class="<?php echo e($containerNav); ?>">
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($navbarHideToggle)): ?>
    <div
        class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0<?php echo e(isset($menuHorizontal) ? ' d-xl-none' : ''); ?><?php echo e(isset($contentNavbar) ? ' d-xl-none' : ''); ?>">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0);">
            <i class="ti ti-menu-2 ti-md"></i>
        </a>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($menuHorizontal)): ?>
        <!-- Search -->
        <div class="navbar-nav align-items-center d-none d-md-inline-block">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
                    <i class="ti ti-search ti-md me-2 me-lg-4 ti-lg"></i>
                    <span class="d-none d-md-inline-block text-muted fw-normal">ابحـث (Ctrl+/)</span>
                </a>
            </div>
        </div>
        <!-- /Search -->
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($menuHorizontal)): ?>
            <li class="nav-item navbar-search-wrapper">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill search-toggler"
                    href="javascript:void(0);">
                    <i class="ti ti-search ti-md"></i>
                </a>
            </li>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('domain.view')): ?>
            <li class="nav-item dropdown me-2 me-xl-2">
                <a href="<?php echo e(tenant_route_name('domains.setting')); ?>" class="menu-link" title="إدارة النطاقات"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="menu-icon tf-icons ti ti-world"></i>
                </a>
            </li>
        <?php endif; ?>



        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['system.settings', 'users.view', 'roles.view', 'activity_log.view', 'message_logs.view',
            'countries.view', 'payment_methods.view', 'client_statuses.view', 'contact_channels.view',
            'message_templates.view', 'subscriptions.view', 'bills.view', 'system.groups'])): ?>
            <li class="nav-item dropdown me-2 me-xl-2">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="ti ti-settings ti-md"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-3 nav-min-width">
                    <div class="row g-2">
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('system.settings')): ?>
                            <div class="col-12">
                                <a href="<?php echo e(tenant_route_name('settings.index')); ?>"
                                    class="d-flex align-items-center p-3 border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md me-3">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti ti-settings ti-lg"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-medium">إعدادات النظام</h6>
                                        <small class="text-muted">إدارة الإعدادات الأساسية للنظام</small>
                                    </div>
                                    <i class="ti ti-chevron-left text-muted"></i>
                                </a>
                            </div>
                        <?php endif; ?>

                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.view')): ?>
                            <div class="col-6">
                                <a href="<?php echo e(tenant_route_name('users.index')); ?>"
                                    class="d-block p-3 text-center border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class="ti ti-users ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">المستخدمين</small>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles.view')): ?>
                            <div class="col-6">
                                <a href="<?php echo e(tenant_route_name('roles.index')); ?>"
                                    class="d-block p-3 text-center border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class="ti ti-shield-lock ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">الأنظمة الأمنية</small>
                                </a>
                            </div>
                        <?php endif; ?>

                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_log.view')): ?>
                            <div class="col-6">
                                <a href="<?php echo e(tenant_route_name('activity-logs.index')); ?>"
                                    class="d-block p-3 text-center border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class="ti ti-activity ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">سجل النشاطات</small>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('message_logs.view')): ?>
                            <div class="col-6">
                                <a href="<?php echo e(tenant_route_name('communication.message-individual-logs.index')); ?>"
                                    class="d-block p-3 text-center border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class="ti ti-message ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">سجل الرسائل</small>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('subscriptions.view')): ?>
                            <div class="col-6">
                                <a href="<?php echo e(tenant_route_name('subscriptions.index')); ?>"
                                    class="d-block p-3 text-center border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-secondary">
                                            <i class="ti ti-settings-cog ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">إدارة الإشتراك</small>
                                </a>
                            </div>
                        <?php endif; ?>


                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bills.view')): ?>
                            <div class="col-12">
                                <a href="<?php echo e(tenant_route_name('bills.index')); ?>"
                                    class="d-block p-3 text-center border rounded hover-bg-secondary text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti ti-receipt ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">الفواتير</small>
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>

                    
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['countries.view', 'payment_methods.view', 'client_statuses.view', 'contact_channels.view',
                        'message_templates.view'])): ?>
                        <div class="dropdown-divider my-3"></div>
                        <div class="d-grid">
                            <a href="<?php echo e(tenant_route_name('settings.center')); ?>" class="btn btn-primary btn-sm">
                                <i class="ti ti-adjustments me-2"></i>
                                الإعدادات التفصيلية
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endif; ?>
        

        <!-- Notification -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2 me-xl-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                id="notificationDropdown">
                <span class="position-relative">
                    <i class="ti ti-bell ti-md"></i>
                    <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"
                        style="<?php echo e((auth()->user()?->unreadNotifications?->count() ?? 0) > 0 ? '' : 'display: none;'); ?>"></span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 350px;">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h6 class="mb-0 me-auto fw-bold">الإشعارات</h6>
                        <div class="d-flex align-items-center h6 mb-0">
                            <span class="badge bg-label-primary me-2" id="unreadCountBadge">
                                <?php echo e(auth()->user()?->unreadNotifications?->count() ?? 0); ?> جديد
                            </span>
                            <a href="javascript:void(0)" onclick="markAllAsRead()"
                                class="btn btn-text-secondary rounded-pill btn-icon" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="تحديد الكل كمقروء">
                                <i class="ti ti-mail-opened text-heading"></i>
                            </a>
                        </div>
                    </div>
                </li>

                <li class="dropdown-notifications-list scrollable-container overflow-auto" style="max-height: 300px;">
                    <ul class="list-group list-group-flush" id="notificationsList">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = auth()->user()?->notifications()->take(10)->get() ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $data = $notification->data;
                                $iconColors = [
                                    'success' => 'bg-label-success',
                                    'warning' => 'bg-label-warning',
                                    'info' => 'bg-label-info',
                                    'danger' => 'bg-label-danger',
                                    'primary' => 'bg-label-primary',
                                    'secondary' => 'bg-secondary',
                                ];
                                $colorClass = $iconColors[$data['type'] ?? 'info'] ?? 'bg-label-info';
                            ?>

                            <li class="list-group-item list-group-item-action dropdown-notifications-item <?php echo e($notification->read_at ? 'marked-as-read' : ''); ?>"
                                data-notification-id="<?php echo e($notification->id); ?>">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle <?php echo e($colorClass); ?>">
                                                <i class="<?php echo e($data['icon'] ?? 'ti-bell'); ?>"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1"
                                        <?php if(!empty($data['url'])): ?> onclick="window.location.href='<?php echo e($data['url']); ?>'" style="cursor: pointer;" <?php endif; ?>>
                                        <h6 class="small mb-1 fw-semibold"><?php echo e($data['title'] ?? 'إشعار'); ?></h6>
                                        <p class="mb-1 small text-muted"><?php echo e($data['message'] ?? ''); ?></p>
                                        <small
                                            class="text-muted"><?php echo e($notification->created_at->diffForHumans()); ?></small>
                                    </div>
                                    <div class="flex-shrink-0 dropdown-notifications-actions">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$notification->read_at): ?>
                                            <span class="badge badge-dot bg-primary"></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <a href="javascript:void(0)"
                                            onclick="deleteNotification('<?php echo e($notification->id); ?>')"
                                            class="dropdown-notifications-archive ms-2">
                                            <span class="ti ti-x"></span>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <li class="list-group-item text-center py-4">
                                <i class="ti ti-bell-off ti-lg text-muted mb-2"></i>
                                <p class="text-muted mb-0">لا توجد إشعارات</p>
                            </li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                </li>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->notifications()->count() > 0): ?>
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex"
                                href="<?php echo e(tenant_route_name('notifications.all')); ?>">
                                <small class="align-middle">عرض كل الإشعارات</small>
                            </a>
                        </div>
                    </li>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </ul>
        </li>
        <!-- /Notification -->

        

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">

                    <img src="<?php echo e(tasset(Auth::user()->image)); ?>" alt="<?php echo e(Auth::user()->name); ?>"
                        class="rounded-circle" width="40" height="40">

                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item mt-0" href="<?php echo e(tenant_route_name('users.profile')); ?>">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-online me-2">
                                <img src="<?php echo e(tasset(Auth::user()->image)); ?>" alt="<?php echo e(Auth::user()->name); ?>"
                                    class="rounded-circle" width="40" height="40">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?php echo e(Auth::user()->name); ?></h6>
                                <small class="text-muted"><?php echo e(Auth::user()->getRoleNames()->implode(', ')); ?></small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo e(tenant_route_name('users.profile')); ?>">
                        <i class="ti ti-user me-3 ti-md"></i>
                        <span class="align-middle">الملف الشخصي</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo e(tenant_route_name('users.password.change')); ?>">
                        <i class="ti ti-settings me-3 ti-md"></i>
                        <span class="align-middle">تغيير كلمة المرور</span>
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="<?php echo e(tenant_route_name('tenants.two-factor.index')); ?>">
                        <i class="ti ti-shield-lock me-3 ti-md"></i>
                        <span class="align-middle">المصادقة الثنائية</span>
                    </a>
                </li>

                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <div class="d-grid px-2 pt-2 pb-1">

                        <button type="button" id="tenant-logout-btn"
                            class="btn btn-sm btn-danger d-flex align-items-center justify-content-center"
                            onclick="confirmLogout('tenant-logout-form')">
                            <small class="align-middle">تسجيل الخروج</small>
                            <i class="ti ti-logout ms-2 ti-14px"></i>
                        </button>

                        <form id="tenant-logout-form" method="POST" action="<?php echo e(tenant_route_name('logout')); ?>">
                            <?php echo csrf_field(); ?>
                        </form>
                    </div>
                </li>

            </ul>
        </li>
        <!--/ User -->
    </ul>
</div>

<!-- Search Small Screens -->
<div class="navbar-search-wrapper search-input-wrapper <?php echo e(isset($menuHorizontal) ? $containerNav : ''); ?> d-none">
    <input type="text"
        class="form-control search-input <?php echo e(isset($menuHorizontal) ? '' : $containerNav); ?> border-0"
        placeholder="بحث..." aria-label="بحث...">
    <i class="ti ti-x search-toggler cursor-pointer"></i>
</div>
<!--/ Search Small Screens -->
</nav>
<?php /**PATH D:\Levan\old\resources\views/tenant/layouts/sections/navbar/navbar.blade.php ENDPATH**/ ?>