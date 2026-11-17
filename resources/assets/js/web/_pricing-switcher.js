/**
 * Pricing Switcher (Monthly / Yearly Filter)
 * Toggles visibility of plans on the landing page without full page reload.
 */

export function initPricingSwitcher() {
  const switcher = document.querySelector('[data-pricing-switcher]');
  const grid = document.querySelector('[data-pricing-grid]');

  if (!switcher || !grid) return;

  const buttons = switcher.querySelectorAll('[data-billing-target]');
  const items = grid.querySelectorAll('.pricing-grid__item');
  const emptyState = document.getElementById('pricing-empty-state');

  function switchCycle(targetCycle) {
    let visibleCount = 0;

    buttons.forEach((btn) => {
      const isTarget = btn.getAttribute('data-billing-target') === targetCycle;
      btn.classList.toggle('is-active', isTarget);
      btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
    });

    items.forEach((item) => {
      const itemCycle = item.getAttribute('data-billing-cycle');
      const matches = itemCycle === targetCycle;

      if (matches) {
        item.classList.remove('is-hidden');
        item.classList.add('is-visible');
        visibleCount++;
      } else {
        item.classList.add('is-hidden');
      }
    });

    grid.setAttribute('data-active-cycle', targetCycle);

    if (emptyState) {
      emptyState.classList.toggle('is-hidden', visibleCount > 0);
    }
  }

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetCycle = btn.getAttribute('data-billing-target');
      if (targetCycle) {
        switchCycle(targetCycle);
      }
    });

    // Keyboard accessibility navigation (Arrow navigation for tabs)
    btn.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        e.preventDefault();
        const btnArray = Array.from(buttons);
        const currentIndex = btnArray.indexOf(btn);
        // RTL orientation: ArrowLeft moves to next tab, ArrowRight moves to previous
        const nextIndex = e.key === 'ArrowLeft'
          ? (currentIndex + 1) % btnArray.length
          : (currentIndex - 1 + btnArray.length) % btnArray.length;
        btnArray[nextIndex].focus();
        btnArray[nextIndex].click();
      }
    });
  });
}
