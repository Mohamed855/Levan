"""Inspect the six generated proofs and create unmasked reference comparisons.

Requires pypdf, Pillow and Poppler. Pass the two original references explicitly;
they are comparison inputs only, never rendering dependencies.
"""
import argparse
import json
import re
import subprocess
import unicodedata
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageStat
from pypdf import PdfReader


def normalize(value):
    return re.sub(r"[\s\u0640\u200e\u200f\u202a-\u202e]", "", unicodedata.normalize("NFKC", value))


def render(binary, source, number, target):
    subprocess.run([binary, "-f", str(number), "-l", str(number), "-singlefile", "-scale-to", "1250", "-png", str(source), str(target)], check=True, capture_output=True)
    return Image.open(str(target) + ".png").convert("RGB")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--pdftoppm", default="pdftoppm")
    parser.add_argument("--establishment-reference", type=Path, required=True)
    parser.add_argument("--closing-reference", type=Path, required=True)
    parser.add_argument("--output", type=Path, default=Path("output/pdf/auction-regulatory-forms"))
    args = parser.parse_args()
    comparison = args.output / "comparison"
    comparison.mkdir(parents=True, exist_ok=True)
    expected = {"establishment-blank": 6, "establishment-example": 6, "establishment-continuation": 9, "closing-blank": 1, "closing-example": 1, "closing-continuation": 3}
    result = {"proofs": {}, "comparisons": [], "exact_visual_match": False}
    for name, count in expected.items():
        reader = PdfReader(args.output / (name + ".pdf"))
        assert len(reader.pages) == count, (name, len(reader.pages), count)
        sizes = []
        text = []
        for index, page in enumerate(reader.pages):
            width, height = float(page.mediabox.width), float(page.mediabox.height)
            landscape = name.startswith("closing") or (name.startswith("establishment") and (index == 3 if count == 6 else 3 <= index <= 5))
            wanted = (841.89, 595.276) if landscape else (595.276, 841.89)
            assert max(abs(width - wanted[0]), abs(height - wanted[1])) < 0.4, (name, index + 1, width, height)
            content = page.extract_text() or ""
            assert len(content) > 80, (name, index + 1, "Missing native text")
            text.append(content)
            sizes.append([round(width, 4), round(height, 4)])
        if name.endswith("example"):
            content = normalize("".join(text))
            assert "000123456789" in content and "000987654321" in content, name
            if name.startswith("closing"):
                assert "1,350,000.25" in content, name
        result["proofs"][name] = {"pages": count, "page_sizes_pt": sizes, "native_text_characters": sum(map(len, text))}

    links = []
    for kind, reference in [("establishment", args.establishment_reference), ("closing", args.closing_reference)]:
        reader = PdfReader(reference)
        generated = args.output / (kind + "-blank.pdf")
        for index in range(len(reader.pages)):
            name = f"{kind}-{index + 1}"
            original = render(args.pdftoppm, reference, index + 1, comparison / (name + "-source"))
            actual = render(args.pdftoppm, generated, index + 1, comparison / (name + "-generated"))
            if actual.size != original.size:
                actual = actual.resize(original.size, Image.Resampling.LANCZOS)
            difference = ImageChops.difference(original, actual)
            threshold = difference.convert("L").point(lambda value: 255 if value > 24 else 0)
            changed = threshold.histogram()[255]
            mean_difference = sum(ImageStat.Stat(difference).mean) / 3
            pair = Image.new("RGB", (original.width * 2 + 16, original.height + 32), "white")
            pair.paste(original, (0, 32))
            pair.paste(actual, (original.width + 16, 32))
            draw = ImageDraw.Draw(pair)
            draw.text((10, 10), "ORIGINAL", fill="black")
            draw.text((original.width + 26, 10), "GENERATED NATIVE TEMPLATE", fill="black")
            pair.save(comparison / (name + "-comparison.png"))
            difference.save(comparison / (name + "-difference.png"))
            result["comparisons"].append({"form": kind, "page": index + 1, "mean_rgb_difference_0_to_255": round(mean_difference, 4), "changed_pixels_above_24": changed, "pixels": original.width * original.height, "identical": difference.getbbox() is None})
            links.append(f'<section><h2>{name}</h2><a href="{name}-difference.png">Unmasked difference</a><img src="{name}-comparison.png" alt="Original and generated page"></section>')
    result["exact_visual_match"] = all(row["identical"] for row in result["comparisons"])
    result["interpretation"] = "Unmasked raster differences include typography, native layout and renderer antialiasing. A low whole-page mean is not proof of 100% fidelity."
    (args.output / "verification.json").write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")
    (comparison / "index.html").write_text('<!doctype html><meta charset="utf-8"><title>REGA template comparison</title><style>body{font:16px sans-serif;margin:24px;background:#eee}img{display:block;width:100%;margin-top:8px}section{margin-bottom:36px}h1{font-size:24px}</style><h1>Unmasked original / generated comparisons</h1><p>Native templates. Visual differences remain; inspect each page.</p>' + "".join(links), encoding="utf-8")
    print(json.dumps({"proofs": {key: value["pages"] for key, value in result["proofs"].items()}, "compared_pages": len(result["comparisons"]), "exact_visual_match": result["exact_visual_match"]}))


if __name__ == "__main__":
    main()
