@php
    $isPendingSubscription = ($row->status->value ?? $row->status) === 'pending';
    $route = 'admin.companies';
@endphp

<div class="d-flex justify-content-center gap-2">
    @if ($isPendingSubscription)
        <span class="btn btn-icon" title="طلب اشتراك جديد ، برجاء مراجعة الطلب لعرض الإجراءات">
            <i class="ti ti-info-circle"></i>
        </span>
    @else
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-label-primary" title="تعديل">
            <i class="ti ti-edit"></i>
        </a>

        <button type="button" class="btn btn-icon btn-label-primary js-delete-btn" title="حذف"
            data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
