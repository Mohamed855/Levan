@extends('tenant.layouts.layoutMaster')
@section('title', 'معاينة مخرج المزاد')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a class="btn btn-secondary" href="{{ tenant_route_name('auctions.show', $auction->id) }}">العودة لتفاصيل
            المزاد</a>
        <span class="badge bg-primary fs-6 p-2">نوع المخرج: {{ $type }}</span>
    </div>
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ $output['template']['template_name'] ?? 'القالب المعتمد' }}</h5>
        </div>
        <div class="card-body p-4 bg-light">
            @if (!empty($output['template']['schema']))
                @include('components.shared.auctions.design-document', [
                    'schema' => $output['template']['schema'],
                    'bindings' => $output['bindings'] ?? [],
                ])
            @else
                <div class="alert alert-warning mb-0">لا يتوفر مخطط تصميم محفوظ لهذا القالب.</div>
            @endif
        </div>
    </div>
@endsection
