<main class="form-panel auction-panel">
    <h2 class="section-heading">معلومات المزاد العقاري</h2>
    <div class="auction-fields">
        <table class="form-field auction-summary"><tbody><tr>
            <th>اسم المزاد العقاري</th><td><span class="data-value">{{ $value('auction.name') }}</span></td>
            <th>عدد العقارات محل<br>المزاد العقاري</th><td><span class="data-value">{{ $value('auction.properties_count', count($data['properties'] ?? []) ?: '') }}</span></td>
            <th>عدد الوحدات<br>العقارية</th><td><span class="data-value">{{ $value('auction.units_count') }}</span></td>
        </tr></tbody></table>
        <table class="form-field"><tbody><tr><th>طريقة إقامة المزاد العقاري</th><td class="choices">
            @foreach(['electronic' => 'إلكتروني', 'in_person' => 'غير إلكتروني', 'hybrid' => 'جميعها'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $value('auction.method') === $key])
            @endforeach
        </td></tr></tbody></table>
        <table class="form-field venue-row"><tbody><tr><th>مكان إقامة المزاد العقاري</th><td><div class="venue-grid"><span><u>اسم المنصة العقارية الإلكترونية المرخصة</u><span class="data-value">{{ $value('auction.platform') }}</span></span><span><u>المدينة:</u><span class="data-value">{{ $value('auction.city') }}</span><br><u>مقر إقامة المزاد العقاري:</u><span class="data-value">{{ $value('auction.venue') }}</span></span></div></td></tr></tbody></table>
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'رابط النقل المرئي للمزادات العقارية الإلكترونية - إن وجد -', 'content' => $value('auction.broadcast_url'), 'labelWidth' => 250, 'ltr' => true])
        <div class="date-row">
            <table class="form-field"><tbody><tr><th>تاريخ إقامة المزاد العقاري</th><td><div class="auction-dates"><span>البداية: <span class="data-value" dir="ltr">{{ $value('auction.start_date', ' / / ') }}</span></span><span>النهاية: <span class="data-value" dir="ltr">{{ $value('auction.end_date', ' / / ') }}</span></span></div></td></tr></tbody></table>
            <table class="start-time"><thead><tr><th>وقت البداية</th></tr></thead><tbody><tr><td class="data-value" dir="ltr">{{ $value('auction.start_time') }}</td></tr></tbody></table>
        </div>
        <table class="form-field"><tbody><tr><th style="width:215pt">آلية استلام أموال المتنافسين في المزاد العقاري</th><td class="choices compact-choices">
            @foreach(['transfer' => 'حوالة بنكية', 'cheque' => 'شيك', 'other' => 'أخرى'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $value('funds.receipt_method') === $key])
            @endforeach
            <span class="other-detail">تحديد آلية الاستلام<br><span class="data-value">{{ $value('funds.receipt_other') }}</span></span>
        </td></tr></tbody></table>
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'آلية حفظ أموال المتنافسين في المزاد العقاري', 'content' => $value('funds.safekeeping'), 'labelWidth' => 242])
        <table class="form-field short-row"><tbody><tr><th style="width:291pt">المدّة الزمنية لاستلام أموال المتنافسين قبل إقامة المزاد العقاري</th><td>عدد الأيام <span class="data-value">{{ $value('funds.receipt_days') }}</span> قبل إقامة المزاد</td></tr></tbody></table>
        <table class="form-field"><tbody><tr><th style="width:161pt">آلية استرجاع أموال المتنافسين<small>(عدا من رسى عليه المزاد العقاري)</small></th><td class="choices compact-choices">
            @foreach(['transfer' => 'حوالة بنكية', 'cheque' => 'شيك', 'other' => 'أخرى'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $value('funds.refund_method') === $key])
            @endforeach
            <span class="other-detail">تحديد آلية الاسترجاع<br><span class="data-value">{{ $value('funds.refund_other') }}</span></span>
        </td></tr></tbody></table>
        <table class="form-field"><tbody><tr><th style="width:303pt">المدة الزمنية لاسترجاع أموال المتنافسين<small>(عدا من رسى عليه المزاد العقاري)<br>• على ألا تتجاوز المدة ٣ أيام عمل</small></th><td>عدد الأيام <span class="data-value">{{ $value('funds.refund_days') }}</span> بعد إقامة المزاد</td></tr></tbody></table>
        <table class="form-field short-row"><tbody><tr><th style="width:145pt">نوع مبلغ الدخول في المزايدة</th><td class="choices compact-choices">
            @foreach(['earnest' => 'عربون', 'advance' => 'مقدم ثمن', 'refundable' => 'تأمين مسترد', 'none' => 'لا يوجد'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $value('funds.entry_type') === $key])
            @endforeach
        </td></tr></tbody></table>
        @include('tenant.auctions.regulatory-forms.field', ['label' => 'المدة الزمنية المحددة للترسية على السعر الأعلى بعد المزايدة', 'note' => '• يجب ألا تتجاوز المدة دقيقتان', 'content' => $value('auction.award_period'), 'labelWidth' => 273])
        <table class="form-field disposition-row"><tbody><tr><th style="width:235pt">نوع التصرف بالعقار محل المزاد العقاري</th><td class="choices compact-choices">
            @foreach(['sale' => 'بيع', 'lease' => 'تأجير'] as $key => $label)
                @include('tenant.auctions.regulatory-forms.choices', ['label' => $label, 'checked' => $value('auction.disposition') === $key])
            @endforeach
        </td></tr></tbody></table>
    </div>
</main>
