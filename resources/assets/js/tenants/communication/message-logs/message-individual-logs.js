'use strict';

$(function () {
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    $('#message-individual-logs-table').on('init.dt', function () {
        const table = $(this).DataTable();

        // عند تغيير الشركة
        $('#filter-company').on('change', function () {
            const companyId = $(this).val();

            resetPortfolioFilter();
            resetCustomerFilter();

            if (companyId) {
                loadPortfoliosByCompany(companyId);
            }
        });

        // عند تغيير المحفظة
        $('#filter-portfolio').on('change', function () {
            const portfolioId = $(this).val();

            resetCustomerFilter();

            if (portfolioId) {
                loadCustomersByPortfolio(portfolioId);
            }
        });

        // عند إعادة تعيين الفلاتر
        $('#btn-reset-filters').on('click', function () {
            resetPortfolioFilter();
            resetCustomerFilter();
        });

        // تحميل المحافظ بحسب الشركة
        function loadPortfoliosByCompany(companyId) {
            const portfolioSelect = $('#filter-portfolio');
            portfolioSelect.prop('disabled', true);

            $.ajax({
                url: window.routes.portfoliosByCompany.replace(':companyId', companyId),
                method: 'GET',
                success: function (response) {
                    portfolioSelect.empty().append('<option value="">الكل</option>');

                    if (response.success && response.data && response.data.length > 0) {
                        response.data.forEach(function (portfolio) {
                            portfolioSelect.append(new Option(portfolio.name, portfolio.id));
                        });
                    }

                    portfolioSelect.prop('disabled', false);
                    portfolioSelect.trigger('change.select2');
                },
                error: function () {
                    portfolioSelect.prop('disabled', false);
                    toastr.error('حدث خطأ أثناء تحميل المحافظ');
                }
            });
        }

        // تحميل العملاء بحسب المحفظة
        function loadCustomersByPortfolio(portfolioId) {
            const customerSelect = $('#filter-customer');
            customerSelect.prop('disabled', true);

            $.ajax({
                url: window.routes.customersByPortfolio.replace(':portfolioId', portfolioId),
                method: 'GET',
                success: function (response) {
                    customerSelect.empty().append('<option value="">الكل</option>');

                    if (response.success && response.data && response.data.length > 0) {
                        response.data.forEach(function (customer) {
                            customerSelect.append(new Option(customer.name, customer.id));
                        });
                    }

                    customerSelect.prop('disabled', false);
                    customerSelect.trigger('change.select2');
                },
                error: function () {
                    customerSelect.prop('disabled', false);
                    toastr.error('حدث خطأ أثناء تحميل العملاء');
                }
            });
        }

        // إعادة تعيين فلتر المحافظ
        function resetPortfolioFilter() {
            const portfolioSelect = $('#filter-portfolio');
            portfolioSelect.empty().append('<option value="">الكل</option>');
            portfolioSelect.val('').trigger('change.select2');
        }

        // إعادة تعيين فلتر العملاء
        function resetCustomerFilter() {
            const customerSelect = $('#filter-customer');
            customerSelect.empty().append('<option value="">الكل</option>');
            customerSelect.val('').trigger('change.select2');
        }

        // تحديد الصفوف
        const tableWrapper = $('#message-individual-logs-table_wrapper');

        tableWrapper.on('click', '#select-all', function () {
            const rows = table.rows({ 'search': 'applied' }).nodes();
            $('input.row-checkbox', rows).prop('checked', this.checked);
        });

        tableWrapper.on('change', '.row-checkbox', function () {
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
        });

        table.on('draw', function () {
            $('#select-all').prop('checked', false);
        });
    });
});

function handleExport() {
    // التحقق من وجود فلاتر
    const hasFilters =
        ($('#filter-status').val() && $('#filter-status').val() !== '') ||
        ($('#filter-company').val() && $('#filter-company').val() !== '') ||
        ($('#filter-portfolio').val() && $('#filter-portfolio').val() !== '') ||
        ($('#filter-customer').val() && $('#filter-customer').val() !== '') ||
        ($('.dataTables_filter input').val() && $('.dataTables_filter input').val() !== '');

    if (!hasFilters) {
        toastr.warning('يرجى تطبيق فلتر واحد على الأقل');
        return;
    }

    // الحصول على الفلاتر الحالية
    const filters = {
        status: $('#filter-status').val() || '',
        company_id: $('#filter-company').val() || '',
        portfolio_id: $('#filter-portfolio').val() || '',
        customer_id: $('#filter-customer').val() || '',
        search: $('.dataTables_filter input').val() || ''
    };

    // إنشاء نموذج وإرسال طلب POST
    $('<form>', {
        'method': 'POST',
        'action': window.routes.export
    }).append($('<input>', {
        'name': '_token',
        'value': window.csrfToken,
        'type': 'hidden'
    })).append($('<input>', {
        'name': 'status',
        'value': filters.status,
        'type': 'hidden'
    })).append($('<input>', {
        'name': 'company_id',
        'value': filters.company_id,
        'type': 'hidden'
    })).append($('<input>', {
        'name': 'portfolio_id',
        'value': filters.portfolio_id,
        'type': 'hidden'
    })).append($('<input>', {
        'name': 'customer_id',
        'value': filters.customer_id,
        'type': 'hidden'
    })).append($('<input>', {
        'name': 'search',
        'value': filters.search,
        'type': 'hidden'
    })).appendTo('body').submit();
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        return;
    }
    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'MessageIndividualLog');
}


window.handleExport = handleExport;
window.handleMassDelete = handleMassDelete;
