<div class="checkout-card scroll-revealed">
    <div class="checkout-card-header">
        <h3 class="checkout-card-title"><i class="ti ti-receipt-2"></i> ملخص الطلب</h3>
    </div>
    <div class="checkout-card-body">
        <div class="summary-plan-badge">
            <span class="plan-name">{{ $plan->name }}</span>
            <span class="plan-price" id="total-display">{{ number_format($plan->price_after_discount, 2) }}
                <span class="icon-saudi_riyal"></span></span>
        </div>
        <div class="coupon-box">
            <div class="coupon-input-group">
                <input type="text" id="coupon_code_input" name="coupon_code_display" class="site-input" placeholder="كود الخصم">
                <button type="button" id="apply-coupon-btn" class="btn-apply-coupon"><i class="ti ti-discount-2"></i> تطبيق</button>
            </div>
            <div id="coupon-message" class="mt-2 text-sm"></div>
        </div>
        <div class="summary-details">
            <div class="summary-row"><span class="row-label">المبلغ قبل الضريبة</span><span class="row-value" id="subtotal-display">{{ number_format($priceWithoutTax, 2) }} <span class="icon-saudi_riyal"></span></span></div>
            <div class="summary-row"><span class="row-label">ضريبة القيمة المضافة (15%)</span><span class="row-value" id="vat-display">{{ number_format($taxAmount, 2) }} <span class="icon-saudi_riyal"></span></span></div>
            <div class="summary-row summary-row--discount hidden" id="discount-row"><span class="row-label"><i class="ti ti-discount-2"></i> الخصم <button type="button" class="btn-remove-file p-0 ms-1" id="remove-coupon"><i class="ti ti-x"></i></button></span><span class="row-value">-<span id="discount-display">0.00</span> <span class="icon-saudi_riyal"></span></span></div>
            <div class="summary-row summary-row--total"><span class="row-label">المبلغ الإجمالي</span><span class="row-value" id="total-final">{{ number_format($plan->price_after_discount, 2) }} <span class="icon-saudi_riyal"></span></span></div>
        </div>
        @if ($showSubmitButton ?? false)
            <button type="submit" id="{{ $paymentStep ? 'submit-payment-btn' : 'submit-customer-details-btn' }}" @if (!empty($formId)) form="{{ $formId }}" @endif class="site-btn-submit w-100 justify-content-center">
                <span class="spinner-border hidden" role="status" aria-hidden="true"></span>
                <span class="btn-text">{{ $paymentStep ? 'متابعة إلى الدفع' : 'التالي' }}</span><i class="ti ti-arrow-left"></i>
            </button>
        @endif
        <div class="security-trust-box">
            <div class="trust-item"><i class="ti ti-lock"></i><span>حماية وأمان كامل للبيانات والخصوصية</span></div>
            <div class="trust-item"><i class="ti ti-shield-check"></i><span>معالجة مصرفية موثوقة ومعتمدة</span></div>
        </div>
    </div>
</div>
