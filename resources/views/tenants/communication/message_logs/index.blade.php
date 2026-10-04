@extends('tenants.layouts.layoutMaster')

@section('title', 'سجل الرسائل')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            messageLogsIndex: "{{ tenant_route_name('communication.message-logs.index') }}",
            messageLogsShow: "{{ tenant_route_name('communication.message-logs.show', ':id') }}",
            messageLogsDestroy: "{{ tenant_route_name('communication.message-logs.destroy', ':id') }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/communication/message-logs/message-logs.js'])
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

    <div id="top_card" class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الرسائل" :value="number_format($statistics['total'])"
            subtitle="منذ البداية" icon="ti-messages" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="تم الإرسال" :value="number_format($statistics['sent'])"
            :subtitle="'معدل النجاح: ' . $statistics['success_rate'] . '%'" icon="ti-check" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="فشل الإرسال" :value="number_format($statistics['failed'])"
            subtitle="يحتاج مراجعة" icon="ti-x" color="danger" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="في الانتظار" :value="number_format($statistics['pending'])"
            subtitle="لم يتم الإرسال بعد" icon="ti-clock" color="warning" />
    </div>


    {{-- Filters Card --}}
    <x-data-table.filters table="#message-logs-table" reset-id="btn-reset-filters">
        <div class="col-md-4">
            <label class="form-label" for="filter-status">الحالة</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر الحالة">
                <option value=""></option>
                <option value="pending">في الانتظار</option>
                <option value="processing">جاري المعالجة</option>
                <option value="sent">تم الإرسال</option>
                <option value="failed">فشل الإرسال</option>
                <option value="cancelled">ملغي</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="filter-message-type">نوع الرسالة</label>
            <select id="filter-message-type" class="form-control select2" data-datatable-filter="true" data-filter-key="message_type" data-placeholder="اختر النوع">
                <option value=""></option>
                <option value="sms">رسالة نصية</option>
                <option value="email">بريد إلكتروني</option>
                <option value="whatsapp">واتساب</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="filter-sender">المرسل</label>
            <select id="filter-sender" class="form-control select2" data-datatable-filter="true" data-filter-key="sender_id" data-placeholder="اختر المرسل">
                <option value=""></option>
                @foreach ($filterData['senders'] as $sender)
                    <option value="{{ $sender->id }}">{{ $sender->name }}</option>
                @endforeach
            </select>
        </div>
    </x-data-table.filters>

    {{-- جدول سجل الرسائل --}}
    <div class="card">
        <div class="card-body">
            <table id="message-logs-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th class="custom-checkbox">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>المرسل</th>
                        <th>محتوى الرسالة</th>
                        <th>المستلمين</th>
                        <th>الحالة</th>
                        <th>النتائج</th>
                        <th>التوقيت</th>
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
