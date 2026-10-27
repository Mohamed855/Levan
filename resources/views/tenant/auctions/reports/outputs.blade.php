@include('tenant.auctions.reports.layouts.document', [
    'design' => 'haram',
    'title' => 'مخرجات المزاد',
    'pageData' => $haramData,
    'dimensions' => config('auction-report-templates.outputs.page_size'),
])
