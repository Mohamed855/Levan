"use strict";

export function initCityDistrictSelector({
    citySelector,
    districtSelector,
    endpoint,
} = {}) {
    const $ = window.jQuery;
    if (!$) return;

    const $city = $(citySelector);
    const $district = $(districtSelector);

    if (!$city.length || !$district.length) return;

    const lookupUrl = endpoint || window.routes?.districts;
    let requestVersion = 0;

    const loadDistricts = (cityName, restoredDistrict = null) => {
        const currentRequest = ++requestVersion;

        if (!cityName) {
            $district.empty().append(new Option("", "")).trigger("change");
            return Promise.resolve();
        }

        if (!lookupUrl) {
            console.warn("Districts endpoint is not defined.");
            return Promise.resolve();
        }

        $district.prop("disabled", true);

        return fetch(`${lookupUrl}?city=${encodeURIComponent(cityName)}`, {
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (currentRequest !== requestVersion) return;

                const previousValue = String(
                    restoredDistrict === null
                        ? $district.val() || ""
                        : restoredDistrict,
                );
                $district.empty().append(new Option("", ""));

                let valuePreserved = false;
                if (data.districts && Array.isArray(data.districts)) {
                    data.districts.forEach((item) => {
                        const isSelected = String(item.id) === previousValue;
                        if (isSelected) {
                            valuePreserved = true;
                        }
                        $district.append(new Option(item.text, item.id, isSelected, isSelected));
                    });
                }

                // If user had a custom tag or current value not in returned list, retain it
                if (previousValue && !valuePreserved) {
                    $district.append(new Option(previousValue, previousValue, true, true));
                }

                $district.prop("disabled", false).trigger("change");
            })
            .catch((error) => {
                if (currentRequest !== requestVersion) return;
                console.error("Failed to fetch districts:", error);
                $district.prop("disabled", false);
            });
    };

    $city.on("change", function () {
        void loadDistricts($(this).val());
    });

    const form = document.querySelector(citySelector)?.closest("form");
    form?.addEventListener("wizardDraftRestored", (event) => {
        const districtName = $district.attr("name");
        const restoredDistrict = event.detail?.data?.[districtName];
        if (typeof restoredDistrict === "undefined") return;

        const task = loadDistricts($city.val(), restoredDistrict);
        event.detail?.waitUntil?.(task);
    });
}
