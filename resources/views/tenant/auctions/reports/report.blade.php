@include('tenant.auctions.reports.layouts.document', [
    'design' => 'asir',
    'title' => 'تقرير المزاد',
    'pageData' => $asirData,
    'dimensions' => config('auction-report-templates.report.page_size'),
])
