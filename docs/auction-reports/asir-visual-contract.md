# Asir final-report visual contract

## Canvas and common layers

- Canvas: `595.276 × 841.890 pt`, RTL, zero print margins.
- Primary measured palette: `#0A395C`, `#224C6B`, `#00365D`, `#00AEEF`, `#205883`, `#FFFFFF`, with document-specific blue translucent layers.
- Cover: white background, navy Infath mark at upper right, very large dark-navy Arabic name, report subtitle, thin black geometric line art, and a lower landscape image.
- Pages 2–18 use the white/blue marketing-report family. Pages 19–20 use the blue translucent results family. Page 21 is the contact closing family.
- Source inventory across pages 1–21 is fixed in the reference package; image-heavy marketing pages must retain their original frame counts and placements even when their media changes.

## Page families

| Pages | Family | Dynamic content | Static design contract |
| --- | --- | --- | --- |
| 1 | Cover | auction name and report image | large headline, line art, lower landscape image, Infath mark |
| 2 | Project and property index | project, auction, manager, dates, property rows | landscape watermark, navy three-column card, two large date cards, 13-row property table |
| 3 | Executive introduction | approved narrative | structured white page with explanatory copy and image pair |
| 4–7 | Marketing methodology | approved narrative and supporting images | numbered/sectioned blue marketing-report composition |
| 8–9 | Board installation | board evidence images/captions | repeated board-photo grid and numbered evidence markers |
| 10–16 | Marketing channels | brochure, platform, video, social, broker details, metrics and media | numbered channel pages with fixed visual evidence frames |
| 17–18 | Board removal | removal evidence images/captions | repeated removal-photo grid and numbered evidence markers |
| 19 | Sold assets | sold properties, opening/sale amounts, participants, bids, total | dark blue translucent result card, one compact property block per sold asset |
| 20 | Unsold assets | unsold properties and reasons | dark blue translucent table card with grouped property rows |
| 21 | Closing | contact channels | white closing page with thank-you mark and contact details |

## Data behavior

- Page 2 property rows are a 13-row index in the reference; overflow repeats the index-family header/footer, not a new generic detail page.
- Page 19 emits one fixed result block per sold property, with three measured blocks on the reference page; extra blocks flow only to a same-family continuation page.
- Page 20 emits unsold rows as an atomic property/deed/reason group, with a measured capacity of 10 rows.
- Marketing/board evidence arrays retain `display_order`; a missing item uses the platform-logo fallback inside the source frame and does not collapse neighboring geometry.
