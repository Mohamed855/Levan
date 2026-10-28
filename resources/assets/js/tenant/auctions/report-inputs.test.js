import test from 'node:test';
import assert from 'node:assert/strict';
import { fieldEntries, nextRowIndex, fileIdentity } from './report-inputs.js';

test('draft nested arrays preserve zero values and sparse row indices', () => {
    assert.deepEqual(fieldEntries({ marketing: { campaigns: { 4: { views: 0, platform: 'x' } } }, property_results: { 12: { board_count: 0 } } }), [
        ['marketing[campaigns][4][views]', 0], ['marketing[campaigns][4][platform]', 'x'], ['property_results[12][board_count]', 0],
    ]);
    assert.deepEqual(fieldEntries({ marketing: { links: [{ role: 'video', url: 'https://example.com' }] } }), [
        ['marketing[links][0][role]', 'video'], ['marketing[links][0][url]', 'https://example.com'],
    ]);
});

test('adding after a deleted row never overwrites the next existing campaign', () => {
    assert.equal(nextRowIndex(['0', '4']), 5);
    assert.equal(nextRowIndex([]), 0);
});

test('file classification follows the actual file when selection is reordered', () => {
    assert.equal(fileIdentity({name: 'a.png', size: 12, lastModified: 7}), 'a.png:12:7');
    assert.notEqual(fileIdentity({name: 'a.png', size: 12, lastModified: 7}), fileIdentity({name: 'a.png', size: 13, lastModified: 7}));
});
