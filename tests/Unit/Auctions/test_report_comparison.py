"""Regression checks for repeatable artifact QA, independent of business databases."""

import importlib.util
import sys
import tempfile
import unittest
from pathlib import Path

from PIL import Image
from pypdf import PdfWriter

ROOT = Path(__file__).resolve().parents[3]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location("report_comparison", ROOT / "scripts/auction-reports/compare-pages.py")
comparison = importlib.util.module_from_spec(spec)
spec.loader.exec_module(comparison)


class ComparisonRasterTest(unittest.TestCase):
    @staticmethod
    def pdf(path, pages):
        writer = PdfWriter()
        for _ in range(pages):
            writer.add_blank_page(width=72, height=72)
        with path.open("wb") as stream:
            writer.write(stream)

    def test_rerun_ignores_its_own_overlays_and_contact_sheets(self):
        with tempfile.TemporaryDirectory(prefix="auction-qa-") as directory:
            root = Path(directory)
            pdf = root / "source.pdf"
            self.pdf(pdf, 1)
            prefix = root / "case"
            self.assertEqual(len(comparison.rasterize(pdf, prefix, 72)), 1)
            for name in ["case-01-overlay.png", "case-01-diff.png", "case-contact-1.png"]:
                Image.new("RGB", (10, 10), "white").save(root / name)
            self.assertEqual(len(comparison.rasterize(pdf, prefix, 72)), 1)

    def test_a_shorter_revision_cannot_reuse_pages_from_an_older_pdf(self):
        with tempfile.TemporaryDirectory(prefix="auction-qa-") as directory:
            root = Path(directory)
            pdf = root / "source.pdf"
            prefix = root / "case"
            self.pdf(pdf, 3)
            self.assertEqual(len(comparison.rasterize(pdf, prefix, 72)), 3)
            self.pdf(pdf, 1)
            self.assertEqual(len(comparison.rasterize(pdf, prefix, 72)), 1)


if __name__ == "__main__":
    unittest.main()
