<script>
    document.addEventListener('DOMContentLoaded', function() {
        function showToast() {
            if (typeof toastr === 'undefined') {
                setTimeout(showToast, 50);
                return;
            }
            @if (Session::has('success'))
                toastr.success(@json(Session::get('success')));
            @endif

            @if (Session::has('error'))
                toastr.error(@json(Session::get('error')));
            @endif

            @if (Session::has('info'))
                toastr.info(@json(Session::get('info')));
            @endif

            @if (Session::has('warning'))
                toastr.warning(@json(Session::get('warning')));
            @endif
        }
        showToast();
    });
</script>
