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

    $city.on("change", function () {
        const cityName = $(this).val();

        if (!cityName) {
            $district.empty().append(new Option("", "")).trigger("change");
            return;
        }

        if (!lookupUrl) {
            console.warn("Districts endpoint is not defined.");
            return;
        }

        $district.prop("disabled", true);

        fetch(`${lookupUrl}?city=${encodeURIComponent(cityName)}`, {
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
                const previousValue = $district.val() || "";
                $district.empty().append(new Option("", ""));

                let valuePreserved = false;
                if (data.districts && Array.isArray(data.districts)) {
                    data.districts.forEach((item) => {
                        const isSelected = item.id === previousValue;
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
                console.error("Failed to fetch districts:", error);
                $district.prop("disabled", false);
            });
    });
}
