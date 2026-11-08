import test from 'node:test';
import assert from 'node:assert/strict';
import { fieldEntries, nextRowIndex, fileIdentity, mountReportInputs } from './report-inputs.js';

for (const hasEvidenceSection of [false, true]) {
    test(`draft restoration completes with legacy evidence section present: ${hasEvidenceSection}`, () => {
        let clears = 0;
        const evidence = { replaceChildren() { clears++; } };
        const controls = {
            assignment_id: { type: 'select-one', value: '' },
            expected_properties_count: { type: 'number', value: '' },
        };
        const form = {
            querySelector: (selector) => selector === '#new-evidence-metadata' && hasEvidenceSection ? evidence : null,
            elements: { namedItem: (name) => controls[name] || null },
            addEventListener() {},
        };
        const inputs = mountReportInputs(form);
        assert.doesNotThrow(() => inputs.restore({ assignment_id: '12', expected_properties_count: 0 }, {
            marketing_evidence: { 0: { name: 'saved-proof.png' } },
        }));
        assert.equal(controls.assignment_id.value, '12');
        assert.equal(controls.expected_properties_count.value, 0);
        assert.equal(clears, hasEvidenceSection ? 1 : 0);
    });
}

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
