@extends('tenants.layouts.layoutMaster')

@section('title', ' الفاتورة')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="row g-2">
        <div class="col-6">
            <div class="card px-3 ">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-repeat text-primary me-2"></i>
                        دفع الفاتورة
                    </h6>
                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="alert alert-outline-warning d-flex justify-content-around my-3 mx-3" role="alert">
                    <p class="m-0 fw-bold">رقم الفاتورة: {{ $bill->bill_number }}</p>
                    <p class="m-0 fw-bold">المبلغ المستحق: {{ number_format($amount, 2) }} <span
                            class="icon-saudi_riyal"></span></p>
                </div>

                <form action="{{ tenant_route_name('bills.upload-receipt', $bill->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf

                    <!-- اختيار البنك -->
                    <div class="form-group mb-3">
                        <label for="bank_id" class="form-label">
                            البنك المحول إليه
                        </label>

                        <select class="form-select select2 @error('bank_id') is-invalid @enderror" id="bank_id"
                            name="bank_id" data-placeholder="اختر البنك" required>
                            <option value=""></option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('bank_id') == $bank->id)>{{ $bank->bank_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="receipt">إيصال التحويل</label>
                        <input type="file" class="form-control @error('receipt') is-invalid @enderror" id="receipt"
                            name="receipt" value="{{ old('receipt') }}" required>
                        @error('receipt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- <div class="form-group mb-3">
                        <label for="transfer_date">تاريخ التحويل</label>
                        <input type="date" class="form-control @error('transfer_date') is-invalid @enderror"
                            id="transfer_date" value="{{ old('transfer_date') }}" name="transfer_date" required>
                        @error('transfer_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="notes">ملاحظات (اختياري)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    </div> --}}

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">رفع الإيصال</button>
                    </div>

                    <div class="alert alert-warning d-flex align-items-center mt-2" role="alert">
                        <i class="ti ti-clock me-2 fs-5"></i>
                        <div>
                            <strong>معلومات هامة:</strong> بعد رفع الإيصال، سيقوم فريقنا بالتحقق منه خلال <strong>يوم عمل
                                واحد</strong>. سيتم تفعيل اشتراكك فورًا بعد تأكيد الدفع
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            <div class="card shadow-sm border-0 ">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-building-bank text-primary me-2"></i>
                        البنوك المتاحة للتحويل
                    </h6>
                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body p-4">
                    @if (count($banks) > 0)
                        <div class="accordion" id="banksAccordion">
                            @foreach ($banks as $index => $bank)
                                <div class="accordion-item border mb-3 shadow-sm">
                                    <h2 class="accordion-header" id="heading{{ $index }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $index }}" aria-expanded="false"
                                            aria-controls="collapse{{ $index }}">
                                            <div class="d-flex align-items-center w-100">
                                                <img class="me-3 rounded-circle border" width="50" height="50"
                                                    src="{{ tasset($bank->icon) }}" alt="{{ $bank->bank_name }}">
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $bank->bank_name }}</h6>
                                                    <small class="text-muted">اضغط لعرض التفاصيل</small>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $index }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading{{ $index }}" data-bs-parent="#banksAccordion">
                                        <div class="accordion-body">
                                            <div class="row">

                                                <div class="col-md-12">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <i class="ti ti-user-circle text-primary me-2 fs-5"></i>
                                                        <div>
                                                            <small class="text-muted d-block">اسم الحساب</small>
                                                            <span class="fw-semibold">{{ $bank->account_name }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex align-items-center mb-3">
                                                        <i class="ti ti-credit-card text-primary me-2 fs-5"></i>
                                                        <div>
                                                            <small class="text-muted d-block">رقم الحساب</small>
                                                            <span class="fw-semibold">{{ $bank->account_number }}</span>

                                                        </div>
                                                    </div>

                                                    <div class="d-flex align-items-center">
                                                        <i class="ti ti-id text-primary me-2 fs-5"></i>
                                                        <div>
                                                            <small class="text-muted d-block">رقم IBAN</small>
                                                            <span class="fw-semibold">{{ $bank->iban }}</span>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert alert-outline-dark mt-3 d-flex align-items-center">
                            <i class="ti ti-info-circle me-2 fs-5"></i>
                            <div>
                                <strong>ملاحظة هامة:</strong> يرجى التأكد من إدخال المعلومات بشكل صحيح عند إجراء التحويل.
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="ti ti-alert-triangle me-2 fs-5"></i>
                            <div>
                                لا توجد معلومات بنكية متاحة حالياً. يرجى التواصل مع الإدارة.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || 'الكل';
                },
                dir: 'rtl', // دعم اللغة العربية
                language: {
                    noResults: function() {
                        return "لا توجد نتائج";
                    },
                    searching: function() {
                        return "جاري البحث...";
                    }
                }
            });
        });
    </script>
@endsection
