<div class="dropdown">
    <button type="button" class="btn btn-link p-0 dropdown-toggle-no-caret" data-bs-toggle="dropdown" aria-expanded="false"
        style="border: none; background: none;">
        <i class="ti ti-dots-vertical" style="font-size: 1.5rem; color:rgb(63, 61, 61); cursor: pointer;"></i>
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

            @can('إعتماد تحصيل')
                <li>
                    <a class="dropdown-item text-dark" href="#"
                        onclick="confirmApproveCollection({{ $id }})">
                        <i class="ti ti-check me-2"></i>اعتماد
                    </a>
                </li>
            @endcan

            @can('رفض تحصيل')
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

        @can('حذف تحصيل')
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
