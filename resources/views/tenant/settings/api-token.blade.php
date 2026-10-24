@extends('layouts.dashboard')

@section('title', 'مفتاح API')
@section('dashboard-scope', 'tenant')

@section('content')
    <div class="container-fluid" dir="rtl">
        <h1 class="h4 mb-4">مفتاح API</h1>
        @if (session('new_api_token'))
            <div class="alert alert-warning">
                <p class="mb-1">انسخ المفتاح الآن؛ لن يكون قابلاً للاسترجاع بعد مغادرة الصفحة.</p>
                <code class="user-select-all d-block text-break">{{ session('new_api_token') }}</code>
            </div>
        @endif
        <div class="card">
            <div class="card-body">
                @if ($token)
                    <p class="mb-1">المفتاح النشط: <code>{{ $token->token_prefix }}…</code></p>
                    <p class="text-muted mb-3">أُنشئ {{ $token->created_at?->translatedFormat('Y-m-d H:i') }}.</p>
                    <form method="POST" action="{{ route('tenant.api-token.revoke') }}" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">إلغاء المفتاح</button>
                    </form>
                @else
                    <p class="text-muted">لا يوجد مفتاح API نشط.</p>
                @endif
                <form method="POST" action="{{ route('tenant.api-token.rotate') }}" class="mt-3">
                    @csrf
                    <div class="input-group">
                        <input class="form-control" name="name" value="default" maxlength="100" aria-label="اسم المفتاح">
                        <button class="btn btn-primary" type="submit">{{ $token ? 'تدوير المفتاح' : 'إنشاء مفتاح' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
