"use strict";

import * as pdfjsLib from "pdfjs-dist";
import pdfWorkerUrl from "pdfjs-dist/build/pdf.worker.mjs?url";

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("wizard-validation-form");
    if (!form) return;

    const wizardElement = document.getElementById("wizard-validation");
    const templateFileInput = document.getElementById("file");
    const definitionsInput = document.getElementById("field_definitions");
    const workspace = document.getElementById("template-placement-workspace");
    const previewBox = document.getElementById("template-preview-box");
    const requiredKeys = document.getElementById("template-required-keys");
    let selectedKey = null;
    let definitions = [];

    function templateGuide() {
        return (
            window.auctionTemplateFieldGuides?.[$("#type").val()]?.fields || []
        );
    }

    function saveDefinitions() {
        if (definitionsInput)
            definitionsInput.value = JSON.stringify(definitions);
    }

    function renderKeyButtons() {
        if (!requiredKeys) return;
        const guide = templateGuide();
        requiredKeys.innerHTML = guide
            .map((field) => {
                const defined = definitions.some(
                    (definition) => definition.key === field.name,
                );
                const status = defined
                    ? "success"
                    : field.required
                      ? "danger"
                      : "secondary";
                return `<button type="button" class="btn btn-sm btn-${status} ${selectedKey === field.name ? "active" : ""}" data-template-key="${escapeHtml(field.name)}">${escapeHtml(field.label)}${field.required ? " *" : ""}</button>`;
            })
            .join("");
        requiredKeys
            .querySelectorAll("[data-template-key]")
            .forEach((button) => {
                button.addEventListener("click", () => {
                    selectedKey = button.dataset.templateKey;
                    renderKeyButtons();
                });
            });
    }

    function renderZones() {
        previewBox
            ?.querySelectorAll(".template-placement-zone")
            .forEach((zone) => zone.remove());
        definitions.forEach((definition) => {
            const canvas = previewBox?.querySelector(
                `.template-placement-canvas[data-page="${definition.page}"]`,
            );
            if (!canvas) return;
            const zone = document.createElement("div");
            zone.className = "template-placement-zone";
            zone.dataset.key = definition.key;
            zone.style.cssText = `left:${definition.x * 100}%;top:${definition.y * 100}%;width:${definition.width * 100}%;height:${definition.height * 100}%;`;
            zone.innerHTML = `<span>${escapeHtml(definition.key)}</span><button type="button" aria-label="حذف الموضع" data-remove-template-key="${escapeHtml(definition.key)}"><i class="ti ti-x"></i></button><i class="template-placement-resize ti ti-arrows-diagonal"></i>`;
            zone.querySelector("[data-remove-template-key]").addEventListener(
                "click",
                (event) => {
                    event.stopPropagation();
                    definitions = definitions.filter(
                        (item) => item.key !== definition.key,
                    );
                    saveDefinitions();
                    renderZones();
                    renderKeyButtons();
                },
            );
            zone.addEventListener("pointerdown", (event) => {
                if (event.target.closest("button")) return;
                selectedKey = definition.key;
                renderKeyButtons();
            });
            canvas.appendChild(zone);
        });
    }

    function bindPlacementSurface(canvas, page) {
        let interaction = null;

        const point = (event) => {
            const rect = canvas.getBoundingClientRect();
            return {
                x: Math.max(
                    0,
                    Math.min(1, (event.clientX - rect.left) / rect.width),
                ),
                y: Math.max(
                    0,
                    Math.min(1, (event.clientY - rect.top) / rect.height),
                ),
            };
        };

        canvas.addEventListener("pointerdown", (event) => {
            event.preventDefault();
            const zone = event.target.closest(".template-placement-zone");
            if (zone) {
                const key = zone.dataset.key;
                const definition = definitions.find(
                    (item) => item.key === key && item.page === page,
                );
                if (!definition) return;
                selectedKey = key;
                interaction = {
                    type: event.target.closest(".template-placement-resize")
                        ? "resize"
                        : "move",
                    definition: { ...definition },
                    start: point(event),
                };
                canvas.setPointerCapture(event.pointerId);
                renderKeyButtons();
                return;
            }
            if (!selectedKey) return;
            interaction = { type: "create", start: point(event) };
            canvas.setPointerCapture(event.pointerId);
        });
        canvas.addEventListener("pointermove", (event) => {
            if (!interaction) return;
            const end = point(event);
            if (interaction.type === "create") return;
            const definition = { ...interaction.definition };
            if (interaction.type === "move") {
                definition.x = Math.max(
                    0,
                    Math.min(
                        1 - definition.width,
                        definition.x + end.x - interaction.start.x,
                    ),
                );
                definition.y = Math.max(
                    0,
                    Math.min(
                        1 - definition.height,
                        definition.y + end.y - interaction.start.y,
                    ),
                );
            } else {
                definition.width = Math.max(
                    0.02,
                    Math.min(1 - definition.x, end.x - definition.x),
                );
                definition.height = Math.max(
                    0.02,
                    Math.min(1 - definition.y, end.y - definition.y),
                );
            }
            definitions = [
                ...definitions.filter((item) => item.key !== definition.key),
                definition,
            ];
            saveDefinitions();
            renderZones();
        });
        canvas.addEventListener("pointerup", (event) => {
            if (!interaction) return;
            if (interaction.type !== "create") {
                interaction = null;
                renderKeyButtons();
                return;
            }
            if (!selectedKey) return;
            const end = point(event);
            const definition = {
                key: selectedKey,
                page,
                x: Math.min(interaction.start.x, end.x),
                y: Math.min(interaction.start.y, end.y),
                width: Math.abs(interaction.start.x - end.x),
                height: Math.abs(interaction.start.y - end.y),
            };
            interaction = null;
            if (definition.width < 0.02 || definition.height < 0.02) return;
            definitions = [
                ...definitions.filter((item) => item.key !== selectedKey),
                definition,
            ];
            saveDefinitions();
            renderZones();
            renderKeyButtons();
        });
    }

    function resetPlacementWorkspace() {
        workspace?.classList.remove("d-none");
        definitions = [];
        selectedKey = null;
        saveDefinitions();
    }

    function setupImagePlacement(file) {
        if (!workspace || !previewBox || !requiredKeys) return;
        resetPlacementWorkspace();
        const image = new Image();
        image.onload = () => {
            previewBox.innerHTML = `<div class="template-placement-canvas" data-page="1" style="aspect-ratio:${image.naturalWidth}/${image.naturalHeight};"><img src="${image.src}" draggable="false" alt="معاينة القالب"></div>`;
            bindPlacementSurface(
                previewBox.querySelector(".template-placement-canvas"),
                1,
            );
            renderKeyButtons();
        };
        image.src = URL.createObjectURL(file);
    }

    async function setupPdfPlacement(file) {
        if (!workspace || !previewBox || !requiredKeys) return;
        resetPlacementWorkspace();
        previewBox.innerHTML =
            '<div class="py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>جاري تجهيز صفحات PDF...</div>';
        const pdf = await pdfjsLib.getDocument({
            data: await file.arrayBuffer(),
        }).promise;
        previewBox.innerHTML = "";
        for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
            const page = await pdf.getPage(pageNumber);
            const viewport = page.getViewport({ scale: 1.5 });
            const canvas = document.createElement("canvas");
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const surface = document.createElement("div");
            surface.className = "template-placement-canvas mb-3";
            surface.dataset.page = String(pageNumber);
            surface.style.aspectRatio = `${viewport.width}/${viewport.height}`;
            surface.appendChild(canvas);
            previewBox.appendChild(surface);
            await page.render({
                canvasContext: canvas.getContext("2d"),
                viewport,
            }).promise;
            bindPlacementSurface(surface, pageNumber);
        }
        renderKeyButtons();
    }

    function showTemplatePreview() {
        const file = templateFileInput?.files?.[0];
        if (!file || !workspace || !previewBox) return;
        if (file.type.startsWith("image/")) return setupImagePlacement(file);
        setupPdfPlacement(file).catch(() => {
            previewBox.innerHTML =
                '<div class="alert alert-danger mb-0">تعذرت معاينة ملف PDF. يرجى اختيار ملف PDF صالح.</div>';
        });
    }

    document.addEventListener(
        "click",
        (event) => {
            const nextButton = event.target.closest(
                "[data-wizard-next], .btn-next:not(.btn-submit)",
            );
            const step = nextButton?.closest("[data-form-step]");
            const file = templateFileInput?.files?.[0];
            if (!nextButton || step?.id !== "step-files" || !file) return;
            const missing = templateGuide().filter(
                (field) =>
                    field.required &&
                    !definitions.some(
                        (definition) => definition.key === field.name,
                    ),
            );
            if (missing.length === 0) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            window.SharedFormValidation?.setFieldError(
                form,
                "field_definitions",
                "يرجى تحديد مواضع جميع الحقول الإلزامية داخل التصميم قبل المتابعة.",
            );
            window.toastr?.error(
                "يرجى تحديد مواضع جميع الحقول الإلزامية داخل التصميم قبل المتابعة.",
            );
        },
        true,
    );

    function escapeHtml(value) {
        if (value === null || value === undefined) return "";
        const element = document.createElement("div");
        element.textContent = String(value);
        return element.innerHTML;
    }

    function updateLivePreview() {
        // Name
        const nameVal = $("#name").val()?.trim();
        $("#preview-template-name").text(nameVal || "اسم القالب");
        $("#review-name").text(nameVal || "-");

        // Type
        const typeSelect = document.getElementById("type");
        const selectedTypeOption =
            typeSelect?.options[typeSelect.selectedIndex];
        const typeText = selectedTypeOption ? selectedTypeOption.text : "-";
        const typeVal = $("#type").val();
        $("#preview-template-type").text(typeText || "نوع القالب");
        $("#review-type").text(typeText || "-");

        // Description
        const descVal = $("#description").val()?.trim();
        $("#preview-template-desc").text(
            descVal || "وصف القالب وملاحظات التصميم",
        );
        $("#review-description").text(descVal || "-");

        // File
        const fileInput = document.getElementById("file");
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            $("#review-file-name").html(`
                <span class="badge bg-primary font-monospace">
                    <i class="ti ti-file me-1"></i>${escapeHtml(file.name)} (${sizeMb} MB)
                </span>
            `);
        } else {
            $("#review-file-name").text("لم يتم اختيار ملف بعد");
        }

        // Status
        const isActive = $("#is_active").is(":checked");
        if (isActive) {
            $("#preview-template-status").html(
                '<span class="badge bg-success">مفعل ومتاح للمستأجرين</span>',
            );
            $("#review-status").html(
                '<span class="badge bg-success">مفعل</span>',
            );
        } else {
            $("#preview-template-status").html(
                '<span class="badge bg-danger">معطل</span>',
            );
            $("#review-status").html(
                '<span class="badge bg-danger">معطل</span>',
            );
        }

        // Specification Guide Synchronize
        $(".spec-guide-item").addClass("d-none");
        if (typeVal === "brochure") {
            $("#spec-guide-brochure").removeClass("d-none");
            if (fileInput)
                fileInput.setAttribute("accept", ".pdf,application/pdf");
        } else if (typeVal === "social") {
            $("#spec-guide-social").removeClass("d-none");
            if (fileInput)
                fileInput.setAttribute(
                    "accept",
                    ".pdf,application/pdf,image/png,image/jpeg,image/webp",
                );
        } else if (typeVal === "billboard") {
            $("#spec-guide-billboard").removeClass("d-none");
            if (fileInput)
                fileInput.setAttribute(
                    "accept",
                    ".pdf,application/pdf,image/png,image/jpeg,image/webp",
                );
        } else {
            $("#spec-guide-default").removeClass("d-none");
            if (fileInput)
                fileInput.setAttribute(
                    "accept",
                    ".pdf,application/pdf,image/png,image/jpeg,image/webp",
                );
        }
    }

    // Input listeners
    $("#name").on("input", updateLivePreview);
    $("#type").on("change", updateLivePreview);
    $("#description").on("input", updateLivePreview);
    $("#file").on("change", () => {
        updateLivePreview();
        showTemplatePreview();
    });
    $("#is_active").on("change", updateLivePreview);
    $("#type").on("change", () => {
        if (templateFileInput?.files?.[0]) showTemplatePreview();
    });

    // Quick jump step buttons in Step 3 Review
    document.querySelectorAll("[data-edit-step]").forEach((button) => {
        button.addEventListener("click", () => {
            const target = Number(button.dataset.editStep);
            if (!target) return;
            if (wizardElement?.bsStepper) {
                wizardElement.bsStepper.to(target);
            } else if (window.stepper) {
                window.stepper.to(target);
            }
        });
    });

    // Wizard draft restored handler
    $(form).on("wizardDraftRestored", function () {
        updateLivePreview();
    });

    // Initial sync
    updateLivePreview();
});
