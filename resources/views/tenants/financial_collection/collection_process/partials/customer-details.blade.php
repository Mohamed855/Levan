<div class="card-body p-3">
    <!-- معلومات العميل الأساسية -->
    <div class="card border mb-3" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="card-body p-3 d-flex align-items-center">
            <div class="flex-shrink-0 me-3">
                <div class="avatar" style="width: 50px; height: 50px;">
                    <div
                        class="avatar-initial bg-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ti ti-user" style="font-size: 1.5rem; color: white;"></i>
                    </div>
                </div>
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1" style="font-size: 1.1rem;">{{ $customer->name ?? 'غير محدد' }}</h5>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-{{ $customer->customer_type === 'individual' ? 'info' : 'warning' }}"
                        style="font-size: 0.75rem;">
                        {{ $customer->customer_type === 'individual' ? 'فردي' : 'شركة' }}
                    </span>
                    <span class="badge bg-secondary" style="font-size: 0.75rem;">
                        هوية رقم : {{ $customer->identity_number ?? 'غير محدد' }}
                    </span>
                    <span class="badge bg-success d-flex align-items-center gap-1 px-2 py-1"
                        style="font-size: 0.75rem; cursor: pointer; border: 1px solid rgba(255,255,255,0.3);"
                        onclick="showWhatsAppShareModal({{ $customer->id }})" title="إرسال عبر الواتساب">
                        <i class="ti ti-brand-whatsapp" style="font-size: 0.85rem; color: white;"></i>
                    </span>
                </div>
            </div>
            <div class="flex-shrink-0 text-end">
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-1">
                        <span class="text-muted" style="font-size: 0.8rem;">جميع الحسابات</span>
                        <span
                            class="badge bg-sm bg-primary bg-opacity-10 text-white border border-primary border-opacity-25 px-2 py-1">
                            {{ $customerData['comprehensive_stats']['total_accounts'] }}
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1" style="font-size: 1.1rem; line-height: 1.2;">
                        {{ number_format($customerData['comprehensive_stats']['total_remaining'], 2) }} ر.س
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
                <i class="ti ti-chart-bar me-2" style="font-size: 1rem;"></i>الملخص المالي الشامل - جميع الحسابات
            </h6>
            <div class="mb-2 border-dashed border-1 border-light"></div>

            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 0.85rem;">
                    <tbody>
                        <tr class="text-center">
                            <td class="fw-bold py-2" style="background-color: #f8f9fa; width: 25%; font-size: 0.8rem;">
                                إجمالي المديونية
                            </td>
                            <td class="py-2" style="width: 25%;">
                                <span class="fw-bold" style="font-size: 0.9rem; color: #dc3545;">
                                    {{ number_format($customerData['comprehensive_stats']['total_debt'], 2) }} ريال
                                </span>
                            </td>

                            <td class="fw-bold py-2" style="background-color: #f8f9fa; width: 25%; font-size: 0.8rem;">
                                المبلغ المدفوع
                            </td>
                            <td class="py-2" style="width: 25%;">
                                <span class="fw-bold" style="font-size: 0.9rem; color: #198754;">
                                    {{ number_format($customerData['comprehensive_stats']['total_paid'], 2) }} ريال
                                </span>
                            </td>
                        </tr>

                        <tr class="text-center">
                            <td class="fw-bold py-2" style="background-color: #f8f9fa; width: 25%; font-size: 0.8rem;">
                                مبلغ التسوية
                            </td>
                            <td class="py-2" style="width: 25%;">
                                <span class="fw-bold" style="font-size: 0.9rem; color: #fd7e14;">
                                    {{ number_format($customerData['comprehensive_stats']['total_settlement'] ?? 0, 2) }}
                                    ريال
                                </span>
                            </td>

                            <td class="fw-bold py-2" style="background-color: #f8f9fa; width: 25%; font-size: 0.8rem;">
                                المبلغ المتبقي
                            </td>
                            <td class="py-2" style="width: 25%;">
                                <span class="fw-bold"
                                    style="font-size: 0.9rem; color: {{ ($customerData['comprehensive_stats']['total_remaining'] ?? 0) > 0 ? '#dc3545' : '#198754' }};">
                                    {{ number_format($customerData['comprehensive_stats']['total_remaining'] ?? 0, 2) }}
                                    ريال
                                </span>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>


    @php
        // ترتيب الحسابات: الحساب الحالي أولاً
        $sortedAccounts = $customerData['all_accounts']->sortByDesc(function ($account) use ($customer) {
            return $account->id === $customer->id ? 1 : 0;
        });

        $hasCustomersPermission = auth()->user()->can('customers.view');
        $colClass = $hasCustomersPermission ? 'col-md-4' : 'col-md-6';
    @endphp

    @foreach ($sortedAccounts as $account)
        @php
            $isCurrentAccount = $account->id === $customer->id;
            $accountCollapseId = 'accountCollapse_' . $account->id;
            $fieldsCollapseId = 'fieldsCollapse_' . $account->id;

            // تصفية الحقول الإضافية الفارغة
            $dynamicFields = $account->dynamicValues->filter(function ($value) {
                $val = $value->value ?? null;
                return !is_null($val) && (!is_string($val) || !in_array(trim($val), ['', 'غير محدد', '-']));
            });
        @endphp

        <div class="card border mb-3"
            style="box-shadow: 0 1px 2px rgba(0,0,0,0.05); {{ $isCurrentAccount ? 'border-primary border-2;' : '' }}">
            <div class="card-header py-2 d-flex justify-content-between align-items-center {{ $isCurrentAccount ? '' : 'cursor-pointer collapsed' }}"
                style="background-color: #e8f7f6; border-bottom: 2px solid #4cc1bb;"
                @if (!$isCurrentAccount) data-bs-toggle="collapse" data-bs-target="#{{ $accountCollapseId }}" aria-expanded="false"
            aria-controls="{{ $accountCollapseId }}" role="button" @endif>

                {{-- Left side --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                        style="width: 2rem; height: 2rem; background-color: #4cc1bb;">
                        <i class="ti ti-credit-card text-white" style="font-size: 1rem;"></i>
                    </div>

                    <div>
                        <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">
                            حساب رقم: {{ $account->account_number ?? 'غير محدد' }}

                            @if ($isCurrentAccount)
                                <span class="badge ms-2" style="background-color: #4cc1bb; font-size: 0.7rem;">
                                    <i class="ti ti-star-filled me-1" style="font-size: 9px;"></i>
                                    الحساب الحالي
                                </span>
                            @endif
                        </h6>
                    </div>
                </div>

                {{-- Right side --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="text-muted d-flex align-items-center justify-content-end gap-2"
                            style="font-size: 0.75rem;">
                            <span class="d-flex align-items-center gap-1">
                                <i class="ti ti-building" style="font-size: 0.75rem;"></i>
                                {{ $account->company->name }}
                            </span>
                            <span style="color: #dee2e6;">|</span>
                            <span class="d-flex align-items-center gap-1">
                                <i class="ti ti-briefcase" style="font-size: 11px;"></i>
                                {{ $account->portfolio->name }}
                            </span>
                        </div>
                    </div>

                    @if (!$isCurrentAccount)
                        <i class="ti ti-chevron-down collapse-icon" style="font-size: 18px;"></i>
                    @endif
                </div>
            </div>

            <div class="{{ $isCurrentAccount ? '' : 'collapse' }}"
                @if (!$isCurrentAccount) id="{{ $accountCollapseId }}" @endif>
                <div class="card-body p-3">
                    <!-- الشركة والمحفظة -->
                    <div class="row g-2 mb-3">
                        <div class="{{ $colClass }} col-sm-6">
                            <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div class="card-body p-3 text-center">
                                    <div class="text-muted mb-2" style="font-size: 0.75rem; font-weight: 500;">الشركة
                                    </div>
                                    @if ($account->company->logo_url)
                                        <img src="{{ tasset($account->company->logo_url) }}"
                                            alt="{{ $account->company->name }}"
                                            style="width: 2rem; height: 2rem; object-fit: contain; border-radius: 0.25rem; margin-bottom: 0.5rem;">
                                    @endif
                                    <div class="fw-medium" style="font-size: 0.85rem;">{{ $account->company->name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="{{ $colClass }} col-sm-6">
                            <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div class="card-body p-3 text-center">
                                    <div class="text-muted mb-2" style="font-size: 0.75rem; font-weight: 500;">المحفظة
                                    </div>
                                    @if ($account->company->logo_url)
                                        <img src="{{ tasset($account->company->logo_url) }}"
                                            alt="{{ $account->company->name }}"
                                            style="width: 2rem; height: 2rem; object-fit: contain; border-radius: 0.25rem; margin-bottom: 0.5rem;">
                                    @endif
                                    <div class="fw-medium" style="font-size: 0.85rem;">
                                        {{ $account->portfolio->name }}</div>
                                </div>
                            </div>
                        </div>

                        @can('customers.view')
                            <div class="col-md-4 col-sm-12">
                                <div class="card border h-100" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted mb-2" style="font-size: 0.75rem; font-weight: 500;">تاريخ
                                            التعاقد
                                        </div>
                                        <div class="fw-medium" style="font-size: 0.85rem;">
                                            {{ $account->contract_date ??
                                                'غير
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        محدد' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>

                    <!-- الإحصائيات المالية -->
                    <div class="card border mb-3" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-2 d-flex align-items-center" style="font-size: 0.9rem;">
                                <i class="ti ti-calculator me-2" style="font-size: 1rem;"></i>الإحصائيات المالية
                            </h6>
                            <div class="mb-2 border-dashed border-1 border-light"></div>
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
                                                الحالة</td>
                                            <td class="py-2">
                                                <span class="badge"
                                                    style="background-color: {{ $account->customerStatus->color ?? '#6c757d' }}; font-size: 0.7rem;">
                                                    {{ $account->customerStatus->name ?? 'غير محدد' }}
                                                </span>
                                            </td>
                                            <td class="fw-bold py-2"
                                                style="background-color: #f8f9fa; font-size: 0.8rem;">
                                                تاريخ التعثر</td>
                                            <td class="py-2">
                                                <span class="fw-medium" style="font-size: 0.85rem;">
                                                    {{ $account->default_date
                                                        ? \Carbon\Carbon::parse($account->default_date)->format('Y-m-d')
                                                        : 'لا
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            يوجد' }}
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

                    <!-- الحقول الإضافية -->
                    @if ($dynamicFields->count() > 0)
                        <div class="card border" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div class="card-header py-2 cursor-pointer" data-bs-toggle="collapse"
                                data-bs-target="#{{ $fieldsCollapseId }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                                        <i class="ti ti-settings me-2" style="font-size: 1rem;"></i>الحقول الإضافية
                                    </h6>
                                    <i class="ti ti-chevron-down collapse-icon" style="font-size: 1rem;"></i>
                                </div>
                            </div>
                            <div class="collapse" id="{{ $fieldsCollapseId }}">
                                <div class="card-body p-2">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                            <thead style="background-color: #f8f9fa;">
                                                <tr>
                                                    <th class="py-1" style="font-size: 0.75rem;">اسم الحقل</th>
                                                    <th class="py-1" style="font-size: 0.75rem;">القيمة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($dynamicFields as $field)
                                                    <tr>
                                                        <td class="py-1 text-muted">
                                                            {{ $field->portfolioField->field_name ?? '' }}
                                                        </td>
                                                        <td class="py-1 fw-medium">{{ $field->value ?? '' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <!-- أرقام الهواتف -->
    <div class="card border mb-3" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <div class="card-body p-2">

            {{-- العنوان + زر الإضافة (مع نفس الشكل المرجعي) --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="card-title mb-0 d-flex align-items-center" style="font-size: 0.9rem;">
                    <i class="ti ti-phone me-2" style="font-size: 1rem; color: #4cc1bb;"></i>
                    أرقام الهاتف
                </h6>
                @can('customers.add_phone')
                    <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                        onclick="showAddPhoneModal({{ $customer->id }})" title="إضافة رقم هاتف"
                        aria-label="إضافة رقم هاتف">
                        <i class="ti ti-circle-plus fs-6"></i>
                        <span class="d-none d-md-inline">إضافة</span>
                    </button>
                @endcan
            </div>
            <div class="mb-2 border-dashed border-1 border-light"></div>


            @if (isset($customer->phones) && $customer->phones->count() > 0)
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
                            {{-- عرض الرقم الأساسي أولاً --}}
                            @if ($customer->phone)
                                <tr style="background-color: #f8f9fa;">
                                    <td class="py-1">
                                        <span class="fw-medium">{{ $customer->phone }}</span>
                                    </td>
                                    <td class="py-1">
                                        <span class="badge" style="background-color: #4cc1bb; font-size: 0.7rem;">
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

                            {{-- أرقام إضافية --}}
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
                                            <span class="badge bg-info" style="font-size: 0.65rem;">
                                                إضافي
                                            </span>
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
                                                <span class="text-success" style="font-size: 0.7rem;">افتراضي</span>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- تنبيه الرقم الافتراضي (بنفس ألوان المرجع) --}}
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

    <!-- ملاحظات العميل -->
    <div class="card border mb-3" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">


        <div class="card-header py-2">
            <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                <i class="ti ti-notes me-2" style="font-size: 1rem; color: #4cc1bb;"></i>اخر 5 ملاحظات على العميل
            </h6>
        </div>
        <div class="mb-2 border-dashed border-1 border-light"></div>

        <div class="card-body p-3">
            @forelse ($customer->notes->take(5) as $note)
                <div class="p-2 border rounded mb-2" style="font-size: 0.85rem;">
                    <p class="mb-1">{{ $note->note }}</p>
                    <small class="text-muted">
                        {{ $note->created_at->format('Y-m-d H:i') }} - {{ $note->createdBy->name ?? 'غير محدد' }}
                    </small>
                </div>
            @empty
                <div class="text-muted text-center py-2" style="font-size: 0.85rem;">
                    لا توجد ملاحظات لهذا العميل
                </div>
            @endforelse
        </div>
    </div>

    <!-- آخر متابعة -->
    @if (isset($customer->customerFollows) && $customer->customerFollows->count() > 0)
        <div class="card border mb-3" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div class="card-header py-2">
                <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                    <i class="bx bx-history me-2" style="font-size: 1rem;"></i>آخر متابعة
                </h6>
            </div>
            <div class="mb-2 border-dashed border-1 border-light"></div>

            <div class="card-body p-3">
                @php $lastFollow = $customer->customerFollows->first(); @endphp
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                            <tr>
                                <td class="w-25 fw-semibold">نتيجة الاتصال</td>
                                <td><span
                                        class="badge bg-primary">{{ $lastFollow->contactResult->name ?? 'غير محدد' }}</span>
                                </td>
                                <td class="w-25 fw-semibold">وسيلة الاتصال</td>
                                <td>{{ $lastFollow->contactMethod->name ?? 'غير محدد' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">التاريخ</td>
                                <td>{{ $lastFollow->created_at->format('m-d H:i') }}</td>
                                <td class="fw-semibold">الموظف</td>
                                <td>{{ $lastFollow->createdBy->name ?? 'غير محدد' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if ($lastFollow->notes)
                    <div class="mb-3">
                        <h6 class="fw-semibold mb-1">الملاحظات</h6>
                        <div class="p-2 border rounded position-relative">
                            {{ $lastFollow->notes }}
                            <button type="button" class="btn btn-sm position-absolute top-0 end-0 m-1"
                                onclick="copyNoteToClipboard(this, {{ json_encode($lastFollow->notes) }})"
                                title="نسخ الملاحظة"
                                style="width: 2rem; height: 2rem; border-radius: 50%; padding: 0; background-color: #f8f9fa; border: 1px solid #dee2e6; transition: all 0.2s;">
                                <i class="ti ti-copy" style="font-size: 14px; color: #6c757d;"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @switch($lastFollow->contact_result_id)
                    @case(1)
                        @if ($lastFollow->fullPaymentDetails)
                            <div class="p-2 border border-success rounded">
                                <h6 class="text-success mb-1 fw-semibold">تفاصيل السداد الكامل</h6>
                                <p class="mb-0">المبلغ:
                                    {{ number_format($lastFollow->fullPaymentDetails->payment_amount, 2) }} ر.س -
                                    التاريخ: {{ $lastFollow->fullPaymentDetails->payment_date->format('Y-m-d') }}</p>
                            </div>
                        @endif
                    @break

                    @case(2)
                        @if ($lastFollow->partialPaymentDetails)
                            <div class="p-2 border border-info rounded">
                                <h6 class="text-info mb-1 fw-semibold">تفاصيل السداد الجزئي</h6>
                                <p class="mb-0">المبلغ:
                                    {{ number_format($lastFollow->partialPaymentDetails->partial_payment_amount, 2) }} ر.س -
                                    التاريخ: {{ $lastFollow->partialPaymentDetails->partial_payment_date->format('Y-m-d') }}
                                </p>
                            </div>
                        @endif
                    @break

                    @case(3)
                        @if ($lastFollow->promisePaymentDetails)
                            <div class="p-2 border border-warning rounded">
                                <h6 class="text-warning mb-1 fw-semibold">وعد السداد</h6>
                                <p class="mb-0">المبلغ:
                                    {{ number_format($lastFollow->promisePaymentDetails->promise_amount, 2) }} ر.س -
                                    الموعد: {{ $lastFollow->promisePaymentDetails->promise_date->format('Y-m-d') }}</p>
                            </div>
                        @endif
                    @break

                    @case(4)
                        @if ($lastFollow->callbackDetails)
                            <div class="p-2 border border-primary rounded">
                                <h6 class="text-primary mb-1 fw-semibold">معاودة الاتصال</h6>
                                <p class="mb-0">الموعد:
                                    {{ $lastFollow->callbackDetails->callback_date->format('Y-m-d H:i') }}</p>
                            </div>
                        @endif
                    @break
                @endswitch
            </div>
        </div>
    @endif

    <!-- سجل المتابعات الأخيرة -->
    @if (isset($customer->customerFollows) && $customer->customerFollows->count() > 1)
        <div class="card border" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div class="card-header py-2">
                <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                    <i class="bx bx-list-ul me-2" style="font-size: 1rem;"></i>سجل المتابعات الأخيرة
                </h6>
            </div>
            <div class="mb-2 border-dashed border-1 border-light"></div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-2 px-3">التاريخ</th>
                                <th class="py-2 px-3">وسيلة الاتصال</th>
                                <th class="py-2 px-3">النتيجة</th>
                                <th class="py-2 px-3">الموظف</th>
                                <th class="py-2 px-3">الملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customer->customerFollows->slice(1, 3) as $follow)
                                <tr>
                                    <td class="py-2 px-3" style="white-space: nowrap;">
                                        <div class="text-nowrap">{{ $follow->created_at->format('m-d') }}</div>
                                        <small class="text-muted">{{ $follow->created_at->format('H:i') }}</small>
                                    </td>
                                    <td class="py-2 px-3" style="word-break: break-word; max-width: 120px;">
                                        <span
                                            class="badge bg-secondary">{{ $follow->contactMethod->name ?? 'غير محدد' }}</span>
                                    </td>
                                    <td class="py-2 px-3" style="word-break: break-word; max-width: 120px;">
                                        <span
                                            class="badge bg-primary">{{ $follow->contactResult->name ?? 'غير محدد' }}</span>
                                    </td>
                                    <td class="small py-2 px-3" style="word-break: break-word; max-width: 100px;">
                                        {{ $follow->createdBy->name ?? 'غير محدد' }}</td>
                                    <td class="py-2 px-3" style="word-break: break-word; max-width: 150px;">
                                        <div class="small">
                                            {{ $follow->notes ?: '-' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
