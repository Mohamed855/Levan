@php
    $isPendingRow = ($isPending ?? false) || ($row->status->value ?? $row->status) === 'pending';
    $companyName = $row->tenant->company_name ?? $row->tenant_id ?? '';
@endphp

<div class="d-flex justify-content-center gap-2">
    @if ($isPendingRow)
        <button type="button"
                class="btn btn-icon btn-label-success"
                title="قبول الاشتراك"
                data-bs-toggle="tooltip"
                onclick="openConfirmModal('accept', {{ $row->id }}, '{{ addslashes($companyName) }}')">
            <i class="ti ti-check"></i>
        </button>

        <button type="button"
                class="btn btn-icon btn-label-danger"
                title="رفض الاشتراك"
                data-bs-toggle="tooltip"
                onclick="openConfirmModal('reject', {{ $row->id }}, '{{ addslashes($companyName) }}')">
            <i class="ti ti-x"></i>
        </button>
    @else
        {{-- @can('subscriptions.edit') --}}
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-label-primary" title="تعديل" data-bs-toggle="tooltip">
            <i class="ti ti-edit"></i>
        </a>
        {{-- @endcan --}}

        {{-- @can('subscriptions.delete') --}}
        <button type="button" class="btn btn-icon btn-label-primary js-delete-btn" title="حذف" data-id="{{ $row->id }}" data-bs-toggle="tooltip">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
        {{-- @endcan --}}
    @endif
</div>
