<div class="d-flex gap-1 justify-content-center">
    @if (!$isEnded && $target->status === 'active')
        {{-- @can('تعديل هدف') --}}
        <a href="{{ tenant_route_name($route . '.edit', $target->id) }}" class="btn btn-sm btn-icon" title="تعديل">
            <i class="ti ti-edit"></i>
        </a>
        {{-- @endcan --}}
    @endif

    {{-- @can('حذف هدف') --}}
    <button type="button" class="btn btn-sm btn-icon"
        onclick="event.stopPropagation(); deleteSingleTarget({{ $target->id }}, '{{ tenant_route_name($route . '.destroy', $target->id) }}')"
        title="حذف">
        <i class="ti ti-trash"></i>
    </button>
    {{-- @endcan --}}

    @if ($isEnded)
        <span class="badge bg-label-secondary" title="الهدف منتهي">
            <i class="ti ti-lock"></i>
        </span>
    @endif
</div>
