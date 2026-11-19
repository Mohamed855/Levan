<?php

return [
    'auctions_management' => [
        'marketing_campaigns.view', 'marketing_campaigns.create', 'marketing_campaigns.edit', 'marketing_campaigns.delete',
        'auction_establishment_requests.view', 'auction_establishment_requests.create', 'auction_establishment_requests.edit', 'auction_establishment_requests.regenerate',
    ],
    'pending_auctions_management' => ['pending_auctions.view', 'auction_establishment_requests.approve'],
    'auction_closings_management' => ['auction_closings.view', 'auction_closings.create', 'auction_closings.edit', 'auction_closings.regenerate'],
];
