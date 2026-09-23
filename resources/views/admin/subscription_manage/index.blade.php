@extends('admin.layouts.layoutMaster')

@section('title', 'الاشتراكات')

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
        massDelete: "{{ route('admin.mass-delete') }}"
     };
</script>
    @vite(['resources/assets/js/admin/subscriptions/filter.js' , 'resources/assets/js/admin/subscriptions/index.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <!-- إجمالي الاشتراكات -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الاشتراكات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalSubscriptions }}</h4>
                            </div>
                            <small class="mb-0">عدد جميع الاشتراكات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-file-invoice ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الاشتراكات الفعالة -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات الفعالة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeSubscriptions }}</h4>
                            </div>
                            <small class="mb-0">اشتراكات فعالة حاليًا</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-bolt ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الاشتراكات المنتهية -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات المنتهية</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $expiredSubscriptions }}</h4>
                            </div>
                            <small class="mb-0">انتهت صلاحيتها </small>
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

        <!-- الاشتراكات المعلّقة -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات المعلقة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $suspendedSubscriptions }}</h4>
                            </div>
                            <small class="mb-0">لم يتم تفعيلها بعد</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-clock-pause ti-26px"></i>
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
              {{--  <div class="col-md-4">
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
                </div>--}}
                <div class="col-md-6">
                    <label for="filter-status">حالة الاشتراكات</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر  حالة الاشتراك">
                        <option value=""></option>
                        {{-- @foreach ($advanceStatus as $status) --}}
                            <option value="active">نشط</option>
                            <option value="cancelled">ملغي</option>
                            <option value="expired">منتهي</option>
                            <option value="suspended">معلق</option>
                        {{-- @endforeach --}}
                    </select>
                </div>
            </div> 
            <hr class="mt-10">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>
@endsection
