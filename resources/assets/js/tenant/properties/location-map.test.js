import assert from "node:assert/strict";
import test from "node:test";
import { parseLocationQuery, initPropertyLocationMap } from "./location-map.js";

test("location search accepts coordinates and prefers the place pin over the map camera", () => {
    assert.deepEqual(parseLocationQuery("0, 0"), {
        coordinates: { lat: 0, lng: 0 },
    });
    assert.deepEqual(
        parseLocationQuery(
            "https://www.google.com/maps/place/Test/@24.1,46.1,14z/data=!3d24.7136!4d46.6753",
        ),
        { coordinates: { lat: 24.7136, lng: 46.6753 } },
    );
    for (const url of [
        "https://maps.google.com/?q=24.7136%2C46.6753",
        "https://www.google.com/maps/search/?api=1&query=24.7136,46.6753",
        "https://www.google.com/maps/@24.7136,46.6753,18z",
    ])
        assert.deepEqual(parseLocationQuery(url), {
            coordinates: { lat: 24.7136, lng: 46.6753 },
        });
    assert.deepEqual(
        parseLocationQuery(
            "https://www.google.com/maps/place/24.7136,46.6753/@24.7,46.6,17z",
        ),
        { coordinates: { lat: 24.7136, lng: 46.6753 } },
    );
    assert.deepEqual(
        parseLocationQuery(
            "https://www.google.com/maps/place/King+Fahd+Road/@24.7,46.6,17z",
        ),
        { text: "King Fahd Road" },
    );
});

test("location search distinguishes names, place IDs and shortened Google links", () => {
    assert.deepEqual(parseLocationQuery("الرياض"), { text: "الرياض" });
    assert.deepEqual(parseLocationQuery("https://maps.google.com/?q=الرياض"), {
        text: "الرياض",
    });
    assert.deepEqual(
        parseLocationQuery(
            "https://www.google.com/maps/search/?api=1&query=Test&query_place_id=ChIJtest",
        ),
        { placeId: "ChIJtest" },
    );
    assert.deepEqual(parseLocationQuery("https://maps.app.goo.gl/abc"), {
        shortUrl: "https://maps.app.goo.gl/abc",
    });
    assert.deepEqual(parseLocationQuery("https://goo.gl/maps/abc"), {
        shortUrl: "https://goo.gl/maps/abc",
    });
    assert.deepEqual(
        parseLocationQuery("https://www.google.com/maps/place/King+Fahd+Road"),
        { text: "King Fahd Road" },
    );
});

test("invalid coordinates and non Maps URLs cannot become a selected property location", () => {
    for (const input of [
        "91,46",
        "24,181",
        "https://evil.example/?q=24,46",
        "https://google.com.evil.example/maps/@24,46",
        "https://user@google.com/maps/@24,46",
        "https://google.com:8443/maps/@24,46",
    ]) {
        assert.throws(() => parseLocationQuery(input));
    }
    assert.equal(parseLocationQuery(""), null);
});

class Control extends EventTarget {
    constructor(value = "") {
        super();
        this.value = value;
        this.hidden = false;
        this.textContent = "";
        this.dataset = {};
    }
    children = [];
    replaceChildren() {
        this.children = [];
    }
    appendChild(child) {
        this.children.push(child);
    }
}

function fixture(values = {}) {
    const controls = Object.fromEntries(
        ["latitude", "longitude", "google_maps_url"].map((name) => [
            name,
            new Control(values[name] ?? ""),
        ]),
    );
    const elements = Object.fromEntries(
        [
            "property-location-map",
            "property-location-search",
            "property-location-search-button",
            "property-location-status",
            "property-location-results",
            "property-location-retry",
            "property-location-fallback",
        ].map((id) => [id, new Control()]),
    );
    elements["property-location-map"].dataset.apiKey = "test-key";
    const form = new Control();
    form.elements = { namedItem: (name) => controls[name] };
    form.querySelector = (selector) => elements[selector.slice(1)] ?? null;
    const listeners = {};
    const state = {};
    class Map {
        constructor(container, options) {
            this.center = options.center;
            this.zoom = options.zoom;
            this.options = options;
            state.hiddenWhenConstructed = container.hidden;
            state.map = this;
        }
        addListener(name, callback) {
            listeners[name] = callback;
        }
        panTo(position) {
            this.center = position;
        }
        setZoom(zoom) {
            this.zoom = zoom;
        }
    }
    class Marker extends EventTarget {
        constructor(options) {
            super();
            Object.assign(this, options);
            state.marker = this;
        }
        addListener(name, callback) {
            listeners[name] = callback;
        }
    }
    const maps = { Map, AdvancedMarkerElement: Marker };
    return { form, controls, elements, maps, listeners, state };
}

function fallbackFixture(values = {}) {
    const f = fixture(values);
    return {
        ...f,
        loaders: {
            loadMaps: async () => {
                throw new Error("Google unavailable");
            },
        },
    };
}

test("Google failure switches to view-only Google fallback with retry action", async () => {
    const f = fallbackFixture();
    await initPropertyLocationMap(f.form, f.loaders);
    assert.equal(f.elements["property-location-map"].hidden, true);
    assert.equal(f.elements["property-location-fallback"].hidden, false);
    assert.match(f.elements["property-location-fallback"].src, /q=24.7136%2C46.6753/);
    assert.equal(f.elements["property-location-retry"].hidden, false);
    assert.equal(f.controls.latitude.value, "");
});

test("Google failure preserves saved location in view-only Google fallback", async () => {
    const f = fallbackFixture({ latitude: "25", longitude: "47" });
    await initPropertyLocationMap(f.form, f.loaders);
    assert.equal(f.elements["property-location-map"].hidden, true);
    assert.equal(f.elements["property-location-fallback"].hidden, false);
    assert.match(f.elements["property-location-fallback"].src, /q=25%2C47/);
    assert.equal(f.elements["property-location-retry"].hidden, false);
});

test("retry restores Google map after a failure with the selected point", async () => {
    const f = fallbackFixture();
    let attempts = 0;
    await initPropertyLocationMap(f.form, {
        loadMaps: async () => {
            if (++attempts === 1) throw new Error("offline");
            return f.maps;
        },
    });
    assert.equal(f.elements["property-location-retry"].hidden, false);
    assert.equal(f.elements["property-location-fallback"].hidden, false);
    f.elements["property-location-search"].value =
        "https://maps.google.com/?q=25.2,47.3";
    f.elements["property-location-search-button"].dispatchEvent(
        new Event("click"),
    );
    assert.equal(f.controls.latitude.value, "25.2000000");
    assert.equal(f.controls.longitude.value, "47.3000000");
    f.elements["property-location-retry"].dispatchEvent(new Event("click"));
    await new Promise(setImmediate);
    assert.equal(f.elements["property-location-fallback"].hidden, true);
    assert.equal(f.elements["property-location-map"].hidden, false);
    assert.deepEqual(f.state.marker.position, { lat: 25.2, lng: 47.3 });
    assert.equal(f.elements["property-location-retry"].hidden, true);
});

test("clicking and dragging persist coordinates and a matching URL in the form payload", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    assert.equal(f.controls.latitude.value, "");
    f.listeners.click({ latLng: { lat: () => 24.7136, lng: () => 46.6753 } });
    assert.equal(f.controls.latitude.value, "24.7136000");
    assert.equal(f.controls.longitude.value, "46.6753000");
    assert.equal(
        f.controls.google_maps_url.value,
        "https://www.google.com/maps/search/?api=1&query=24.7136000,46.6753000",
    );
    f.state.marker.position = { lat: 0, lng: 0 };
    f.state.marker.dispatchEvent(new Event("gmp-dragend"));
    assert.equal(f.controls.latitude.value, "0.0000000");
    assert.equal(f.controls.longitude.value, "0.0000000");
});

test("edit and draft restoration keep exact coordinates even when an older URL has another center", async () => {
    const f = fixture({
        latitude: "24.5",
        longitude: "46.5",
        google_maps_url: "https://maps.google.com/?q=24,46",
    });
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    assert.equal(f.controls.latitude.value, "24.5000000");
    f.controls.latitude.value = "25.25";
    f.controls.longitude.value = "47.75";
    f.form.dispatchEvent(new Event("wizardDraftRestored"));
    assert.equal(f.controls.latitude.value, "25.2500000");
    assert.match(f.controls.google_maps_url.value, /25.2500000,47.7500000$/);
});

test("a draft restored before Maps finishes loading becomes the initial marker", async () => {
    const f = fixture();
    let resolve;
    const ready = initPropertyLocationMap(f.form, {
        loadMaps: () =>
            new Promise((done) => {
                resolve = done;
            }),
    });
    f.controls.latitude.value = "0";
    f.controls.longitude.value = "46.6";
    f.form.dispatchEvent(new Event("wizardDraftRestored"));
    resolve(f.maps);
    await ready;
    assert.equal(f.controls.latitude.value, "0.0000000");
    assert.equal(f.controls.longitude.value, "46.6000000");
    assert.deepEqual(f.state.marker.position, { lat: 0, lng: 46.6 });
    assert.equal(f.state.marker.map, f.state.map);
});

test("map loader failing preserves a saved location and shows a retry action", async () => {
    const f = fixture({ latitude: "24.5", longitude: "46.5" });
    await initPropertyLocationMap(f.form, {
        loadMaps: async () => {
            throw new Error("offline");
        },
    });
    assert.equal(f.controls.latitude.value, "24.5");
    assert.equal(f.elements["property-location-retry"].hidden, false);
    assert.ok(f.elements["property-location-status"].textContent.length > 0);
    assert.equal(f.elements["property-location-fallback"].hidden, false);
    assert.match(f.elements["property-location-fallback"].src, /q=24.5%2C46.5/);
});

test("coordinates remain searchable while the map loads", async () => {
    const f = fixture();
    let resolve;
    const ready = initPropertyLocationMap(f.form, {
        loadMaps: () =>
            new Promise((done) => {
                resolve = done;
            }),
    });
    try {
        assert.equal(
            f.elements["property-location-search-button"].disabled,
            false,
        );
        f.elements["property-location-search"].value = "25.2,47.3";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        assert.equal(f.controls.latitude.value, "25.2000000");
        assert.equal(f.controls.longitude.value, "47.3000000");
    } finally {
        resolve(f.maps);
        await ready;
    }
    assert.deepEqual(f.state.marker.position, { lat: 25.2, lng: 47.3 });
});

test("coordinate links can select a location even when Google Maps fails", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, {
        loadMaps: async () => {
            throw new Error("offline");
        },
    });
    assert.equal(f.elements["property-location-search-button"].disabled, false);
    f.elements["property-location-search"].value =
        "https://maps.google.com/?q=25.2,47.3";
    f.elements["property-location-search-button"].dispatchEvent(
        new Event("click"),
    );
    assert.equal(f.controls.latitude.value, "25.2000000");
    assert.equal(f.controls.longitude.value, "47.3000000");
    assert.match(f.elements["property-location-fallback"].src, /q=25.2%2C47.3/);
});

test("Google authentication failure after initialization switches to view-only Google fallback", async () => {
    const f = fixture({ latitude: "25", longitude: "47" });
    let onAuthFailureCallback;
    await initPropertyLocationMap(f.form, {
        loadMaps: async (key, onAuthFailure) => {
            onAuthFailureCallback = onAuthFailure;
            return f.maps;
        },
    });
    assert.equal(f.elements["property-location-map"].hidden, false);
    assert.equal(f.elements["property-location-fallback"].hidden, true);
    onAuthFailureCallback?.();
    await new Promise(setImmediate);
    assert.equal(f.elements["property-location-retry"].hidden, false);
    assert.equal(f.elements["property-location-map"].hidden, true);
    assert.equal(f.elements["property-location-fallback"].hidden, false);
    assert.match(f.elements["property-location-fallback"].src, /q=25%2C47/);
});

test("an empty location displays Riyadh without filling or saving any location fields", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    assert.deepEqual(f.state.map.center, { lat: 24.7136, lng: 46.6753 });
    assert.equal(f.state.map.zoom, 12);
    assert.equal(f.state.marker.map, null);
    assert.equal(f.controls.latitude.value, "");
    assert.equal(f.controls.longitude.value, "");
    assert.equal(f.controls.google_maps_url.value, "");
    assert.equal(f.elements["property-location-fallback"].hidden, true);
});

test("an empty draft recenters Riyadh after a different saved location without selecting Riyadh", async () => {
    const f = fixture({ latitude: "25", longitude: "47" });
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    f.controls.latitude.value = "";
    f.controls.longitude.value = "";
    f.controls.google_maps_url.value = "";
    f.form.dispatchEvent(new Event("wizardDraftRestored"));
    assert.deepEqual(f.state.map.center, { lat: 24.7136, lng: 46.6753 });
    assert.equal(f.controls.latitude.value, "");
    assert.equal(f.controls.longitude.value, "");
    assert.equal(f.controls.google_maps_url.value, "");
});

test("a failed Maps script displays Riyadh as a view only fallback and retry restores the picker", async () => {
    const f = fixture();
    const previousDocument = globalThis.document;
    const previousWindow = globalThis.window;
    const previousGoogle = globalThis.google;
    const scripts = [];
    globalThis.window = globalThis;
    delete globalThis.google;
    globalThis.document = {
        createElement: () => ({ remove() {} }),
        querySelector: () => ({ nonce: "test-nonce" }),
        head: { appendChild: (script) => scripts.push(script) },
        // Support the outside-click listener added by the map initialiser.
        addEventListener: () => {},
    };
    try {
        const { initPropertyLocationMap: initialize } = await import(
            "./location-map.js?failed-loader-regression"
        );
        const ready = initialize(f.form);
        scripts[0].onerror();
        await ready;
        assert.equal(f.elements["property-location-map"].hidden, true);
        assert.equal(f.elements["property-location-fallback"].hidden, false);
        assert.match(
            f.elements["property-location-fallback"].src,
            /q=24.7136%2C46.6753/,
        );
        assert.equal(f.controls.latitude.value, "");
        assert.equal(f.controls.longitude.value, "");
        assert.equal(f.controls.google_maps_url.value, "");
        f.elements["property-location-retry"].dispatchEvent(new Event("click"));
        assert.equal(scripts.length, 2);
        assert.equal(scripts[1].nonce, "test-nonce");
        globalThis.google = { maps: { importLibrary: async () => f.maps } };
        globalThis.__propertyMapsReady();
        await new Promise(setImmediate);
        assert.equal(f.elements["property-location-fallback"].hidden, true);
        assert.equal(f.elements["property-location-map"].hidden, false);
        assert.equal(f.state.hiddenWhenConstructed, false);
        assert.deepEqual(f.state.map.center, { lat: 24.7136, lng: 46.6753 });
        assert.equal(f.controls.latitude.value, "");
    } finally {
        globalThis.document = previousDocument;
        globalThis.window = previousWindow;
        globalThis.google = previousGoogle;
        delete globalThis.__propertyMapsReady;
    }
});

test("searching coordinates selects the pin without calling a Places API", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    f.elements["property-location-search"].value = "25.2, 47.3";
    f.elements["property-location-search-button"].dispatchEvent(
        new Event("click"),
    );
    assert.deepEqual(f.state.marker.position, { lat: 25.2, lng: 47.3 });
    assert.equal(f.controls.latitude.value, "25.2000000");
});

test("clearing the restored location removes a previous marker", async () => {
    const f = fixture({ latitude: "24.5", longitude: "46.5" });
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    f.controls.latitude.value = "";
    f.controls.longitude.value = "";
    f.controls.google_maps_url.value = "";
    f.form.dispatchEvent(new Event("wizardDraftRestored"));
    assert.equal(f.state.marker.map, null);
    assert.equal(f.controls.latitude.value, "");
});

test("an outdated place search cannot overwrite a point picked while waiting for results", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    let resolveSearch;
    const previousGoogle = globalThis.google;
    const previousDocument = globalThis.document;
    globalThis.document = { createElement: () => new Control() };
    globalThis.google = {
        maps: {
            importLibrary: async () => ({
                Place: {
                    searchByText: () =>
                        new Promise((resolve) => {
                            resolveSearch = resolve;
                        }),
                },
            }),
            places: { PlacesServiceStatus: { OK: "OK" } },
            GeocoderStatus: { OK: "OK" },
            Geocoder: class {
                geocode(_, cb) { cb([], "ZERO_RESULTS"); }
            },
        },
    };
    try {
        f.elements["property-location-search"].value = "الرياض";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        // User clicks the map while search is still in flight → takes priority.
        f.listeners.click({ latLng: { lat: () => 25, lng: () => 47 } });
        // The stale search result arrives after the map click — must NOT overwrite the pin.
        resolveSearch({
            places: [
                {
                    location: { toJSON: () => ({ lat: 24, lng: 46 }) },
                    displayName: "الرياض",
                    formattedAddress: "الرياض",
                },
            ],
        });
        await new Promise(setImmediate);
        // Map-click position must win.
        assert.deepEqual(f.state.marker.position, { lat: 25, lng: 47 });
        assert.equal(f.controls.latitude.value, "25.0000000");
        // The stale dropdown must have been discarded (version guard).
        assert.equal(
            f.elements["property-location-results"].children.length,
            0,
        );
    } finally {
        globalThis.google = previousGoogle;
        globalThis.document = previousDocument;
    }
});

test("place search results let the user pick the exact matching location", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    const previousGoogle = globalThis.google;
    const previousDocument = globalThis.document;
    globalThis.document = { createElement: () => new Control() };
    globalThis.google = {
        maps: {
            importLibrary: async () => ({
                Place: {
                    searchByText: async () => ({
                        places: [
                            {
                                displayName: "الموقع الأول",
                                formattedAddress: "الرياض",
                                location: {
                                    toJSON: () => ({ lat: 24, lng: 46 }),
                                },
                            },
                            {
                                displayName: "الموقع الثاني",
                                formattedAddress: "الرياض",
                                location: {
                                    toJSON: () => ({ lat: 25, lng: 47 }),
                                },
                            },
                        ],
                    }),
                },
            }),
            places: { PlacesServiceStatus: { OK: "OK" } },
            GeocoderStatus: { OK: "OK" },
            Geocoder: class {
                geocode(_, cb) { cb([], "ZERO_RESULTS"); }
            },
        },
    };
    try {
        f.elements["property-location-search"].value = "موقع";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        assert.equal(
            f.elements["property-location-results"].children.length,
            2,
        );
        f.elements["property-location-results"].children[1].dispatchEvent(
            new Event("click"),
        );
        assert.deepEqual(f.state.marker.position, { lat: 25, lng: 47 });
        assert.equal(f.controls.longitude.value, "47.0000000");
    } finally {
        globalThis.google = previousGoogle;
        globalThis.document = previousDocument;
    }
});

test("a single text search result shows in the dropdown and is not auto-placed until the user clicks it", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    const previousGoogle = globalThis.google;
    const previousDocument = globalThis.document;
    globalThis.document = { createElement: () => new Control() };
    globalThis.google = {
        maps: {
            importLibrary: async () => ({
                Place: {
                    searchByText: async () => ({
                        places: [
                            {
                                displayName: "الرياض",
                                formattedAddress: "الرياض، المملكة العربية السعودية",
                                location: { toJSON: () => ({ lat: 24.7136, lng: 46.6753 }) },
                            },
                        ],
                    }),
                },
            }),
            places: { PlacesServiceStatus: { OK: "OK" } },
            GeocoderStatus: { OK: "OK" },
            Geocoder: class {
                geocode(_, cb) { cb([], "ZERO_RESULTS"); }
            },
        },
    };
    try {
        f.elements["property-location-search"].value = "الرياض";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        // Must show dropdown — not auto-place the dot.
        assert.equal(
            f.elements["property-location-results"].children.length,
            1,
        );
        assert.equal(f.controls.latitude.value, "", "dot must not be placed before user picks");
        // User clicks the dropdown item → now the dot is placed.
        f.elements["property-location-results"].children[0].dispatchEvent(
            new Event("click"),
        );
        assert.equal(f.controls.latitude.value, "24.7136000");
        assert.equal(f.controls.longitude.value, "46.6753000");
        // Dropdown must close after selection.
        assert.equal(
            f.elements["property-location-results"].children.length,
            0,
        );
    } finally {
        globalThis.google = previousGoogle;
        globalThis.document = previousDocument;
    }
});

test("searching a short link selects the location from its expanded Google Maps URL", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    const previousFetch = globalThis.fetch;
    globalThis.fetch = async () => ({
        ok: true,
        json: async () => ({
            data: {
                url: "https://www.google.com/maps/place/Test/data=!3d25.5!4d47.5",
            },
        }),
    });
    try {
        f.elements["property-location-search"].value =
            "https://maps.app.goo.gl/example";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        assert.deepEqual(f.state.marker.position, { lat: 25.5, lng: 47.5 });
        assert.equal(f.controls.latitude.value, "25.5000000");
    } finally {
        globalThis.fetch = previousFetch;
    }
});

test("google map initializes with roadmap as default and provides map type controls for realistic and roadmap options", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    assert.equal(f.state.map.options.mapTypeId, "roadmap");
    assert.equal(f.state.map.options.mapTypeControl, true);
    assert.deepEqual(f.state.map.options.mapTypeControlOptions?.mapTypeIds, [
        "roadmap",
        "satellite",
        "hybrid",
        "terrain",
    ]);
});

test("text search falls back to Geocoder when Places API (New) is unavailable", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    const previousGoogle = globalThis.google;
    const previousDocument = globalThis.document;
    globalThis.document = { createElement: () => new Control(), addEventListener: () => {} };
    globalThis.google = {
        maps: {
            // Places API (New) throws — simulates API not enabled
            importLibrary: async () => {
                throw new Error("Places API not enabled");
            },
            places: { PlacesServiceStatus: { OK: "OK" } },
            GeocoderStatus: { OK: "OK" },
            Geocoder: class {
                geocode(opts, cb) {
                    cb(
                        [
                            {
                                geometry: {
                                    location: { toJSON: () => ({ lat: 24.7, lng: 46.7 }) },
                                },
                                formatted_address: "الرياض، المملكة العربية السعودية",
                            },
                        ],
                        "OK",
                    );
                }
            },
        },
    };
    try {
        f.elements["property-location-search"].value = "الرياض";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        // Result must appear in the dropdown, not auto-placed on the map.
        assert.equal(
            f.elements["property-location-results"].children.length,
            1,
            "Geocoder result must appear as a dropdown item",
        );
        assert.equal(f.controls.latitude.value, "", "dot must not be auto-placed");
        // Click the dropdown item → pin placed.
        f.elements["property-location-results"].children[0].dispatchEvent(
            new Event("click"),
        );
        assert.equal(f.controls.latitude.value, "24.7000000");
        assert.equal(f.controls.longitude.value, "46.7000000");
    } finally {
        globalThis.google = previousGoogle;
        globalThis.document = previousDocument;
    }
});

test("text search falls back to classic PlacesService when Places API (New) returns no results", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    const previousGoogle = globalThis.google;
    const previousDocument = globalThis.document;
    globalThis.document = { createElement: () => new Control(), addEventListener: () => {} };
    globalThis.google = {
        maps: {
            // Places API (New) returns empty results
            importLibrary: async () => ({
                Place: {
                    searchByText: async () => ({ places: [] }),
                },
            }),
            places: {
                PlacesServiceStatus: { OK: "OK" },
                PlacesService: class {
                    constructor() {}
                    textSearch(opts, cb) {
                        cb(
                            [
                                {
                                    name: "الرياض",
                                    formatted_address: "الرياض، السعودية",
                                    geometry: {
                                        location: { toJSON: () => ({ lat: 24.68, lng: 46.72 }) },
                                    },
                                },
                            ],
                            "OK",
                        );
                    }
                },
            },
            GeocoderStatus: { OK: "OK" },
            Geocoder: class {
                geocode(_, cb) { cb([], "ZERO_RESULTS"); }
            },
        },
    };
    try {
        f.elements["property-location-search"].value = "الرياض";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        // Result must appear in the dropdown, not auto-placed.
        assert.equal(
            f.elements["property-location-results"].children.length,
            1,
            "Classic PlacesService result must appear as a dropdown item",
        );
        assert.equal(f.controls.latitude.value, "", "dot must not be auto-placed");
        // User picks from the dropdown.
        f.elements["property-location-results"].children[0].dispatchEvent(
            new Event("click"),
        );
        assert.equal(f.controls.latitude.value, "24.6800000");
        assert.equal(f.controls.longitude.value, "46.7200000");
    } finally {
        globalThis.google = previousGoogle;
        globalThis.document = previousDocument;
    }
});

test("debounced input triggers search after 350ms without touching the button", async () => {
    const f = fixture();
    await initPropertyLocationMap(f.form, { loadMaps: async () => f.maps });
    const previousGoogle = globalThis.google;
    const previousDocument = globalThis.document;
    const previousSetTimeout = globalThis.setTimeout;
    const previousClearTimeout = globalThis.clearTimeout;
    globalThis.document = { createElement: () => new Control(), addEventListener: () => {} };
    // Collect scheduled callbacks
    const timers = new Map();
    let nextId = 1;
    globalThis.setTimeout = (cb, delay) => {
        const id = nextId++;
        timers.set(id, cb);
        return id;
    };
    globalThis.clearTimeout = (id) => timers.delete(id);
    globalThis.google = {
        maps: {
            importLibrary: async () => ({
                Place: {
                    searchByText: async () => ({
                        places: [
                            {
                                displayName: "الرياض",
                                formattedAddress: "الرياض، السعودية",
                                location: { toJSON: () => ({ lat: 24.7, lng: 46.7 }) },
                            },
                        ],
                    }),
                },
            }),
            places: { PlacesServiceStatus: { OK: "OK" } },
            GeocoderStatus: { OK: "OK" },
            Geocoder: class {
                geocode(_, cb) { cb([], "ZERO_RESULTS"); }
            },
        },
    };
    try {
        f.elements["property-location-search"].value = "الرياض";
        f.elements["property-location-search"].dispatchEvent(new Event("input"));
        // Exactly one debounce timer should be pending
        assert.equal(timers.size, 1);
        // Fire the debounce manually
        const [, cb] = [...timers.entries()][0];
        timers.clear();
        cb();
        await new Promise(setImmediate);
        // Dropdown must appear — no auto-placement.
        assert.equal(
            f.elements["property-location-results"].children.length,
            1,
            "Debounced search must show results in dropdown",
        );
        assert.equal(f.controls.latitude.value, "", "dot must not be auto-placed by debounced search");
        // User picks the item.
        f.elements["property-location-results"].children[0].dispatchEvent(
            new Event("click"),
        );
        assert.equal(f.controls.latitude.value, "24.7000000");
    } finally {
        globalThis.setTimeout = previousSetTimeout;
        globalThis.clearTimeout = previousClearTimeout;
        globalThis.google = previousGoogle;
        globalThis.document = previousDocument;
    }
});
