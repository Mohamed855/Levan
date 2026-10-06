@props([
    'amountInHalala',
    'callbackUrl',
    'description',
    'publishableKey',
])

@php($formId = 'moyasar-form-' . uniqid())

<p id="{{ $formId }}-error" class="alert alert-danger mb-4" role="alert" hidden>
    تعذر تحميل نموذج الدفع الآمن. تحقق من الاتصال ثم أعد المحاولة.
</p>

<div id="{{ $formId }}" class="mysr-form" aria-label="نموذج الدفع الآمن"></div>

@once('moyasar-form-sdk')
    <script src="https://cdn.moyasar.com/mpf.js"></script>
@endonce

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formElement = document.getElementById(@json($formId));
        const errorMessage = document.getElementById(@json($formId . '-error'));

        if (typeof window.Moyasar === 'undefined') {
            errorMessage.hidden = false;
            return;
        }

        try {
            window.Moyasar.init({
                element: '#' + @json($formId),
                amount: @json($amountInHalala),
                currency: 'SAR',
                description: @json($description),
                publishable_api_key: @json($publishableKey),
                callback_url: @json($callbackUrl),
                methods: ['creditcard'],
                supported_networks: ['mada', 'visa', 'mastercard', 'amex'],
            });
        } catch (error) {
            errorMessage.hidden = false;
            formElement.hidden = true;
        }
    });
</script>
