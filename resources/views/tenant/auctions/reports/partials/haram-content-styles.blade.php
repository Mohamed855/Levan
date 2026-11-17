<style>
    .haram-native-page:not(.haram-native-index) { background: var(--haram-page); }
    .haram-native-main { padding: 38pt 45.5pt 0 46pt; }
    .haram-native-main > [data-native-section] + [data-native-section] { margin-block-start: 18pt; }
    .haram-native-main h2 { overflow-wrap: anywhere; }
    .haram-native-main p { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; }
    .haram-native-main figure { margin: 0; }
    .haram-native-main img { display: block; max-width: 100%; }
    .haram-native-main a { color: #218fb0; overflow-wrap: anywhere; text-decoration: underline; text-underline-offset: 2pt; }
    .haram-results-main { padding-block-start: 47pt; }
    .haram-narrative-card { margin-block-start: 10pt; min-height: 59.5pt; padding: 12pt 8pt; background: white; border-radius: 5pt; border-inline-start: 1.5pt solid #299d94; font: 9pt/1.5 NotoReportBold, sans-serif; color: #717171; }
    .haram-result-frame { position: relative; margin-block-start: 8pt; border-radius: 5pt; overflow: hidden; background: white; }
    .haram-result-frame::before { content: ''; position: absolute; inset-block-start: 0; inset-inline: 0; height: 28.8pt; background: linear-gradient(90deg, #196243, #1b9874); }
    .haram-result-frame--unsold::before { background: #ba3e30; }
    .haram-result-table { position: relative; }
    .haram-result-table th { height: 28.8pt; padding: 7pt 8pt; color: white; font: 9pt/1.4 NotoReportBold, sans-serif; }
    .haram-result-table td { height: 28.8pt; padding: 7pt 8pt; font: 7.998pt/1.4 MontserratReport, NotoReport, sans-serif; }
    .haram-result-table td:nth-child(2) { font-family: NotoReportBold, sans-serif; }
    .haram-result-frame--unsold td:last-child { font-family: NotoReport, sans-serif; }
    .haram-result-table td:first-child { font-family: ArimoReportBold, sans-serif; }
    .haram-result-frame--sold tbody tr:nth-child(even) { background: #e8f5ee; }
    .haram-result-frame--unsold td { height: 31.33pt; }
    .haram-result-frame--unsold tbody tr:nth-child(even) { background: #ba3e30; color: white; }
    .haram-results-main > .haram-result-section--unsold:not(:first-child) { margin-block-start: 36pt; }
    .haram-marketing-main { padding: 19pt 79.5pt 0 102pt; }
    .haram-marketing-main h2 { font-size: 9.412pt; min-height: 16pt; padding-inline-start: 6pt; padding-block-end: 1pt; border-inline-start-width: 2pt; }
    .haram-marketing-main > [data-native-section] + [data-native-section] { margin-block-start: 14.5pt; }
    .haram-summary-grid { display: flex; flex-direction: row-reverse; gap: 10pt; margin-block-start: 9pt; }
    .haram-summary-card { position: relative; display: flex; flex-direction: column; justify-content: space-between; align-items: center; flex: 1; min-width: 0; min-height: 59.5pt; padding: 8pt 7pt; background: white; border-radius: 4pt; text-align: center; font-size: 6.15pt; }
    .haram-summary-card::after { content: ''; position: absolute; inset-inline: 1pt; inset-block-end: 0; height: 1pt; background: linear-gradient(90deg, #239a91, #307aab); }
    .haram-summary-card strong { display: block; font: 11.325pt/1.2 NotoReportBold, sans-serif; margin-block: 3pt; overflow-wrap: anywhere; }
    .haram-summary-platform, .haram-summary-card > span { font-family: NotoReportBold, sans-serif; }
    .haram-platform-icon { display: inline-block !important; vertical-align: middle; width: 7pt; height: 7pt; font-family: Arial, sans-serif; margin-inline-end: 2pt; }
    .haram-marketing-image-frame { display: flex; justify-content: center; min-height: 142.33pt; background: #efefef; border-radius: 4pt; overflow: hidden; margin-block-start: 8pt !important; }
    .haram-marketing-image-frame img { width: 140.15pt; height: 142.33pt; }
    .haram-native-link-card { display: flex; align-items: center; gap: 10pt; min-height: 34.5pt; margin-block-start: 9pt; padding: 7pt 14pt; border-radius: 4pt; background: white; border-inline-start: 1.5pt solid #299d94; font-size: 9.412pt; }
    .haram-native-link-card strong { flex: 0 0 auto; font-family: NotoReportBold, sans-serif; }
    .haram-native-link-card a { flex: 1; text-align: right; font-size: 8pt; text-decoration: none; border-block-end: .4pt solid #79bddd; }
    .haram-tiktok-panel { min-height: 104pt; margin-block-start: 8pt; padding: 7pt 16pt; border-radius: 4pt; background: #f9f9f9; color: #333; }
    .haram-tiktok-panel h3 { margin: 0 68pt 11pt 0; font: 7.6pt/1.4 NotoReportBold, sans-serif; }
    .haram-tiktok-grid { display: flex; flex-direction: row-reverse; justify-content: center; gap: 4pt; }
    .haram-tiktok-tile { display: flex; flex-direction: column; align-items: flex-start; gap: 7pt; min-width: 0; width: 77pt; min-height: 43.8pt; padding: 4pt 6pt; border-radius: 1pt; background: #efefef; font-size: 4.307pt; }
    .haram-tiktok-tile strong { align-self: center; font: 7.06pt/1.2 NotoReportBold, sans-serif; }
    .haram-tiktok-tile > span:last-child:not(:first-child) { align-self: center; font-size: 5.357pt; }
    .haram-tiktok-tile--views { background: #22a3c1; color: white; }
    .haram-tiktok-period { margin-block-start: 8pt; padding-inline-start: 68pt; font-size: 5.36pt; }
    .haram-tiktok-period span { font-size: 4.8pt; color: #777; }
    .haram-extra-metrics { display: flex; flex-wrap: wrap; gap: 8pt 14pt; padding: 10pt; margin: 8pt 0 0; border-radius: 4pt; background: white; font-size: 8pt; }
    .haram-extra-metrics > div { display: flex; align-items: baseline; gap: 5pt; min-width: 0; }
    .haram-extra-metrics dt { overflow-wrap: anywhere; }
    .haram-extra-metrics dd { margin: 0; overflow-wrap: anywhere; }
    .haram-snapchat-grid { display: flex; gap: 14pt; margin-block-start: 8pt; }
    .haram-snapchat-images { width: 202pt; flex: 0 0 202pt; }
    .haram-snapchat-image-frame { display: flex; justify-content: center; min-height: 175.57pt; border-radius: 4pt; background: #efefef; overflow: hidden; }
    [data-native-marketing-items] > .haram-snapchat-image-frame { margin-block-start: 8pt; }
    .haram-snapchat-image-frame img { width: 95.43pt; height: 175.57pt; }
    .haram-snapchat-details { flex: 1; min-width: 0; }
    .haram-snapchat-card { display: flex; flex-direction: column; gap: 4pt; min-height: 48pt; margin-block-end: 6pt; padding: 11pt 14pt; border-radius: 4pt; background: white; border-inline-start: 2pt solid #299d94; color: #333; font: 6.4pt/1.4 NotoReportBold, sans-serif; }
    .haram-snapchat-card > div { display: flex; justify-content: flex-start; align-items: baseline; gap: 13pt; color: #777; font-size: 6.15pt; }
    .haram-snapchat-card strong { font-family: MontserratReport, NotoReport, sans-serif; font-weight: normal; }
    .haram-snapchat-card a { align-self: flex-start; font-size: 6.4pt; }
    .haram-boards-main { padding: 35pt 45.24pt 0 45pt; }
    .haram-boards-main h2 { min-height: 18.5pt; }
    .haram-native-board-grid { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 8.2pt 10.66pt; margin-block-start: 11.78pt; }
    .haram-native-board-frame { width: calc((100% - 10.66pt) / 2); height: 124.55pt; }
    .haram-native-board-frame img { width: 100%; height: 100%; }
    .haram-native-board-frame--small { width: 162.06pt; height: 103.54pt; margin-block-start: 3pt !important; }
    .haram-boards-main > [data-native-section] + [data-native-section] { margin-block-start: 17.5pt; }
    .haram-brokers-main { padding-block-start: 39pt; }
    .haram-brokers-grid { display: grid; grid-template-columns: repeat(2, 175.75pt); justify-content: space-between; gap: 92pt 24pt; margin-block-start: 15pt; }
    .haram-broker-frame img { width: 175.75pt; height: 217.62pt; }
    .haram-broker-frame figcaption { min-height: 18pt; margin-block-end: 8pt; overflow-wrap: anywhere; font-family: NotoReportBold, sans-serif; }
    .haram-broker-name { padding: 12pt; background: white; border-radius: 4pt; margin-block-start: 12pt !important; }
    .haram-gallery-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 25pt 25pt; margin-block-start: 20pt; }
    .haram-gallery-frame img { width: 100%; height: 175pt; }
    .haram-gallery-frame figcaption { margin-block-start: 7pt; overflow-wrap: anywhere; text-align: center; font-size: 9pt; }
</style>
