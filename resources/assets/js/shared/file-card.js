function escapeHtml(value) {
    if (value === null || value === undefined) return "";
    const element = document.createElement("div");
    element.textContent = String(value);
    return element.innerHTML;
}

function formatFileSize(bytes) {
    if (!bytes || bytes <= 0 || Number.isNaN(bytes)) return "";
    const units = ["B", "KB", "MB", "GB"];
    const index = Math.min(
        units.length - 1,
        Math.floor(Math.log(bytes) / Math.log(1024)),
    );
    return `${Math.round((bytes / Math.pow(1024, index)) * 10) / 10} ${units[index]}`;
}

export function renderFileCardHtml({
    name,
    file = null,
    url = null,
    downloadUrl = null,
    metaLabel = "",
}) {
    const fileName = name || file?.name || "ملف مرفق";
    const ext = (fileName.split(".").pop() || "").toLowerCase();
    const isImage =
        Boolean(file && file.type && file.type.startsWith("image/")) ||
        ["jpg", "jpeg", "png", "webp", "gif", "svg"].includes(ext) ||
        (typeof url === "string" &&
            (url.startsWith("data:image/") ||
                url.includes("/storage/") ||
                url.includes("tasset") ||
                /\.(jpg|jpeg|png|webp|gif|svg)(\?.*)?$/i.test(url)));

    let previewUrl = url || file?._draftUrl || null;
    if (
        !previewUrl &&
        file &&
        window.URL &&
        typeof window.URL.createObjectURL === "function"
    ) {
        try {
            previewUrl = URL.createObjectURL(file);
        } catch {
            previewUrl = null;
        }
    }

    const resolvedDownloadUrl = downloadUrl || previewUrl;
    const downloadName =
        ext && ext !== "file" && !fileName.toLowerCase().endsWith(`.${ext}`)
            ? `${fileName}.${ext}`
            : fileName;

    let typeCategory = "generic";
    let iconClass = "ti-file";
    let badgeColor = "secondary";
    let typeName = "مستند";

    if (isImage) {
        typeName = "صورة";
        badgeColor = "info";
    } else if (ext === "pdf") {
        typeCategory = "pdf";
        iconClass = "ti-file-type-pdf";
        badgeColor = "danger";
        typeName = "مستند PDF";
    } else if (["doc", "docx"].includes(ext)) {
        typeCategory = "word";
        iconClass = "ti-file-description";
        badgeColor = "primary";
        typeName = "مستند Word";
    } else if (["xls", "xlsx", "csv"].includes(ext)) {
        typeCategory = "excel";
        iconClass = "ti-file-spreadsheet";
        badgeColor = "success";
        typeName = "جدول بيانات";
    } else if (["zip", "rar", "7z", "tar", "gz"].includes(ext)) {
        typeCategory = "zip";
        iconClass = "ti-file-zip";
        badgeColor = "warning";
        typeName = "أرشيف مضغوط";
    }

    const actionButtons = [];
    if (previewUrl) {
        actionButtons.push(
            `<a href="${escapeHtml(previewUrl)}" target="_blank" rel="noopener noreferrer" class="assignment-file-card__action app-file-card__action assignment-file-card__action--preview" title="معاينة الملف" aria-label="معاينة"><i class="ti ti-eye"></i></a>`,
        );
    }
    if (resolvedDownloadUrl) {
        actionButtons.push(
            `<a href="${escapeHtml(resolvedDownloadUrl)}" download="${escapeHtml(downloadName)}" class="assignment-file-card__action app-file-card__action assignment-file-card__action--download" title="تحميل الملف" aria-label="تحميل"><i class="ti ti-download"></i></a>`,
        );
    }
    const actionMarkup = actionButtons.length
        ? `<div class="assignment-file-card__actions app-file-card__actions">${actionButtons.join("")}</div>`
        : "";

    const previewMarkup =
        isImage && previewUrl
            ? `<img src="${escapeHtml(previewUrl)}" alt="${escapeHtml(fileName)}" class="assignment-file-card__img" loading="lazy">
         <span class="assignment-file-card__type-pill">${escapeHtml((ext || "IMG").toUpperCase())}</span>`
            : `<div class="assignment-file-card__doc-wrap type-${typeCategory}">
           <div class="assignment-file-card__icon-circle">
             <i class="ti ${iconClass}"></i>
           </div>
           <span class="assignment-file-card__type-pill">${escapeHtml((ext || "FILE").toUpperCase())}</span>
         </div>`;

    const sizeFormatted = file?.size
        ? formatFileSize(file.size)
        : metaLabel || typeName;

    return `
      <article class="assignment-file-card app-file-card" data-file-type="${escapeHtml(ext)}">
        <div class="assignment-file-card__preview">
          ${previewMarkup}
          ${actionMarkup}
        </div>
        <div class="assignment-file-card__info">
          <span class="assignment-file-card__name" title="${escapeHtml(fileName)}">${escapeHtml(fileName)}</span>
          <div class="assignment-file-card__meta">
            <span class="text-muted text-truncate me-1">${escapeHtml(sizeFormatted)}</span>
            <span class="badge bg-label-${badgeColor}">${escapeHtml((ext || "FILE").toUpperCase())}</span>
          </div>
        </div>
      </article>`;
}

