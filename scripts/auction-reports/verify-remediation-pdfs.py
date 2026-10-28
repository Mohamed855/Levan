"""Evidence-based contracts for all four file-only acceptance PDFs; no static score gate."""

import hashlib
import json
import re
import sys
import unicodedata
from pathlib import Path

from pypdf import PdfReader


def normalized(text):
    return unicodedata.normalize("NFKC", text).translate(str.maketrans("٠١٢٣٤٥٦٧٨٩", "0123456789"))


def inspect_pdf(path, expected_pages, dimensions):
    reader = PdfReader(path)
    text = normalized("\n".join(page.extract_text() or "" for page in reader.pages))
    links = []
    for page_index, page in enumerate(reader.pages):
        for annotation in page.get("/Annots", []):
            item = annotation.get_object()
            if item.get("/Subtype") == "/Link":
                box = [float(value) for value in item["/Rect"]]
                assert 0 <= box[0] < box[2] <= float(page.mediabox.width) + 0.5, (path, "link outside page", box)
                assert 0 <= box[1] < box[3] <= float(page.mediabox.height) + 0.5, (path, "link outside page", box)
                assert (box[2] - box[0]) * (box[3] - box[1]) < float(page.mediabox.width) * float(page.mediabox.height) / 4, "Oversized link hitbox"
                links.append({"page": page_index + 1, "url": str(item.get("/A", {}).get("/URI", "")), "box": box})
    errors = []
    if len(reader.pages) != expected_pages:
        errors.append(f"unexpected blank/extra PDF pages: {len(reader.pages)} vs HTML {expected_pages}")
    if "\0" in text or "\ufffd" in text:
        errors.append("corrupted Unicode mappings")
    differences = [[float(page.mediabox.width) - dimensions[0], float(page.mediabox.height) - dimensions[1]] for page in reader.pages]
    if any(abs(value) > 0.5 for delta in differences for value in delta):
        errors.append("MediaBox differs by over 0.5pt")
    return {"path": str(path), "sha256": hashlib.sha256(path.read_bytes()).hexdigest(), "pages": len(reader.pages), "media_box_delta_pt": differences, "unicode_errors": text.count("\0") + text.count("\ufffd"), "links": links, "errors": errors}, text


root = Path(__file__).resolve().parents[2]
directory = root / "output/pdf/remediation-acceptance"
receipt = json.loads((directory / "receipt.json").read_text(encoding="utf-8"))
manifest = json.loads((root / "tests/Fixtures/AuctionReports/acceptance-manifest.json").read_text(encoding="utf-8"))
for reference in manifest["references"].values():
    assert hashlib.sha256(Path(reference["path"]).read_bytes()).hexdigest() == reference["sha256"], "An immutable original changed"
results = {"mode": "original backup values; incomplete original source explicitly not claimed identical", "documents": {}, "visual_acceptance": "pending", "missing_source": {}}
for auction, item in receipt["auctions"].items():
    snapshot = json.loads((directory / f"{auction}-snapshot.json").read_text(encoding="utf-8"))
    missing = {section: "not present in the saved auction evidence" for section in ["brochure", "brokers"] if not snapshot["evidence"].get(section)}
    if not snapshot["marketing"].get("contacts"):
        missing["contacts"] = "no phone/social values in saved auction"
    if not snapshot["marketing"].get("introduction"):
        missing["introduction"] = "auction description fallback, not the original report introduction"
    results["missing_source"][auction] = missing
    for kind, document in item["documents"].items():
        result, text = inspect_pdf(Path(document["path"]), document["pages"], manifest["references"][kind]["media_box"])
        for campaign in snapshot["marketing"].get("campaigns", []):
            value = campaign.get("impressions")
            if value is not None and f"{value:,.0f}" not in text:
                result["errors"].append(f"missing or split metric: {value:,.0f}")
        for prop in snapshot["properties"]:
            # Ignore whitespace only, never mask the data region or substitute reference values.
            if re.sub(r"\s+", "", normalized(prop["number"])) not in re.sub(r"\s+", "", text):
                result["errors"].append(f"property number missing: {prop['id']}")
        results["documents"][f"{auction}-{kind}"] = result
for kind in ["outputs", "report"]:
    path = root / f"tmp/pdfs/remediation/link-proof-{kind}.pdf"
    if path.exists():
        result, _ = inspect_pdf(path, len(PdfReader(path).pages), manifest["references"][kind]["media_box"])
        required = {"tel:0534811800", "tel:920000000", "https://example.com/social", "https://example.com/x", "https://example.com/tiktok"}
        required |= {"https://example.com/marketing_evidence", "https://example.com/boards"} if kind == "outputs" else {"https://example.com/brochure", "https://example.com/video", "https://example.com/platform", "https://example.com/whatsapp"}
        for url in required - {link["url"] for link in result["links"]}:
            result["errors"].append("missing PDF LinkAnnotation: " + url)
        results["documents"]["link-proof-" + kind] = result
results["functional_contract_pass"] = all(not document["errors"] for document in results["documents"].values())
results["full_reference_acceptance"] = False  # Source gaps and visual review are independent gates.
(directory / "pdf-contract.json").write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps({name: {"pages": value["pages"], "unicode_errors": value["unicode_errors"], "links": len(value["links"]), "errors": value["errors"]} for name, value in results["documents"].items()}, ensure_ascii=True, indent=2))
sys.exit(0 if results["functional_contract_pass"] else 1)
