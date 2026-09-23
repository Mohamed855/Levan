    <div class="d-flex justify-content-center">

        @if ($row->status->value === 'cancelled')
            <form class="m-0 p-0" action="{{ route($route . '.reactivate', $row->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm text-secondary">
                    <i class="ti ti-refresh me-1"></i>
                </button>
            </form>
        @endif

        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-sm text-secondary">
            <i class="ti ti-invoice"></i>
        </a>

    </div>
