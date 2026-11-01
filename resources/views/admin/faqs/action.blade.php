<div class="d-flex justify-content-center gap-2">
    @can('faqs.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل السؤال" aria-label="تعديل السؤال">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('faqs.delete')
        <button type="button" class="btn btn-icon btn-primary bg-primary" onclick="confirmDelete({{ $row->id }})" title="حذف السؤال" aria-label="حذف السؤال">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</div>
