for (const select of document.querySelectorAll('[data-auction-properties]')) {
  const assignment = document.querySelector('[name="assignment_id"]');
  const filter = () => {
    const id = assignment?.value;
    for (const option of select.options) {
      const matches = !id || option.dataset.assignment === id;
      option.hidden = !matches;
      option.disabled = !matches;
      if (!matches) option.selected = false;
    }
  };
  assignment?.addEventListener('change', filter);
  filter();
}