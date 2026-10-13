'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('wizard-validation-form');
    if (!form) return;

    let restoredPropertyIds = [];

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        const element = document.createElement('div');
        element.textContent = String(value);
        return element.innerHTML;
    }

    // Wizard draft restored handler
    $(form).on('wizardDraftRestored', function(event) {
        const detailData = event.originalEvent?.detail?.data || event.detail?.data || {};
        if (detailData.property_ids) {
            restoredPropertyIds = (Array.isArray(detailData.property_ids) ? detailData.property_ids : [detailData.property_ids]).map(String);
        }

        if (detailData.assignment_id) {
            $('#assignment_id').val(detailData.assignment_id).trigger('change');
        }

        updateLivePreview();
    });

    function updateLivePreview() {
        const nameVal = $('#name').val();
        $('#preview-auction-name').text(nameVal || 'اسم المزاد');
        $('#review-name').text(nameVal || '-');

        const cityVal = $('#city').val();
        $('#preview-auction-city').text(cityVal || 'المدينة');
        $('#review-city').text(cityVal || '-');

        const categoryText = $('#category option:selected').text();
        $('#preview-auction-category').text(categoryText || 'التصنيف');
        $('#review-category').text(categoryText || '-');

        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        $('#preview-auction-date').text(startDate || 'تاريخ الانطلاق');
        if (startDate || endDate) {
            $('#review-dates').text((startDate || '-') + ' إلى ' + (endDate || '-'));
        } else {
            $('#review-dates').text('-');
        }

        const typeText = $('#type option:selected').text();
        $('#review-type').text(typeText || '-');

        const supervising = $('#supervising_entity').val();
        $('#review-supervising').text(supervising || '-');
    }

    // Live preview event listeners
    $('#name').on('input', updateLivePreview);
    $('#city').on('change', updateLivePreview);
    $('#category').on('change', updateLivePreview);
    $('#start_date, #end_date').on('change input', updateLivePreview);
    $('#type').on('change', updateLivePreview);
    $('#supervising_entity').on('input', updateLivePreview);

    // Dynamic Assignment Handling in Step 2 -> Loading Properties in Step 3
    $('#assignment_id').on('change', function() {
        const assignmentId = $(this).val();
        if (!assignmentId) {
            $('#selected-assignment-card').addClass('d-none');
            $('#properties-container').html(
                '<tr><td colspan="6" class="text-center py-4 text-muted">يرجى اختيار الإسناد في الخطوة السابقة لعرض العقارات التابعة له</td></tr>'
            );
            calculateSelectedProperties();
            return;
        }

        const urlTemplate = form.dataset.assignmentPropertiesUrl || '/assignments/:id/properties';
        const url = urlTemplate.replace(':id', encodeURIComponent(assignmentId));

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                if (res.success && res.assignment) {
                    $('#asg-summary-num').text(res.assignment.assignment_number || '-');
                    $('#asg-summary-entity').text(res.assignment.issuing_entity || '-');
                    $('#asg-summary-status').text(res.assignment.status_label || '-');
                    $('#selected-assignment-card').removeClass('d-none');

                    if (!$('#supervising_entity').val()) {
                        $('#supervising_entity').val(res.assignment.issuing_entity || '').trigger('input');
                    }

                    $('#review-assignment-name').text(
                        (res.assignment.name || '') + ' (' + (res.assignment.assignment_number || '') + ')'
                    );
                    $('#review-supervising').text(res.assignment.issuing_entity || '-');

                    let html = '';
                    if (res.properties && res.properties.length > 0) {
                        res.properties.forEach(function(prop) {
                            const img = prop.main_image
                                ? '/tenant-assets/' + encodeURI(prop.main_image)
                                : '/assets/img/illustrations/page-pricing-standard.png';

                            html += `
                                <tr>
                                    <td>
                                        <input type="checkbox" name="property_ids[]" value="${escapeHtml(prop.id)}" class="form-check-input property-select-check" data-form-control
                                            data-area="${parseFloat(prop.area) || 0}" data-val="${parseFloat(prop.estimated_value) || 0}">
                                    </td>
                                    <td><code>${escapeHtml(prop.property_number)}</code></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="${escapeHtml(img)}" class="rounded me-2" style="width:28px;height:28px;object-fit:cover" alt="" />
                                            <span class="fw-medium">${escapeHtml(prop.name)}</span>
                                        </div>
                                    </td>
                                    <td>${escapeHtml(prop.city || '')}${prop.district ? ' - ' + escapeHtml(prop.district) : ''}</td>
                                    <td>${Number(prop.area || 0).toLocaleString()} م²</td>
                                    <td>${Number(prop.estimated_value || 0).toLocaleString()} ر.س</td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="6" class="text-center py-4 text-muted">لا توجد عقارات مسجلة لهذا الإسناد حالياً.</td></tr>';
                    }

                    $('#properties-container').html(html);

                    if (restoredPropertyIds.length > 0) {
                        restoredPropertyIds.forEach(function(propertyId) {
                            $('.property-select-check[value="' + propertyId + '"]').prop('checked', true);
                        });
                    }

                    calculateSelectedProperties();
                }
            },
            error: function() {
                $('#properties-container').html(
                    '<tr><td colspan="6" class="text-center py-4 text-danger">حدث خطأ أثناء تحميل عقارات الإسناد.</td></tr>'
                );
            }
        });
    });

    // Select all properties checkbox
    $(document).on('change', '#select-all-properties', function() {
        $('.property-select-check').prop('checked', $(this).prop('checked'));
        calculateSelectedProperties();
    });

    $(document).on('change', '.property-select-check', function() {
        calculateSelectedProperties();
    });

    function calculateSelectedProperties() {
        let count = 0;
        let totalArea = 0;
        let totalVal = 0;

        $('.property-select-check:checked').each(function() {
            count++;
            totalArea += parseFloat($(this).data('area')) || 0;
            totalVal += parseFloat($(this).data('val')) || 0;
        });

        $('#selected-props-count').text(count);
        $('#selected-props-area').text(totalArea.toLocaleString() + ' م²');
        $('#selected-props-val').text(totalVal.toLocaleString() + ' ر.س');
        $('#review-selected-props-count').text(count + ' عقارات');

        const allCount = $('.property-select-check').length;
        if (allCount > 0 && count === allCount) {
            $('#select-all-properties').prop('checked', true);
        } else {
            $('#select-all-properties').prop('checked', false);
        }

        form.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Quick jump step buttons in Step 5 Review
    document.querySelectorAll('[data-edit-step]').forEach(button => {
        button.addEventListener('click', () => {
            const target = Number(button.dataset.editStep);
            if (!target) return;
            const stepper = window.auctionStepper || window.stepper;
            if (stepper) {
                stepper.to(target);
            }
        });
    });

    // Initial sync
    updateLivePreview();
});
