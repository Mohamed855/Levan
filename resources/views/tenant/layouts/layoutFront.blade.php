@php
    $configData = App\Helpers\Helpers::appClasses();
    $isFront = true;
@endphp

@section('layoutContent')
    @extends('tenant.layouts.commonMaster')

    @if (!isset($hideHeaderFooter) || !$hideHeaderFooter)
        @include('tenant.layouts.sections.navbar.navbar-front')
    @endif

    @if (!isset($excludeJquery) || !$excludeJquery)
        <script src="{{ asset('static/shared/js/cdn/jquery-init.js') }}"></script>
        {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script> --}}
    @endif

    @yield('content')

    @yield('toastr')

    @if (!isset($hideHeaderFooter) || !$hideHeaderFooter)
        @include('tenant.layouts.sections.footer.footer-front')
    @endif


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
@endsection
