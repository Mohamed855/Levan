"""Four-document, unmasked review against the immutable original PDFs."""

import argparse
import hashlib
import json
import subprocess
import unicodedata
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw
from pypdf import PdfReader


def pdf_contract(path, count, dimensions):
    reader = PdfReader(path)
    text = unicodedata.normalize("NFKC", "\n".join(page.extract_text() or "" for page in reader.pages))
    sizes = [[float(page.mediabox.width), float(page.mediabox.height)] for page in reader.pages]
    errors = []
    if len(reader.pages) != count:
        errors.append("PDF page count differs from rendered HTML")
    if not text or "\0" in text or "\ufffd" in text:
        errors.append("Empty or corrupted searchable text")
    if any(abs(width - dimensions[0]) > .5 or abs(height - dimensions[1]) > .5 for width, height in sizes):
        errors.append("MediaBox outside 0.5pt tolerance")
    return {"pages": len(reader.pages), "sizes": sizes, "extractable_characters": len(text), "unicode_errors": text.count("\0") + text.count("\ufffd"), "errors": errors, "pass": not errors}


ASIR = ["asir-cover", "asir-index", "asir-introduction", "asir-summary", "asir-planning", "asir-design", "asir-promotion", "asir-board-installation-1", "asir-board-installation-2", "asir-brochure", "asir-platform", "asir-video", "asir-x", "asir-whatsapp", "asir-social", "asir-brokers", "asir-board-removal-1", "asir-board-removal-2", "asir-sold-assets", "asir-unsold-assets", "asir-closing"]
HARAM = ["haram-cover", "haram-index", "haram-results", "haram-marketing", "haram-board-before", "haram-brokers", "haram-closing"]


def reference_index(family, kind):
    base = family.removesuffix("-continuation")
    families = HARAM if kind == "outputs" else ASIR
    if base in families:
        return families.index(base)
    if kind == "outputs":
        return 4  # Purpose-built gallery shares the boards page, not an unrelated channel.
    return 9  # Report gallery shares the brochure page's native header/panel.


def rasterize(pdf, prefix, dpi):
    existing = sorted(prefix.parent.glob(prefix.name + "-*.png"))
    if existing:
        # Never reuse images from an older PDF revision.
        stamp = prefix.with_suffix(".sha256")
        if stamp.exists() and stamp.read_text() == hashlib.sha256(pdf.read_bytes()).hexdigest():
            return existing
    subprocess.run(["pdftoppm", "-r", str(dpi), "-png", str(pdf), str(prefix)], check=True, capture_output=True)
    prefix.with_suffix(".sha256").write_text(hashlib.sha256(pdf.read_bytes()).hexdigest())
    return sorted(prefix.parent.glob(prefix.name + "-*.png"))


def comparison(receipt_path, manifest_path, dpi):
    root = Path(__file__).resolve().parents[2]
    receipt = json.loads(receipt_path.read_text(encoding="utf-8"))
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    target = root / "output/pdf/remediation-comparison"
    target.mkdir(parents=True, exist_ok=True)
    summary = {"dpi": dpi, "dynamic_masks": False, "automatic_visual_acceptance": False, "visual_status": "requires_human_review", "documents": {}}
    refs = {}
    for kind, reference in manifest["references"].items():
        path = Path(reference["path"])
        digest = hashlib.sha256(path.read_bytes()).hexdigest()
        if digest != reference["sha256"]:
            raise ValueError("Immutable original changed: " + kind)
        refs[kind] = rasterize(path, target / ("reference-" + kind), dpi)
    for auction, record in receipt["auctions"].items():
        for kind, document in record["documents"].items():
            key = auction + "-" + kind
            pdf = Path(document["path"])
            generated = rasterize(pdf, target / key, dpi)
            html = pdf.with_suffix(".html").read_text(encoding="utf-8")
            import re
            families = re.findall(r'data-page-type="([^"]+)"', html)
            if len(generated) != len(families):
                raise ValueError("Raster/HTML page mismatch: " + key)
            rows, tiles = [], []
            for index, (image_path, family) in enumerate(zip(generated, families)):
                source_index = reference_index(family, kind)
                ref = Image.open(refs[kind][source_index]).convert("RGB")
                actual = Image.open(image_path).convert("RGB")
                # Compare in source point coordinates. MediaBox drift is separately reported, never masked.
                scaled = actual.resize(ref.size, Image.Resampling.LANCZOS)
                delta = np.max(np.abs(np.asarray(ref).astype(np.int16) - np.asarray(scaled).astype(np.int16)), axis=2)
                difference = np.full((*delta.shape, 3), 255, dtype=np.uint8)
                difference[delta > 3] = [220, 40, 40]
                stem = f"{key}-{index + 1:02}"
                Image.blend(ref, scaled, .5).save(target / (stem + "-overlay.png"))
                Image.fromarray(difference).save(target / (stem + "-diff.png"))
                rows.append({"page": index + 1, "family": family, "reference_page": source_index + 1, "full_page_changed_ratio_rgb3": float((delta > 3).mean()), "excluded_regions": [], "reason": "different auction data" if (auction == "haram") != (kind == "outputs") else "same auction backup; source gaps separately recorded", "visual_status": "requires_human_review"})
                tile = Image.new("RGB", (720, 370), "white")
                for offset, image in [(0, ref), (240, scaled), (480, Image.fromarray(difference))]:
                    thumbnail = image.copy()
                    thumbnail.thumbnail((238, 337))
                    tile.paste(thumbnail, (offset, 25))
                ImageDraw.Draw(tile).text((4, 4), f"{key} {index + 1}: ref / output / FULL diff", fill="black")
                tiles.append(tile)
            for start in range(0, len(tiles), 6):
                group = tiles[start:start + 6]
                sheet = Image.new("RGB", (1440, 370 * ((len(group) + 1) // 2)), "#ddd")
                for index, tile in enumerate(group):
                    sheet.paste(tile, ((index % 2) * 720, (index // 2) * 370))
                sheet.save(target / f"{key}-contact-{start // 6 + 1}.png")
            summary["documents"][key] = {"source_hash": manifest["references"][kind]["sha256"], "snapshot_hash": record["snapshot_sha256"], "pdf_contract": pdf_contract(pdf, len(families), manifest["references"][kind]["media_box"]), "pages": rows}
            print(key + ": " + str(len(rows)) + " unmasked page comparisons")
    (target / "comparison-summary.json").write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")
    return all(record["pdf_contract"]["pass"] for record in summary["documents"].values())


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("reference_directory", nargs="?", type=Path, help="Legacy argument; immutable originals come from the acceptance manifest.")
    parser.add_argument("--receipt", type=Path)
    parser.add_argument("--manifest", type=Path, default=Path(__file__).resolve().parents[2] / "tests/Fixtures/AuctionReports/acceptance-manifest.json")
    parser.add_argument("--dpi", type=int, default=150)
    parser.add_argument("--pdf", type=Path)
    parser.add_argument("--pages", type=int)
    parser.add_argument("--width", type=float)
    parser.add_argument("--height", type=float)
    args = parser.parse_args()
    if args.pdf:
        if any(value is None for value in [args.pages, args.width, args.height]):
            parser.error("--pdf requires --pages, --width and --height")
        contract = pdf_contract(args.pdf, args.pages, (args.width, args.height))
        print(json.dumps(contract, indent=2))
        return 0 if contract["pass"] else 1
    receipt = args.receipt or Path(__file__).resolve().parents[2] / "output/pdf/remediation-acceptance/receipt.json"
    return 0 if comparison(receipt, args.manifest, args.dpi) else 1


if __name__ == "__main__":
    raise SystemExit(main())
