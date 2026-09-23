'use strict';

/**
 * Follow Accounts Modal Manager
 * إدارة مودال اختيار الحسابات المرتبطة
 */
class FollowAccountsModalManager {
    constructor() {
        this.modal = null;
        this.modalElement = null;
        this.relatedAccounts = [];
        this.currentCustomerId = null;
        this.selectedAccountIds = [];
        this.formData = null;
        this.onConfirmCallback = null;

        this.init();
    }

    init() {
        $(document).ready(() => {
            this.modalElement = document.getElementById('followAccountsModal');
            if (this.modalElement) {
                this.modal = new bootstrap.Modal(this.modalElement);
                this.bindEvents();
            }
        });
    }

    bindEvents() {
        // Select All checkbox
        $(document).on('change', '#selectAllAccounts', (e) => {
            this.handleSelectAll(e.target.checked);
        });

        // Individual account checkboxes
        $(document).on('change', '.account-checkbox:not(:disabled)', () => {
            this.updateSelectAllState();
        });

        // Confirm button
        $(document).on('click', '#confirmAccountsSelection', () => {
            this.confirmSelection();
        });

        // Modal hidden event
        $(this.modalElement).on('hidden.bs.modal', () => {
            this.resetModal();
        });
    }

    async show(customerId, formData, onConfirmCallback) {
        this.currentCustomerId = parseInt(customerId); 
        this.formData = formData;
        this.onConfirmCallback = onConfirmCallback;

        await this.loadRelatedAccounts();
    }

    async loadRelatedAccounts() {
        this.showLoadingState();

        try {
            const url = window.routes.getRelatedAccounts.replace('CUSTOMER_ID', this.currentCustomerId);
            const response = await $.ajax({
                url: url,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': window.csrfToken }
            });

            if (response.success && response.data) {
                this.relatedAccounts = response.data;
                if (this.relatedAccounts.length === 0) {
                    this.onConfirmCallback?.([this.currentCustomerId]);
                    return;
                }

                this.modal.show();
                this.renderAccounts();
            } else {
                this.onConfirmCallback?.([this.currentCustomerId]);
            }
        } catch (error) {
            console.error('[FollowModal] Error loading related accounts:', error);
            this.onConfirmCallback?.([this.currentCustomerId]);
        }
    }

    renderAccounts() {
        const container = $('#relatedAccountsList');
        container.empty();

        this.relatedAccounts.forEach(account => {
            container.append(this.createAccountItem(account));
        });

        this.selectedAccountIds = [this.currentCustomerId];
        this.showAccountsList();
    }

    createAccountItem(account) {
        const isCurrentAccount = account.id === this.currentCustomerId;

        const remaining = Number(account.remaining_debt || 0);
        const remainingText = this.formatCurrency(Math.abs(remaining));

        // لون المديونية (إذا عليه مبلغ = أحمر، إذا 0 أو أقل = أخضر)
        const debtBg = remaining > 0 ? '#fdecec' : '#e8f7f6';
        const debtColor = remaining > 0 ? '#dc3545' : '#198754';
        const debtBorder = remaining > 0 ? '#f5c2c7' : '#4cc1bb';

        return `
    <div class="border-bottom px-2 py-2 ${isCurrentAccount ? 'bg-light' : ''}">
        <div class="d-flex align-items-center gap-2">
            
            <div class="form-check m-0">
                <input 
                    class="form-check-input account-checkbox" 
                    type="checkbox" 
                    value="${account.id}" 
                    id="account_${account.id}"
                    ${isCurrentAccount ? 'checked disabled' : ''}
                    data-account-id="${account.id}">
            </div>

            <div class="flex-grow-1">
                <div class="d-flex align-items-center justify-content-between">
                    
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold" style="font-size: 0.82rem;">
                            ${account.account_number || 'غير محدد'}
                        </span>

                        ${isCurrentAccount ? `
                        <span class="badge bg-primary" style="font-size: 0.62rem; padding: 2px 6px;">
                            الحالي
                        </span>` : ''}
                    </div>

                    <span class="px-2 py-1"
                        style="
                            font-size: 0.72rem;
                            border-radius: 8px;
                            background: ${debtBg};
                            color: ${debtColor};
                            border: 1px solid ${debtBorder};
                            line-height: 1;
                            white-space: nowrap;
                        ">
                        المديونية: <strong>${remainingText}</strong>
                    </span>

                </div>
            </div>
        </div>
    </div>
    `;
    }

    formatCurrency(amount) {
        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount || 0);
        return formatted;
    }

    handleSelectAll(checked) {
        $('.account-checkbox:not(:disabled)').prop('checked', checked);
    }

    updateSelectAllState() {
        const totalCheckboxes = $('.account-checkbox:not(:disabled)').length;
        const checkedCheckboxes = $('.account-checkbox:not(:disabled):checked').length;

        $('#selectAllAccounts').prop('checked', totalCheckboxes === checkedCheckboxes);
    }

    confirmSelection() {
        this.selectedAccountIds = $('.account-checkbox:checked').map(function () {
            return parseInt($(this).val());
        }).get();

        if (!this.selectedAccountIds.includes(this.currentCustomerId)) {
            this.selectedAccountIds.push(this.currentCustomerId);
        }

        // console.log('[FollowModal] confirmSelection() selectedAccountIds:', this.selectedAccountIds);

        if (this.onConfirmCallback && typeof this.onConfirmCallback === 'function') {
            // console.log('[FollowModal] calling onConfirmCallback...');
            this.onConfirmCallback(this.selectedAccountIds);
        } else {
            console.warn('[FollowModal] onConfirmCallback is missing!');
        }

        this.modal.hide();
    }

    showLoadingState() {
        $('#accountsLoadingState').show();
        $('#accountsListContainer').hide();
        $('#accountsErrorState').hide();
    }

    showAccountsList() {
        $('#accountsLoadingState').hide();
        $('#accountsListContainer').show();
        $('#accountsErrorState').hide();
    }

    showErrorState(message) {
        $('#accountsLoadingState').hide();
        $('#accountsListContainer').hide();
        $('#accountsErrorState').show();
        $('#accountsErrorMessage').text(message);
    }

    resetModal() {
        this.relatedAccounts = [];
        this.selectedAccountIds = [];
        this.currentCustomerId = null;
        this.formData = null;
        this.onConfirmCallback = null;

        $('#relatedAccountsList').empty();
        $('#selectAllAccounts').prop('checked', false);
        this.showLoadingState();
    }
}


$(document).ready(function () {
    window.FollowAccountsModalManager = new FollowAccountsModalManager();
});