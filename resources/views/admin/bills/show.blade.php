@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الفاتورة #' . $bill->bill_number)

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('content')
    @include('shared.bills._details', [
        'isAdmin' => true,
        'backUrl' => route('admin.bills.index'),
    ])
@endsection

@section('page-script')
    <script>
        $(document).on('click', '.js-verify-bill-btn', function(e) {
            e.preventDefault();
            window.confirmAction({
                title: 'تأكيد الإيصال البنكي',
                text: 'هل أنت متأكد من صحة الإيصال وتأكيد دفع الفاتورة؟',
                variant: 'success',
                icon: 'ti-check',
                confirmText: 'نعم، تأكيد الدفع',
                confirmClass: 'btn btn-success px-4',
                onConfirm: function() {
                    document.getElementById('verifyBillForm')?.submit();
                }
            });
        });
    </script>
@endsection
