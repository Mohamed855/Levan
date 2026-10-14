<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <h6 class="fw-bold mb-0">تفاصيل العميل الشاملة</h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm js-whatsapp-share-btn" data-id="{{ $customer->id }}"
                title="إرسال عبر الواتساب">
                <i class="ti ti-brand-whatsapp me-1"></i>
            </button>
            <a href="{{ tenant_route_name('customers.follows.next-customer', $customer->id) }}"
                class="btn btn-primary btn-sm" title="الانتقال إلى العميل التالي">
                التالي
                <i class="ti ti-arrow-left me-1"></i>
            </a>
        </div>
    </div>
    <div class="card-body p-3">
        <!-- معلومات العميل الأساسية -->
        <div class="card border mb-3" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <div class="avatar" style="width: 50px; height: 50px;">
                        <div
                            class="avatar-initial bg-primary rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-user" style="font-size: 24px; color: white;"></i>
                        </div>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-size: 1.1rem;">{{ $customer->name }}</h5>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-{{ $customer->customer_type === 'individual' ? 'info' : 'warning' }}"
                            style="font-size: 0.75rem;">
                            {{ $customer->customer_type === 'individual' ? 'فردي' : 'شركة' }}
                        </span>
                        <span class="badge bg-secondary" style="font-size: 0.75rem;">
                            رقم الهوية: {{ $customer->identity_number }}
                        </span>
                    </div>
                </div>
                <div class="flex-shrink-0 text-end">
                    <div class="mb-2">
                        <div class="d-flex align-items-center justify-content-end gap-2 mb-1">
                            <span class="text-muted" style="font-size: 0.8rem;"> جميع الحسابات</span>
                            <span
                                class="badge bg-sm bg-primary bg-opacity-10 text-white border border-primary border-opacity-25 px-2 py-1">
                                {{ $allAccounts->count() }}
                            </span>
                        </div>
                        <h5 class="text- fw-bold mb-1" style="font-size: 1.1rem; line-height: 1.2;">
                            {{ number_format($comprehensiveStats['total_remaining'], 2) }} ر.س
                        </h5>
                        <small class="text-muted opacity-75" style="font-size: 0.7rem;">المبلغ المتبقي الإجمالي</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- الملخص المالي الشامل -->
        <div class="card border mb-3" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div class="card-body p-2">
                <h6 class="card-title mb-2 d-flex align-items-center" style="font-size: 0.9rem;">
                    <i class="ti ti-chart-bar me-2" style="font-size: 16px;"></i>الملخص المالي الشامل - جميع الحسابات
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" style="font-size: 0.85rem;">
                        <tbody>
                            <tr class="text-center">
                                <td class="fw-bold py-2"
                                    style="background-color: #f8f9fa; width: 25%; font-size: 0.8rem;">
                                    إجمالي المديونية
                                </td>
                                <td class="py-2" style="width: 25%;">
                                    <span class="fw-bold" style="font-size: 0.9rem; color: #dc3545;">
                                        {{ number_format($comprehensiveStats['total_debt'], 2) }} ريال
                                    </span>
                                </td>
                                <td class="fw-bold py-2"
                                    style="background-color: #f8f9fa; width: 25%; font-size: 0.8rem;">
                                    المبلغ المدفوع
                                </td>
                                <td class="py-2" style="width: 25%;">
                                    <span class="fw-bold" style="font-size: 0.9rem; color: #198754;">
                                        {{ number_format($comprehensiveStats['total_paid'], 2) }} ريال
                                    </span>
                                </td>
                            </tr>
                            <tr class="text-center">
                                <td class="fw-bold py-2" style="background-color: #f8f9fa; font-size: 0.8rem;">
                                    مبلغ التسوية
                                </td>
                                <td class="py-2">
                                    <span class="fw-bold" style="font-size: 0.9rem; color: #fd7e14;">
                                        {{ number_format($comprehensiveStats['total_settlement'], 2) }} ريال
                                    </span>
                                </td>
                                <td class="fw-bold py-2" style="background-color: #f8f9fa; font-size: 0.8rem;">
                                    المبلغ المتبقي
                                </td>
                                <td class="py-2">
                                    <span class="fw-bold"
                                        style="font-size: 0.9rem; color: {{ $comprehensiveStats['total_remaining'] > 0 ? '#dc3545' : '#198754' }};">
                                        {{ number_format($comprehensiveStats['total_remaining'], 2) }} ريال
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- تفاصيل كل حساب -->
        @foreach ($allAccounts as $index => $account)
            <div class="card border mb-3"
                style="box-shadow: 0 1px 2px rgba(0,0,0,0.05); {{ $account->id === $customer->id ? 'border-primary border-2;' : '' }}">
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: {{ $account->id === $customer->id ? '#e8f7f6' : '#e8f7f6' }};
           border-bottom: 2px solid {{ $account->id === $customer->id ? '#4cc1bb' : '#4cc1bb' }};">

                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center rounded-circle"
                            style="width: 32px; height: 32px; background-color: {{ $account->id === $customer->id ? '#4cc1bb' : '#4cc1bb' }};">
                            <i class="ti ti-credit-card text-white" style="font-size: 16px;"></i>
                        </div>

                        <div>
                            <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">
                                حساب رقم: {{ $account->account_number ?? 'غير محدد' }}
                                @if ($account->id === $customer->id)
                                    <span class="badge ms-2" style="background-color: #4cc1bb; font-size: 0.7rem;">
                                        <i class="ti ti-star-filled me-1" style="font-size: 9px;"></i>
                                        الحساب الحالي
                                    </span>
                                @endif
                            </h6>
                        </div>
                    </div>

                    <div class="text-end">
                        <div class="text-muted d-flex align-items-center justify-content-end gap-2"
                            style="font-size: 0.75rem;">
                            <span class="d-flex align-items-center gap-1">
                                <i class="ti ti-building" style="font-size: 12px;"></i>
                                {{ $account->company->name }}
                            </span>
                            <span style="color: #dee2e6;">|</span>
                            <span class="d-flex align-items-center gap-1">
                                <i class="ti ti-briefcase" style="font-size: 11px;"></i>
                                {{ $account->portfolio->name }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- معلومات الحساب -->
                    <div class="row g-2 mb-3">
                        @php
                            $hasCustomersPermission = auth()->user()->can('customers.view');
                            $colClass = $hasCustomersPermission ? 'col-md-3' : 'col-md-6';
                        @endphp

                        <!-- الشركة -->
                        <div class="col-md-6 col-sm-6">
                            <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div class="card-body p-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-2" style="font-size: 0.75rem; font-weight: 500;">
                                            الشركة
                                        </div>
                                        <div class="d-flex flex-column align-items-center">
                                            @if ($account->company->logo_url)
                                                <img src="{{ tasset($account->company->logo_url) }}"
                                                    alt="{{ $account->company->name }}"
                                                    style="width: 32px; height: 32px; object-fit: contain; border-radius: 4px; margin-bottom: 8px;">
                                            @endif
                                            <div class="fw-medium text-center"
                                                style="font-size: 0.85rem; line-height: 1.3;">
                                                {{ $account->company->name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- المحفظة -->
                        <div class="col-md-6 col-sm-6">
                            <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div class="card-body p-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-2" style="font-size: 0.75rem; font-weight: 500;">
                                            المحفظة
                                        </div>
                                        <div class="d-flex flex-column align-items-center">
                                            @if ($account->company->logo_url)
                                                <img src="{{ tasset($account->company->logo_url) }}"
                                                    alt="{{ $account->company->name }}"
                                                    style="width: 32px; height: 32px; object-fit: contain; border-radius: 4px; margin-bottom: 8px;">
                                            @endif
                                            <div class="fw-medium text-center"
                                                style="font-size: 0.85rem; line-height: 1.3;">
                                                {{ $account->portfolio->name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- الحالة المالية للحساب -->
                    <div class="card border" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-2 d-flex align-items-center" style="font-size: 0.9rem;">
                                <i class="ti ti-calculator me-2" style="font-size: 16px;"></i>الإحصائيات المالية
                                للحساب
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" style="font-size: 0.85rem;">
                                    <tbody>
                                        <tr class="text-center">
                                            <td class="fw-bold py-2"
                                                style="background-color: #f8f9fa; width: 20%; font-size: 0.8rem;">
                                                المديونية
                                            </td>
                                            <td class="py-2" style="width: 20%;">
                                                <span class="fw-bold" style="font-size: 0.9rem; color: #dc3545;">
                                                    {{ number_format($account->total_debit ?? 0, 2) }} ر.س
                                                </span>
                                            </td>
                                            <td class="fw-bold py-2"
                                                style="background-color: #f8f9fa; width: 20%; font-size: 0.8rem;">
                                                المدفوع
                                            </td>
                                            <td class="py-2" style="width: 20%;">
                                                <span class="fw-bold" style="font-size: 0.9rem; color: #198754;">
                                                    {{ number_format($account->total_credit ?? 0, 2) }} ر.س
                                                </span>
                                            </td>
                                            <td class="fw-bold py-2"
                                                style="background-color: #f8f9fa; width: 20%; font-size: 0.8rem;">
                                                المتبقي
                                            </td>
                                        </tr>
                                        <tr class="text-center">
                                            <td class="fw-bold py-2"
                                                style="background-color: #f8f9fa; font-size: 0.8rem;">
                                                الحالة
                                            </td>
                                            <td class="py-2">
                                                <span class="badge"
                                                    style="background-color: {{ $account->customerStatus->color ?? '#6c757d' }}; font-size: 0.7rem;">
                                                    {{ $account->customerStatus->name ?? 'غير محدد' }}
                                                </span>
                                            </td>
                                            <td class="fw-bold py-2"
                                                style="background-color: #f8f9fa; font-size: 0.8rem;">
                                                تاريخ التعثر
                                            </td>
                                            <td class="py-2">
                                                <span class="fw-medium" style="font-size: 0.85rem;">
                                                    {{ $account->default_date ?? 'لا يوجد' }}
                                                </span>
                                            </td>
                                            <td class="py-2">
                                                <span class="fw-bold"
                                                    style="font-size: 0.9rem; color: {{ ($account->total_debit ?? 0) - ($account->total_credit ?? 0) > 0 ? '#dc3545' : '#198754' }};">
                                                    {{ number_format(($account->total_debit ?? 0) - ($account->total_credit ?? 0), 2) }}
                                                    ر.س
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات إضافية -->
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-user-check" style="font-size: 18px; color: #4cc1bb;"></i>
                                        <div class="flex-grow-1">
                                            <div class="text-muted" style="font-size: 0.7rem; margin-bottom: 2px;">
                                                مدير العلاقات
                                            </div>
                                            <div class="fw-semibold" style="font-size: 0.85rem;">
                                                {{ $account->relationshipManager->name ?? 'غير محدد' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-calendar-plus" style="font-size: 18px; color: #4cc1bb;"></i>
                                        <div class="flex-grow-1">
                                            <div class="text-muted" style="font-size: 0.7rem; margin-bottom: 2px;">
                                                تاريخ الإنشاء
                                            </div>
                                            <div class="fw-semibold" style="font-size: 0.85rem;">
                                                {{ $account->created_at->format('Y-m-d') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- الحقول الإضافية إذا وجدت -->
                    @if ($account->dynamicValues && $account->dynamicValues->count() > 0)
                        <div class="card border mt-2" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-2 d-flex align-items-center" style="font-size: 0.9rem;">
                                    <i class="ti ti-settings me-2" style="font-size: 16px;"></i>الحقول الإضافية
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                        <tbody>
                                            @php
                                                $dynamicValuesArray = $account->dynamicValues->toArray();
                                                $chunks = array_chunk($dynamicValuesArray, 3); // تقسيم إلى مجموعات من 3
                                            @endphp

                                            @foreach ($chunks as $chunk)
                                                <tr>
                                                    @foreach ($chunk as $value)
                                                        <td class="py-2 text-muted fw-medium"
                                                            style="background-color: #f8f9fa; width: 16.66%; font-size: 0.75rem;">
                                                            {{ $value['portfolio_field']['field_name'] ?? 'غير محدد' }}
                                                        </td>
                                                        <td class="py-2 fw-medium" style="width: 16.66%;">
                                                            {{ $value['value'] ?? '-' }}
                                                        </td>
                                                    @endforeach

                                                    @if (count($chunk) == 2)
                                                        <!-- إذا كان هناك حقلين فقط في الصف، أضف خلايا فارغة للثالث -->
                                                        <td class="py-2"
                                                            style="background-color: #f8f9fa; width: 16.66%;"></td>
                                                        <td class="py-2" style="width: 16.66%;"></td>
                                                    @elseif (count($chunk) == 1)
                                                        <!-- إذا كان هناك حقل واحد فقط في الصف، أضف خلايا فارغة للثاني والثالث -->
                                                        <td class="py-2"
                                                            style="background-color: #f8f9fa; width: 16.66%;"></td>
                                                        <td class="py-2" style="width: 16.66%;"></td>
                                                        <td class="py-2"
                                                            style="background-color: #f8f9fa; width: 16.66%;"></td>
                                                        <td class="py-2" style="width: 16.66%;"></td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        <!-- أرقام الهواتف -->
        <div class="card border" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div class="card-body p-2">
                <h6 class="card-title mb-2 d-flex align-items-center" style="font-size: 0.9rem;">
                    <i class="ti ti-phone me-2" style="font-size: 16px; color: #4cc1bb;"></i>أرقام الهاتف
                </h6>

                @if ($customer->phones && $customer->phones->count() > 0)
                    <!-- حالة وجود أرقام هاتف إضافية -->
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: 0.85rem;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th class="border-0 py-1" style="font-size: 0.75rem;">رقم الهاتف</th>
                                    <th class="border-0 py-1" style="font-size: 0.75rem;">النوع</th>
                                    @can('customers.add_phone')
                                        <th class="border-0 py-1 text-center" style="font-size: 0.75rem;">الإجراءات</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                <!-- عرض الرقم الأساسي أولاً -->
                                @if ($customer->phone)
                                    <tr style="background-color: #f8f9fa;">
                                        <td class="py-1">
                                            <span class="fw-medium">{{ $customer->phone }}</span>
                                        </td>
                                        <td class="py-1">
                                            <span class="badge"
                                                style="background-color: #4cc1bb; font-size: 0.7rem;">
                                                <i class="ti ti-star-filled me-1" style="font-size: 9px;"></i>
                                                أساسي
                                            </span>
                                        </td>
                                        @can('customers.add_phone')
                                            <td class="py-1 text-center">
                                                <span class="text-muted" style="font-size: 0.7rem;">الأساسي</span>
                                            </td>
                                        @endcan
                                    </tr>
                                @endif

                                @foreach ($customer->phones->sortByDesc('is_primary') as $phone)
                                    <tr class="{{ $phone->is_primary ? 'table-warning' : '' }}">
                                        <td class="py-1">
                                            <span class="fw-medium">{{ $phone->phone }}</span>
                                        </td>
                                        <td class="py-1">
                                            @if ($phone->is_primary)
                                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
                                                    افتراضي
                                                </span>
                                            @else
                                                <span class="badge bg-info" style="font-size: 0.65rem;">إضافي</span>
                                            @endif
                                        </td>
                                    @can('customers.add_phone')
                                            <td class="py-1 text-center">
                                                @if (!$phone->is_primary)
                                                    <button type="button" class="btn btn-xs set-primary-phone-btn"
                                                        data-phone-id="{{ $phone->id }}"
                                                        data-phone-number="{{ $phone->phone }}" title="تعيين كافتراضي"
                                                        style="font-size: 0.7rem; padding: 2px 6px; background-color: #4cc1bb; border-color: #4cc1bb; color: white;">
                                                        <i class="ti ti-star"></i>
                                                    </button>
                                                @else
                                                    <span class="text-success" style="font-size: 0.7rem;">
                                                        افتراضي
                                                    </span>
                                                @endif
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($customer->phones->where('is_primary', true)->count() > 0)
                        <div class="alert mt-2 mb-0 py-1"
                            style="font-size: 0.75rem; background-color: #e8f7f6; border: 1px solid #4cc1bb; color: #2c7873;">
                            <i class="ti ti-info-circle me-1"></i>
                            <strong>الرقم الافتراضي:</strong>
                            {{ $customer->phones->where('is_primary', true)->first()->phone }}
                            - سيتم استخدامه في المراسلات والاتصالات.
                        </div>
                    @endif
                @elseif($customer->phone)
                    <div class="alert mb-0 py-2"
                        style="font-size: 0.85rem; background-color: #e8f7f6; border: 1px solid #4cc1bb; color: #2c7873;">
                        <i class="ti ti-phone me-2"></i>
                        <strong>الهاتف الأساسي:</strong> {{ $customer->phone }}
                    </div>
                @else
                    <div class="alert alert-warning mb-0 py-2" style="font-size: 0.85rem;">
                        <i class="ti ti-alert-triangle me-2"></i>
                        لا توجد أرقام هاتف مسجلة لهذا العميل.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@include('tenants.business_center.customers.partials.whatsapp_share')
