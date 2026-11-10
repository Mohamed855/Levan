@php
    $route = 'admin.clients';
    $dashboardUrl = route('path.dashboard', ['tenant' => $row->id]);
@endphp

<div class="d-flex justify-content-center gap-2">
    @canany(['clients.show', 'companies.show'])
        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="عرض التفاصيل">
            <i class="ti ti-eye"></i>
        </a>
    @endcanany

    @canany(['clients.edit', 'companies.edit'])
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل">
            <i class="ti ti-edit"></i>
        </a>
    @endcanany

    <a href="{{ $dashboardUrl }}" class="btn btn-icon btn-primary bg-primary" title="لوحة تحكم العميل" target="_blank">
        <i class="ti ti-dashboard"></i>
    </a>

    @canany(['clients.delete', 'companies.delete'])
        <button type="button" class="btn btn-icon btn-primary bg-primary js-delete-btn" title="حذف"
            data-id="{{ $row->id }}" data-delete-url="{{ route($route . '.destroy', $row->id) }}"
            data-delete-title="هل أنت متأكد من حذف هذا العميل؟"
            data-delete-message="سيتم نقل العميل للأرشيف وحذف جميع اشتراكاته نهائياً."
            data-delete-confirm-text="نعم، حذف العميل">
            <i class="ti ti-trash"></i>
        </button>
    @endcanany

    <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
