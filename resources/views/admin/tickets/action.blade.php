<div class="d-flex justify-content-center gap-2">
    <a href="{{ route('admin.tickets.show', $row->id) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض التذكرة">
        <i class="ti ti-eye"></i>
    </a>
    <x-actions.delete :id="$row->id" :url="route('admin.tickets.destroy', $row->id)" title="حذف التذكرة" permission="tickets.delete" />
</div>
