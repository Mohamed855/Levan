<div class="d-flex justify-content-center gap-2">
    {{-- زر التفعيل --}}

    @if ($row->type == 'transfered' && $row->trasnfer_status !== 'Completed')
        {{-- TODO islam --}}
        {{-- الشرط هنا بدون محتوى اذا لم تكن بحاجة له احذفه --}}
    @else
        {{-- زر التفعيل --}}
        <form action="{{ tenant_route_name('domains.modifyaccount') }}" method="POST" style="display: inline-block;">
            @csrf

            {{-- TODO islam --}}
            {{-- حذف كل القيم hidden وجلبها من الكنترول   --}}
            <input type="hidden" name="domain_id" value="{{ $row->id }}">
            <input type="hidden" name="domain" value="{{ $row->domain }}">
            <input type="hidden" name="tenant_id" value="{{ tenant('id') }}">

            <button type="submit" class="btn btn-icon btn-primary bg-primary"
                title="{{ $row->is_active ? 'دومين مفعّل' : 'تفعيل الدومين' }}"
                @if ($row->is_active) disabled @endif>
                <i class="fas fa-toggle-{{ $row->is_active ? 'on' : 'off' }}"></i>
            </button>
        </form>

        {{-- زر التجديد --}}
        @if ($row->type == 'registered')
            <form action="{{ tenant_route_name('domains.payment.renewal') }}" method="POST"
                style="display: inline-block;">
                @method('POST')
                @csrf
                <input type="hidden" name="domain_id" value="{{ $row->id }}">
                <input type="hidden" name="domain_name" value="{{ $row->domain }}">
                <button type="submit" class="btn btn-icon btn-primary bg-primary" title="تجديد الدومين">
                    <i class="fas fa-redo-alt"></i>
                </button>
            </form>
        @endif
    @endif
</div>
