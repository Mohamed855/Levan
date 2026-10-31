<?php

return [
    'payment_methods' => [
        ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
        ['value' => 'cash', 'label' => 'Cash / Direct Deposit'],
        ['value' => 'card', 'label' => 'Credit Card / Mada'],
        ['value' => 'system_free', 'label' => 'Promotional / Free Gift'],
    ],
    'payment_statuses' => [
        ['value' => 'completed', 'label' => 'Completed (Payment received & confirmed)'],
        ['value' => 'pending', 'label' => 'Pending (Verification required later)'],
    ],
];
