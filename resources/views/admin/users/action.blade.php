<div class="d-flex justify-content-center gap-2">
   <a href="{{ route('admin.users.profile' , $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="عرض الملف الشخصي" aria-label="عرض الملف الشخصي">
        <i class="ti ti-eye"></i>
    </a>
    @can('users.edit')
    <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل المستخدم" aria-label="تعديل المستخدم">
        <i class="ti ti-edit"></i>
    </a>
    @endcan

    @can('users.delete')
    <button type="button" class="btn btn-icon btn-primary bg-primary js-delete-btn" data-id="{{ $row->id }}" title="حذف المستخدم" aria-label="حذف المستخدم">
        <i class="ti ti-trash"></i>
    </button>

    <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    @endcan

    
</div>
@push('scripts')
<script>
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-delete-btn');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        confirmDelete(id);
    });
</script>
@endpush
