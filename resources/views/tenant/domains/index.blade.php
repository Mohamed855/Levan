@extends('layouts.dashboard')

@section('title', 'إدارة النطاقات')
@section('dashboard-scope', 'tenant')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('content')
<div class="container-fluid" dir="rtl">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4"><h1 class="h4 mb-0">إدارة النطاقات</h1></div>
    @if (session('domain_verification'))
        <div class="alert alert-warning"><strong>خطوة التحقق المطلوبة:</strong> أضف سجل TXT للنطاق <code>{{ session('domain_verification.domain') }}</code> بالقيمة <code class="user-select-all">levan-verification={{ session('domain_verification.token') }}</code>، ثم استخدم زر التحقق من الجدول.</div>
    @endif
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card title="إجمالي النطاقات" :value="$stats['total']" icon="ti-world" color="primary" /></div>
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card title="النطاقات النشطة" :value="$stats['active']" icon="ti-circle-check" color="success" /></div>
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card title="طلبات معلقة" :value="$stats['pending']" icon="ti-clock" color="warning" /></div>
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card title="تنتهي خلال 30 يومًا" :value="$stats['expiring']" icon="ti-alert-triangle" color="danger" /></div>
    </div>
    @can('domains.manage')
    <div class="row g-4 mb-4">
        <div class="col-lg-6"><form id="domain-availability-form" class="card h-100" method="POST" action="{{ route('tenant.domains.availability') }}">@csrf<div class="card-body"><h2 class="h6">فحص توافر نطاق</h2><p class="text-muted small">افصل بين النطاقات بفاصلة، حتى عشرة نطاقات لكل طلب.</p><div class="input-group"><input class="form-control" name="domains" placeholder="example.com, example.sa" required><button class="btn btn-outline-primary">فحص</button></div><div id="domain-availability-result" class="small mt-3" aria-live="polite"></div></div></form></div>
        <div class="col-lg-6"><form class="card h-100" method="POST" action="{{ route('tenant.domains.link') }}">@csrf<div class="card-body"><h2 class="h6">ربط نطاق خارجي</h2><p class="text-muted small">سنثبت الملكية بسجل TXT قبل تفعيله.</p><div class="input-group"><input class="form-control" name="domain" placeholder="example.com" required><button class="btn btn-primary">بدء الربط</button></div></div></form></div>
        <div class="col-lg-6"><form class="card h-100" method="POST" action="{{ route('tenant.domains.subdomains.store') }}">@csrf<div class="card-body"><h2 class="h6">طلب نطاق فرعي للمنصة</h2><p class="text-muted small">تتم تهيئته من نطاقات المنصة المعتمدة.</p><div class="input-group"><input class="form-control" name="label" placeholder="اسم-المنشأة" required><select class="form-select" name="control_domain_id" required>@foreach($platformDomains as $platformDomain)<option value="{{ $platformDomain->id }}">{{ $platformDomain->domain }}</option>@endforeach</select><button class="btn btn-primary" @disabled($platformDomains->isEmpty())>إرسال</button></div></div></form></div>
    </div>
    @endcan
    <x-data-table.page table-id="tenant-managed-domains-table">
        <x-slot:table>{!! $dataTable->table(['class' => 'table table-bordered']) !!}</x-slot:table>
    </x-data-table.page>
</div>
@endsection

@section('page-script')
<script>
document.getElementById('domain-availability-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const result = document.getElementById('domain-availability-result');
    result.textContent = 'جارٍ الفحص…';
    const domains = this.elements.domains.value.split(',').map((value) => value.trim()).filter(Boolean);
    try {
        const response = await fetch(this.action, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': this.querySelector('[name="_token"]').value, 'Content-Type': 'application/json'}, body: JSON.stringify({domains})});
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'تعذر فحص النطاقات.');
        result.replaceChildren(...data.results.map((item) => {
            const badge = document.createElement('span');
            badge.className = `badge bg-label-${item.available ? 'success' : 'danger'} me-1`;
            badge.textContent = `${item.domain}: ${item.available ? 'متاح' : 'غير متاح'}${item.premium ? ' (مميز)' : ''}`;
            return badge;
        }));
    } catch (error) {
        result.textContent = error.message;
        result.classList.add('text-danger');
    }
});
</script>
@endsection
