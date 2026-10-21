/**
 * DataTable Search Autocomplete Fix
 *
 * Replaces the inline <script> block in:
 *   - tenants/layouts/layoutMaster.blade.php
 *
 * Disables browser autofill/autocorrect/autocomplete on DataTable search inputs
 * using a readonly trick to prevent browser from pre-filling.
 */
$(document).ready(function () {
    setTimeout(function () {

        function applySearchFix(el) {
            $(el)
                .attr('autocomplete',   'off')
                .attr('autocorrect',    'off')
                .attr('autocapitalize', 'off')
                .attr('spellcheck',     'false')
                .attr('readonly',       'readonly');

            $(el).on('focus', function () {
                $(this).removeAttr('readonly');
            });

            $(el).on('blur', function () {
                if ($(this).val() === '') {
                    $(this).attr('readonly', 'readonly');
                }
            });
        }

        $('input[type="search"]').each(function () {
            applySearchFix(this);
        });

        $('.dataTables_filter input').each(function () {
            applySearchFix(this);
        });

    }, 500);
});
