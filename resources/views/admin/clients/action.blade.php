@php
    $route = 'admin.clients';
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

    @canany(['clients.delete', 'companies.delete'])
        <button type="button" class="btn btn-icon btn-primary bg-primary js-delete-btn" title="حذف"
            data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>
    @endcanany

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
</div>
