import assert from "node:assert/strict";
import test from "node:test";
import vm from "node:vm";
import { readFileSync } from "node:fs";
import * as boundaryDrawing from "./boundary-drawing.js";

function editor({
    original = "",
    image = "https://example.test/annotated.jpg",
} = {}) {
    class Control extends EventTarget {
        constructor(value = "") {
            super();
            this.value = value;
            this.dataset = {};
            this.style = {};
            this.classList = { add() {}, remove() {}, toggle() {} };
        }
        setAttribute() {}
    }
    const form = new Control();
    const images = [];
    const controls = {
        "property-aerial-image": new Control(image),
        "property-raw-aerial-image": new Control(original),
        "property-boundary-geojson": new Control(
            '{"type":"Polygon","coordinates":[[[10,10],[80,10],[50,80],[10,10]]]}',
        ),
        "property-drawing-canvas": new Control(),
    };
    const context = new Proxy({}, { get: () => () => {} });
    controls["property-drawing-canvas"].getContext = () => context;
    controls["property-drawing-canvas"].getBoundingClientRect = () => ({
        left: 0,
        top: 0,
        width: 100,
        height: 100,
    });
    form.elements = { namedItem: () => null };
    form.querySelectorAll = () => [];
    let ready;
    const document = {
        addEventListener: (_, callback) => {
            ready = callback;
        },
        querySelector: () => form,
        querySelectorAll: () => [],
        getElementById: (id) => controls[id] ?? null,
        createElement: () => ({
            getContext: () => context,
            toDataURL: () => "data:image/jpeg;base64,rendered",
        }),
    };
    class Image {
        constructor() {
            this.naturalWidth = 100;
            this.naturalHeight = 100;
            images.push(this);
        }
    }
    const source = readFileSync(
        new URL("./create.js", import.meta.url),
        "utf8",
    ).replace(/^import [\s\S]*?;\r?\n/gm, "");
    const window = new Control();
    window.requestAnimationFrame = (callback) => {
        callback();
        return 1;
    };
    vm.runInNewContext(source, {
        document,
        Image,
        Event,
        CustomEvent,
        window,
        setTimeout,
        clearTimeout,
        initCityDistrictSelector() {},
        initPropertyLocationMap() {},
        ...boundaryDrawing,
    });
    ready();
    return { form, controls, images, window };
}

test("opening an old property image does not repaint or rewrite its saved annotation", () => {
    const e = editor();
    e.images[0].onload();
    assert.equal(
        e.controls["property-aerial-image"].value,
        "https://example.test/annotated.jpg",
    );
    assert.equal(e.controls["property-raw-aerial-image"].value, "");
});

test("an older image callback cannot replace the restored draft image", () => {
    const e = editor({ original: "https://example.test/original.jpg" });
    e.form.dispatchEvent(
        new CustomEvent("wizardDraftRestored", {
            detail: {
                data: {
                    raw_aerial_image: "data:image/jpeg;base64,draft-original",
                    aerial_image: "data:image/jpeg;base64,draft-rendered",
                    boundary_geojson:
                        '{"type":"Polygon","coordinates":[[[20,20],[90,20],[50,90],[20,20]]]}',
                },
            },
        }),
    );
    e.images[1].onload();
    const restored = e.controls["property-aerial-image"].value;
    const boundary = e.controls["property-boundary-geojson"].value;
    e.images[0].onload();
    assert.equal(e.controls["property-aerial-image"].value, restored);
    assert.equal(e.controls["property-boundary-geojson"].value, boundary);
});

test("saving a draft immediately after dragging a boundary keeps the latest point", () => {
    const e = editor({ original: "https://example.test/original.jpg" });
    e.images[0].onload();
    const down = Object.assign(new Event("mousedown"), {
        clientX: 10,
        clientY: 10,
    });
    e.controls["property-drawing-canvas"].dispatchEvent(down);
    e.window.dispatchEvent(
        Object.assign(new Event("mousemove"), { clientX: 20, clientY: 20 }),
    );
    e.window.dispatchEvent(new Event("mouseup"));
    assert.deepEqual(
        JSON.parse(e.controls["property-boundary-geojson"].value)
            .coordinates[0][0],
        [10, 10],
    );
    const save = new Event("click");
    Object.defineProperty(save, "target", { value: { closest: () => true } });
    e.form.dispatchEvent(save);
    assert.deepEqual(
        JSON.parse(e.controls["property-boundary-geojson"].value)
            .coordinates[0][0],
        [20, 20],
    );
});
