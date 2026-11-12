const coordinatePair = /^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/;
const riyadhCenter = { lat: 24.7136, lng: 46.6753 };
const defaultZoom = 12;
const mapsHosts = new Set([
    "google.com",
    "www.google.com",
    "maps.google.com",
    "google.com.sa",
    "www.google.com.sa",
    "google.com.eg",
    "www.google.com.eg",
    "google.co.uk",
    "www.google.co.uk",
]);

function coordinates(lat, lng) {
    if (lat === "" || lng === "" || lat == null || lng == null) return null;
    const position = { lat: Number(lat), lng: Number(lng) };
    return Number.isFinite(position.lat) &&
        Number.isFinite(position.lng) &&
        Math.abs(position.lat) <= 90 &&
        Math.abs(position.lng) <= 180
        ? position
        : null;
}

export function parseLocationQuery(value) {
    const text = String(value || "").trim();
    if (!text) return null;
    const pair = text.replace(/^loc:/i, "").match(coordinatePair);
    if (pair) {
        const position = coordinates(pair[1], pair[2]);
        if (!position) throw new Error("إحداثيات الموقع غير صالحة.");
        return { coordinates: position };
    }
    if (!/^https?:\/\//i.test(text)) return { text };
    const url = new URL(text);
    if (url.username || url.password || url.port)
        throw new Error("رابط خرائط جوجل غير صالح.");
    if (
        url.hostname === "maps.app.goo.gl" ||
        (url.hostname === "goo.gl" && url.pathname.startsWith("/maps/"))
    ) {
        return { shortUrl: url.href };
    }
    if (
        !mapsHosts.has(url.hostname) ||
        (url.hostname !== "maps.google.com" &&
            !/^\/maps(?:\/|$)/.test(url.pathname))
    ) {
        throw new Error("استخدم رابط موقع من خرائط جوجل.");
    }
    const decoded = decodeURIComponent(url.href);
    // A shared place link contains both the camera center and the actual pin.
    const pin = decoded.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/);
    if (pin) return parseLocationQuery(`${pin[1]},${pin[2]}`);
    const placeId =
        url.searchParams.get("query_place_id") ||
        url.searchParams.get("place_id");
    if (placeId) return { placeId };
    const query =
        url.searchParams.get("query") ||
        url.searchParams.get("q") ||
        url.searchParams.get("ll");
    if (query?.startsWith("place_id:")) return { placeId: query.slice(9) };
    if (query && !/^https?:/i.test(query)) return parseLocationQuery(query);
    const place = decodeURIComponent(url.pathname).match(
        /\/place\/([^/]+)/,
    )?.[1];
    if (place) return parseLocationQuery(place.replaceAll("+", " "));
    const camera = decoded.match(/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
    if (camera) return parseLocationQuery(`${camera[1]},${camera[2]}`);
    throw new Error(
        "تعذر قراءة الموقع من الرابط. ابحث باسم المكان أو حدد النقطة على الخريطة.",
    );
}

let mapsPromise;
async function loadLeaflet() {
    const [leaflet] = await Promise.all([
        import("leaflet"),
        import("leaflet/dist/leaflet.css"),
    ]);
    return leaflet;
}

async function loadGoogleMaps(apiKey) {
    if (!apiKey) throw new Error("مفتاح خرائط جوجل غير متاح.");
    if (!globalThis.google?.maps?.importLibrary) {
        mapsPromise ??= new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.nonce = document.querySelector("script[nonce]")?.nonce || "";
            const callback = "__propertyMapsReady";
            const timeout = setTimeout(() => fail(), 20000);
            function fail() {
                clearTimeout(timeout);
                script.remove();
                delete window[callback];
                mapsPromise = null;
                reject(
                    new Error(
                        "تعذر تحميل خرائط جوجل. تحقق من الاتصال وإعدادات المفتاح ثم أعد المحاولة.",
                    ),
                );
            }
            window[callback] = () => {
                clearTimeout(timeout);
                delete window[callback];
                resolve();
            };
            script.onerror = fail;
            script.src = `https://maps.googleapis.com/maps/api/js?${new URLSearchParams({ key: apiKey, loading: "async", callback, v: "weekly", language: "ar", region: "SA" })}`;
            script.async = true;
            document.head.appendChild(script);
        });
        await mapsPromise;
    }
    const [{ Map }, { AdvancedMarkerElement }] = await Promise.all([
        google.maps.importLibrary("maps"),
        google.maps.importLibrary("marker"),
    ]);
    return { Map, AdvancedMarkerElement };
}

export async function initPropertyLocationMap(
    form,
    { loadMaps = loadGoogleMaps, loadFallbackMaps = loadLeaflet } = {},
) {
    const container = form.querySelector("#property-location-map");
    if (!container) return;
    const latitude = form.elements.namedItem("latitude");
    const longitude = form.elements.namedItem("longitude");
    const mapsUrl = form.elements.namedItem("google_maps_url");
    const search = form.querySelector("#property-location-search");
    const searchButton = form.querySelector("#property-location-search-button");
    const results = form.querySelector("#property-location-results");
    const status = form.querySelector("#property-location-status");
    const retry = form.querySelector("#property-location-retry");
    const fallback = form.querySelector("#property-location-fallback");
    let map;
    let marker;
    let backupMap;
    let backupMarker;
    let mapFailed = false;
    let requestVersion = 0;

    function message(text, error = false) {
        status.textContent = text;
        status.className = `small mb-0 ${error ? "text-danger" : "text-muted"}`;
    }

    function selectPosition(position, pan = true) {
        const point = coordinates(position?.lat, position?.lng);
        if (!point || (!map && !backupMap)) return;
        requestVersion++;
        latitude.value = point.lat.toFixed(7);
        longitude.value = point.lng.toFixed(7);
        mapsUrl.value = `https://www.google.com/maps/search/?api=1&query=${latitude.value},${longitude.value}`;
        if (map) {
            marker.position = point;
            marker.map = map;
        } else {
            backupMarker.setLatLng(point).addTo(backupMap);
        }
        if (pan) {
            const activeMap = map || backupMap;
            activeMap.panTo(point);
            activeMap.setZoom(18);
        }
        message(`الموقع المحدد: ${latitude.value}, ${longitude.value}`);
        mapsUrl.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function savedPosition() {
        let point = coordinates(latitude.value, longitude.value);
        if (!point && mapsUrl.value) {
            try {
                point = parseLocationQuery(mapsUrl.value)?.coordinates;
            } catch (_) {
                /* Keep the saved URL available for search. */
            }
        }
        return point;
    }

    function showFallback() {
        const point = savedPosition() || riyadhCenter;
        if (fallback) {
            fallback.src = `https://www.google.com/maps?${new URLSearchParams({ q: `${point.lat},${point.lng}`, z: String(defaultZoom), output: "embed", hl: "ar" })}`;
            fallback.hidden = false;
            container.hidden = true;
        }
        message(
            "تحديد الموقع غير متاح حاليًا. تُعرض خريطة للعرض فقط، ويمكنك إعادة المحاولة.",
            true,
        );
    }

    function restoreLocation() {
        requestVersion++;
        results.replaceChildren?.();
        search.value = mapsUrl.value || "";
        if (!map && !backupMap) {
            if (mapFailed) showFallback();
            return;
        }
        const point = savedPosition();
        if (point) selectPosition(point);
        else {
            if (map) marker.map = null;
            else backupMarker.remove();
            const activeMap = map || backupMap;
            activeMap.panTo(riyadhCenter);
            activeMap.setZoom(defaultZoom);
            message("ابحث عن الموقع أو اضغط على الخريطة لتحديده.");
        }
    }

    async function initialize() {
        backupMap?.remove();
        backupMap = null;
        backupMarker = null;
        retry.hidden = true;
        searchButton.disabled = true;
        message("جارٍ تحميل الخريطة...");
        try {
            const { Map, AdvancedMarkerElement } = await loadMaps(
                container.dataset.apiKey,
            );
            container.hidden = false;
            map = new Map(container, {
                center: riyadhCenter,
                zoom: defaultZoom,
                mapId: container.dataset.mapId || "DEMO_MAP_ID",
                mapTypeId: "hybrid",
                streetViewControl: false,
                fullscreenControl: true,
            });
            marker = new AdvancedMarkerElement({
                gmpDraggable: true,
                title: "موقع العقار — اسحب لتعديل الموقع",
            });
            map.addListener("click", (event) => {
                if (!event.latLng) return;
                event.stop?.();
                selectPosition(
                    event.latLng.toJSON?.() || {
                        lat: event.latLng.lat(),
                        lng: event.latLng.lng(),
                    },
                );
            });
            marker.addEventListener("gmp-dragend", () => {
                const point = marker.position?.toJSON?.() || marker.position;
                selectPosition(point, false);
            });
            mapFailed = false;
            if (fallback) {
                fallback.hidden = true;
                fallback.removeAttribute?.("src");
            }
            restoreLocation();
        } catch (error) {
            if (marker) marker.map = null;
            marker = null;
            map = null;
            mapFailed = true;
            try {
                const leaflet = await loadFallbackMaps();
                container.hidden = false;
                container.replaceChildren();
                backupMap = leaflet
                    .map(container)
                    .setView(riyadhCenter, defaultZoom);
                leaflet
                    .tileLayer(
                        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
                        {
                            attribution:
                                "Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community",
                            maxZoom: 19,
                        },
                    )
                    .addTo(backupMap);
                backupMarker = leaflet.marker(riyadhCenter, {
                    draggable: true,
                    title: "موقع العقار — اسحب لتعديل الموقع",
                    icon: leaflet.divIcon({
                        className: "bg-transparent border-0 text-primary fs-2",
                        html: '<i class="ti ti-map-pin-filled" aria-hidden="true"></i>',
                        iconSize: [28, 36],
                        iconAnchor: [14, 36],
                    }),
                });
                backupMap.on("click", (event) => selectPosition(event.latlng));
                backupMarker.on("dragend", () =>
                    selectPosition(backupMarker.getLatLng(), false),
                );
                if (fallback) {
                    fallback.hidden = true;
                    fallback.removeAttribute?.("src");
                }
                restoreLocation();
                message(
                    "تعذر تحميل خرائط جوجل. يمكنك تحديد الموقع على الخريطة البديلة أو لصق إحداثيات أو رابط موقع.",
                );
            } catch (_) {
                backupMap?.remove();
                backupMap = null;
                backupMarker = null;
                showFallback();
            }
            retry.hidden = false;
        } finally {
            searchButton.disabled = !map && !backupMap;
        }
    }

    async function searchLocation() {
        let parsed;
        try {
            parsed = parseLocationQuery(search.value);
        } catch (error) {
            message(error.message, true);
            return;
        }
        if (!parsed || (!map && !backupMap)) return;
        const version = ++requestVersion;
        searchButton.disabled = true;
        results.replaceChildren();
        message("جارٍ البحث عن الموقع...");
        try {
            if (parsed.shortUrl) {
                const response = await fetch(container.dataset.resolveUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN":
                            form.querySelector('[name="_token"]')?.value || "",
                    },
                    body: JSON.stringify({ url: parsed.shortUrl }),
                });
                if (!response.ok)
                    throw new Error(
                        "تعذر قراءة رابط الموقع المختصر. تأكد من الرابط وأعد المحاولة.",
                    );
                parsed = parseLocationQuery((await response.json()).data.url);
                if (parsed?.shortUrl)
                    throw new Error("تعذر قراءة رابط الموقع المختصر.");
            }
            if (version !== requestVersion) return;
            if (parsed.coordinates) {
                selectPosition(parsed.coordinates);
                return;
            }
            if (!map) {
                message(
                    "حدد النقطة على الخريطة البديلة أو الصق إحداثيات أو رابط موقع يحتوي على إحداثيات. البحث بالأسماء يتطلب خرائط جوجل.",
                    true,
                );
                return;
            }
            const { Place } = await google.maps.importLibrary("places");
            let places;
            if (parsed.placeId) {
                const place = new Place({ id: parsed.placeId });
                await place.fetchFields({
                    fields: ["location", "displayName", "formattedAddress"],
                });
                places = [place];
            } else {
                ({ places } = await Place.searchByText({
                    textQuery: parsed.text,
                    fields: ["location", "displayName", "formattedAddress"],
                    language: "ar",
                    region: "sa",
                    maxResultCount: 5,
                }));
            }
            if (version !== requestVersion) return;
            const located = places.filter((place) => place.location);
            if (!located.length) {
                message(
                    "لم يتم العثور على موقع. جرّب بحثًا آخر أو حدد النقطة على الخريطة.",
                    true,
                );
                return;
            }
            if (located.length === 1) {
                selectPosition(located[0].location.toJSON());
                return;
            }
            located.forEach((place) => {
                const button = document.createElement("button");
                button.type = "button";
                button.className =
                    "list-group-item list-group-item-action text-start";
                button.textContent = [place.displayName, place.formattedAddress]
                    .filter(Boolean)
                    .join(" — ");
                button.addEventListener("click", () => {
                    selectPosition(place.location.toJSON());
                    results.replaceChildren();
                });
                results.appendChild(button);
            });
            message("اختر الموقع من نتائج البحث، ثم حرّك العلامة لضبطه بدقة.");
        } catch (error) {
            if (version === requestVersion)
                message(
                    "تعذر العثور على الموقع. تحقق من البحث والاتصال أو حدد النقطة على الخريطة.",
                    true,
                );
        } finally {
            searchButton.disabled = false;
        }
    }

    searchButton.addEventListener("click", searchLocation);
    search.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            if (!searchButton.disabled) void searchLocation();
        }
    });
    retry.addEventListener("click", initialize);
    form.addEventListener("wizardDraftRestored", restoreLocation);
    // The wizard may initially hide this step. Recenter when it becomes visible.
    form.addEventListener("stepChanged", () => {
        if (map) map.panTo(savedPosition() || riyadhCenter);
        if (backupMap) {
            backupMap.invalidateSize();
            backupMap.panTo(savedPosition() || riyadhCenter);
        }
    });
    await initialize();
}
