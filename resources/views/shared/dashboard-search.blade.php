@php
    $scope = $scope ?? (Auth::guard('admin')->check() ? 'admin' : (Auth::guard('tenant')->check() ? 'tenant' : null));
    $searchPages = app(\App\Services\Shared\Navigation\DashboardSearchService::class)->getSearchablePages($scope);
    $containerNavClass = isset($containerNav) && $containerNav ? $containerNav : 'container-xxl';
    $quickSearchUrl = $scope === 'admin'
        ? (Route::has('admin.quick-search') ? route('admin.quick-search') : '')
        : (function_exists('tenant_route_name') ? tenant_route_name('quick-search') : '');
@endphp

<!-- Global Search Input -->
<div class="navbar-search-wrapper search-input-wrapper {{ isset($menuHorizontal) ? $containerNavClass : '' }} d-none"
    data-search-url="{{ $quickSearchUrl }}">
    <input type="text"
        class="form-control search-input {{ isset($menuHorizontal) ? '' : $containerNavClass }} border-0"
        placeholder="ابحث عن صفحة... (مثلاً: عقارات، مزادات، مستخدمين)"
        aria-label="ابحث عن صفحة..."
        autocomplete="off"
        data-search-pages='{!! json_encode($searchPages, JSON_UNESCAPED_UNICODE) !!}'>
    <i class="ti ti-x search-toggler cursor-pointer" title="إغلاق البحث"></i>
</div>
<!--/ Global Search Input -->

<script>
    window.dashboardSearchPages = {!! json_encode($searchPages, JSON_UNESCAPED_UNICODE) !!};
</script>
