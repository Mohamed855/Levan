<main class="form-panel organization-panel">
    <section class="organization-section">
        <h2 class="section-heading">معلومات المنشأة</h2>
        <div class="organization-fields">
            @include('tenant.auctions.regulatory-forms.field', ['label' => 'اســــم المنشــــــــــــــــأة', 'content' => $value('organization.name')])
            @include('tenant.auctions.regulatory-forms.field', ['label' => 'رقــم السجــل التجــــــاري', 'content' => $value('organization.commercial_registration'), 'ltr' => true])
            @include('tenant.auctions.regulatory-forms.field', ['label' => 'الــــرقــــم المــــوحــــد', 'content' => $value('organization.unified_number'), 'ltr' => true])
            @include('tenant.auctions.regulatory-forms.field', ['label' => 'رخصة فال للمزادات العقارية', 'content' => $value('organization.fal_license'), 'ltr' => true])
        </div>
    </section>
    @foreach(['manager' => 'معلومات مديــر المــزاد', 'auctioneer' => 'معلومات محرّج المــزاد'] as $person => $heading)
        <section class="person-section person-section--{{ $person }}">
            <h2 class="section-heading">{{ $heading }}</h2>
            <div class="person-fields">
                @include('tenant.auctions.regulatory-forms.field', ['label' => 'الاســــــــــــــــــــــــــــم', 'content' => $value($person.'.name')])
                @include('tenant.auctions.regulatory-forms.field', ['label' => 'رقــــم الهــــــويــــــة', 'content' => $value($person.'.identity_number'), 'ltr' => true])
                @include('tenant.auctions.regulatory-forms.field', ['label' => 'رقــــم الجــــــــــــوال', 'content' => $value($person.'.mobile'), 'ltr' => true])
                @include('tenant.auctions.regulatory-forms.field', ['label' => 'البريــــد الإلكــــتروني', 'content' => $value($person.'.email'), 'ltr' => true])
            </div>
        </section>
    @endforeach
</main>
