@php
    $regularFont = base64_encode(file_get_contents(public_path('static/fonts/almarai/Almarai-Regular.ttf')));
    $boldFont = base64_encode(file_get_contents(public_path('static/fonts/almarai/Almarai-Bold.ttf')));
    $navyLogo = base64_encode(file_get_contents(public_path('static/brand/logos/lot-logo.svg')));
    $lightLogo = base64_encode(file_get_contents(public_path('static/brand/logos/lot-logo-light.svg')));
@endphp
<style>
@font-face { font-family: 'AlmaraiReport'; src: url('data:font/ttf;base64,{{ $regularFont }}') format('truetype'); font-weight: 400; }
@font-face { font-family: 'AlmaraiReport'; src: url('data:font/ttf;base64,{{ $boldFont }}') format('truetype'); font-weight: 700; }
@page auctionReportPage { size:595.92pt 841.92pt; margin:0; }
* { box-sizing: border-box; }
body { margin: 0; direction: rtl; font-family: 'AlmaraiReport', Tahoma, Arial, sans-serif; color: #15392f; background: #edf3f0; }
.page { display:block; width:595.92pt; height:841.92pt; padding:16mm; margin:0; background:#fff; page:auctionReportPage; page-break-after:always; break-after:page; position:relative; overflow:hidden; }
.page:last-child { page-break-after: auto; break-after: auto; }
.brand { color: #caa64a; letter-spacing: .04em; font-weight: 700; }
.hero { background: linear-gradient(145deg,#062f24,#0d5441); color:#fff; padding: 18mm 14mm; border-bottom: 4px solid #caa64a; }
.hero h1 { margin: 6mm 0 2mm; font-size: 30px; line-height:1.5; overflow-wrap:anywhere; }
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
.property-gallery { display:grid; grid-template-columns:repeat(2,1fr); gap:4mm; margin:7mm 0; }
.property-gallery img { width:100%; height:62mm; object-fit:cover; border-radius:3mm; border:1px solid #d8e2de; }
.muted { color:#71827c; }
.gold { color:#a9852d; }
.amount { font-weight:700; white-space:nowrap; }
.footer { position:absolute; bottom:7mm; right:16mm; left:16mm; border-top:1px solid #d7e0dc; padding-top:3mm; color:#73837d; font-size:10px; display:flex; justify-content:space-between; }
.evidence-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:4mm; }
.evidence-grid img { width:100%; height:75mm; object-fit:cover; border:1px solid #d8e2de; border-radius:3mm; }
.no-data { padding:12mm; text-align:center; color:#788983; background:#f2f6f4; border-radius:3mm; }
.cover-title { font-size:34px; line-height:1.55; margin:5mm 0; overflow-wrap:anywhere; }
.levan-logo-light,.levan-logo-navy { display:block; width:28mm; height:22mm; background-position:center; background-repeat:no-repeat; background-size:contain; }
.levan-logo-light { background-image:url('data:image/svg+xml;base64,{{ $lightLogo }}'); }
.levan-logo-navy { background-image:url('data:image/svg+xml;base64,{{ $navyLogo }}'); }
.outputs-cover { padding:0; color:#fff; background:linear-gradient(135deg,#17111d 0%,#1e2632 100%); }
.outputs-cover__background { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.09; filter:grayscale(1); }
.outputs-cover__grid { position:absolute; inset:0; opacity:.24; background-image:linear-gradient(rgba(48,190,159,.17) 1px,transparent 1px),linear-gradient(90deg,rgba(48,190,159,.17) 1px,transparent 1px); background-size:14mm 14mm; }
.outputs-cover__top { position:absolute; top:15mm; right:14mm; left:14mm; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(55,194,164,.62); padding-bottom:6mm; }
.outputs-cover__eyebrow { color:#42c4a3; font-size:12px; font-weight:700; }
.outputs-cover__content { position:absolute; top:102mm; right:18mm; left:18mm; text-align:center; }
.outputs-cover__kicker { margin:0 0 8mm; color:#42c4a3; font-size:14px; font-weight:700; }
.outputs-cover__content h1 { margin:0; font-size:43px; line-height:1.45; overflow-wrap:anywhere; }
.outputs-cover__content p:not(.outputs-cover__kicker) { width:78%; margin:5mm auto 0; color:#d8d2dc; line-height:1.85; }
.outputs-cover__accent { height:12mm; width:72%; margin:12mm auto 0; border-radius:0 0 4mm 4mm; background:linear-gradient(90deg,#3387c8,#47c18e); border-top:1px solid #36c19b; }
.outputs-cover__meta { position:absolute; top:213mm; right:18mm; left:18mm; display:grid; grid-template-columns:repeat(4,1fr); gap:3mm; }
.outputs-cover__meta div { min-height:16mm; border:1px solid rgba(255,255,255,.16); background:rgba(48,38,51,.84); border-radius:2mm; padding:3mm; text-align:center; }
.outputs-cover__meta span,.outputs-cover__meta strong { display:block; }
.outputs-cover__meta span { color:#aaa3ae; font-size:9px; margin-bottom:2mm; }
.outputs-cover__meta strong { color:#47c79e; font-size:11px; line-height:1.5; overflow-wrap:anywhere; }
.outputs-cover__footer { position:absolute; right:14mm; left:14mm; bottom:13mm; display:flex; justify-content:space-between; color:#aaa4ae; font-size:9px; border-top:1px solid rgba(55,194,164,.45); padding-top:4mm; }
.outputs-results-page { background:#f5f7fb; padding-top:31mm; color:#183d65; }
.document-header { position:absolute; top:0; right:0; left:0; height:22mm; padding:3mm 14mm; display:flex; align-items:center; justify-content:space-between; background:linear-gradient(90deg,#2e77ad 0 72%,#251b29 72%); color:#fff; font-size:18px; }
.document-header .levan-logo-light { height:16mm; width:22mm; }
.outputs-results-page .section-title { color:#173f68; border-color:#2ea590; }
.outputs-results-page th { background:#1e4269; }
.outputs-results-page tbody tr:nth-child(even) { background:#e9eef4; }
.outputs-results-page .stat { background:#fff; border-color:#2fa98d; }
.outputs-results-page .stat strong { color:#1e4269; }
.report-cover { padding:0; background:#fff; color:#11194f; }
.report-cover__logo { position:absolute; top:12mm; right:14mm; width:32mm; height:28mm; }
.report-cover__title { position:absolute; top:82mm; right:12mm; left:12mm; text-align:center; z-index:2; }
.report-cover__title p { margin:0 0 4mm; font-size:21px; font-weight:700; }
.report-cover__title h1 { margin:0; font-size:46px; line-height:1.42; overflow-wrap:anywhere; }
.report-cover__title span { display:block; margin:6mm auto 0; width:76%; color:#39406a; font-size:14px; line-height:1.8; }
.report-cover__lines { position:absolute; top:112mm; left:-22mm; width:97mm; height:72mm; border:1.5px solid #312b34; border-radius:50%; opacity:.9; }
.report-cover__lines:after { content:''; position:absolute; top:-18mm; left:105mm; width:95mm; height:64mm; border:1.5px solid #312b34; transform:rotate(-43deg); }
.report-cover__image { position:absolute; right:0; left:0; bottom:0; height:111mm; overflow:hidden; background:#eef0f4; }
.report-cover__image img { width:100%; height:100%; object-fit:cover; }
.report-cover__image:after { content:''; position:absolute; inset:0; background:linear-gradient(180deg,rgba(255,255,255,.18),transparent 32%); }
.report-cover__meta { position:absolute; z-index:3; bottom:8mm; right:12mm; left:12mm; display:flex; justify-content:space-between; padding:3mm 5mm; color:#fff; background:rgba(16,24,73,.78); border-radius:2mm; font-size:10px; }
.report-document .section-title { color:#11194f; border-color:#2e77ad; }
.report-document .card h3,.report-document th { background:#11194f; }
.report-document .stat { border-color:#2e77ad; background:#f0f3f9; }
.report-document .stat strong { color:#11194f; }
</style>
