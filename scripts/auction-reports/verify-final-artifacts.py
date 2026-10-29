"""Inspect downloaded CI PDFs and synthetic edge PDFs, not only their file headers."""

import hashlib
import json
import re
import unicodedata
from pathlib import Path

import pdfplumber
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[2]
MANIFEST = json.loads((ROOT / "tests/Fixtures/AuctionReports/acceptance-manifest.json").read_text(encoding="utf-8"))
DIRECTORY = ROOT / "output/pdf/queued-ci-acceptance"


def inspect(path, kind):
    reader = PdfReader(path)
    text = unicodedata.normalize("NFKC", "\n".join(page.extract_text() or "" for page in reader.pages))
    text = text.translate(str.maketrans("٠١٢٣٤٥٦٧٨٩", "0123456789"))
    assert text and "\0" not in text and "\ufffd" not in text, (path, "invalid searchable text")
    width, height = MANIFEST["references"][kind]["media_box"]
    links = []
    for page in reader.pages:
        assert abs(float(page.mediabox.width) - width) <= 0.5, (path, "page width")
        assert abs(float(page.mediabox.height) - height) <= 0.5, (path, "page height")
        for reference in page.get("/Annots", []):
            annotation = reference.get_object()
            if annotation.get("/Subtype") != "/Link":
                continue
            link = str(annotation.get("/A", {}).get("/URI", ""))
            assert link.startswith(("https://", "tel:")), (path, "unsafe link", link)
            left, bottom, right, top = map(float, annotation["/Rect"])
            assert 0 <= left < right <= float(page.mediabox.width) + 0.5
            assert 0 <= bottom < top <= float(page.mediabox.height) + 0.5
            links.append(link)
    # pypdf's bidi extraction reverses Arabic-Indic numeric runs. Check values
    # independently by positioned glyphs, not by accepting a reversed number.
    with pdfplumber.open(path) as positioned:
        numeric_text = unicodedata.normalize("NFKC", "\n".join(page.extract_text() or "" for page in positioned.pages))
    numeric_text = numeric_text.translate(str.maketrans("٠١٢٣٤٥٦٧٨٩", "0123456789"))
    return {"sha256": hashlib.sha256(path.read_bytes()).hexdigest(), "pages": len(reader.pages), "unicode_errors": 0, "links": links}, text, numeric_text


results = {"queued": {}, "edge": {}, "functional_pass": False, "full_reference_acceptance": False}
for trigger in ["manual", "auto"]:
    receipt = json.loads((DIRECTORY / f"{trigger}-result.json").read_text(encoding="utf-8"))
    documents = receipt["documents"]
    assert len(documents) == 4 and receipt["tenant"] == "auction_report_ci"
    identities = {}
    verified = []
    for document in documents:
        assert document["status"] == "ready" and document["private"] is True
        identity = (document["generation"], document["revision"], document["snapshot_sha256"])
        identities.setdefault(document["id"], identity)
        assert identities[document["id"]] == identity, "Both documents must share a generation and snapshot"
        auction = "haram" if "الحرم" in document["name"] else "asir"
        path = Path(document["pdf"])
        result, text, numeric_text = inspect(path, document["type"])
        assert result["sha256"] == document["sha256"], "Downloaded artifact changed since collection"
        expected = json.loads((ROOT / f"output/pdf/remediation-acceptance/{auction}-snapshot.json").read_text(encoding="utf-8"))
        for property_record in expected["properties"]:
            assert re.sub(r"\s+", "", property_record["number"]) in re.sub(r"\s+", "", text), (path, "missing property", property_record["id"])
        if document['type'] == 'report':
            assert f'{MANIFEST["auctions"][auction]["sales_total"]:,.0f}' in numeric_text, (path, "missing sales total")
        for property_record in expected['properties']:
            if property_record.get('sale_price') is not None:
                assert f'{property_record["sale_price"]:,.0f}' in numeric_text, (path, "missing sale price", property_record['id'])
        verified.append({"auction": auction, "type": document["type"], "generation": document["generation"], **result})
    assert len(identities) == 2
    results["queued"][trigger] = verified

for kind in ["outputs", "report"]:
    directory = ROOT / "output/pdf/edge-acceptance"
    result, text, numeric_text = inspect(directory / f"{kind}.pdf", kind)
    html = (directory / f"{kind}.html").read_text(encoding="utf-8")
    assert result["pages"] == html.count("data-page-type=")
    for destination in ["https://example.com/first-video", "https://example.com/second-video"]:
        assert destination in result["links"], (kind, "missing per-campaign annotation", destination)
    for value in ["123", "456", "12.50", "1.25"]:
        assert value in numeric_text, (kind, "missing campaign metric", value)
    geometry = json.loads((directory / f"{kind}.geometry.json").read_text(encoding="utf-8"))
    overflow = [region for page in geometry for region in page["regions"] if region["kind"] == "dynamic_text" and (region["overWidth"] or region["outsidePage"])]
    assert not overflow, (kind, "edge-case text overflow", overflow)
    result["geometry_overflows"] = len(overflow)
    results["edge"][kind] = result

results["functional_pass"] = True
output = ROOT / "output/pdf/remediation-acceptance/final-artifacts.json"
output.write_text(json.dumps(results, indent=2), encoding="utf-8")
print(json.dumps(results, indent=2))
