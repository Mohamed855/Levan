<form id="followForm" novalidate class="mt-3">
    @csrf
    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

    <div class="mb-3">
        <label class="form-label">وسيلة الاتصال <span class="text-danger">*</span></label>
        <select name="contact_method_id" id="contact_method_id" class="form-select select2" required
            data-placeholder="اختر وسيلة الاتصال">
            <option value=""></option>
            @if (isset($formData['contact_methods']))
                @foreach ($formData['contact_methods'] as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                @endforeach
            @endif
        </select>
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label">مكان الزيارة</label>
        <input type="text" name="contact_location" class="form-control" maxlength="255">
    </div>

    <div class="mb-3">
        <label class="form-label">الشخص المتصل به</label>
        <select name="contacted_person" id="contacted_person" class="form-select select2"
            data-placeholder="اختر الشخص المتصل به">
            <option value=""></option>
            <x-form.controls.enum-options :enum="\App\Enums\Tenants\Customers\ContactedPerson::class" />
        </select>
    </div>

    <div class="mb-3" id="contacted_person_name_field" style="display: none;">
        <label class="form-label">اسم الشخص <span class="text-danger">*</span></label>
        <input type="text" name="contacted_person_name" id="contacted_person_name" class="form-control"
            maxlength="255">
    </div>

    <div class="mb-3">
        <label class="form-label">حالة العميل</label>
        <select name="customer_status_id" id="customer_status_id" class="form-select select2"
            data-placeholder="اختر حالة العميل">
            <option value=""></option>
            @if (isset($formData['customer_statuses']))
                @foreach ($formData['customer_statuses'] as $status)
                    <option value="{{ $status->id }}"
                        {{ $customer->customer_status_id == $status->id ? 'selected' : '' }}>
                        {{ $status->name }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">نتيجة الاتصال <span class="text-danger">*</span></label>
        <select name="contact_result_id" id="contact_result_id" class="form-select select2" required
            data-placeholder="اختر نتيجة الاتصال">
            <option value=""></option>
            @if (isset($formData['contact_results']))
                @foreach ($formData['contact_results'] as $result)
                    <option value="{{ $result->id }}">{{ $result->name }}</option>
                @endforeach
            @endif
        </select>
        <div class="invalid-feedback"></div>
    </div>

    <div id="contact_result_fields" style="display: none;">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">تفاصيل نتيجة الاتصال</h6>
            </div>
            <div class="card-body" id="dynamic_fields_container"></div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" maxlength="2000"></textarea>
    </div>

    <div class="d-grid gap-2">
        <button type="button" id="save-follow" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
            حفظ المتابعة
        </button>
    </div>
</form>
