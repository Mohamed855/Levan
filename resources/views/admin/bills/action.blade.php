    <div class="d-flex justify-content-center gap-2">

        @if ($row->status->value === 'cancelled')
            <form class="m-0 p-0" action="{{ route($route . '.reactivate', $row->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-icon btn-primary bg-primary">
                    <i class="ti ti-refresh"></i>
                </button>
            </form>
        @endif

        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-icon btn-primary bg-primary">
            <i class="ti ti-invoice"></i>
        </a>

    </div>
