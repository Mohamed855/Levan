<div class="d-flex justify-content-center gap-2">
    @can('خطابات الشركات')
    <a href="{{ tenant_route_name('company-letters.show', $id) }}" class="btn btn-sm btn-icon border-0 bg-transparent"
        title="عرض التفاصيل" data-bs-toggle="tooltip" data-bs-placement="top">
        <i class="ti ti-eye ti-sm"></i>
    </a>
    @endcan
</div>