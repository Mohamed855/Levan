# Auction report data binding matrix

All keys below are derived from `AuctionReportDataBuilder::build()` and remain tenant-scoped. Template content is escaped in Blade; images are data URIs generated only from tenant storage.

| Reference region | Snapshot key | Fallback / rule |
| --- | --- | --- |
| Auction name | `auction.name` | required |
| Project name | `assignment.name` | `-` when no assignment |
| Project/assignment number | `assignment.number` | `-` |
| Manager | `auction.project_manager_name` | `-` |
| City / category / type | `auction.city`, `auction.category`, `auction.type` | `-` per field |
| Start and end date/time | `auction.start_date`, `auction.start_time`, `auction.end_date`, `auction.end_time` | formatter preserves absent values as `-` |
| Cover / report image | `auction.cover_image`, `auction.report_image` | platform-logo fallback, preserve source frame/crop |
| Property index | `properties[]` | stable property number order; continuation when capacity is exceeded |
| Property fields | `properties[].number`, `.type`, `.deed_number`, `.board`, `.area` | do not invent a board dimension or deed value |
| Result fields | `properties[].result_status`, `.opening_price`, `.sale_price`, `.participants_count`, `.bids_count`, `.unsold_reason` | `0` differs from unavailable; use `-` only when value is unavailable |
| Result totals | `totals.*` | derived solely from the same property snapshot |
| Marketing metrics | `marketing.campaigns[]` | missing campaign section remains a deliberate empty template state |
| Marketing evidence | `evidence.marketing_evidence[]` | ordered by attachment `display_order` |
| Board evidence | `evidence.board_before[]`, `evidence.board_after[]` | ordered by attachment `display_order` |
| Broker/contract evidence | classified auction attachments | requires explicit `report_section`; never infer from file name |
| Closing contacts | `marketing.contacts.phone`, `.unified_phone`, `.social` | `-`; no reference contact value is used |

Evidence channel keys are `brochure`, `platform`, `video`, `x`, `whatsapp`, `social`, `brokers`, `marketing_evidence`, `board_before`, and `board_after`. `design_asset` is excluded from business evidence. Arrays are normalized to lists before pagination. Haram combines marketing-channel evidence into its marketing frames; Asir uses channel-specific pages. Top campaign cards preserve canonical channel placement and also display unknown/duplicate channel names and their recorded metrics.

PDF evidence is converted into one PNG per document page before pagination, preserving the attachment's position and page order. Document pages use `contain`; ordinary photographs use `cover`. A corrupt/unreadable PDF fails the generation visibly rather than becoming a fallback logo. Temporary conversion files are private, uniquely named and removed on success or failure.

Text uses the original font size. Long text wraps; rows exceeding the measured slot get an expanded continuation and retain record order. Scalar text starts at its original first baseline; table text is vertically centered. Continuations retain the contextual report landscape and do not repeat paginated evidence.
