@extends('tenant.layouts.layoutMaster')

@section('title', 'إنشاء إعلان ترويجي')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-speakerphone me-2 text-primary"></i>إنشاء إعلان ترويجي للمزاد
            </h4>
            <p class="text-muted small mb-0">اختر نوع القالب المطلوب، القالب النشط، والمزاد لاستخراج الإعلان وتوليد الصورة
                الترويجية.</p>
        </div>
        <a href="{{ tenant_route_name('auctions.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i>العودة للمزادات
        </a>
    </div>

    <x-ui.validation-errors class="mb-4" />

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ tenant_route_name('promotional-advertisements.store') }}" method="POST"
                        id="promotional-ad-form">
                        @csrf
                        @include('tenant.promotional-advertisements.partials._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formCard = document.getElementById('promotional-ad-form-card');
            if (!formCard) return;

            let templatesMap = {};
            try {
                templatesMap = JSON.parse(formCard.dataset.templatesMap || '{}');
            } catch (e) {
                templatesMap = {};
            }

            const typeSelect = $('#template_type');
            const templateSelect = $('#auction_template_id');
            const auctionSelect = $('#auction_id');

            // Initialize Select2 on the 3 dropdowns
            if ($.fn.select2) {
                typeSelect.select2({
                    dir: 'rtl',
                    dropdownParent: typeSelect.parent(),
                    placeholder: 'اختر نوع القالب'
                });

                templateSelect.select2({
                    dir: 'rtl',
                    dropdownParent: templateSelect.parent(),
                    placeholder: 'اختر القالب'
                });

                auctionSelect.select2({
                    dir: 'rtl',
                    dropdownParent: auctionSelect.parent(),
                    placeholder: 'اختر المزاد المطلوب'
                });
            }

            function updateTemplatesList(selectedType, preselectedId = null) {
                templateSelect.empty();
                templateSelect.append(new Option('اختر القالب', '', false, false));

                const templates = templatesMap[selectedType] || [];
                if (templates.length > 0) {
                    templates.forEach(function(tpl) {
                        const isSelected = preselectedId && String(tpl.value) === String(preselectedId);
                        const option = new Option(tpl.label, tpl.value, isSelected, isSelected);
                        templateSelect.append(option);
                    });
                }

                templateSelect.trigger('change');
            }

            // Initial population
            const initialType = typeSelect.val() || 'billboard';
            const initialTemplateId = '{{ old('auction_template_id', '') }}';
            updateTemplatesList(initialType, initialTemplateId);

            // On template type change
            typeSelect.on('change', function() {
                const newType = $(this).val();
                updateTemplatesList(newType);
            });
        });
    </script>
@endsection
