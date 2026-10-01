@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل النسخة الجديدة')

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if (session('success'))
                toastr.success(@json(session('success')));
            @endif
            @if (session('info'))
                toastr.info(@json(session('info')));
            @endif
            @if (session('error'))
                toastr.error(@json(session('error')));
            @endif
        });
    </script>
@endsection

@section('content')
    <div class="row g-4 align-items-stretch">
        <div class="col-6">
            <div class="card h-100">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-list-details text-warning me-2"></i>
                        تفاصيل الإصدارة
                    </h6>
                </div>

                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body px-5">
                    <div class="table-responsive">
                        <table class="table table-striped small text-center">
                            <tr>
                                <td class="fw-bold">رقم الإصدارة</td>
                                <td>{{ $latestVersion->version }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">المرحلة</td>
                                <td>
                                    <span class="badge bg-{{ $latestVersion->stage->color() }}">
                                        {{ $latestVersion->stage->label() }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">تاريخ الإصدار</td>
                                <td>{{ $latestVersion->released_at ? $latestVersion->released_at->format('Y-m-d') : '-' }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- زر الترقية -->
                    <div class="text-center mt-10">
                        <form action="{{ tenant_route_name('versions.upgrade') }}" method="POST" id="upgradeForm">
                            @csrf
                            <input type="hidden" name="target_id" value="{{ $latestVersion->id }}">
                            <button type="submit" class="btn btn-primary btn-sm" id="upgradeBtn" aria-disabled="true">
                                ترقية النظام الآن
                                <i class="ti ti-upload ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card h-100">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-sitemap text-warning me-2"></i>
                        سجل التغييرات
                    </h6>
                </div>

                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body">
                    @php
                        $changes = collect(preg_split('/\r\n|\n|\r/', $latestVersion->changelog ?? ''))->filter(
                            fn($line) => trim($line) !== '',
                        );
                    @endphp

                    @if ($changes->isEmpty())
                        <p class="text-muted text-center my-4">لا توجد تفاصيل تغييرات متاحة لهذه النسخة.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($changes as $item)
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="ti ti-swipe-left text-success me-2"></i>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const form = document.getElementById('upgradeForm');
            const btn = document.getElementById('upgradeBtn');

            form.addEventListener('submit', function(e) {
                const ok = confirm('سيتم تفعيل وضع التطوير وبدء الترقية في الخلفية. المتابعة؟');
                if (!ok) {
                    e.preventDefault();
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الترقية...';
            });
        })();
    </script>
@endsection
