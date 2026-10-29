<x-form.controls.input name="billing_company_name" label="اسم الشركة أو المؤسسة"
    placeholder="مثال: شركة لوت للمزادات العقارية" :value="old('billing_company_name', $checkoutData['billing_company_name'] ?? '')" required />

<x-form.controls.input name="billing_commercial_number" label="رقم السجل التجاري" placeholder="1010xxxxxx"
    inputmode="numeric" maxlength="10" data-digits-only :value="old('billing_commercial_number', $checkoutData['billing_commercial_number'] ?? '')" required />

<x-form.controls.input name="billing_tax_number" label="الرقم الضريبي" placeholder="3xxxxxxxxxxxxx3" inputmode="numeric"
    maxlength="15" data-digits-only :value="old('billing_tax_number', $checkoutData['billing_tax_number'] ?? '')" required />

<x-form.controls.select name="billing_city_id" label="المدينة" placeholder="اختر المدينة" :options="$cities ?? []"
    :selected="old('billing_city_id', $checkoutData['billing_city_id'] ?? '')" required />

<x-form.controls.input name="billing_address" label="العنوان" placeholder="الرياض، حي السليمانية" :value="old('billing_address', $checkoutData['billing_address'] ?? '')"
    required />

<x-form.controls.input name="billing_name" label="اسم المدير المسؤول" placeholder="أدخل اسم المدير" :value="old('billing_name', $checkoutData['billing_name'] ?? '')"
    required />

<x-form.controls.input name="billing_email" type="email" label="البريد الإلكتروني للإدارة"
    placeholder="admin@company.com" autocomplete="off" :value="old('billing_email', $checkoutData['billing_email'] ?? '')" required />

<x-form.controls.input name="billing_phone" type="tel" label="رقم الجوال" placeholder="05XXXXXXXX"
    inputmode="numeric" maxlength="10" required data-phone-format="international" dir="ltr" class="text-end"
    :value="old('billing_phone', $checkoutData['billing_phone'] ?? '')" />

<x-form.controls.password name="billing_password" label="كلمة المرور" placeholder="••••••••" required />

<x-form.controls.password name="billing_password_confirmation" label="تأكيد كلمة المرور" placeholder="••••••••"
    required />
