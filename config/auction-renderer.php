<?php

return [
    'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
    'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    'pdf_rasterizer_binary' => env('AUCTION_REPORT_PDF_RASTERIZER', 'pdftoppm'),
    'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 120),
    'queue_connection' => env('AUCTION_REPORT_QUEUE_CONNECTION', 'auction_reports'),
];
