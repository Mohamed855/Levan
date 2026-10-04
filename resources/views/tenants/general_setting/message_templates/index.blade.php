@extends('tenants.layouts.layoutMaster')

@section('title', 'قوالب الرسائل')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            messageTemplatesIndex: "{{ tenant_route_name('general-settings.message-templates.index') }}",
            messageTemplatesCreate: "{{ tenant_route_name('general-settings.message-templates.create') }}",
            toggleStatus: "{{ tenant_route_name('general-settings.message-templates.toggle-status', ':id') }}",
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/general-settings/message_templates/message-templates-index.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- إحصائيات سريعة --}}
    <div class="row g-4 mb-4">
        {{-- إجمالي القوالب --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي القوالب</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['total'] }}</h4>
                            </div>
                            <small class="mb-0">جميع القوالب</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-template ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- قوالب الشركات --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">قوالب الشركات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['companies'] }}</h4>
                            </div>
                            <small class="mb-0">قوالب موجهة للشركات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-building ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- قوالب العملاء --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">قوالب العملاء</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['customers'] }}</h4>
                            </div>
                            <small class="mb-0">قوالب موجهة للعملاء</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- قوالب المستخدمين (الموظفين) --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">قوالب المستخدمين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['users'] }}</h4>
                            </div>
                            <small class="mb-0">قوالب موجهة للموظفين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-users ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#message-templates-table" reset-id="btn-reset-filters">
        <div class="col-md-3">
            <label class="form-label" for="filter-category">الفئة</label>
            <select id="filter-category" class="form-control select2" data-datatable-filter="true" data-filter-key="category" data-placeholder="اختر الفئة">
                <option value=""></option>
                @foreach ($categories as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="filter-channel">القناة</label>
            <select id="filter-channel" class="form-control select2" data-datatable-filter="true" data-filter-key="channel" data-placeholder="اختر القناة">
                <option value=""></option>
                @foreach ($channels as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="filter-status">الحالة</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر الحالة">
                <option value=""></option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="filter-search">البحث</label>
            <input type="text" id="filter-search" class="form-control" data-datatable-filter="true" data-filter-key="search" placeholder="البحث في الاسم أو المحتوى">
        </div>
    </x-data-table.filters>

    {{-- جدول القوالب --}}
    <div class="card">
        <div class="card-body">
            <table id="message-templates-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th>اسم القالب</th>
                        <th>الفئة</th>
                        <th>القناة</th>
                        <th>الحالة</th>
                        <th>المنشئ</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- سيتم تعبئة البيانات بواسطة DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
@endsection
