@extends('tenants.layouts.layoutMaster')

@section('title', 'مفتاح API وسجل الاستخدام')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">مفتاح API</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-primary d-flex align-items-start mb-4" role="alert">
                        <i class="ti ti-info-circle ti-md me-2 mt-1"></i>
                        <div>
                            يُستخدم هذا المفتاح للسماح لأنظمة خارجية بالاطلاع على بيانات العملاء والتحصيلات عبر الـ API،
                            من خلال إرساله في هيدر <code>Authorization: Bearer</code>. لا تشارك هذا المفتاح مع جهات غير
                            موثوقة.
                        </div>
                    </div>

                    @if (session('new_api_token'))
                        <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                            <i class="ti ti-alert-triangle ti-md me-2 mt-1"></i>
                            <div>احفظ هذا التوكن الآن في مكان آمن - لن يظهر بالكامل مرة أخرى بعد مغادرة الصفحة.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">التوكن الجديد</label>
                            <div class="input-group">
                                <input type="text" id="apiTokenField" class="form-control font-monospace"
                                    value="{{ session('new_api_token') }}" readonly>
                                <button type="button" class="btn btn-secondary" id="copyApiTokenBtn">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mb-4">
                            <label class="form-label">التوكن الحالي</label>
                            <input type="text" class="form-control font-monospace"
                                value="{{ tenant()->api_token ? '•••••••••••••••••••••••••••••• (مخفي لأسباب أمنية)' : 'لا يوجد توكن بعد' }}"
                                readonly>
                            @if (tenant()->api_token_regenerated_at)
                                <small class="text-muted">آخر توليد:
                                    {{ tenant()->api_token_regenerated_at->format('Y-m-d H:i') }}</small>
                            @endif
                        </div>
                    @endif

                    <form id="regenerateApiTokenForm" method="POST"
                        action="{{ tenant_route_name('api-token-settings.regenerate') }}">
                        @csrf
                        <button type="button" id="regenerateApiTokenBtn" class="btn btn-danger">
                            <i class="ti ti-refresh me-1"></i>
                            توليد توكن جديد
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">سجل استخدام الـ API</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الوقت</th>
                                    <th>الطريقة</th>
                                    <th>المسار</th>
                                    <th>الحالة</th>
                                    <th>عنوان IP</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td><span class="badge bg-secondary">{{ $log->method }}</span></td>
                                        <td class="font-monospace">{{ $log->path }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $log->status_code < 300 ? 'bg-label-success' : 'bg-label-danger' }}">
                                                {{ $log->status_code }}
                                            </span>
                                        </td>
                                        <td>{{ $log->ip_address }}</td>
                                        <td class="text-truncate" style="max-width: 250px;">{{ $log->user_agent }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">لا توجد استعلامات مسجّلة بعد
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        {{ $logs->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyBtn = document.getElementById('copyApiTokenBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const field = document.getElementById('apiTokenField');
                    field.select();
                    field.setSelectionRange(0, field.value.length);

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(field.value).then(function() {
                            toastr.success('تم نسخ التوكن');
                        });
                    } else {
                        document.execCommand('copy');
                        toastr.success('تم نسخ التوكن');
                    }
                });
            }

            const regenerateBtn = document.getElementById('regenerateApiTokenBtn');
            if (regenerateBtn) {
                regenerateBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'هل أنت متأكد؟',
                        text: 'سيتوقف عمل أي نظام خارجي يستخدم التوكن الحالي فوراً بعد التوليد.',
                        icon: 'warning',
                        showCancelButton: true,
                        buttonsStyling: false,
                        customClass: {
                            popup: 'custom-popup',
                            title: 'custom-title',
                            text: 'custom-text',
                            confirmButton: 'btn btn-danger custom-confirm',
                            cancelButton: 'btn btn-secondary custom-cancel'
                        },
                        confirmButtonText: 'توليد الآن',
                        cancelButtonText: 'تراجع'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            document.getElementById('regenerateApiTokenForm').submit();
                        }
                    });
                });
            }
        });
    </script>
@endsection
