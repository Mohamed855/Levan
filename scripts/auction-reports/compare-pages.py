"""Development-only visual comparison; use the bundled isolated Python runtime."""

import argparse
import hashlib
import json
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw
from pypdf import PdfReader


def pdf_contract(path, count, dimensions):
    reader = PdfReader(path)
    fonts = {}
    for page in reader.pages:
        for font in page.get("/Resources", {}).get("/Font", {}).values():
            font = font.get_object()
            if font.get("/Subtype") == "/Type3":
                fonts["Type3:"+str(font.get("/Name", len(fonts)))] = bool(font.get("/CharProcs"))
                continue
            descendants = font.get("/DescendantFonts", [font])
            for descendant in descendants:
                descriptor = descendant.get_object().get("/FontDescriptor")
                if descriptor:
                    descriptor = descriptor.get_object()
                    fonts[str(font.get("/BaseFont"))] = any(k in descriptor for k in ["/FontFile", "/FontFile2", "/FontFile3"])
    sizes = [[float(p.mediabox.width), float(p.mediabox.height)] for p in reader.pages]
    return {
        "pages": len(reader.pages), "sizes": sizes, "fonts": fonts,
        "extractable_characters": sum(len(p.extract_text() or "") for p in reader.pages),
        "pass": len(reader.pages) == count and all(abs(w-dimensions[0]) < .02 and abs(h-dimensions[1]) < .02 for w,h in sizes) and bool(fonts) and all(fonts.values()),
    }


def compare(root, design, count, target):
    masks = json.loads((root / f"output/pdf/reference-fixtures/{design}.masks.json").read_text())
    rows, tiles = [], []
    for index in range(count):
        number = index + 1
        references = sorted((root / "tmp/pdfs").glob(f"{design}-reference-*.png"))
        generated = sorted((root / "tmp/pdfs").glob(f"{design}-generated-*.png"))
        ref, actual = Image.open(references[index]).convert("RGB"), Image.open(generated[index]).convert("RGB")
        if ref.size != actual.size:
            raise ValueError(f"{design} page {number}: raster size mismatch {ref.size} vs {actual.size}")
        definition = masks[index]
        mask = Image.new("L", ref.size)
        draw = ImageDraw.Draw(mask)
        sx, sy = ref.width / definition["width"], ref.height / definition["height"]
        for entry in definition["boxes"]:
            x0,y0,x1,y1 = entry["box"]
            if x1 > x0 and y1 > y0:
                # One raster pixel includes the antialiased edge of an image frame.
                padding = 1 if entry["reason"].startswith("media:") else 0
                draw.rectangle((x0*sx-padding,y0*sy-padding,x1*sx+padding,y1*sy+padding), fill=255)
        a, b, excluded = np.asarray(ref).astype(np.int16), np.asarray(actual).astype(np.int16), np.asarray(mask)>0
        distance = np.max(np.abs(a-b), axis=2)
        mismatch = (distance > 12) & ~excluded
        mismatch_ratio = float(mismatch.sum() / max(1,(~excluded).sum()))
        rows.append({"page":number,"masked_ratio":float(excluded.mean()),"static_mismatch_ratio":mismatch_ratio,"pass":mismatch_ratio <= .005 and float(excluded.mean()) < .9})
        overlay = Image.blend(ref, actual, .5)
        heat = np.full_like(a, 255, dtype=np.uint8)
        heat[excluded] = [220,220,220]
        heat[mismatch] = [255,0,0]
        overlay.save(target / f"{design}-{number:02}-overlay.png")
        Image.fromarray(heat).save(target / f"{design}-{number:02}-diff.png")
        mask.save(target / f"{design}-{number:02}-mask.png")
        tile = Image.new("RGB",(720,550),"#ddd")
        for offset, image in [(0,ref),(240,actual),(480,Image.fromarray(heat))]:
            image.thumbnail((238,520))
            tile.paste(image,(offset,25))
        ImageDraw.Draw(tile).text((5,5),f"{design} {number}: ref / generated / static diff ({mismatch_ratio:.2%})",fill="black")
        tiles.append(tile)
    for start in range(0,count,7):
        group = tiles[start:start+7]
        sheet = Image.new("RGB",(720*min(2,len(group)),550*((len(group)+1)//2)),"white")
        for i,tile in enumerate(group):
            sheet.paste(tile,((i%2)*720,(i//2)*550))
        sheet.save(target / f"{design}-contact-{start//7+1}.png")
    return rows


parser = argparse.ArgumentParser()
parser.add_argument("reference_directory",type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[2]
target = root / "output/pdf/reference-comparison"
target.mkdir(parents=True,exist_ok=True)
summary = {"dpi":96,"channel_tolerance":12,"maximum_static_mismatch_ratio":.005,"designs":{}}
for design,count,dimensions,digest in [
    ("haram",7,(595.92,841.92),"e3ce21e270035e308e9f47d919f7b003f415a185b0eb5128af21025f50acce7f"),
    ("asir",21,(594.96,841.92),"88624e3bb36950fb9ed6e759471fae9e58096c39c5727b7e6e93b59bf73385d3"),
]:
    source = args.reference_directory / f"{design}-print.pdf"
    if hashlib.sha256(source.read_bytes()).hexdigest() != digest:
        raise ValueError(f"{design}: reference PDF hash mismatch")
    pages = compare(root,design,count,target)
    contract = pdf_contract(root / f"output/pdf/reference-fixtures/{design}.pdf",count,dimensions)
    summary["designs"][design] = {"pages":pages,"pdf_contract":contract,"pass":contract["pass"] and all(p["pass"] for p in pages)}
(target / "comparison-summary.json").write_text(json.dumps(summary,indent=2),encoding="utf-8")
print(json.dumps({d:{"pass":v["pass"],"pdf_contract":v["pdf_contract"]["pass"],"static_mismatch":[round(p["static_mismatch_ratio"]*100,2) for p in v["pages"]]} for d,v in summary["designs"].items()},indent=2))
raise SystemExit(0 if all(v["pass"] for v in summary["designs"].values()) else 1)
