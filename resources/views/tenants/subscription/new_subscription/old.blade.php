@extends('tenants.layouts.layoutMaster')

@section('title', 'إتمام عملية الدفع')
@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection
@section('content')

    <style>
        :root {
            --primary-color: #3a416f;
            --secondary-color: #545b8c;
            --accent-color: #6c63ff;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #3a416f15 0%, #545b8c15 100%);
            min-height: 100vh;
        }

        .payment-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 20px;
        }

        .payment-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 4px solid var(--primary-color);
        }

        .payment-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .payment-header .icon i {
            font-size: 2.5rem;
            color: white;
        }

        .payment-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .alert-payment-required {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid var(--warning-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-payment-required .icon-warning {
            font-size: 2rem;
            color: var(--warning-color);
            margin-bottom: 1rem;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .payment-card h3 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 1rem;
        }

        .billing-toggle {
            background: white;
            border-radius: 50px;
            padding: 0.25rem;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .billing-toggle .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            border: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .billing-toggle .btn.active {
            background: var(--primary-color) !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(58, 65, 111, 0.4);
        }

        .billing-toggle .btn:not(.active) {
            background: transparent;
            color: #6c757d;
        }

        .plans-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 2rem;
        }

        .plan-card-full {
            background: white;
            border: 3px solid #e5e7eb;
            border-radius: 15px;
            padding: 0;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .plan-card-full:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(58, 65, 111, 0.15);
            border-color: var(--accent-color);
        }

        .plan-card-full.selected {
            border-color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(58, 65, 111, 0.25);
        }

        .plan-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
            flex-shrink: 0;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .plan-price .currency {
            font-size: 1.2rem;
        }

        .plan-period {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .plan-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
            flex-shrink: 0;
        }

        .plan-features li {
            padding: 0.5rem 0;
            display: flex;
            align-items: start;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .plan-features li i {
            color: var(--success-color);
            font-size: 1rem;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .license-section {
            border-top: 2px solid #e5e7eb;
            padding-top: 1.5rem;
            margin-top: auto;
            flex-shrink: 0;
        }

        .license-counter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 1rem 0;
            min-height: 40px;
        }

        .counter-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .counter-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
        }

        .counter-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .counter-input {
            width: 80px;
            height: 40px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .counter-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(58, 65, 111, 0.2);
        }

        .ranges-table-wrapper {
            max-height: 180px;
            overflow-y: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-top: 1rem;
        }

        .ranges-table {
            width: 100%;
            font-size: 0.85rem;
            border-collapse: collapse;
        }

        .ranges-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 0.65rem 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
            border-bottom: 2px solid var(--primary-color);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .ranges-table td {
            padding: 0.65rem 0.5rem;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .ranges-table tr:last-child td {
            border-bottom: none;
        }

        .ranges-table tbody tr:hover {
            background: #f8fafc;
        }

        .ranges-table tr.active-range {
            background: linear-gradient(135deg, #3a416f15 0%, #545b8c15 100%);
            font-weight: bold;
        }

        .select-plan-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            flex-shrink: 0;
        }

        .select-plan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 65, 111, 0.3);
        }

        .select-plan-btn.selected {
            background: var(--success-color);
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 65, 111, 0.1);
        }

        .form-control.card-valid {
            border-color: var(--success-color);
        }

        .form-control.card-invalid {
            border-color: var(--danger-color);
        }

        .card-icons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .card-icon {
            width: 50px;
            height: 32px;
            background: #f3f4f6;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
            color: #6b7280;
            opacity: 0.4;
            transition: all 0.3s ease;
        }

        .card-icon.active {
            opacity: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            transform: scale(1.1);
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 10px;
            margin-top: 1.5rem;
        }

        .security-badge i {
            color: var(--success-color);
            font-size: 1.5rem;
        }

        .price-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .price-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--primary-color);
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid var(--primary-color);
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 1.2rem 2rem;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-pay:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(58, 65, 111, 0.3);
        }

        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .input-group-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        #paymentLoader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(58, 65, 111, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            backdrop-filter: blur(8px);
        }

        #paymentLoader.show {
            display: flex !important;
        }

        .loader-content {
            background: white;
            padding: 3rem 4rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .loader-spinner {
            width: 70px;
            height: 70px;
            border: 7px solid #f3f3f3;
            border-top: 7px solid var(--primary-color);
            border-right: 7px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1.2s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 992px) {
            .plans-wrapper {
                grid-template-columns: 1fr;
            }

            .input-group-card {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- Loading Overlay -->
    <div id="paymentLoader">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <h4>جاري معالجة الدفع...</h4>
            <p>الرجاء عدم إغلاق الصفحة</p>
        </div>
    </div>

    <!-- Alert -->
    <div class="alert-payment-required">
        <div class="text-center">
            <i class="bi bi-exclamation-triangle icon-warning"></i>
            <h4 class="fw-bold mb-2">الاشتراك مطلوب</h4>
            <p class="mb-0">
                <strong>يجب عليك إتمام عملية الدفع أولاً للوصول إلى النظام.</strong><br>
                بعد إتمام الدفع، سيتم تفعيل حسابك فوراً وستتمكن من استخدام جميع الميزات.
            </p>
        </div>
    </div>

    <!-- Billing Toggle -->
    <div class="text-center mb-4">
        <div class="billing-toggle">
            <button type="button" class="btn billing-cycle-btn active" data-cycle="monthly">
                شهري
            </button>
            <button type="button" class="btn billing-cycle-btn" data-cycle="yearly">
                سنوي
                <span class="badge bg-success ms-1">وفّر 20%</span>
            </button>
        </div>
    </div>

    <!-- Plans -->
    <div class="plans-wrapper">
        @foreach ($plans as $plan)
            <div class="plan-card-full" data-plan-id="{{ $plan->id }}">
                <div class="plan-header">
                    <div class="plan-name">{{ $plan->name }}</div>
                    <div class="plan-price">
                        <span class="plan-price-value">{{ number_format($plan->price_monthly, 2) }}</span>
                        <span class="currency">ر.س</span>
                    </div>
                    <div class="plan-period plan-period-text">/ شهرياً</div>
                </div>

                <div class="plan-body">
                    <!-- Features -->
                    <ul class="plan-features">
                        @foreach ($plan->features ?? [] as $feature)
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <!-- License Section -->
                    @if ($plan->userRanges && $plan->userRanges->count() > 0)
                        <div class="license-section">
                            <h6 class="fw-semibold text-center mb-2">
                                <i class="bi bi-people-fill me-2"></i>
                                التراخيص الإضافية
                            </h6>
                            <p class="text-center text-muted small mb-2">الباقة تشمل ترخيص واحد افتراضياً</p>

                            <!-- License Counter -->
                            <div class="license-counter">
                                <button type="button" class="counter-btn plan-decrement">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number" class="counter-input plan-license-count" value="0" min="0"
                                    readonly>
                                <button type="button" class="counter-btn plan-increment">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>

                            <p class="text-center mb-2">
                                <small class="fw-semibold">سعر الترخيص الحالي: <span
                                        class="plan-current-price text-primary">0.00</span> ر.س</small>
                            </p>

                            <!-- Ranges Table -->
                            <div class="ranges-table-wrapper">
                                <table class="ranges-table">
                                    <thead>
                                        <tr>
                                            <th>النطاق</th>
                                            <th>الاسم</th>
                                            <th>السعر</th>
                                        </tr>
                                    </thead>
                                    <tbody class="plan-ranges-body">
                                        @foreach ($plan->userRanges->sortBy('from') as $range)
                                            <tr data-range-id="{{ $range->id }}" data-from="{{ $range->from }}"
                                                data-to="{{ $range->to ?? 999999 }}" data-price="{{ $range->price }}">
                                                <td>
                                                    @if ($range->to)
                                                        {{ $range->from }}-{{ $range->to }}
                                                    @else
                                                        {{ $range->from }}+
                                                    @endif
                                                </td>
                                                <td>{{ $range->name }}</td>
                                                <td class="fw-bold">{{ number_format($range->price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Select Button -->
                    <button type="button" class="select-plan-btn" data-plan-id="{{ $plan->id }}">
                        <i class="bi bi-check-circle me-2"></i>
                        اختر هذه الباقة
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Payment Form -->
    <form id="paymentForm" method="POST" action="{{ tenant_route_name('subscriptions.payment.nosubscription') }}"
        style="display: none;">
        @csrf

        <input type="hidden" name="plan_id" id="selectedPlanId">
        <input type="hidden" name="billing_cycle" id="selectedBillingCycle" value="monthly">
        <input type="hidden" name="amount" id="selectedAmount">
        <input type="hidden" name="price" id="selectedPrice">
        <input type="hidden" name="licenses_quota" id="selectedLicenses" value="0">
        <input type="hidden" name="range_id" id="selectedRangeId">
        <input type="hidden" name="price_per_license" id="selectedPricePerLicense" value="0">
        <input type="hidden" name="cardType" id="cardType" value="">
        <input type="hidden" name="description" value="دفع اشتراك جديد">

        <div class="row g-4 mt-4">
            <!-- Payment Details -->
            <div class="col-lg-7">
                <div class="payment-card">
                    <h3>
                        <i class="bi bi-credit-card-2-front"></i>
                        معلومات الدفع
                    </h3>

                    <!-- Card Holder Name -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-person"></i>
                            اسم حامل البطاقة
                        </label>
                        <input type="text" name="card_holder_name" id="cardHolderName" class="form-control"
                            placeholder="الاسم كما يظهر على البطاقة" required>
                    </div>

                    <!-- Card Number -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-credit-card"></i>
                            رقم البطاقة
                        </label>
                        <input type="text" name="card_number" id="cardNumber" class="form-control"
                            placeholder="1234 5678 9012 3456" maxlength="19" inputmode="numeric" required>
                        <div class="card-icons mt-2">
                            <div class="card-icon" data-type="visa">VISA</div>
                            <div class="card-icon" data-type="mastercard">MC</div>
                            <div class="card-icon" data-type="amex">AMEX</div>
                            <div class="card-icon" data-type="mada">MADA</div>
                        </div>
                        <small class="text-muted" id="cardTypeText"></small>
                    </div>

                    <!-- Expiry & CVV -->
                    <div class="input-group-card mb-3">
                        <div>
                            <label class="form-label">
                                <i class="bi bi-calendar-event"></i>
                                تاريخ الانتهاء
                            </label>
                            <input type="text" name="exp_date_display" id="cardExpiry" class="form-control"
                                placeholder="MM/YY" maxlength="5" inputmode="numeric" required>
                        </div>
                        <div>
                            <label class="form-label">
                                <i class="bi bi-lock"></i>
                                CVV
                            </label>
                            <input type="text" name="cvc" id="cardCvv" class="form-control" placeholder="123"
                                maxlength="4" inputmode="numeric" required>
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="security-badge">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>دفع آمن ومشفر</strong>
                            <p class="mb-0 small text-muted">جميع معلومات الدفع محمية بواسطة Moyasar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="col-lg-5">
                <div class="payment-card">
                    <h3>
                        <i class="bi bi-receipt"></i>
                        ملخص الطلب
                    </h3>

                    <div class="mb-3">
                        <strong>الباقة المختارة:</strong>
                        <p class="mb-0" id="summaryPlanName">-</p>
                    </div>

                    <div class="price-summary">
                        <div class="price-row">
                            <span>سعر الباقة</span>
                            <span id="summaryPlanPrice">0.00 ر.س</span>
                        </div>
                        <div class="price-row" id="licenseCostRow" style="display: none;">
                            <span>تكلفة التراخيص (<span id="summaryLicenseCount">0</span>)</span>
                            <span id="summaryLicenseCost">0.00 ر.س</span>
                        </div>
                        <div class="price-row">
                            <span>المجموع قبل الضريبة</span>
                            <span id="summarySubtotal">0.00 ر.س</span>
                        </div>
                        <div class="price-row">
                            <span>ضريبة القيمة المضافة (15%)</span>
                            <span id="summaryVat">0.00 ر.س</span>
                        </div>
                        <div class="price-row">
                            <span>الإجمالي</span>
                            <span id="summaryTotal">0.00 ر.س</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-pay" id="payButton">
                        <i class="bi bi-lock-fill me-2"></i>
                        إتمام الدفع
                    </button>

                    <p class="text-center text-muted small mt-3 mb-0">
                        بالضغط على "إتمام الدفع"، أنت توافق على
                        <a href="#" style="color: var(--primary-color);">الشروط والأحكام</a>
                    </p>
                </div>
            </div>
        </div>
    </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('paymentLoader');
            const form = document.getElementById('paymentForm');
            const planCards = document.querySelectorAll('.plan-card-full');
            const billingCycleBtns = document.querySelectorAll('.billing-cycle-btn');
            const cardNumberInput = document.getElementById('cardNumber');
            const cardTypeInput = document.getElementById('cardType');
            const cardTypeText = document.getElementById('cardTypeText');
            const cardIcons = document.querySelectorAll('.card-icon');

            let selectedPlanData = null;
            let currentCycle = 'monthly';
            let detectedCardType = '';

            // Card Type Detection - Regex مُصلح
            function getCardType(number) {
                const cleanNumber = number.replace(/\s/g, '');

                // MADA - تبسيط الـ Regex
                if (/^(4(0(0861|1757)|1(0685|7633|9593)|2(2817|2818|2819|8331|8671|8672|8673)|3(1361|2328|4107|9954)|4(0533|0647|0795|5564|6393|6404|6672)|5(5036|5708|7865|8456)|6(2220|8540|8541|8542|8543)|8(3010|3011|3012|4783|6094|6095|6096|9317|9318|9319)|93428))/
                    .test(cleanNumber)) {
                    return {
                        type: 'mada',
                        name: 'MADA',
                        maxLength: 16
                    };
                }

                if (/^(5(04300|08160|13333|21683|20160|28121))/.test(cleanNumber)) {
                    return {
                        type: 'mada',
                        name: 'MADA',
                        maxLength: 16
                    };
                }

                // VISA
                if (/^4[0-9]{0,15}$/.test(cleanNumber)) {
                    return {
                        type: 'creditcard',
                        name: 'Visa',
                        maxLength: 16
                    };
                }

                // Mastercard
                if (/^(5[1-5][0-9]{0,14}|2(22[1-9]|2[3-9][0-9]|[3-6][0-9]{2}|7[01][0-9]|720)[0-9]{0,12})$/.test(
                        cleanNumber)) {
                    return {
                        type: 'creditcard',
                        name: 'Mastercard',
                        maxLength: 16
                    };
                }

                // American Express
                if (/^3[47][0-9]{0,13}$/.test(cleanNumber)) {
                    return {
                        type: 'creditcard',
                        name: 'American Express',
                        maxLength: 15
                    };
                }

                return {
                    type: '',
                    name: 'Unknown',
                    maxLength: 16
                };
            }

            // Luhn Algorithm
            function validateLuhn(cardNumber) {
                const digits = cardNumber.replace(/\s/g, '').split('').reverse();
                let sum = 0;

                for (let i = 0; i < digits.length; i++) {
                    let digit = parseInt(digits[i]);

                    if (i % 2 === 1) {
                        digit *= 2;
                        if (digit > 9) digit -= 9;
                    }

                    sum += digit;
                }

                return sum % 10 === 0;
            }

            // Format Card Number
            function formatCardNumber(value, maxLength) {
                const cleaned = value.replace(/\s/g, '');
                const limited = cleaned.substring(0, maxLength);
                return limited.match(/.{1,4}/g)?.join(' ') || limited;
            }

            // Card Number Handler
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');

                    const cardInfo = getCardType(value);
                    detectedCardType = cardInfo.type;

                    cardTypeInput.value = detectedCardType;

                    e.target.value = formatCardNumber(value, cardInfo.maxLength);
                    const spacesCount = Math.floor(cardInfo.maxLength / 4);
                    e.target.maxLength = cardInfo.maxLength + spacesCount;

                    cardIcons.forEach(icon => {
                        icon.classList.remove('active');
                        const iconType = icon.dataset.type.toLowerCase();
                        const cardName = cardInfo.name.toLowerCase();

                        if ((iconType === 'visa' && cardName.includes('visa')) ||
                            (iconType === 'mastercard' && cardName.includes('mastercard')) ||
                            (iconType === 'amex' && cardName.includes('amex')) ||
                            (iconType === 'mada' && cardName.includes('mada'))) {
                            icon.classList.add('active');
                        }
                    });

                    if (cardInfo.name !== 'Unknown') {
                        cardTypeText.textContent = 'نوع البطاقة: ' + cardInfo.name;
                        cardTypeText.style.color = 'var(--success-color)';
                    } else {
                        cardTypeText.textContent = '';
                    }

                    if (value.length >= 13) {
                        const isValid = validateLuhn(value);
                        if (isValid) {
                            e.target.classList.add('card-valid');
                            e.target.classList.remove('card-invalid');
                        } else {
                            e.target.classList.add('card-invalid');
                            e.target.classList.remove('card-valid');
                        }
                    } else {
                        e.target.classList.remove('card-valid', 'card-invalid');
                    }
                });
            }

            // Expiry Date
            const expiryInput = document.getElementById('cardExpiry');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.slice(0, 2) + '/' + value.slice(2, 4);
                    }
                    e.target.value = value;
                });
            }

            // CVV
            const cvvInput = document.getElementById('cardCvv');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                });
            }

            // Billing Cycle
            billingCycleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    billingCycleBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentCycle = this.dataset.cycle;
                    document.getElementById('selectedBillingCycle').value = currentCycle;

                    updateAllPlanPrices();

                    if (selectedPlanData) {
                        updateSummary();
                    }
                });
            });

            // حفظ الأسعار
            planCards.forEach(card => {
                const priceElement = card.querySelector('.plan-price-value');
                const originalPrice = parseFloat(priceElement.textContent.replace(/,/g, ''));
                priceElement.dataset.monthly = originalPrice;
            });

            // تحديث الأسعار
            function updateAllPlanPrices() {
                planCards.forEach(card => {
                    const priceElement = card.querySelector('.plan-price-value');
                    const periodElement = card.querySelector('.plan-period-text');
                    const monthlyPrice = parseFloat(priceElement.dataset.monthly);

                    if (currentCycle === 'monthly') {
                        priceElement.textContent = monthlyPrice.toFixed(2);
                        periodElement.textContent = '/ شهرياً';
                    } else {
                        const yearlyPrice = monthlyPrice * 12 * 0.8;
                        const yearlyMonthly = yearlyPrice / 12;
                        priceElement.textContent = yearlyMonthly.toFixed(2);
                        periodElement.textContent = '/ شهرياً (سنوي)';
                    }
                });
            }

            // العدادات
            planCards.forEach(card => {
                const incrementBtn = card.querySelector('.plan-increment');
                const decrementBtn = card.querySelector('.plan-decrement');
                const countInput = card.querySelector('.plan-license-count');
                const currentPriceSpan = card.querySelector('.plan-current-price');
                const rangesBody = card.querySelector('.plan-ranges-body');

                if (!incrementBtn || !decrementBtn || !countInput) return;

                let licenseCount = 0;

                updatePlanLicensePrice(card, licenseCount, currentPriceSpan, rangesBody);

                incrementBtn.addEventListener('click', function() {
                    licenseCount++;
                    countInput.value = licenseCount;
                    updatePlanLicensePrice(card, licenseCount, currentPriceSpan, rangesBody);
                });

                decrementBtn.addEventListener('click', function() {
                    if (licenseCount > 0) {
                        licenseCount--;
                        countInput.value = licenseCount;
                        updatePlanLicensePrice(card, licenseCount, currentPriceSpan, rangesBody);
                    }
                });
            });

            function updatePlanLicensePrice(card, count, priceSpan, rangesBody) {
                if (!rangesBody) return;

                const rows = rangesBody.querySelectorAll('tr');
                let currentPrice = 0;

                rows.forEach(row => {
                    row.classList.remove('active-range');
                    const from = parseInt(row.dataset.from);
                    const to = parseInt(row.dataset.to);
                    const price = parseFloat(row.dataset.price);

                    if (count >= from && count <= to) {
                        row.classList.add('active-range');
                        currentPrice = price;
                    }
                });

                if (priceSpan) {
                    priceSpan.textContent = currentPrice.toFixed(2);
                }
            }

            // اختيار الباقة
            document.querySelectorAll('.select-plan-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const planId = this.dataset.planId;
                    const card = document.querySelector('.plan-card-full[data-plan-id="' + planId +
                        '"]');

                    if (!card) return;

                    document.querySelectorAll('.select-plan-btn').forEach(b => {
                        b.innerHTML =
                            '<i class="bi bi-check-circle me-2"></i>اختر هذه الباقة';
                        b.classList.remove('selected');
                    });
                    document.querySelectorAll('.plan-card-full').forEach(c => {
                        c.classList.remove('selected');
                    });

                    card.classList.add('selected');
                    this.classList.add('selected');
                    this.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>تم الاختيار';

                    const planName = card.querySelector('.plan-name').textContent;
                    const planPriceElement = card.querySelector('.plan-price-value');
                    const monthlyPrice = parseFloat(planPriceElement.dataset.monthly);
                    const licenseCountInput = card.querySelector('.plan-license-count');
                    const licenseCount = licenseCountInput ? parseInt(licenseCountInput.value) ||
                        0 : 0;

                    let rangeId = null;
                    let licensePrice = 0;

                    if (licenseCount > 0) {
                        const rangesBody = card.querySelector('.plan-ranges-body');
                        if (rangesBody) {
                            const activeRange = rangesBody.querySelector('tr.active-range');
                            if (activeRange) {
                                rangeId = activeRange.dataset.rangeId;
                                licensePrice = parseFloat(activeRange.dataset.price);
                            }
                        }
                    }

                    selectedPlanData = {
                        id: planId,
                        name: planName,
                        monthlyPrice: monthlyPrice,
                        licenseCount: licenseCount,
                        licensePrice: licensePrice,
                        rangeId: rangeId
                    };

                    form.style.display = 'block';
                    updateSummary();

                    setTimeout(function() {
                        form.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                });
            });

            // تحديث الملخص
            function updateSummary() {
                if (!selectedPlanData) return;

                const basePrice = currentCycle === 'monthly' ?
                    selectedPlanData.monthlyPrice :
                    (selectedPlanData.monthlyPrice * 12 * 0.8);

                const licensePrice = selectedPlanData.licensePrice || 0;
                const licensesCount = selectedPlanData.licenseCount || 0;

                const licenseCost = licensesCount * licensePrice * (currentCycle === 'yearly' ? 12 : 1);
                const subtotal = basePrice + licenseCost;
                const vat = subtotal * 0.15;
                const total = subtotal + vat;

                document.getElementById('selectedPlanId').value = selectedPlanData.id;
                document.getElementById('selectedBillingCycle').value = currentCycle;
                document.getElementById('selectedAmount').value = total.toFixed(2);
                document.getElementById('selectedPrice').value = subtotal.toFixed(2);
                document.getElementById('selectedLicenses').value = licensesCount;
                document.getElementById('selectedRangeId').value = selectedPlanData.rangeId || '';
                document.getElementById('selectedPricePerLicense').value = licensePrice.toFixed(2);

                document.getElementById('summaryPlanName').textContent =
                    selectedPlanData.name + ' - ' + (currentCycle === 'monthly' ? 'شهري' : 'سنوي');
                document.getElementById('summaryPlanPrice').textContent = basePrice.toFixed(2) + ' ر.س';
                document.getElementById('summarySubtotal').textContent = subtotal.toFixed(2) + ' ر.س';
                document.getElementById('summaryVat').textContent = vat.toFixed(2) + ' ر.س';
                document.getElementById('summaryTotal').textContent = total.toFixed(2) + ' ر.س';

                const licenseCostRow = document.getElementById('licenseCostRow');
                if (licensesCount > 0) {
                    licenseCostRow.style.display = 'flex';
                    document.getElementById('summaryLicenseCount').textContent = licensesCount;
                    document.getElementById('summaryLicenseCost').textContent = licenseCost.toFixed(2) + ' ر.س';
                } else {
                    licenseCostRow.style.display = 'none';
                }

                document.getElementById('payButton').innerHTML =
                    '<i class="bi bi-lock-fill me-2"></i> إتمام الدفع بمبلغ ' + total.toFixed(2) + ' ر.س';
            }

            // Form Submission
            form.addEventListener('submit', function(e) {
                if (!selectedPlanData) {
                    e.preventDefault();
                    alert('الرجاء اختيار باقة أولاً');
                    return false;
                }

                const cardNumber = cardNumberInput.value.replace(/\s/g, '');
                if (!validateLuhn(cardNumber)) {
                    e.preventDefault();
                    alert('رقم البطاقة غير صحيح. الرجاء التحقق من الأرقام');
                    return false;
                }

                if (!detectedCardType) {
                    e.preventDefault();
                    alert('لم نتمكن من تحديد نوع البطاقة. الرجاء استخدام بطاقة صالحة');
                    return false;
                }

                loader.classList.add('show');
                document.body.style.overflow = 'hidden';

                return true;
            });

            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    loader.classList.remove('show');
                    document.body.style.overflow = 'auto';
                }
            });
        });
    </script>

@endsection
