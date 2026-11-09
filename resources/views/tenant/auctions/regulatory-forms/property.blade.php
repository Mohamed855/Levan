@php
    $property = $data['property'] ?? (count($data['properties'] ?? []) === 1 ? $firstProperty : []);
    $pv = static fn (string $key): string => (string) ($property[$key] ?? '');
@endphp
<main class="form-panel property-panel">
    <div class="property-heading">
        <h2 class="section-heading">بيانات العقار محل المزاد العقاري</h2>
        <p>في حال كان المزاد العقاري يحتوي على أكثر من مالك أو أكثر من عقار يجب استخدام نموذج البيان (صفحة رقم ٤) في تحديد معلومات العقارات محل المزاد العقاري وفي حال وجود وكلاء يجب تعبئة البيان (صفحة رقم ٥) مع التنويه بتعبئة كافة البيانات المطلوبة في البيان ومواءمتها في عقد المزاد الإلكتروني</p>
    </div>
    <div class="property-identity">
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'اسم المالك', 'content' => $pv('owner_name')])
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'رقم وثيقة الملكية', 'content' => $pv('deed_number'), 'ltr' => true])
    </div>
    <fieldset class="agent-info"><legend>معلومات الوكيل - إن وجد</legend>
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'اسم الوكيل', 'content' => $pv('agent_name')])
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'رقم الوكالة الإلكترونية', 'content' => $pv('agency_number'), 'ltr' => true])
    </fieldset>
    <div class="property-description">
        <div class="two-fields">
            @include('tenant.auctions.regulatory-forms.field', ['label' => 'مساحة العقار', 'content' => $pv('area'), 'labelWidth' => 76])
            @include('tenant.auctions.regulatory-forms.field', ['label' => 'عُمر العقار', 'content' => $pv('age'), 'labelWidth' => 78])
        </div>
        <div class="two-fields">
            <table class="form-field"><tbody><tr><th>نوع العقار <small>(فيلا، شقة، دبلكس، إلخ)</small></th><td class="data-value">{{ $pv('type') }}</td></tr></tbody></table>
            <table class="form-field"><tbody><tr><th>نوع استخدام العقار <small>(سكني، تجاري)</small></th><td class="data-value">{{ $pv('usage') }}</td></tr></tbody></table>
        </div>
        <table class="form-field property-status"><tbody><tr><th style="width:63pt">حالة العقار</th><td class="choices compact-choices">
            @foreach(['clear' => 'بدون قيود', 'seized' => 'محجوز', 'mortgaged' => 'مرهون', 'endowed' => 'موقوف', 'other' => 'أخرى:'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $pv('status') === $key])
            @endforeach
            <span class="other-detail">يتم تحديدها<br><span class="data-value">{{ $pv('status_other') }}</span></span>
        </td></tr></tbody></table>
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'الحد الأدنى لقيمة بدء المزاد العقاري', 'content' => $pv('opening_value'), 'labelWidth' => 166, 'class' => 'opening-value'])
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'مبلغ الدخول في المزايدة - إن وجد -', 'note' => '- في حال تم تحديد مبلغ الدخول في المزايدة يجب إرفاق نموذج التقييم العقاري المعتمد على ألا يتجاوز مبلغ الدخول نسبة ٥٪ من القيمة التقديرية للعقار بناءً على تقييم من مقيم عقاري معتمد', 'content' => $pv('entry_amount'), 'labelWidth' => 242, 'class' => 'entry-amount'])
    </div>
    <section class="property-rights">
        <h2 class="section-heading">الحقوق والقيود محل المزاد العقاري</h2>
        <table class="form-field rights-types"><tbody><tr><th style="width:120pt">هل توجد أحكام متعلقة<br>بالتصرف في العقار</th><td class="choices compact-choices">
            @foreach(['none' => 'لا يوجد', 'preemption' => 'حق الشفعة', 'session_option' => 'خيار المجلس', 'condition_option' => 'خيار الشرط', 'earnest_sale' => 'بيع العربون', 'other' => 'غير ذلك'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $value('disclosures.disposition_right') === $key])
            @endforeach
            <span class="data-value">{{ $value('disclosures.disposition_right_other') }}</span>
        </td></tr></tbody></table>
        <table class="form-field ownership-share"><tbody><tr><th style="width:117pt">نسبة ملكية العقار</th><td class="choices compact-choices">
            @include('tenant.auctions.regulatory-forms.choices', ['label' => '100%', 'checked' => $pv('ownership_share') === '100'])
            @include('tenant.auctions.regulatory-forms.choices', ['label' => 'غير ذلك', 'checked' => $pv('ownership_share') !== '' && $pv('ownership_share') !== '100'])
            <span class="data-value">{{ $pv('ownership_share') !== '100' ? $pv('ownership_share') : '' }}</span>
        </td></tr></tbody></table>
        @foreach([
            'real_rights' => 'هل توجد حقوق عينية أو التزامات مرتبطة بالعقار',
            'dispute' => 'هل يوجد رهن أو قيد أو نزاع قد يؤثر في الانتفاع بالعقار',
            'value_information' => 'هل توجد معلومات تؤثر في قيمة العقار',
            'off_plan' => 'هل سيتم بيع أحد العقارات بنموذج البيع أو التأجير على الخارطة',
            'contribution' => 'هل توجد مساهمة عقارية مرتبطة بأحد العقارات محل المزاد العقاري',
        ] as $key => $label)
            <table class="form-field disclosure-row"><tbody><tr><th>{{ $label }}</th><td class="choices compact-choices">
                @include('tenant.auctions.regulatory-forms.choices', ['label' => 'لا', 'checked' => $value('disclosures.'.$key) === 'no'])
                @include('tenant.auctions.regulatory-forms.choices', ['label' => 'نعم (يتم ذكرها):', 'checked' => $value('disclosures.'.$key) === 'yes'])
                <span class="dotted data-value">{{ $value('disclosures.'.$key.'_details') }}</span>
            </td></tr></tbody></table>
        @endforeach
    </section>
</main>
