"use strict";

import flatpickr from "flatpickr";
import { Arabic } from "flatpickr/dist/esm/l10n/ar";

try {
    if (typeof window !== "undefined") {
        window.flatpickr = window.flatpickr || flatpickr;
    }
} catch (_) {}

const SYSTEM_DATE_FORMAT = "d-m-Y";
const STORAGE_DATE_FORMAT = "Y-m-d";
const STORAGE_DATETIME_FORMAT = "Y-m-d\\TH:i";
const SYSTEM_DATETIME_ALT_FORMAT = "d-m-Y h:i K";

function pad2(num) {
    return String(num).padStart(2, "0");
}

function parseDateParts(value) {
    if (value === null || value === undefined || value === "") {
        return null;
    }

    if (value instanceof Date) {
        if (Number.isNaN(value.getTime())) return null;
        return {
            year: String(value.getFullYear()),
            month: pad2(value.getMonth() + 1),
            day: pad2(value.getDate()),
            hours: value.getHours(),
            minutes: value.getMinutes(),
        };
    }

    const str = String(value).trim();
    if (!str || str === "-") {
        return null;
    }

    const dmyMatch = str.match(
        /^(\d{2})[-\/](\d{2})[-\/](\d{4})(?:\s+(\d{2}):(\d{2})(?::\d{2})?)?$/
    );
    if (dmyMatch) {
        return {
            day: dmyMatch[1],
            month: dmyMatch[2],
            year: dmyMatch[3],
            hours: dmyMatch[4] !== undefined ? parseInt(dmyMatch[4], 10) : 0,
            minutes: dmyMatch[5] !== undefined ? parseInt(dmyMatch[5], 10) : 0,
        };
    }

    const ymdMatch = str.match(
        /^(\d{4})[-\/](\d{2})[-\/](\d{2})(?:[T\s](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?)?$/
    );
    if (ymdMatch) {
        return {
            year: ymdMatch[1],
            month: ymdMatch[2],
            day: ymdMatch[3],
            hours: ymdMatch[4] !== undefined ? parseInt(ymdMatch[4], 10) : 0,
            minutes: ymdMatch[5] !== undefined ? parseInt(ymdMatch[5], 10) : 0,
        };
    }

    const parsed = new Date(str);
    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return {
        year: String(parsed.getFullYear()),
        month: pad2(parsed.getMonth() + 1),
        day: pad2(parsed.getDate()),
        hours: parsed.getHours(),
        minutes: parsed.getMinutes(),
    };
}

export function formatSystemDate(value, fallback = "-") {
    const parts = parseDateParts(value);
    if (!parts) {
        return fallback;
    }

    return `${parts.day}-${parts.month}-${parts.year}`;
}

export function formatSystemDateTime(value, fallback = "-") {
    const parts = parseDateParts(value);
    if (!parts) {
        return fallback;
    }

    const meridiem = parts.hours >= 12 ? "م" : "ص";
    const hour12 = parts.hours % 12 || 12;

    return `${parts.day}-${parts.month}-${parts.year} ${pad2(hour12)}:${pad2(parts.minutes)} ${meridiem}`;
}

function normalizeToStorageFormat(value, isDateTime = false) {
    const parts = parseDateParts(value);
    if (!parts) {
        return "";
    }

    const base = `${parts.year}-${parts.month}-${parts.day}`;
    if (isDateTime) {
        return `${base}T${pad2(parts.hours)}:${pad2(parts.minutes)}`;
    }

    return base;
}

const nativeInputValueDescriptor = Object.getOwnPropertyDescriptor(
    HTMLInputElement.prototype,
    "value"
);

const DEFAULT_DATE_PLACEHOLDER = "اختر التاريخ";
const DEFAULT_DATETIME_PLACEHOLDER = "اختر التاريخ والوقت";

function resolveDatePlaceholder(el, isDateTime) {
    const existing = (el.getAttribute("placeholder") || "").trim();
    if (!existing) {
        return isDateTime ? DEFAULT_DATETIME_PLACEHOLDER : DEFAULT_DATE_PLACEHOLDER;
    }

    return existing;
}

function buildAltInputClassName(el) {
    const allowedUtilityClasses = ["form-control-sm", "form-control-lg", "mb-0"];
    const classes = ["form-control", "input"];

    allowedUtilityClasses.forEach((cls) => {
        if (el.classList.contains(cls)) {
            classes.push(cls);
        }
    });

    if (!el.hasAttribute("readonly")) {
        classes.push("flatpickr-input");
    }

    return classes.join(" ");
}

function syncAltInputDirection(instance) {
    if (!instance || !instance.altInput) return;

    const hasValue = Boolean(instance.altInput.value);
    instance.altInput.setAttribute("dir", hasValue ? "ltr" : "rtl");
    instance.altInput.style.textAlign = "right";
}

function syncReadonlyVisualState(el, fp) {
    if (!fp || !fp.altInput) return;
    const isExplicitReadonly = el.hasAttribute("readonly");
    const isDisabled = el.disabled;

    // Never allow manual typing; only toggle open/visual state for readonly/disabled.
    fp.altInput.readOnly = true;
    fp.altInput.setAttribute("readonly", "readonly");
    fp.altInput.disabled = isDisabled;

    if (isExplicitReadonly && !isDisabled) {
        fp.altInput.classList.remove("flatpickr-input");
        fp.altInput.classList.add("bg-light");
        fp.altInput.style.cursor = "not-allowed";
    } else if (isDisabled) {
        fp.altInput.classList.add("flatpickr-input");
        fp.altInput.classList.remove("bg-light");
        fp.altInput.style.cursor = "not-allowed";
    } else {
        fp.altInput.classList.add("flatpickr-input");
        fp.altInput.classList.remove("bg-light");
        fp.altInput.style.cursor = "pointer";
    }

    fp.set("clickOpens", !isDisabled && !isExplicitReadonly);
    if (fp.config) {
        fp.config.allowInput = false;
    }
}

function attachDatePicker(el) {
    if (
        !(el instanceof HTMLInputElement) ||
        el._flatpickr ||
        el.classList.contains("flatpickr-input") ||
        el.dataset.datePickerSkip !== undefined ||
        el.dataset.flatpickrSkip !== undefined
    ) {
        return;
    }

    const originalType = el.getAttribute("type");
    if (originalType !== "date" && originalType !== "datetime-local") {
        return;
    }

    const isDateTime = originalType === "datetime-local";
    const storageFormat = isDateTime
        ? STORAGE_DATETIME_FORMAT
        : STORAGE_DATE_FORMAT;
    const altFormat = isDateTime
        ? SYSTEM_DATETIME_ALT_FORMAT
        : SYSTEM_DATE_FORMAT;

    el.dataset.datePicker = isDateTime ? "datetime" : "date";

    const initialStorageVal = normalizeToStorageFormat(el.value, isDateTime);
    if (el.value && initialStorageVal && el.value !== initialStorageVal) {
        nativeInputValueDescriptor.set.call(el, initialStorageVal);
    }

    const wasExplicitReadonly = el.hasAttribute("readonly");
    const isInteractive = !el.disabled && !wasExplicitReadonly;
    const resolvedPlaceholder = resolveDatePlaceholder(el, isDateTime);
    if ((el.getAttribute("placeholder") || "").trim() !== resolvedPlaceholder) {
        el.setAttribute("placeholder", resolvedPlaceholder);
    }

    let internalSync = false;

    const minAttr = el.getAttribute("min");
    const maxAttr = el.getAttribute("max");

    const fp = flatpickr(el, {
        locale: Arabic,
        dateFormat: storageFormat,
        altInput: true,
        altInputClass: buildAltInputClassName(el),
        altFormat: altFormat,
        enableTime: isDateTime,
        time_24hr: false,
        allowInput: false,
        clickOpens: isInteractive,
        disableMobile: true,
        minDate: minAttr ? normalizeToStorageFormat(minAttr, isDateTime) : undefined,
        maxDate: maxAttr ? normalizeToStorageFormat(maxAttr, isDateTime) : undefined,
        defaultDate: initialStorageVal || undefined,
        onReady(_selectedDates, _dateStr, instance) {
            if (!instance.altInput) return;

            instance.altInput.dataset.datatableFilter = "false";
            instance.altInput.dataset.validationSkip = "true";
            instance.altInput.readOnly = true;
            instance.altInput.setAttribute("readonly", "readonly");
            instance.altInput.placeholder = resolvedPlaceholder;
            syncAltInputDirection(instance);

            const originalClear = instance.clear.bind(instance);
            instance.clear = (emitChangeEvent = true, toInitial = false) => {
                originalClear(emitChangeEvent, toInitial);
                syncAltInputDirection(instance);
            };

            instance.altInput.addEventListener("keydown", (event) => {
                if (el.disabled || el.hasAttribute("readonly")) return;

                if (event.key === "Backspace" || event.key === "Delete") {
                    event.preventDefault();
                    if (instance.altInput.value || el.value) {
                        instance.clear(true);
                    }
                    return;
                }

                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    instance.open();
                }
            });
            if (el.classList.contains("is-invalid")) {
                instance.altInput.classList.add("is-invalid");
            }
            if (el.classList.contains("is-valid")) {
                instance.altInput.classList.add("is-valid");
            }
            if (el.getAttribute("aria-describedby")) {
                instance.altInput.setAttribute(
                    "aria-describedby",
                    el.getAttribute("aria-describedby")
                );
            }

            syncReadonlyVisualState(el, instance);

            instance.altInput.addEventListener("focus", () => {
                el.dispatchEvent(new Event("focus", { bubbles: false }));
            });

            instance.altInput.addEventListener("input", () => {
                if (instance.altInput.value.trim() === "") {
                    instance.clear(true);
                }
            });

            instance.altInput.addEventListener("blur", () => {
                el.dispatchEvent(new Event("blur", { bubbles: false }));
            });
        },
        onValueUpdate(_selectedDates, _dateStr, instance) {
            syncAltInputDirection(instance);
        },
        onChange(_selectedDates, _dateStr, instance) {
            syncAltInputDirection(instance);
            if (internalSync) return;
            el.dispatchEvent(new Event("input", { bubbles: true }));
        },
        onClose() {
            el.dispatchEvent(new Event("blur", { bubbles: false }));
        },
    });

    if (fp && typeof fp.clear === "function") {
        const originalClear = fp.clear.bind(fp);
        fp.clear = function (...args) {
            el._clearingFp = true;
            try {
                return originalClear(...args);
            } finally {
                el._clearingFp = false;
            }
        };
    }

    if (nativeInputValueDescriptor) {
        Object.defineProperty(el, "value", {
            configurable: true,
            enumerable: true,
            get() {
                return nativeInputValueDescriptor.get.call(this);
            },
            set(newVal) {
                const normalized = normalizeToStorageFormat(newVal, isDateTime);
                const finalVal =
                    normalized || (newVal === null || newVal === undefined ? "" : String(newVal));

                nativeInputValueDescriptor.set.call(this, finalVal);

                if (internalSync || this._clearingFp || !this._flatpickr) {
                    return;
                }

                const currentFpVal = this._flatpickr.selectedDates.length
                    ? this._flatpickr.formatDate(
                          this._flatpickr.selectedDates[0],
                          storageFormat
                      )
                    : "";

                if (finalVal !== currentFpVal) {
                    internalSync = true;
                    try {
                        this._flatpickr.setDate(finalVal || null, false);
                    } finally {
                        internalSync = false;
                    }
                }
            },
        });
    }

    const attrObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.attributeName === "min") {
                const min = el.getAttribute("min");
                fp.set(
                    "minDate",
                    min ? normalizeToStorageFormat(min, isDateTime) : null
                );
            } else if (mutation.attributeName === "max") {
                const max = el.getAttribute("max");
                fp.set(
                    "maxDate",
                    max ? normalizeToStorageFormat(max, isDateTime) : null
                );
            } else if (
                mutation.attributeName === "disabled" ||
                mutation.attributeName === "readonly"
            ) {
                syncReadonlyVisualState(el, fp);
            }
        }
    });

    attrObserver.observe(el, {
        attributes: true,
        attributeFilter: ["min", "max", "disabled", "readonly"],
    });
}

export function initSystemDatePickers(root = document) {
    if (!root || typeof root.querySelectorAll !== "function") return;
    const inputs = root.querySelectorAll(
        'input[type="date"], input[type="datetime-local"]'
    );
    inputs.forEach(attachDatePicker);
}

if (typeof window !== "undefined") {
    window.formatSystemDate = formatSystemDate;
    window.formatSystemDateTime = formatSystemDateTime;
    window.initSystemDatePickers = initSystemDatePickers;

    const boot = () => {
        initSystemDatePickers(document);

        const domObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node instanceof Element) {
                        if (
                            node.matches &&
                            node.matches('input[type="date"], input[type="datetime-local"]')
                        ) {
                            attachDatePicker(node);
                        } else if (node.querySelectorAll) {
                            initSystemDatePickers(node);
                        }
                    }
                }
            }
        });

        if (document.body) {
            domObserver.observe(document.body, { childList: true, subtree: true });
        }
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", boot);
    } else {
        boot();
    }

    document.addEventListener("reset", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        window.setTimeout(() => {
            form.querySelectorAll("[data-date-picker]").forEach((el) => {
                if (el._flatpickr) {
                    const def = el.getAttribute("value") || "";
                    el._flatpickr.setDate(def || null, false);
                }
            });
        }, 0);
    });
}

