@php
    $route = 'admin.companies';
    $dashboardUrl = url('/' . $row->id . '/dashboard');
@endphp

<div class="d-flex justify-content-center gap-1">
    @can('companies.show')
        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-icon btn-label-primary" title="عرض التفاصيل">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    @can('companies.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-sm btn-icon btn-label-primary" title="تعديل">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    <a href="{{ $dashboardUrl }}" class="btn btn-icon btn-label-primary" title="لوحة تحكم الشركة" target="_blank">
        <i class="ti ti-dashboard"></i>
    </a>

    @can('companies.delete')
        <button type="button" class="btn btn-icon btn-label-primary js-delete-btn" title="حذف"
            data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</div>
