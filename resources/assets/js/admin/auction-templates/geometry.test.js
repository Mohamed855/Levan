import test from 'node:test';
import assert from 'node:assert/strict';
import { clampRect, resizeRect } from './geometry.js';

test('clampRect keeps rectangles inside canvas', () => {
  assert.deepEqual(clampRect({ x: 90, y: 90, width: 30, height: 30 }, { width: 100, height: 100 }), { x: 70, y: 70, width: 30, height: 30 });
});

test('resizeRect supports corner handles and image aspect lock', () => {
  const result = resizeRect({ x: 10, y: 10, width: 100, height: 50 }, 'se', { x: 20, y: 20 }, { width: 300, height: 300 }, { lockAspect: true });
  assert.equal(result.width / result.height, 2);
  assert.equal(result.width, 120);
});
