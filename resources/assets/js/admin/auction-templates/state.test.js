import test from 'node:test';
import assert from 'node:assert/strict';
import { createDesignState, replaceSchema } from './state.js';

test('createDesignState supplies a valid page background', () => {
  const state = createDesignState(null, 'social', () => 'page-1', () => ({ width: 1, height: 1 }));
  assert.equal(state.schema.pages[0].background.fit, 'cover');
  assert.equal(state.schema.pages[0].background.position_x, 50);
  assert.equal(state.schema.pages[0].background.position_y, 50);
});

test('replaceSchema resets selection and page index', () => {
  const state = createDesignState(null, 'social', () => 'page-1', () => ({ width: 1, height: 1 }));
  state.pageIndex = 4; state.selectedId = 'x';
  replaceSchema(state, { pages: [{ id: 'new', elements: [] }] }, () => 'id', () => ({ width: 2, height: 2 }), 'social');
  assert.equal(state.pageIndex, 0);
  assert.equal(state.selectedId, null);
  assert.equal(state.schema.pages[0].id, 'new');
});
