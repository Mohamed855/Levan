@php
    $configData = App\Helpers\Helpers::appClasses();
    $isFront = true;
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if (Session::has('success'))
            toastr.success("{{ Session::get('success') }}");
        @endif

        @if (Session::has('error'))
            toastr.error("{{ Session::get('error') }}");
        @endif

        @if (Session::has('info'))
            toastr.info("{{ Session::get('info') }}");
        @endif

        @if (Session::has('warning'))
            toastr.warning("{{ Session::get('warning') }}");
        @endif
    });
</script>

@section('layoutContent')
    @extends('admin.layouts.commonMaster')

    @if (!isset($hideHeaderFooter) || !$hideHeaderFooter)
        @include('admin.layouts.sections.navbar.navbar-front')
    @endif

    @yield('content')

    @if (!isset($hideHeaderFooter) || !$hideHeaderFooter)
        @include('admin.layouts.sections.footer.footer-front')
    @endif
@endsection
