{{-- في ملف create.blade.php --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">إضافة رقم هاتف إضافي</h5>
    </div>
    <div class="card-body">
        <form id="addPhoneForm">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control phone-input"
                        placeholder="أدخل رقم الهاتف (10 أرقام)" maxlength="10" required>
                    <div class="form-text">يجب أن يكون رقم الهاتف 10 أرقام بالضبط</div>
                    <div class="invalid-feedback" id="phone-error"></div>
                </div>
            </div>

            <div class="text-end">
                @if (auth()->user()->can('تحديث مباشر للبيانات'))
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> إضافة هاتف
                    </button>
                @else
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> إرسال
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

@if (!auth()->user()->can('تحديث مباشر للبيانات'))
    @include('tenants.business_center.customers.partials.phones-list', ['customer' => $customer])
@endif
