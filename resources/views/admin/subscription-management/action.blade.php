@php
    $isPendingRow = ($isPending ?? false) || ($row->status->value ?? $row->status) === 'pending';
    $companyName = $row->tenant->company_name ?? $row->tenant_id ?? '';
@endphp

<div class="d-flex justify-content-center gap-2">
    @if ($isPendingRow)
        @can('subscriptions.pending.approve')
        <button type="button"
                class="btn btn-icon btn-success js-subscription-action"
                title="قبول الاشتراك"
                data-bs-toggle="tooltip"
                data-action="accept"
                data-subscription-id="{{ $row->id }}"
                data-company-name="{{ $companyName }}">
            <i class="ti ti-check" style="pointer-events: none;"></i>
        </button>
        @endcan

        @can('subscriptions.pending.reject')
        <button type="button"
                class="btn btn-icon btn-danger js-subscription-action"
                title="رفض الاشتراك"
                data-bs-toggle="tooltip"
                data-action="reject"
                data-subscription-id="{{ $row->id }}"
                data-company-name="{{ $companyName }}">
            <i class="ti ti-x" style="pointer-events: none;"></i>
        </button>
        @endcan
    @endif
</div>
