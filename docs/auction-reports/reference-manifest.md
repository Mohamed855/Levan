# Auction report visual reference manifest

## Acceptance sources

Reference directory is intentionally external to the repository. Local comparison tooling resolves it from `AUCTION_REPORT_REFERENCE_DIR`; it must never hard-code a user-profile path in production code.

| Document | Source file | SHA-256 | Size | Pages | Rendered MediaBox |
| --- | --- | --- | ---: | ---: | --- |
| Outputs / Haram | `haram-print.pdf` | `E3CE21E270035E308E9F47D919F7B003F415A185B0EB5128AF21025F50ACCE7F` | 7,609,083 bytes | 7 | 595.92 × 841.92 pt |
| Final report / Asir | `asir-print.pdf` | `88624E3BB36950FB9ED6E759471FAE9E58096C39C5727B7E6E93B59BF73385D3` | 10,526,264 bytes | 21 | 594.96 × 841.92 pt |

Both references report `Skia/PDF m151` and PDF 1.4. They are printed package outputs, so the expected small Chromium rounding is documented separately from visual fidelity.

## Reference package integrity

| File | SHA-256 |
| --- | --- |
| `haram.html` | `CBE05B0DAE76C5CCCDF162BF73FAB656B9D79A0773EB9AC4BEC5174E557E1FD5` |
| `asir.html` | `D2C024B60CE31688D14710BC6E53B6DFB634B907A9FE34928D853928FC04D160` |
| `haram-data.json` | `B5B35EBA47E77C67E7267940E03BB2A1A6638AFA06A3239A60EFA2CD10568FE6` |
| `asir-data.json` | `4DD75B53FA68B97221FEF936E30FBBA059B2A92B435F387E6A2EEE3145E2039A` |
| `IBMPlexSansArabic-OFL.txt` | `7E6B2818EDBD8F6A01AE80641CC8F16A51080D08FB4E532BE3A0B6F74ADB07DA` |
| `NotoSansArabic-OFL.txt` | `07FC70BFEB985CC1A87A8587D0A0C80BAB11C86C9DC3FD95B6F0CB332F983E96` |

Integrity check: **PASS** on 26 October 2026.

## Protected acceptance records

The acceptance tenant is `test` / database `etmam_levan_test`.

| Auction ID | Auction name | Properties | Attachments | Existing report generations |
| ---: | --- | ---: | ---: | ---: |
| 5 | جوار الحرم | 11 | 11 | 1 |
| 6 | واجهة عسير | 13 | 51 | 3 |

These records are read-only acceptance inputs. No `migrate:fresh`, `db:wipe`, destructive seeder, data replacement, attachment replacement, or destructive database command is allowed during report-fidelity work.

## Runtime boundary

The production flow remains `AuctionReportDataBuilder → AuctionReportRenderer → BrowserDocumentRenderer → tenant_private`.

The HTML and JSON package files are analysis-only artifacts. They must not be included, parsed, or executed by the production report renderer.
