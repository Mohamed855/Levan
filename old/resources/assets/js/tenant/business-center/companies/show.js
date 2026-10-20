// resources/assets/js/company-datatables.js

$(document).ready(function() {
    const languageUrl = '/static/i18n/ar.json';

    // ✅ تدمير أي DataTable موجود قبل ما تعمل واحد جديد
    if ($.fn.DataTable.isDataTable('#contracts-table')) {
        $('#contracts-table').DataTable().destroy();
    }

    // ✅ DataTable للعقود
    const contractsTable = $('#contracts-table').DataTable({
        language: { url: languageUrl },
        responsive: true,
        order: [[0, 'desc']], // ✅ غيرت من [[1, 'desc']] لـ [[0, 'desc']]
        pageLength: 1, // ✅ صف واحد بس
        lengthMenu: [[1, 5, 10, 25], ['1', '5', '10', '25']], 
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        paging: true, // ✅ تأكيد تفعيل الـ pagination
        autoWidth: false,
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
             '<"table-responsive"t>' +
             '<"row mt-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });

    // ✅ للتأكد: اطبع عدد الصفوف
    console.log('Contracts Table - Page Length:', contractsTable.page.len());
    console.log('Contracts Table - Total Rows:', contractsTable.rows().count());

    // ✅ DataTable للمحافظ
    if ($.fn.DataTable.isDataTable('#portfolios-table')) {
        $('#portfolios-table').DataTable().destroy();
    }

    $('#portfolios-table').DataTable({
        language: { url: languageUrl },
        responsive: true,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], ['5', '10', '25']],
        lengthChange: true,
        searching: true,
        ordering: true,
        paging: true,
        dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
             '<"table-responsive"t>' +
             '<"row mt-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });

    // ✅ DataTable للمفوضين
    if ($.fn.DataTable.isDataTable('#authorized-table')) {
        $('#authorized-table').DataTable().destroy();
    }

    $('#authorized-table').DataTable({
        language: { url: languageUrl },
        responsive: true,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], ['5', '10', '25']],
        lengthChange: true,
        searching: true,
        ordering: true,
        paging: true,
        dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
             '<"table-responsive"t>' +
             '<"row mt-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });

 
});
