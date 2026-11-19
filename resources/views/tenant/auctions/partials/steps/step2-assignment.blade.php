@php
    $isEdit = isset($auction) && $auction->exists;
    $estData = $isEdit ? ($auction->establishmentRequest?->data ?? []) : [];
    $funds = old('funds', $estData['funds'] ?? []);
    $paymentMethods = [
        'bank_transfer' => 'تحويل بنكي',
        'certified_cheque' => 'شيك مصرفي مصدق',
        'sadad' => 'سداد',
        'pos' => 'نقاط بيع (مدى)',
        'other' => 'أخرى',
    ];
@endphp

<div class="row g-4">
    {{-- تفاصيل المنصة وإدارة المشروع --}}
    <x-form.controls.select name="platform_entity_id" id="platform_entity_id" label="منصة المزاد"
        placeholder="اختر منصة المزاد" :options="$platformEntityOptions ?? []"
        :value="old('platform_entity_id', $auction->platform_entity_id ?? '')" required col="col-md-6" />

    <x-form.controls.input name="platform_url" id="platform_url" type="url" label="رابط المزاد على المنصة"
        placeholder="https://mubasher.sa/auctions/..." :value="old('platform_url', $auction->platform_url ?? '')" col="col-md-6" />

    <x-form.controls.input name="project_manager_name" id="project_manager_name" label="اسم مدير المشروع"
        placeholder="مثال: محمد عبدالله" :value="old('project_manager_name', $auction->project_manager_name ?? '')" required col="col-md-6" />

    <x-form.controls.input name="award_period" id="award_period" label="مدة الترسية بالدقائق" type="number" min="1" step="1"
        placeholder="مثال: 30" :value="old('award_period', $estData['auction']['award_period'] ?? 30)" required col="col-md-3" />

    <x-form.controls.input name="venue" id="venue" label="مقر إقامة المزاد (للحضوري/الهجين)"
        placeholder="مثال: قاعة الفورسيزونز - الرياض" :value="old('venue', $estData['auction']['venue'] ?? '')" col="col-md-3" />

    <x-form.controls.textarea name="notes" id="notes" label="ملاحظات المنصة"
        placeholder="أي تفاصيل أو اشتراطات خاصة بالمنصة" :value="old('notes', $auction->notes ?? '')" col="col-12" rows="2" />

    {{-- استلام وحفظ وإعادة أموال المتنافسين --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-cash fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">استلام وحفظ وإعادة أموال المتنافسين</h6>
        </div>
        <p class="text-muted small mb-0">بيانات الحسابات وضوابط المبالغ المالية للمتنافسين (متطلب نظامي لطلب الإقامة)</p>
    </div>

    <x-form.controls.select name="funds[receipt_method]" id="funds_receipt_method" label="طريقة استلام مبالغ الدخول"
        :options="$paymentMethods" :value="$funds['receipt_method'] ?? 'certified_cheque'" required col="col-md-4" />

    <x-form.controls.input name="funds[receipt_days]" id="funds_receipt_days" label="مدة الاستلام (بالأيام)"
        type="number" min="0" max="365" step="1" :value="$funds['receipt_days'] ?? 0" required col="col-md-4" />

    <x-form.controls.select name="funds[entry_type]" id="funds_entry_type" label="نوع مبلغ الدخول"
        :options="[
            'earnest' => 'عربون',
            'advance' => 'مقدم ثمن',
            'refundable' => 'مبلغ مسترد',
            'none' => 'بدون مبلغ',
        ]" :value="$funds['entry_type'] ?? 'refundable'" required col="col-md-4" />

    <x-form.controls.select name="funds[refund_method]" id="funds_refund_method" label="طريقة إعادة مبالغ الدخول"
        :options="$paymentMethods" :value="$funds['refund_method'] ?? 'bank_transfer'" required col="col-md-4" />

    <x-form.controls.input name="funds[refund_days]" id="funds_refund_days" label="مدة الإعادة (بالأيام - أقصى حد 3)"
        type="number" min="0" max="3" step="1" :value="$funds['refund_days'] ?? 3" required col="col-md-4" />

    <x-form.controls.input name="funds[safekeeping]" id="funds_safekeeping" label="آلية حفظ الأموال"
        placeholder="مثال: حساب مصرفي مخصص لدى بنك الرياض" :value="$funds['safekeeping'] ?? 'حساب مصرفي مخصص للمزاد'" required col="col-md-4" />
</div>
