<div class="dropdown d-flex justify-content-center gap-2">
    <button type="button" class="btn btn-icon btn-label-primary dropdown-toggle-no-caret" data-bs-toggle="dropdown" aria-expanded="false"
        title="الإجراءات" aria-label="الإجراءات">
        <i class="ti ti-dots-vertical" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item text-dark" href="{{ tenant_route_name($route . '.show', $id) }}">
                <i class="ti ti-eye me-2"></i>عرض
            </a>
        </li>

        @if ($collection->status === 'pending')
            <li>
                <hr class="dropdown-divider">
            </li>

            @can('collections.approve')
                <li>
                    <a class="dropdown-item text-dark" href="#"
                        onclick="confirmApproveCollection({{ $id }})">
                        <i class="ti ti-check me-2"></i>اعتماد
                    </a>
                </li>
            @endcan

            @can('collections.reject')
                <li>
                    <a class="dropdown-item text-dark" href="#" onclick="showRejectModal({{ $id }})">
                        <i class="ti ti-x me-2"></i>رفض
                    </a>
                </li>
            @endcan

            <li>
                <hr class="dropdown-divider">
            </li>
        @endif

        @can('admin_collections.delete_selected')
            <li>
                <a class="dropdown-item text-dark" href="#" onclick="confirmDelete({{ $id }})">
                    <i class="ti ti-trash me-2"></i>حذف
                </a>
            </li>
        @endcan

    </ul>
</div>

<form id="delete-form-{{ $id }}" action="{{ tenant_route_name($route . '.destroy', $id) }}" method="POST"
    style="display: none;">
    @csrf
    @method('DELETE')
</form>
