@php
    $configData = App\Helpers\Helpers::appClasses();
    $isFront = true;
@endphp


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
