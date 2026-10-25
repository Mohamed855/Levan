<style>
@page { size: A4; margin: 0; }
* { box-sizing: border-box; }
body { margin: 0; direction: rtl; font-family: Tahoma, Arial, sans-serif; color: #15392f; background: #edf3f0; }
.page { width: 210mm; min-height: 297mm; padding: 16mm; margin: 0 auto; background: #fff; page-break-after: always; position: relative; overflow: hidden; }
.page:last-child { page-break-after: auto; }
.brand { color: #caa64a; letter-spacing: .04em; font-weight: 700; }
.hero { background: linear-gradient(145deg,#062f24,#0d5441); color:#fff; padding: 18mm 14mm; border-bottom: 4px solid #caa64a; }
.hero h1 { margin: 6mm 0 2mm; font-size: 30px; }
.hero p { margin: 0; color: #cfe4dc; line-height: 1.8; }
.cover-image { width: 100%; height: 105mm; object-fit: cover; margin-top: 10mm; border: 3px solid #caa64a; border-radius: 4mm; }
.image-placeholder { height: 105mm; margin-top: 10mm; background:#e7efeb; display:flex; align-items:center; justify-content:center; color:#658078; border-radius:4mm; }
.section-title { color:#0b4b39; border-right: 5px solid #caa64a; padding-right: 4mm; margin: 0 0 7mm; font-size: 23px; }
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:4mm; margin:8mm 0; }
.stat { background:#edf5f1; border-top:3px solid #caa64a; padding:5mm 3mm; text-align:center; border-radius:2mm; }
.stat strong { display:block; font-size:22px; color:#0b4b39; }
.stat span { font-size:11px; color:#61766f; }
.grid { display:grid; grid-template-columns:repeat(2,1fr); gap:5mm; }
.card { border:1px solid #d8e2de; border-radius:3mm; overflow:hidden; margin-bottom:5mm; break-inside:avoid; }
.card h3 { margin:0; padding:4mm; color:#fff; background:#0b4b39; font-size:16px; }
.card-body { padding:5mm; line-height:1.8; }
table { width:100%; border-collapse:collapse; font-size:11px; }
thead { display:table-header-group; }
th { background:#0b4b39; color:#fff; padding:3mm 2mm; }
td { border:1px solid #dce5e1; padding:3mm 2mm; vertical-align:top; }
tr { break-inside:avoid; }
.property-image { width:100%; height:70mm; object-fit:cover; border-radius:3mm; }
.muted { color:#71827c; }
.gold { color:#a9852d; }
.amount { font-weight:700; white-space:nowrap; }
.footer { position:absolute; bottom:7mm; right:16mm; left:16mm; border-top:1px solid #d7e0dc; padding-top:3mm; color:#73837d; font-size:10px; display:flex; justify-content:space-between; }
.evidence-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:4mm; }
.evidence-grid img { width:100%; height:75mm; object-fit:cover; border:1px solid #d8e2de; border-radius:3mm; }
.no-data { padding:12mm; text-align:center; color:#788983; background:#f2f6f4; border-radius:3mm; }
</style>
