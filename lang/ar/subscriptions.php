<?php

return [
    'payment_methods' => [
        ['value' => 'bank_transfer', 'label' => 'تحويل بنكي'],
        ['value' => 'cash', 'label' => 'نقدي / إيداع مباشر'],
        ['value' => 'card', 'label' => 'بطاقة ائتمان / مدى'],
        ['value' => 'system_free', 'label' => 'إهداء / ترويجي (مجاني)'],
    ],
    'payment_statuses' => [
        ['value' => 'completed', 'label' => 'مكتمل (تم استلام المبلغ وتأكيده)'],
        ['value' => 'pending', 'label' => 'قيد الانتظار (سيتم التحقق لاحقاً)'],
    ],
];
