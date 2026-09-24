@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل الرسالة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        $(document).ready(function() {
            $('#recipientsTable').DataTable({
                order: [
                    [0, 'asc']
                ],
                responsive: true,
                language: {
                    url: "{{ asset('assets/json/ar.json') }}"
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                lengthMenu: [
                    [10, 25, 50, -1],
                    ['10', '25', '50']
                ]
            });

            if (window.location.hash) {
                $('.nav-tabs a[href="' + window.location.hash + '"]').tab('show');
            }
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                history.pushState(null, null, e.target.hash);
            });
        });

        window.csrfToken = "{{ csrf_token() }}";
    </script>
@endsection

@section('content')
    <style>
        .nav-tabs .nav-link,
        .nav-pills .nav-link {
            justify-content: start;
            margin-bottom: 10px;
        }

        .nav-tabs .nav-link.active,
        .nav-tabs .nav-link.active:hover,
        .nav-tabs .nav-link.active:focus {
            box-shadow: none;
            border-left: 5px solid;
        }
    </style>
    <div class="row">
        <div class="col-12 col-md-3 col-xl-3">
            <div>
                <ul class="nav nav-tabs flex-column border-bottom-0">
                    <!-- تبويب تفاصيل الرسالة -->
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#messageDetails">
                            <i class="ti ti-info-circle ti-sm me-1"></i><span>تفاصيل الرسالة</span>
                        </a>
                    </li>
                    <!-- تبويب المستلمين -->
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#recipients">
                            <i class="ti ti-users ti-sm me-1"></i><span>المستلمين</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        {{-- المحتوى --}}
        <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                {{-- تفاصيل الرسالة --}}
                <div class="tab-pane fade show active" id="messageDetails">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">تفاصيل الرسالة</h5>
                        </div>
                        <div class="card-body">
                            {{-- بطاقة المرسل --}}
                            <div class="card border mb-4">
                                <div class="card-body d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        @php
                                            $avatar = $messageLog->sender && $messageLog->sender->profile_photo_url
                                                ? $messageLog->sender->profile_photo_url
                                                : asset('img/logo.svg');
                                        @endphp
                                        <img src="{{ $avatar }}" class="rounded-circle" width="70" height="70">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-0">{{ $messageLog->sender->name ?? 'النظام' }}</h5>
                                        <small>{{ $messageLog->created_at->format('Y-m-d H:i:s') }}</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span
                                            class="badge bg-{{ $messageLog->status_color }} fs-6">{{ $messageLog->status_label }}</span>
                                    </div>
                                </div>
                            </div>
                            {{-- بيانات الرسالة في بطاقات --}}
                            <div class="row mb-4">
                                <!-- إجمالي المستلمين -->
                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-users me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">إجمالي المستلمين</h6>
                                                    <h4 class="mb-0 fw-bold">
                                                        {{ number_format($messageLog->recipients_count) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- فشل الإرسال -->
                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-template me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">نموذج الارسال</h6>
                                                    @if ($messageLog->template_name)
                                                        <span class="fw-bold">
                                                            {{ $messageLog->template_name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- المرسل إليهم -->
                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-target me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">المرسل إليهم</h6>
                                                    @php
                                                        $models = [
                                                            'customers' => 'العملاء',
                                                            'users' => 'المستخدمين',
                                                            'employees' => 'الموظفين',
                                                        ];
                                                    @endphp
                                                    <span
                                                        class="fw-bold">{{ $models[$messageLog->target_model] ?? ($messageLog->target_model ?? 'غير محدد') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6><i class="ti ti-message-circle me-1"></i>محتوى الرسالة</h6>
                                <div class="border rounded p-3">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $messageLog->message_content }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- المستلمين --}}
                <div class="tab-pane fade" id="recipients">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">قائمة المستلمين</h5>
                        </div>
                        <div class="card-body">
                            @if ($messageLog->recipients_data && count($messageLog->recipients_data) > 0)
                                @php
                                    // تجميع الأرقام حسب العميل
                                    $groupedRecipients = collect($messageLog->recipients_data)->groupBy('id');
                                @endphp
                                <div class="table-responsive">
                                    <table id="recipientsTable" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>الاسم</th>
                                                <th>أرقام الهاتف</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($groupedRecipients as $customerId => $customerPhones)
                                                @php
                                                    $customerName = $customerPhones->first()['name'] ?? 'غير محدد';
                                                    $phoneNumbers = $customerPhones->pluck('phone')->filter()->unique();
                                                @endphp
                                                <tr>
                                                    <td>{{ $customerName }}</td>
                                                    <td>
                                                        @foreach($phoneNumbers as $phone)
                                                            <span class="badge bg-primary me-1 mb-1">{{ $phone }}</span>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="ti ti-info-circle me-2"></i>لا توجد بيانات مستلمين مسجلة.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
