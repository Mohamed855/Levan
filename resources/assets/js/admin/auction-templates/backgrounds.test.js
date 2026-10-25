import test from 'node:test';
import assert from 'node:assert/strict';
import { backgroundStyle, setPageBackground } from './backgrounds.js';

test('backgrounds always use cover with centered default position', () => {
  const page = { background: { image: 'auction_templates/background.webp', fit: 'contain' } };

  setPageBackground(page, {});
  const style = backgroundStyle(page.background);

  assert.equal(page.background.fit, 'cover');
  assert.equal(page.background.position_x, 50);
  assert.equal(page.background.position_y, 50);
  assert.equal(style.backgroundSize, 'cover');
  assert.equal(style.backgroundPosition, '50% 50%');
});

test('background position is clamped to the available percentage range', () => {
  const page = { background: { image: 'auction_templates/background.webp' } };

  setPageBackground(page, { position_x: -20, position_y: 140 });
  const style = backgroundStyle(page.background);

  assert.equal(page.background.position_x, 0);
  assert.equal(page.background.position_y, 100);
  assert.equal(style.backgroundPosition, '0% 100%');
});

test('background style clears a previous page image when the active page has none', () => {
  const style = backgroundStyle({ color: '#123456' });

  assert.equal(style.backgroundImage, 'none');
  assert.equal(style.backgroundSize, 'cover');
  assert.equal(style.backgroundPosition, '50% 50%');
  assert.equal(style.backgroundRepeat, 'no-repeat');
});
