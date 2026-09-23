@extends('tenants.layouts.layoutMaster')

@section('title', 'إدارة النطاقات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

{{-- TODO islam --}}
{{-- فعلها اذا كنت محتاج لها او احذفها  --}}
{{-- @section('page-script')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', function() {
            const selector = document.getElementById('domain_selector');
            if (selector) {
                selector.addEventListener('change', function(e) {
                    const selectedDomain = this.value;
                    const selectedText = this.options[this.selectedIndex].text;

                    if (!selectedText.includes('نشط حالياً')) {
                        if (!confirm(
                                `هل تريد تفعيل الدومين: ${selectedDomain}؟\n\nسيتم إلغاء تفعيل الدومين الحالي.`
                            )) {
                            e.preventDefault();
                            this.selectedIndex = Array.from(this.options).findIndex(opt => opt.text
                                .includes('نشط حالياً'));
                            return false;
                        }
                    }
                });
            }
        });
    </script>
@endsection --}}



@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي النطاقات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalDomains }}</h4>
                            </div>
                            <small class="mb-0">عدد النطاقات المسجلة في حسابك</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-world-www ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">النطاقات النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeDomains }}</h4>
                            </div>
                            <small class="mb-0">نطاقات مفعّلة وتعمل حاليًا</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-circle-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">النطاقات غير النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $inactiveDomains }}</h4>
                            </div>
                            <small class="mb-0">نطاقات غير مفعّلة </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-alert-circle ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6 mb-4">
            <div class="card h-100" style="border: 2px solid #e9ecef;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div
                            style="width: 50px; height: 50px; background: #e3f2fd; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-left: 15px;">
                            <i class="ti ti-lifebuoy" style="font-size: 24px; color: #2196f3;"></i>
                        </div>
                        <h5 class="mb-0">مركز المساعدة</h5>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2" style="justify-content: space-between">
                        <p class="text-muted mb-0">تعرّف على طريقة إدارة نطاقاتك وربطها بالخادم خطوة بخطوة</p>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ tenant_route_name('domains.explain') }}" class="btn btn-outline-primary">
                                <i class="ti ti-book-open me-2"></i>
                                قراءة الدليل
                            </a>

                            <a href="{{ tenant_route_name('domains.faq') }}" class="btn btn-outline-primary">
                                <i class="ti ti-help-circle me-2"></i>
                                الأسئلة الشائعة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100" style="border: 2px solid #e9ecef;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div
                            style="width: 50px; height: 50px; background: #f3e5f5; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-left: 15px;">
                            <i class="ti ti-headset" style="font-size: 24px; color: #9c27b0;"></i>
                        </div>
                        <h5 class="mb-0">الدعم الفني</h5>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2" style="justify-content: space-between">
                        <p class="text-muted mb-0">فريق الدعم الفني جاهز لمساعدتك في أي استفسار يخص النطاقات</p>

                        <a href="{{ tenant_route_name('tickets.index') }}" class="btn btn-outline-primary">
                            <i class="ti ti-ticket me-2"></i>
                            فتح تذكرة دعم
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

@endsection
