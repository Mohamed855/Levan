"""Build full static Noto faces for deterministic PDF embedding, never subsets.

Run with the isolated tmp/auction-font-runtime Python environment (fonttools).
The source font and its OFL license remain in resources/auction-reports/fonts.
"""

from pathlib import Path

from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont


root = Path(__file__).resolve().parents[2] / 'resources/auction-reports/fonts'
for weight, style in [(400, 'regular'), (700, 'bold')]:
    source = TTFont(root / 'noto-sans-arabic.ttf')
    axes = {axis.axisTag: axis.defaultValue for axis in source['fvar'].axes}
    axes['wght'] = weight
    instance = instantiateVariableFont(source, axes)
    assert 'fvar' not in instance
    instance.save(root / f'noto-sans-arabic-{style}.ttf')
    print(f'Noto Sans Arabic {style}: full static font saved')
