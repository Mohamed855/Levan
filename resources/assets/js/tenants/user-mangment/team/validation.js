/**
 * Form Wizard Validation for Teams
 */
'use strict';

let FormValidation1, FormValidation2;
let selectedMembers = [];

document.addEventListener('DOMContentLoaded', function (e) {
    (function () {
        const wizardValidation = document.querySelector('#wizard-validation'),
            wizardValidationBtnNextList = [].slice.call(wizardValidation.querySelectorAll('.btn-next')),
            wizardValidationBtnPrevList = [].slice.call(wizardValidation.querySelectorAll('.btn-prev')),
            wizardValidationBtnSubmit = wizardValidation.querySelector('.btn-submit');

        if (typeof wizardValidation !== undefined && wizardValidation !== null) {
            const validationStepper = new Stepper(wizardValidation, {
                linear: false
            });

            /*
            |----------------------------------------------------------------------
            | Step 1 Validation
            |----------------------------------------------------------------------
            */
            FormValidation1 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    name: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال اسم المجموعة'
                            },
                            stringLength: {
                                min: 2,
                                max: 255,
                                message: 'اسم المجموعة يجب أن يكون بين 2 و 255 حرف'
                            }
                        }
                    },
                    branch_id: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار الفرع'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-sm-6, .col-12'
                    }),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                    submitButton: new FormValidation.plugins.SubmitButton()
                }
            }).on('core.form.valid', function () {
                validationStepper.next();
            });

            /*
            |----------------------------------------------------------------------
            | Step 2 Validation
            |----------------------------------------------------------------------
            */
            FormValidation2 = FormValidation.formValidation(wizardValidation, {
                fields: {},
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-12'
                    }),
                    autoFocus: new FormValidation.plugins.AutoFocus()
                }
            });

            /*
            |----------------------------------------------------------------------
            | Navigation Buttons
            |----------------------------------------------------------------------
            */
            wizardValidationBtnNextList.forEach(function (wizardValidationBtnNext) {
                wizardValidationBtnNext.addEventListener('click', function (e) {
                    e.preventDefault();
                    const currentStep = validationStepper._currentIndex;

                    switch (currentStep) {
                        case 0:
                            FormValidation1.validate();
                            break;
                        default:
                            break;
                    }
                });
            });

            wizardValidationBtnPrevList.forEach(function (wizardValidationBtnPrev) {
                wizardValidationBtnPrev.addEventListener('click', function (e) {
                    e.preventDefault();

                    switch (validationStepper._currentIndex) {
                        case 1:
                            validationStepper.previous();
                            break;
                        case 0:
                        default:
                            break;
                    }
                });
            });

            /*
            |----------------------------------------------------------------------
            | Submit Button Handler - التحقق المحسّن
            |----------------------------------------------------------------------
            */
            if (wizardValidationBtnSubmit) {
                wizardValidationBtnSubmit.addEventListener('click', function (e) {
                    e.preventDefault();

                    // التحقق من وجود أعضاء
                    if (selectedMembers.length === 0) {
                        toastr.warning('يجب إضافة أعضاء للمجموعة');
                        return;
                    }

                    // التحقق من وجود عضوين على الأقل
                    if (selectedMembers.length < 2) {
                        toastr.warning('يجب إضافة عضوين على الأقل (عضو واحد + قائد واحد)');
                        return;
                    }

                    // التحقق من وجود قائد
                    const hasLeader = selectedMembers.some(m => m.is_leader);
                    if (!hasLeader) {
                        toastr.warning('يجب تحديد قائد واحد على الأقل للمجموعة');
                        return;
                    }

                    // التحقق من وجود أعضاء عاديين (غير قادة)
                    const regularMembers = selectedMembers.filter(m => !m.is_leader);
                    if (regularMembers.length === 0) {
                        toastr.warning('يجب أن يكون هناك عضو عادي واحد على الأقل بالإضافة إلى القائد');
                        return;
                    }

                    submitForm();
                });
            }

            /*
            |----------------------------------------------------------------------
            | Revalidate on change
            |----------------------------------------------------------------------
            */
            $('#branch_id').on('change', function () {
                if (FormValidation1 && FormValidation1.getFields()['branch_id']) {
                    FormValidation1.revalidateField('branch_id');
                }
            });
        }
    })();
});

/*
|--------------------------------------------------------------------------
| Initialize Select2
|--------------------------------------------------------------------------
*/
$(document).ready(function () {
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl'
        });
    });

    /*
    |----------------------------------------------------------------------
    | Load Users when Branch is selected
    |----------------------------------------------------------------------
    */
    $('#branch_id').on('change', function () {
        const branchId = $(this).val();

        if (!branchId) {
            $('#user_select').empty().append('<option value=""></option>').trigger('change');
            selectedMembers = [];
            updateMembersTable();
            return;
        }

        loadUsersByBranch(branchId);
    });

    /*
    |----------------------------------------------------------------------
    | Add Member when User is selected
    |----------------------------------------------------------------------
    */
    $('#user_select').on('change', function () {
        const userId = $(this).val();

        if (!userId) return;

        const userText = $(this).find('option:selected').text();
        const userData = {
            id: parseInt(userId),
            name: userText.split('(')[0].trim(),
            email: userText.match(/\((.*?)\)/)?.[1] || '',
            is_leader: false
        };

        addMember(userData);

        $(this).val('').trigger('change');
    });

    /*
    |----------------------------------------------------------------------
    | For Edit Mode: Load existing data
    |----------------------------------------------------------------------
    */
    if (typeof window.teamData !== 'undefined') {
        loadEditModeData();
    }
});

/*
|--------------------------------------------------------------------------
| Load Users by Branch (AJAX)
|--------------------------------------------------------------------------
*/
function loadUsersByBranch(branchId) {
    if (!window.routes || !window.routes.getUsersByBranch) {
        toastr.error('خطأ في الإعدادات');
        return;
    }

    let url = window.routes.getUsersByBranch.replace('___BRANCH_ID___', branchId);

    if (typeof window.teamData !== 'undefined' && window.teamData.teamId) {
        url += `?team_id=${window.teamData.teamId}`;
    }

    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function () {
            $('#user_select').prop('disabled', true);
        },
        success: function (response) {
            if (response.success) {
                populateUsersDropdown(response.users); 
            } else {
                toastr.error(response.message || 'حدث خطأ أثناء جلب المستخدمين');
            }
        },
        error: function (xhr) {
            toastr.error('حدث خطأ أثناء جلب المستخدمين');
        },
        complete: function () {
            $('#user_select').prop('disabled', false);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Populate Users Dropdown
|--------------------------------------------------------------------------
*/
function populateUsersDropdown(users) {
    const $userSelect = $('#user_select');
    $userSelect.empty().append('<option value=""></option>');

    if (!users || users.length === 0) {
        const isEditMode = typeof window.teamData !== 'undefined';

        if (!isEditMode) {
            toastr.info('جميع المستخدمين في هذا الفرع مرتبطون بفرق ');
        }
        return;
    }

    users.forEach(function (user) {
        const isAlreadyAdded = selectedMembers.some(m => m.id === user.id);

        if (!isAlreadyAdded) {
            const option = new Option(
                `${user.name} (${user.email})`,
                user.id,
                false,
                false
            );
            $userSelect.append(option);
        }
    });

    $userSelect.trigger('change');
}
/*
|--------------------------------------------------------------------------
| Add Member to List
|--------------------------------------------------------------------------
*/
function addMember(userData) {
    const exists = selectedMembers.some(m => m.id === userData.id);

    if (exists) {
        toastr.warning('هذا المستخدم موجود بالفعل في القائمة');
        return;
    }

    selectedMembers.push(userData);
    updateMembersTable();

    const $userSelect = $('#user_select');
    $userSelect.find(`option[value="${userData.id}"]`).remove();
}

/*
|--------------------------------------------------------------------------
| Remove Member from List
|--------------------------------------------------------------------------
*/
function removeMember(userId) {
    selectedMembers = selectedMembers.filter(m => m.id !== userId);
    updateMembersTable();

    const branchId = $('#branch_id').val();
    if (branchId) {
        loadUsersByBranch(branchId);
    }
}

/*
|--------------------------------------------------------------------------
| Toggle Leader Status
|--------------------------------------------------------------------------
*/
function toggleLeader(userId) {
    selectedMembers = selectedMembers.map(member => {
        member.is_leader = (member.id === userId);
        return member;
    });

    updateMembersTable();
}

/*
|--------------------------------------------------------------------------
| Update Members Table
|--------------------------------------------------------------------------
*/
function updateMembersTable() {
    const tbody = $('#members-table-body');
    tbody.empty();

    if (selectedMembers.length === 0) {
        tbody.append(`
            <tr id="no-members-row">
                <td colspan="3" class="text-center text-muted py-4">
                    <i class="ti ti-users ti-lg mb-2 d-block"></i>
                    لا يوجد أعضاء في المجموعة
                </td>
            </tr>
        `);
    } else {
        selectedMembers.forEach(member => {
            const row = `
                <tr class="align-middle">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    ${member.name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold">${member.name}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="form-check d-flex align-items-center">
                            <input 
                                class="form-check-input mt-0 me-2" 
                                type="radio" 
                                name="leader_radio" 
                                id="leader_${member.id}"
                                ${member.is_leader ? 'checked' : ''}
                                onchange="toggleLeader(${member.id})"
                            >
                            <label class="form-check-label mb-0" for="leader_${member.id}">
                                ${member.is_leader ? '<span class="badge bg-label-primary"><i class="ti ti-crown ti-xs me-1"></i>قائد الفريق</span>' : '<span class="text-muted">تعيين كقائد</span>'}
                            </label>
                        </div>
                    </td>
                    <td class="text-center">
                        <button 
                            type="button" 
                            class="btn btn-icon btn-label-primary"
                            onclick="removeMember(${member.id})"
                            data-bs-toggle="tooltip"
                            title="حذف"
                        >
                            <i class="ti ti-trash ti-md"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    $('#members-count').text(selectedMembers.length);
}
/*
|--------------------------------------------------------------------------
| Submit Form
|--------------------------------------------------------------------------
*/
function submitForm() {
    const form = document.getElementById('wizard-validation-form');

    if (!form) {
        toastr.error('خطأ: لم يتم العثور على النموذج');
        return;
    }

    const memberIds = selectedMembers.map(m => m.id);
    const uniqueIds = [...new Set(memberIds)];

    if (memberIds.length !== uniqueIds.length) {
        toastr.error('يوجد أعضاء مكررين في القائمة');
        return;
    }

    const oldInputs = form.querySelectorAll('input[name^="members"], input[name^="leaders"]');
    oldInputs.forEach(input => input.remove());

    selectedMembers.forEach((member, index) => {
        const memberInput = document.createElement('input');
        memberInput.type = 'hidden';
        memberInput.name = `members[${index}]`;
        memberInput.value = member.id;
        form.appendChild(memberInput);

        if (member.is_leader) {
            const leaderInput = document.createElement('input');
            leaderInput.type = 'hidden';
            leaderInput.name = `leaders[0]`;
            leaderInput.value = member.id;
            form.appendChild(leaderInput);
        }
    });

    form.onsubmit = null;
    form.submit();
}

/*
|--------------------------------------------------------------------------
| Load Edit Mode Data
|--------------------------------------------------------------------------
*/
function loadEditModeData() {
    const teamData = window.teamData;

    selectedMembers = teamData.members || [];

    populateUsersDropdown(teamData.users);
    updateMembersTable();
}

window.removeMember = removeMember;
window.toggleLeader = toggleLeader;