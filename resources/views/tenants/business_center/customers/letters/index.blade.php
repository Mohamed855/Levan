@extends('tenants.layouts.layoutMaster')

@section('title', 'خطابات الشركات')

{{-- ═══ Vendor Styles ═══ --}}
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

{{-- ═══ Vendor Scripts ═══ --}}
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

{{-- ═══ Toastr ═══ --}}
@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

{{-- ═══ Page Styles ═══ --}}
@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

{{-- ═══ Page Scripts ═══ --}}
@section('page-script')
    <script>
        window.routes = {
            companyLettersIndex: "{{ tenant_route_name('company-letters.index') }}",
            companyLettersShow: "{{ tenant_route_name('company-letters.show', ':id') }}",
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/business-center/customers/letters/index.js'])

    {!! $dataTable->scripts() !!}
@endsection

{{-- ═══ Page Content ═══ --}}
@section('content')

    {{-- ═══ الكروت الإحصائية ═══ --}}
    <div class="row g-4 mb-4">
        {{-- إجمالي الخطابات --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الخطابات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['total'] }}</h4>
                            </div>
                            <small class="mb-0">جميع الخطابات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-mail ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- جاهز للإرسال --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">جاهز للإرسال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['ready_to_send'] }}</h4>
                            </div>
                            <small class="mb-0">خطابات معلقة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-send ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- تم الإرسال --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">تم الإرسال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['sent'] }}</h4>
                            </div>
                            <small class="mb-0">خطابات مرسلة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- فشل الإرسال --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">فشل الإرسال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['failed'] }}</h4>
                            </div>
                            <small class="mb-0">خطابات فاشلة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ جدول الخطابات ═══ --}}
    <div class="card">
        <div class="card-body">
            {{-- ═══ فلاتر البحث ═══ --}}
            <div class="filters row mb-4">
                <div class="col-md-3">
                    <label for="filter-status">الحالة</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر الحالة">
                        <option value=""></option>
                        <option value="ready_to_send">جاهز للإرسال</option>
                        <option value="approved_ready_to_send">معتمد - جاهز للإرسال</option>
                        <option value="sent">تم الإرسال</option>
                        <option value="failed">فشل الإرسال</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filter-company">الشركة</label>
                    <select id="filter-company" class="form-control select2" data-placeholder="اختر الشركة">
                        <option value=""></option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filter-portfolio">المحفظة</label>
                    <select id="filter-portfolio" class="form-control select2" data-placeholder="اختر المحفظة">
                        <option value=""></option>
                        @foreach ($portfolios as $portfolio)
                            <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                                {{ $portfolio->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filter-date">الفترة الزمنية</label>
                    <input type="date" id="filter-date" class="form-control" placeholder="اختر الفترة">
                </div>
            </div>

            <hr>

            {{-- ═══ الجدول ═══ --}}
            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table table-striped table-bordered w-100 text-center',
                    'id' => 'company-letters-table',
                ]) !!}
            </div>
        </div>
    </div>

@endsection
