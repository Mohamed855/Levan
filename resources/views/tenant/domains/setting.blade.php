@extends('tenant.layouts.layoutMaster')

@section('title', 'إدارة النطاقات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
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
    <x-ui.validation-errors />

    <x-data-table.page table-id="domain-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي النطاقات" :value="$totalDomains ?? 0"
                subtitle="عدد النطاقات المسجلة في حسابك" icon="ti-world-www" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl-4" title="النطاقات النشطة" :value="$activeDomains ?? 0"
                subtitle="نطاقات مفعّلة وتعمل حالياً" icon="ti-circle-check" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl-4" title="النطاقات غير النشطة" :value="$inactiveDomains ?? 0"
                subtitle="نطاقات غير مفعّلة" icon="ti-alert-circle" color="warning" />
        </x-slot:stats>

        <x-slot:custom_slot>
            <div class="row g-4">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div
                                    style="width: 50px; height: 50px; background: #e3f2fd; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-left: 15px;">
                                    <i class="ti ti-lifebuoy" style="font-size: 1.5rem; color: #2196f3;"></i>
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
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div
                                    style="width: 50px; height: 50px; background: #f3e5f5; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-left: 15px;">
                                    <i class="ti ti-headset" style="font-size: 1.5rem; color: #9c27b0;"></i>
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
        </x-slot:custom_slot>

        <x-slot:filters>
            <x-data-table.filters table="#domain-table">
                <x-filter.select id="filter-status" filterKey="status" label="حالة النطاق" placeholder="جميع الحالات"
                    :options="$statusOptions" />
                <x-filter.select id="filter-type" filterKey="type" label="نوع النطاق" placeholder="جميع الأنواع"
                    :options="$typeOptions" />
                <x-filter.select id="filter-transfer-status" filterKey="transfer_status" label="حالة النقل"
                    placeholder="جميع حالات النقل" :options="$transferStatusOptions" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ التسجيل من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ التسجيل إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'domain-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>

@endsection
