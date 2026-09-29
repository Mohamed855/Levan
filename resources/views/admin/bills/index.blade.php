@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة المعاملات المالية ')

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

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
        };
    </script>
    @vite(['resources/assets/js/admin/bills/filter.js', 'resources/assets/js/admin/bills/index.js'])
@endsection

@section('content')
    <div class="mb-4 row g-4">
        <!-- بطاقات الإحصائيات -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المعاملات</span>
                            <div class="my-1 d-flex align-items-center">
                                <h4 class="mb-0 me-2">{{ $billcounts }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المعاملات</small>
                        </div>
                        <div class="avatar">
                            <span class="rounded avatar-initial bg-label-primary">
                                <i class="ti ti-receipt-2 ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المعاملات المدفوعة</span>
                            <div class="my-1 d-flex align-items-center">
                                <h4 class="mb-0 me-2">{{ $paidbills }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي المعاملات المدفوعة</small>
                        </div>
                        <div class="avatar">
                            <span class="rounded avatar-initial bg-label-warning">
                                <i class="ti ti-cash ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المعاملات الغير مدفوعة</span>
                            <div class="my-1 d-flex align-items-center">
                                <h4 class="mb-0 me-2">{{ $unpaidbills }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي المعاملات الغير مدفوعة</small>

                        </div>
                        <div class="avatar">
                            <span class="rounded avatar-initial bg-label-success">
                                <i class="ti ti-file-invoice ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">معاملات لم يوافق عليها</span>
                            <div class="my-1 d-flex align-items-center">
                                <h4 class="mb-0 me-2">{{ $pendingbills }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المعاملات التي لم يوافق عليها</small>
                        </div>
                        <div class="avatar">
                            <span class="rounded avatar-initial bg-label-danger">
                                <i class="ti ti-ban ti-26px"></i>
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

    <div class="card">
        <div class="card-body">
            <div class="filters row g-3">
                {{-- <div class="col-md-4">
                    <label for="filter-employee">الموظف</label>
                    <select id="filter-employee" class="form-control select2" data-placeholder="اختر الموظف">
                        <option value=""></option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="filter-type">نوع السلفة</label>
            <select id="filter-type" class="form-control select2" data-placeholder="اختر نوع السلفة">
                <option value=""></option>
                @foreach ($advanceTypes as $type)
                <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                @endforeach
            </select>
        </div> --}}
                <div class="col-md-4">
                    <label for="filter-status">نوع العملية</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر  نوع العملية">
                        <option value=""></option>
                        {{-- @foreach ($advanceStatus as $status) --}}
                        <option value="new_subscription">دفع الكتروني</option>
                        <option value="renewal">تجديد اشتراك</option>
                        <option value="license_purchase">شراء ترخيص</option>
                        <option value="billing_cycle_change">تغيير دورة الفاتورة</option>
                        {{-- @endforeach --}}
                    </select>
                </div>


                <div class="col-md-4">
                    <label for="filter-paid">حالة العملية</label>
                    <select id="filter-paid" class="form-control select2" data-placeholder="اختر حالة العملية">
                        <option value=""></option>
                        <option value="paid">مدفوع</option>
                        <option value="unpaid">غير مدفوع</option>
                        <option value="partially_paid">مدفوع جزئياً</option>
                        <option value="overdue">متأخر</option>
                        <option value="refunded">مسترجع</option>
                        <option value="pending">قيد الانتظار</option>
                        <option value="pending_verification">بانتظار التحقق</option>
                        <option value="rejected">مرفوض</option>
                    </select>
                </div>


                <div class="col-md-4">
                    <label for="filter-type">نوع الدفع</label>
                    <select id="filter-type" class="form-control select2" data-placeholder="اختر نوع الدفع">
                        <option value=""></option>
                        <option value="online">دفع الكتروني</option>
                        <option value="cash">يدوي</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                    </select>
                </div>

            </div>
            <hr class="mt-10">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

@endsection
