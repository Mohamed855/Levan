@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل العميل - ' . $customer->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/tenants/cdn/tribute.css'])
@endsection

@section('page-script')
    <script>
        $(document).ready(function() {
            // التحقق مما إذا كان هناك hash في الرابط عند التحميل
            if (window.location.hash) {
                var hash = window.location.hash;
                // تنشيط علامة التبويب بناءً على الـ hash
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }

            // عند تغيير التبويب، قم بتحديث الـ URL بالـ hash
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                var targetHash = e.target.hash; // الحصول على الـ hash الجديد
                if (targetHash) {
                    history.replaceState(null, null, targetHash); // تحديث الـ URL دون إعادة تحميل الصفحة
                }
            });
        });


        window.csrfToken = "{{ csrf_token() }}";
        window.customerId = {{ $customer->id }};
        window.tenantStorageUrl = "{{ Storage::disk('tenant_public')->url('') }}";
        // تعريف كائن الروابط
        window.routes = {
            'customers.follows.contact-result-fields': "{{ tenant_route_name('customers.follows.contact-result-fields', ['customer' => $customer->id]) }}",
            'customers.follows.create': "{{ tenant_route_name('customers.follows.create', ['customer' => $customer->id]) }}",
            'customers.follows.edit': "{{ tenant_route_name('customers.follows.edit', ['customer' => $customer->id, 'customerFollow' => 'FOLLOW_ID']) }}",
            'customers.follows.update': "{{ tenant_route_name('customers.follows.update', ['customer' => $customer->id, 'customerFollow' => 'FOLLOW_ID']) }}",
            'customers.follows.destroy': "{{ tenant_route_name('customers.follows.destroy', ['customer' => $customer->id, 'customerFollow' => 'FOLLOW_ID']) }}",
            'customers.follows.store': "{{ tenant_route_name('customers.follows.store', ['customer' => $customer->id]) }}",
            'customers.follows.get-customer-debt': "{{ tenant_route_name('customers.follows.get-customer-debt', ['customer' => $customer->id]) }}",
            'customers.follows.list': "{{ tenant_route_name('customers.follows.list', ['customer' => 'CUSTOMER_ID']) }}",
            'customers.follows.get-related-accounts': "{{ tenant_route_name('customers.follows.get-related-accounts', ['customer' => $customer->id]) }}",
            'customers.set-primary-phone': "{{ tenant_route_name('customers.set-primary-phone', ['customer' => $customer->id]) }}",
            'customers.get-phone-number': "{{ tenant_route_name('customers.get-phone-number', ['customer' => $customer->id]) }}",
            'customers.whatsapp-preview-template': "{{ tenant_route_name('customers.whatsapp-preview-template') }}",
            // Comments and Mentions
            'customers.comments.list': "{{ tenant_route_name('customers.comments.list', ['customer' => $customer->id]) }}",
            'customers.comments.store': "{{ tenant_route_name('customers.comments.store', ['customer' => $customer->id]) }}",
            'customers.comments.destroy': "{{ tenant_route_name('customers.comments.destroy', ['customer' => $customer->id, 'customerComment' => 'COMMENT_ID']) }}",
            'customers.comments.mentionable-users': "{{ tenant_route_name('customers.comments.mentionable-users', ['customer' => $customer->id]) }}",
            // Payment Plans
            'customers.payment-plans.list': "{{ tenant_route_name('customers.payment-plans.list', ['customer' => $customer->id]) }}",
            'customers.payment-plans.create': "{{ tenant_route_name('customers.payment-plans.create', ['customer' => $customer->id]) }}",
            'customers.payment-plans.store': "{{ tenant_route_name('customers.payment-plans.store', ['customer' => $customer->id]) }}",
            'customers.payment-plans.edit': "{{ tenant_route_name('customers.payment-plans.edit', ['customer' => $customer->id, 'customerPaymentPlan' => 'PLAN_ID']) }}",
            'customers.payment-plans.update': "{{ tenant_route_name('customers.payment-plans.update', ['customer' => $customer->id, 'customerPaymentPlan' => 'PLAN_ID']) }}",
            'customers.payment-plans.destroy': "{{ tenant_route_name('customers.payment-plans.destroy', ['customer' => $customer->id, 'customerPaymentPlan' => 'PLAN_ID']) }}",
            // Payment History
            'customers.payment-history.list': "{{ tenant_route_name('customers.payment-history.list', ['customer' => $customer->id]) }}",
            // تسويات العملاء
            'customers.settlements.list': "{{ tenant_route_name('customers.settlements.list', ['customer' => $customer->id]) }}",
            'customers.settlements.create': "{{ tenant_route_name('customers.settlements.create', ['customer' => $customer->id]) }}",
            'customers.settlements.store': "{{ tenant_route_name('customers.settlements.store', ['customer' => $customer->id]) }}",
            'customers.settlements.edit': "{{ tenant_route_name('customers.settlements.edit', ['customer' => $customer->id, 'customerSettlement' => 'SETTLEMENT_ID']) }}",
            'customers.settlements.update': "{{ tenant_route_name('customers.settlements.update', ['customer' => $customer->id, 'customerSettlement' => 'SETTLEMENT_ID']) }}",
            'customers.settlements.destroy': "{{ tenant_route_name('customers.settlements.destroy', ['customer' => $customer->id, 'customerSettlement' => 'SETTLEMENT_ID']) }}",
            // Notes
            'customers.notes.list': "{{ tenant_route_name('customers.notes.list', ['customer' => $customer->id]) }}",
            'customers.notes.store': "{{ tenant_route_name('customers.notes.store', ['customer' => $customer->id]) }}",
            'customers.notes.destroy': "{{ tenant_route_name('customers.notes.destroy', ['customer' => $customer->id, 'customerNote' => 'NOTE_ID']) }}",
            // Attachments
            'customers.attachments.list': "{{ tenant_route_name('customers.attachments.list', ['customer' => $customer->id]) }}",
            'customers.attachments.store': "{{ tenant_route_name('customers.attachments.store', ['customer' => $customer->id]) }}",
            'customers.attachments.destroy': "{{ tenant_route_name('customers.attachments.destroy', ['customer' => $customer->id, 'customerAttachment' => 'ATTACHMENT_ID']) }}",
            // Phone Requests Routes
            'customers.phone-requests.list': "{{ tenant_route_name('customers.phone-requests.list', ['customer' => $customer->id]) }}",
            'customers.phone-requests.store': "{{ tenant_route_name('customers.phone-requests.store', ['customer' => $customer->id]) }}",

        };
    </script>


    @vite([
        'resources/assets/js/tenants/business-center/customers/follow-modals/customer-follow-modals.js',
        'resources/assets/js/tenants/business-center/customers/follow-modals/follow-with-accounts.js',
        'resources/assets/js/tenants/business-center/customers/attachment/customer-attachment.js',
        'resources/assets/js/tenants/business-center/customers/note/customer-note.js',
        'resources/assets/js/tenants/business-center/customers/payment-plan/customer-payment-plan.js',
        'resources/assets/js/tenants/business-center/customers/payment-history/index.js',
        'resources/assets/js/tenants/business-center/customers/settlement/customer-settlement.js',
        'resources/assets/js/tenants/business-center/customers/sms/customer-sms.js',
        'resources/assets/js/tenants/business-center/customers/payment/customer-payment.js',
        'resources/assets/js/tenants/business-center/customers/phone-request/index.js',
        'resources/assets/js/tenants/business-center/customers/primary-phone/primary-phone.js',
        'resources/assets/js/tenants/business-center/customers/whatsapp-share/index.js',
        'resources/assets/js/tenants/business-center/customers/comment/customer-comment.js',
    ])
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
        <!-- التبويبات الجانبية -->
        <div class="col-12 col-md-2 col-xl-2">
            <div>
                <ul class="nav nav-tabs flex-column border-bottom-0">
                    <!-- تبويب تفاصيل العميل -->
                    @can('customers.view')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#customerDetails">
                                <i class="ti ti-info-circle ti-sm me-1"></i><span>تفاصيل العميل</span>
                            </a>
                        </li>
                    @endcan

                    <!-- تبويب متابعة العميل -->
                    @can('customers.edit')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#customerFollow">
                                <i class="ti ti-eye ti-sm me-1"></i><span>متابعة العميل</span>
                            </a>
                        </li>
                    @endcan

                    <!-- إضافة التاب الجديد بعد تاب متابعة العميل -->
                    {{-- @can('تفاصيل السداد')
                <li class="card rounded-0 mb-3 nav-item shadow-none">
                    <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#paymentDetails">
                        <i class="ti ti-credit-card ti-sm me-1"></i><span> تفاصيل السداد</span>
                    </a>
                </li>
                @endcan --}}

                    <!-- تبويب الرسائل النصية -->
                    {{-- <li class="card rounded-0 mb-3 nav-item shadow-none">
                    <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#smsMessages">
                        <i class="ti ti-message-circle ti-sm me-1"></i><span>الرسائل النصية</span>
                    </a>
                </li> --}}

                    <!-- تبويب خطة سداد -->
                    @can('customers.edit')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#paymentPlan">
                                <i class="ti ti-calendar-event ti-sm me-1"></i><span>خطط السداد</span>
                            </a>
                        </li>
                    @endcan

                    <!-- ✅ التاب الجديد: سجل السدادات -->
                    {{-- @can('خطة سداد') --}}
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#paymentHistory">
                            <i class="ti ti-receipt ti-sm me-1"></i><span>سجل السدادات</span>
                        </a>
                    </li>
                    {{-- @endcan --}}



                    <!-- تبويب طلبات التسوية -->
                    @can('customers.edit')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#settlementRequests">
                                <i class="ti ti-file-check ti-sm me-1"></i><span>طلبات التسوية</span>
                            </a>
                        </li>
                    @endcan

                    <!-- تبويب الملاحظات -->
                    @can('customers.edit')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#notes">
                                <i class="ti ti-notes ti-sm me-1"></i><span>ملاحظات</span>
                            </a>
                        </li>
                    @endcan

                    <!-- تبويب التعليقات والمنشنات (الجديد) -->
                    @can('customers.edit')
                        <!-- استخدم نفس صلاحية الملاحظات أو أنشئ صلاحية جديدة -->
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#comments">
                                <i class="ti ti-message-circle-2 ti-sm me-1"></i><span>تعليقات ومنشنات</span>
                            </a>
                        </li>
                    @endcan

                    <!-- تبويب المرفقات -->
                    @can('customers.edit')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#attachments">
                                <i class="ti ti-paperclip ti-sm me-1"></i><span>المرفقات</span>
                            </a>
                        </li>
                    @endcan

                    <!-- تبويب إضافة هاتف إضافي -->
                    @can('customers.add_phone')
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#addPhone">
                                <i class="ti ti-phone-plus ti-sm me-1"></i><span>إضافة رقم هاتف</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
        </div>

        <!-- محتوى التبويبات -->
        <div class="col-12 col-md-10 col-xl-10 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                <!-- تبويب تفاصيل العميل -->
                <div class="tab-pane fade show active" id="customerDetails">
                    @include('tenants.business_center.customers.partials.details', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تبويب متابعة العميل -->
                <div class="tab-pane fade" id="customerFollow">
                    @include('tenants.business_center.customers.partials.follow', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تبويب تفاصيل السداد -->
                <div class="tab-pane fade" id="paymentDetails">
                    @include('tenants.business_center.customers.partials.payment-details', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تبويب الرسائل النصية -->
                {{-- <div class="tab-pane fade" id="smsMessages">
                @include('tenants.business_center.customers.partials.sms-messages', ['customer' => $customer])
            </div> --}}

                <!-- تبويب خطة سداد -->
                <div class="tab-pane fade" id="paymentPlan">
                    @include('tenants.business_center.customers.partials.payment-plan', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- ✅ تبويب سجل السدادات (الجديد) -->
                <div class="tab-pane fade" id="paymentHistory">
                    @include('tenants.business_center.customers.partials.payment-history', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تبويب طلبات التسوية -->
                <div class="tab-pane fade" id="settlementRequests">
                    @include('tenants.business_center.customers.partials.settlement-requests', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تبويب الملاحظات -->
                <div class="tab-pane fade" id="notes">
                    @include('tenants.business_center.customers.partials.notes', ['customer' => $customer])
                </div>

                <!-- تبويب التعليقات والمنشنات (الجديد) -->
                <div class="tab-pane fade" id="comments">
                    @include('tenants.business_center.customers.customer_comments.form', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تبويب المرفقات -->
                <div class="tab-pane fade" id="attachments">
                    @include('tenants.business_center.customers.partials.attachments', [
                        'customer' => $customer,
                    ])
                </div>

                <!-- تاب إضافة هاتف -->
                <div class="tab-pane fade" id="addPhone">
                    @include('tenants.business_center.customers.phone-requests.create', [
                        'customer' => $customer,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
