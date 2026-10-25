@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء قالب مزاد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/scss/shared/template-designer.scss'])
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.auction-templates.store') }}" id="auction-template-create-form" data-form-validate
        data-draft-url="{{ route('admin.wizard-drafts.store') }}"
        data-draft-resource-key="admin.auction-templates.create">
        @csrf
        <input type="hidden" name="_wizard_draft_id" data-wizard-draft-id>
        <input type="hidden" name="_wizard_resource_key" value="admin.auction-templates.create">
        @include('admin.auction-templates.partials.form')
    </form>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    <script>
        window.auctionTemplateFieldGuides = @json($fieldGuide);
        window.auctionTemplateSamples = @json(\App\Support\Auctions\TemplateSchema::samplePresets());
    </script>
    @vite([
        'resources/assets/js/admin/auction-templates/designer.js'
    ])
@endsection
