@php
    $isPendingRow = ($isPending ?? false) || ($row->status->value ?? $row->status) === 'pending';
    $companyName = $row->tenant->company_name ?? $row->tenant_id ?? '';
@endphp

<div class="d-flex justify-content-center gap-2">
    @if ($isPendingRow)
        <button type="button"
                class="btn btn-icon btn-label-success js-subscription-action"
                title="قبول الاشتراك"
                data-bs-toggle="tooltip"
                data-action="accept"
                data-subscription-id="{{ $row->id }}"
                data-company-name="{{ $companyName }}">
            <i class="ti ti-check"></i>
        </button>

        <button type="button"
                class="btn btn-icon btn-label-danger js-subscription-action"
                title="رفض الاشتراك"
                data-bs-toggle="tooltip"
                data-action="reject"
                data-subscription-id="{{ $row->id }}"
                data-company-name="{{ $companyName }}">
            <i class="ti ti-x"></i>
        </button>
    @endif
</div>
