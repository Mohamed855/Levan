<div class="d-flex justify-content-center gap-2">
    @if ($row->type === 'linked' && $row->status === 'pending_dns')
        <form method="POST" action="{{ route('tenant.domains.verify', $row->id) }}">
            @csrf
            <button class="btn btn-sm btn-label-primary" type="submit" title="التحقق من DNS"><i class="ti ti-refresh"></i></button>
        </form>
    @endif
    <form method="POST" action="{{ route('tenant.domains.destroy', $row->id) }}" onsubmit="return confirm('هل تريد إزالة هذا النطاق؟')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-label-danger" type="submit" title="إزالة"><i class="ti ti-trash"></i></button>
    </form>
</div>
