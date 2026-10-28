"""Assert readable numbers AND complete searchable Arabic, not text length."""

import sys
import unicodedata

from pypdf import PdfReader

text = unicodedata.normalize('NFKC', '\n'.join(page.extract_text() or '' for page in PdfReader(sys.argv[1]).pages))
assert '\0' not in text, 'Font shaping produced Unicode NUL mappings'
assert '\ufffd' not in text, 'Font shaping produced replacement characters'
for value in ['573,000', '554,000', '220,000', '1,547', '1,000,006', '78,000']:
    assert value in text, f'Missing numeric value: {value}'
assert text.count('مزاد جوار الحرم') == 6, 'Some Arabic font samples are not searchable'
assert text.count('واجهة عسير') == 6, 'Some Arabic font samples are not searchable'
print('PASS: six numbers and six complete Arabic font samples; no Unicode corruption')
