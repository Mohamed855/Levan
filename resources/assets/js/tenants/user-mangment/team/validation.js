'use strict';

let selectedMembers = [];

function teamForm() {
  return document.getElementById('team-form');
}

function normalizeMember(member) {
  return {
    id: Number(member.id),
    name: String(member.name || ''),
    email: String(member.email || ''),
    is_leader: Boolean(member.is_leader),
  };
}

function refreshAvailableUsers() {
  const select = $('#user_select');
  const users = window.teamData?.users || [];
  const selectedIds = new Set(selectedMembers.map((member) => member.id));

  select.empty().append(new Option('', '', false, false));
  users.forEach((user) => {
    if (!selectedIds.has(Number(user.id))) {
      select.append(new Option(`${user.name} (${user.email})`, user.id, false, false));
    }
  });
  select.val('').trigger('change.select2');
}

function syncHiddenInputs() {
  const membersContainer = document.getElementById('team-members-inputs');
  const leadersContainer = document.getElementById('team-leaders-inputs');
  if (!membersContainer || !leadersContainer) return;

  membersContainer.replaceChildren();
  leadersContainer.replaceChildren();

  selectedMembers.forEach((member) => {
    const memberInput = document.createElement('input');
    memberInput.type = 'hidden';
    memberInput.name = 'members[]';
    memberInput.value = String(member.id);
    membersContainer.append(memberInput);

    if (member.is_leader) {
      const leaderInput = document.createElement('input');
      leaderInput.type = 'hidden';
      leaderInput.name = 'leaders[]';
      leaderInput.value = String(member.id);
      leadersContainer.append(leaderInput);
    }
  });
}

function validateAssignments() {
  const form = teamForm();
  if (form && window.SharedFormValidation) {
    window.SharedFormValidation.validateFields(form, ['members', 'leaders']);
  }
}

function renderMembers() {
  const body = document.getElementById('members-table-body');
  const count = document.getElementById('members-count');
  if (!body || !count) return;

  body.replaceChildren();
  count.textContent = String(selectedMembers.length);

  if (selectedMembers.length === 0) {
    const row = body.insertRow();
    const cell = row.insertCell();
    cell.colSpan = 3;
    cell.className = 'text-center text-muted py-4';
    cell.textContent = 'لا يوجد أعضاء في المجموعة';
    return;
  }

  selectedMembers.forEach((member) => {
    const row = body.insertRow();
    row.className = 'align-middle';

    const userCell = row.insertCell();
    const name = document.createElement('div');
    name.className = 'fw-semibold';
    name.textContent = member.name;
    const email = document.createElement('small');
    email.className = 'text-muted';
    email.textContent = member.email;
    userCell.append(name, email);

    const leaderCell = row.insertCell();
    const leaderWrapper = document.createElement('div');
    leaderWrapper.className = 'form-check';
    const leaderInput = document.createElement('input');
    leaderInput.className = 'form-check-input';
    leaderInput.type = 'radio';
    leaderInput.name = 'team-leader-picker';
    leaderInput.checked = member.is_leader;
    leaderInput.dataset.validationSkip = '';
    leaderInput.id = `leader-${member.id}`;
    leaderInput.addEventListener('change', () => {
      selectedMembers = selectedMembers.map((current) => ({
        ...current,
        is_leader: current.id === member.id,
      }));
      syncHiddenInputs();
      renderMembers();
      validateAssignments();
    });
    const leaderLabel = document.createElement('label');
    leaderLabel.className = 'form-check-label';
    leaderLabel.htmlFor = leaderInput.id;
    leaderLabel.textContent = 'تعيين كقائد';
    leaderWrapper.append(leaderInput, leaderLabel);
    leaderCell.append(leaderWrapper);

    const actionCell = row.insertCell();
    actionCell.className = 'text-center';
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-icon btn-label-danger';
    removeButton.setAttribute('aria-label', `حذف ${member.name}`);
    removeButton.innerHTML = '<i class="ti ti-trash" aria-hidden="true"></i>';
    removeButton.addEventListener('click', () => {
      selectedMembers = selectedMembers.filter((current) => current.id !== member.id);
      syncHiddenInputs();
      refreshAvailableUsers();
      renderMembers();
      validateAssignments();
    });
    actionCell.append(removeButton);
  });
}

function addSelectedUser() {
  const select = $('#user_select');
  const userId = Number(select.val());
  if (!userId) return;

  const user = (window.teamData?.users || []).find((candidate) => Number(candidate.id) === userId);
  if (!user || selectedMembers.some((member) => member.id === userId)) return;

  selectedMembers.push(normalizeMember(user));
  syncHiddenInputs();
  refreshAvailableUsers();
  renderMembers();
  validateAssignments();
}

$(document).ready(() => {
  selectedMembers = (window.teamData?.members || []).map(normalizeMember);

  $('#user_select').select2({
    placeholder: $('#user_select').data('placeholder'),
    allowClear: true,
    width: '100%',
    language: 'ar',
    dir: 'rtl',
  }).on('change', addSelectedUser);

  syncHiddenInputs();
  refreshAvailableUsers();
  renderMembers();
});
