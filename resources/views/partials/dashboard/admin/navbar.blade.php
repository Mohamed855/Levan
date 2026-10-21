<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" aria-label="شريط إدارة المنصة">
    <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100 px-3">
        <span class="fw-semibold">إدارة المنصة</span>
        <div class="dropdown">
            <button class="btn btn-text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-user-circle me-1"></i>{{ auth('admin')->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted">{{ auth('admin')->user()->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit"><i class="ti ti-logout me-2"></i>تسجيل الخروج</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
