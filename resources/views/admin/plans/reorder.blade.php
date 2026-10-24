@extends('layouts.dashboard')
@section('title', 'ترتيب الباقات')
@section('dashboard-scope', 'admin')
@section('content')
<form method="POST" action="{{ route('admin.plans.reorder') }}">
    @csrf @method('PUT')
    <div class="card"><div class="card-header"><h5 class="mb-0">ترتيب ظهور الباقات</h5><small class="text-muted">استخدم أرقامًا فريدة؛ الرقم الأصغر يظهر أولًا في الموقع والاشتراك.</small></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>الباقة</th><th>الكود</th><th style="width: 180px">الترتيب</th></tr></thead><tbody>
        @foreach($plans as $index => $plan)
            <tr><td>{{ $plan->name }}</td><td><code>{{ $plan->code }}</code></td><td><input type="number" min="0" class="form-control" name="order[{{ $plan->id }}]" value="{{ old('order.'.$plan->id, $plan->sort_order ?? $index) }}" required></td></tr>
        @endforeach
        </tbody></table></div>
    </div>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">حفظ الترتيب</button><a class="btn btn-label-secondary" href="{{ route('admin.plans.index') }}">إلغاء</a></div>
</form>
@endsection
