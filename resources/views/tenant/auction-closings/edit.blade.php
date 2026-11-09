@extends('tenant.layouts.layoutMaster')

@section('title', 'إقفال المزاد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/auctions/cycle-forms.js'])
@endsection

@section('content')
    <x-ui.validation-errors />
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-body">
                    <form id="report-cycle-form" method="POST" enctype="multipart/form-data" autocomplete="off"
                        data-form-validate action="{{ tenant_route_name('auction-closings.update', $auction) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-4 mb-4">
                            <x-form.controls.input required name="approval_number" label="رقم الموافقة على إقامة المزاد"
                                :value="old('approval_number', $auction->closing?->approval_number)" col="col-md-4" />
                            <x-form.controls.input name="closing_date" label="تاريخ الإقفال" type="date"
                                :value="old(
                                    'closing_date',
                                    $auction->closing?->closing_date?->format('Y-m-d') ?? now()->toDateString(),
                                )" required col="col-md-4" />
                            <x-form.controls.input name="board_removal_date" label="تاريخ فك اللوحات" type="date"
                                :value="old(
                                    'board_removal_date',
                                    $auction->marketing_data['board_removal_date'] ?? null,
                                )" required col="col-md-4" />
                            <x-form.controls.textarea name="results_narrative" label="تفاصيل نتائج المزاد" :value="old('results_narrative', $auction->marketing_data['results_narrative'] ?? null)"
                                col="col-12" />
                        </div>
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="ti ti-alert-triangle ti-md me-1 mt-1"></i>
                            <span>يجب تحديد نتيجة كل عقار داخل المزاد لإقفاله.</span>
                        </div>
                        @forelse($auction->properties as $property)
                            @php($prefix = 'results[' . $property->id . ']')
                            <section class="border rounded p-4 mb-4" data-closing-result>
                                <h6 class="fw-bold mb-4">{{ $property->name }} — {{ $property->property_number }}</h6>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="row g-4">
                                            @foreach (['opening_price' => 'سعر الافتتاح', 'participants_count' => 'عدد المشاركين', 'bids_count' => 'عدد المزايدات'] as $field => $label)
                                                <x-form.controls.input :name="$prefix . '[' . $field . ']'" :label="$label" type="number"
                                                    min="0" :step="$field === 'opening_price' ? '0.01' : '1'" :value="old(
                                                        'results.' . $property->id . '.' . $field,
                                                        $property->pivot->{$field},
                                                    )" col="col-md-4" />
                                            @endforeach
                                        </div>
                                    </div>
                                    <x-form.controls.select :name="$prefix . '[status]'" label="نتيجة العقار" :options="['sold' => 'مباع / تمت الترسية', 'unsold' => 'غير مباع']"
                                        :value="old('results.'.$property->id.'.status', $property->pivot->result_status === 'pending' ? '' : $property->pivot->result_status)" required col="col-12" data-result-status />
                                    <x-form.controls.input :name="$prefix . '[buyer_name]'" label="اسم من تمت الترسية عليه"
                                        :value="old(
                                            'results.' . $property->id . '.buyer_name',
                                            $property->pivot->buyer_name,
                                        )" required col="col-md-6" data-result-kind="sold" />
                                    <x-form.controls.input :name="$prefix . '[sale_price]'" label="قيمة الترسية" type="number"
                                        min="0" step="0.01" :value="old(
                                            'results.' . $property->id . '.sale_price',
                                            $property->pivot->sale_price,
                                        )" required col="col-md-6"
                                        data-result-kind="sold" />
                                    <x-form.controls.textarea :name="$prefix . '[unsold_reason]'" label="سبب عدم البيع" :value="old(
                                        'results.' . $property->id . '.unsold_reason',
                                        $property->pivot->unsold_reason,
                                    )"
                                        required col="col-12" data-result-kind="unsold" />

                                    <x-form.controls.textarea :name="$prefix . '[result_notes]'" label="ملاحظات النتيجة" :value="old(
                                        'results.' . $property->id . '.result_notes',
                                        $property->pivot->result_notes,
                                    )"
                                        col="col-12" />
                                    <x-form.controls.file :name="'board_images_after[' . $property->id . '][]'" label="صور فك اللوحات" multiple
                                        accept=".png,.jpg,.jpeg,.webp" col="col-12" />
                                    @foreach ($auction->attachments->where('property_id', $property->id) as $file)
                                        <div class="col-md-6">
                                            <x-ui.file-card :file="$file" />
                                            <x-form.controls.checkbox name="remove_board_evidence[]" :id="'remove-board-' . $file->id"
                                                :value="$file->id" label="حذف الصورة عند الحفظ" col="mt-3" />
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @empty
                            <div class="alert alert-info">لا توجد عقارات مرتبطة بالمزاد لإقفالها.</div>
                        @endforelse
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('auction-closings.index')" text="العودة" />
                            <x-form.wizard.save-button :disabled="$auction->properties->isEmpty()" />
                        </x-form.wizard.actions>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
