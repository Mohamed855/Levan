"""Render selected numeric, table, cover and appendix pages at 300 DPI for visual QA."""

import hashlib
import json
import re
import subprocess
from pathlib import Path

from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parents[2]
TARGET = ROOT / "output/pdf/remediation-details"
TARGET.mkdir(parents=True, exist_ok=True)
cases = [
    ("remediation-acceptance/haram-outputs", "haram-marketing"),
    ("remediation-acceptance/asir-outputs", "haram-marketing"),
    ("remediation-acceptance/asir-report", "asir-cover"),
    ("remediation-acceptance/asir-report", "asir-video"),
    ("remediation-acceptance/asir-report", "asir-social"),
    ("edge-acceptance/outputs", "haram-results-continuation"),
    ("edge-acceptance/outputs", "haram-campaigns-continuation"),
]
receipt = []
tiles = []
for relative, family in cases:
    stem = ROOT / "output/pdf" / relative
    sections = re.findall(r'<section class="auction-print-page" data-page-type="([^"]+)">(.*?)</section>', stem.with_suffix(".html").read_text(encoding="utf-8"), re.S)
    matching = [(index + 1, content) for index, (kind, content) in enumerate(sections) if kind == family]
    if not matching:
        raise ValueError("Missing family: " + relative + ":" + family)
    page = next((index for index, content in matching if "campaign_metric_watch_hours" in content), matching[0][0])
    pdf = stem.with_suffix(".pdf")
    digest = hashlib.sha256(pdf.read_bytes()).hexdigest()
    prefix = TARGET / f"{stem.parent.name}-{stem.name}-p{page}-{digest[:12]}-300dpi"
    png = prefix.with_suffix(".png")
    if not png.exists():
        subprocess.run(["pdftoppm", "-f", str(page), "-l", str(page), "-singlefile", "-r", "300", "-png", str(pdf), str(prefix)], check=True, capture_output=True)
    with Image.open(png) as original:
        tile = Image.new("RGB", (620, 910), "white")
        preview = original.convert("RGB")
        preview.thumbnail((610, 865))
        tile.paste(preview, ((620 - preview.width) // 2, 35))
        ImageDraw.Draw(tile).text((8, 8), f"{relative}: page {page} / 300 DPI", fill="black")
        tiles.append(tile)
    receipt.append({"pdf": str(pdf), "sha256": digest, "page": page, "family": family, "dpi": 300, "png": str(png)})
for start in range(0, len(tiles), 4):
    group = tiles[start:start + 4]
    sheet = Image.new("RGB", (1240, 910 * ((len(group) + 1) // 2)), "#ddd")
    for index, tile in enumerate(group):
        sheet.paste(tile, (index % 2 * 620, index // 2 * 910))
    sheet.save(TARGET / f"contact-{start // 4 + 1}.png")
(TARGET / "receipt.json").write_text(json.dumps(receipt, indent=2), encoding="utf-8")
print(f"Rendered {len(receipt)} detailed pages at 300 DPI: {TARGET}")
