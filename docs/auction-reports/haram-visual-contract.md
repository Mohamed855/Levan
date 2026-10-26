# Haram outputs visual contract

## Canvas and common layers

- Canvas: `595.50 × 842.25 pt`, RTL, zero print margins.
- Permanent visual identity: Infath at upper left, Levan at upper right, navy/blue header and footer for interior pages.
- Primary measured palette from the package: `#1F3B5E`, `#214570`, `#007E9B`, `#49B584`, `#47B582`, `#A49FA5`, white.
- Covers retain the dark navy/eggplant grid, cyan/green accent lines, wide blue-to-green capsule, four lower metadata chips, and branded footer.
- Interior pages retain the pale neutral background, full-width blue header split with dark brand segment, rounded white information/table cards, and dark footer.
- The source package defines 16/90/86/72/11/9/8 text fields and 2/1/5/11/12/4/2 image regions across pages 1–7. This inventory is a regression baseline.

## Page families

| Page | Family | Dynamic content | Static design contract |
| ---: | --- | --- | --- |
| 1 | Cover | auction name, project, manager, property count, date range | dark gridded cover, logos, large centered title, accent capsule and metadata chips |
| 2 | Project and property index | project, auction, manager, start/end dates, property rows | blue split header, right-aligned section rules, three-column project card, two date cards, striped properties table |
| 3 | Auction results | narrative, sold rows, unsold rows/reasons | blue split header, white narrative card, green sold table, red unsold table |
| 4 | Digital marketing | campaign platform, copy, metrics, evidence images | blue split header, card sequence, fixed marketing image frames |
| 5 | Boards before auction | board captions and evidence images | title/section rule, fixed photo/grid frames, caption band |
| 6 | Participating brokers and contracts | broker names, contracts/evidence images | fixed broker/photo layout with shared branded header/footer |
| 7 | Closing | contact channels and auction name | minimalist dark closing page, thank-you mark and contact details |

## Data behavior

- Page 2 property rows are a table family. A continuation page repeats the page-2 table header and footer; rows are never split.
- Page 3 sold and unsold sections use separate capacities and may create a same-family continuation page independently.
- Pages 4–6 use image frames with `cover` crop and their documented visual anchor; image absence renders the platform-logo fallback inside the same frame.
- No generic property-detail page exists in this visual system.
