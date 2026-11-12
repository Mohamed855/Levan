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
    const events = {};
    const map = {
        setView(position, zoom) {
            this.center = position;
            this.zoom = zoom;
            return this;
        },
        panTo(position) {
            this.center = position;
        },
        setZoom(zoom) {
            this.zoom = zoom;
        },
        on(name, callback) {
            events[name] = callback;
            return this;
        },
        invalidateSize() {
            this.resized = true;
        },
        remove() {
            this.removed = true;
        },
    };
    const marker = {
        setLatLng(position) {
            this.position = position;
            return this;
        },
        getLatLng() {
            return this.position;
        },
        addTo(target) {
            this.map = target;
            return this;
        },
        remove() {
            this.map = null;
        },
        on(name, callback) {
            events[`marker:${name}`] = callback;
            return this;
        },
    };
    const leaflet = {
        map: () => map,
        tileLayer: () => ({ addTo() {} }),
        divIcon: (options) => options,
        marker: (position, options) => {
            marker.position = position;
            marker.options = options;
            return marker;
        },
    };
    return {
        ...f,
        fallbackMap: map,
        fallbackMarker: marker,
        fallbackEvents: events,
        loaders: {
            loadMaps: async () => {
                throw new Error("Google unavailable");
            },
            loadFallbackMaps: async () => leaflet,
        },
    };
}

test("Google failure keeps an interactive map whose clicks and marker drags save the location", async () => {
    const f = fallbackFixture();
    await initPropertyLocationMap(f.form, f.loaders);
    assert.equal(f.elements["property-location-map"].hidden, false);
    assert.equal(f.elements["property-location-fallback"].hidden, true);
    assert.deepEqual(f.fallbackMap.center, { lat: 24.7136, lng: 46.6753 });
    assert.equal(f.controls.latitude.value, "");
    assert.equal(f.fallbackMarker.map, null);
    f.fallbackEvents.click({ latlng: { lat: 25.5, lng: 47.5 } });
    assert.equal(f.controls.latitude.value, "25.5000000");
    assert.equal(f.controls.longitude.value, "47.5000000");
    assert.equal(
        f.controls.google_maps_url.value,
        "https://www.google.com/maps/search/?api=1&query=25.5000000,47.5000000",
    );
    assert.equal(f.fallbackMarker.map, f.fallbackMap);
    assert.equal(f.fallbackMarker.options.draggable, true);
    f.fallbackMarker.position = { lat: 0, lng: 46 };
    f.fallbackEvents["marker:dragend"]();
    assert.equal(f.controls.latitude.value, "0.0000000");
    assert.equal(f.controls.longitude.value, "46.0000000");
});

test("the backup map restores and clears drafts and resizes when the wizard step opens", async () => {
    const f = fallbackFixture({ latitude: "25", longitude: "47" });
    await initPropertyLocationMap(f.form, f.loaders);
    assert.deepEqual(f.fallbackMarker.position, { lat: 25, lng: 47 });
    f.form.dispatchEvent(new Event("stepChanged"));
    assert.equal(f.fallbackMap.resized, true);
    f.controls.latitude.value = "0";
    f.controls.longitude.value = "48";
    f.form.dispatchEvent(new Event("wizardDraftRestored"));
    assert.deepEqual(f.fallbackMarker.position, { lat: 0, lng: 48 });
    for (const control of Object.values(f.controls)) control.value = "";
    f.form.dispatchEvent(new Event("wizardDraftRestored"));
    assert.equal(f.fallbackMarker.map, null);
    assert.deepEqual(f.fallbackMap.center, { lat: 24.7136, lng: 46.6753 });
    assert.equal(f.controls.latitude.value, "");
});

test("the backup map accepts coordinate links and retry restores Google with the selected point", async () => {
    const f = fallbackFixture();
    let attempts = 0;
    await initPropertyLocationMap(f.form, {
        ...f.loaders,
        loadMaps: async () => {
            if (++attempts === 1) throw new Error("offline");
            return f.maps;
        },
    });
    assert.equal(f.elements["property-location-search-button"].disabled, false);
    f.elements["property-location-search"].value =
        "https://maps.google.com/?q=25.2,47.3";
    f.elements["property-location-search-button"].dispatchEvent(
        new Event("click"),
    );
    assert.deepEqual(f.fallbackMarker.position, { lat: 25.2, lng: 47.3 });
    f.elements["property-location-retry"].dispatchEvent(new Event("click"));
    await new Promise(setImmediate);
    assert.equal(f.fallbackMap.removed, true);
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

test("both map loaders failing preserves a saved location and shows a retry action", async () => {
    const f = fixture({ latitude: "24.5", longitude: "46.5" });
    await initPropertyLocationMap(f.form, {
        loadMaps: async () => {
            throw new Error("offline");
        },
        loadFallbackMaps: async () => {
            throw new Error("backup unavailable");
        },
    });
    assert.equal(f.controls.latitude.value, "24.5");
    assert.equal(f.elements["property-location-retry"].hidden, false);
    assert.ok(f.elements["property-location-status"].textContent.length > 0);
    assert.equal(f.elements["property-location-fallback"].hidden, false);
    assert.match(f.elements["property-location-fallback"].src, /q=24.5%2C46.5/);
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

test("a failed Maps script and backup display Riyadh as a view only fallback and retry restores the picker", async () => {
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
    };
    try {
        const { initPropertyLocationMap: initialize } = await import(
            "./location-map.js?failed-loader-regression"
        );
        const ready = initialize(f.form, {
            loadFallbackMaps: async () => {
                throw new Error("backup unavailable");
            },
        });
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
        },
    };
    try {
        f.elements["property-location-search"].value = "الرياض";
        f.elements["property-location-search-button"].dispatchEvent(
            new Event("click"),
        );
        await new Promise(setImmediate);
        f.listeners.click({ latLng: { lat: () => 25, lng: () => 47 } });
        resolveSearch({
            places: [{ location: { toJSON: () => ({ lat: 24, lng: 46 }) } }],
        });
        await new Promise(setImmediate);
        assert.deepEqual(f.state.marker.position, { lat: 25, lng: 47 });
        assert.equal(f.controls.latitude.value, "25.0000000");
        assert.equal(
            f.elements["property-location-results"].children.length,
            0,
        );
    } finally {
        globalThis.google = previousGoogle;
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
                                location: {
                                    toJSON: () => ({ lat: 24, lng: 46 }),
                                },
                            },
                            {
                                displayName: "الموقع الثاني",
                                location: {
                                    toJSON: () => ({ lat: 25, lng: 47 }),
                                },
                            },
                        ],
                    }),
                },
            }),
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
