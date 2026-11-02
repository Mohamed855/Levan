import assert from "node:assert/strict";
import test from "node:test";

import {
    bindBoundaryDragEndListeners,
    getBoundaryPointerAction,
    getBoundaryRenderState,
} from "./boundary-drawing.js";

const triangle = [
    { x: 0.1, y: 0.1 },
    { x: 0.8, y: 0.1 },
    { x: 0.5, y: 0.8 },
];

test("closes an open boundary only when its first point is selected", () => {
    assert.deepEqual(
        getBoundaryPointerAction({
            points: triangle,
            isClosed: false,
            nearPointIndex: 0,
            point: { x: 0.1, y: 0.1 },
        }),
        { type: "close" },
    );
});

test("does not close a boundary before it has three points", () => {
    assert.deepEqual(
        getBoundaryPointerAction({
            points: triangle.slice(0, 2),
            isClosed: false,
            nearPointIndex: 0,
            point: triangle[0],
        }),
        { type: "drag", pointIndex: 0 },
    );
});

test("adds preview segments while open and keeps vertices draggable after closing", () => {
    const cursorPoint = { x: 0.4, y: 0.4 };

    assert.deepEqual(
        getBoundaryPointerAction({
            points: triangle,
            isClosed: false,
            nearPointIndex: -1,
            point: cursorPoint,
        }),
        { type: "add", point: cursorPoint, previewPoint: cursorPoint },
    );
    assert.deepEqual(
        getBoundaryPointerAction({
            points: triangle,
            isClosed: true,
            nearPointIndex: 1,
            point: cursorPoint,
        }),
        { type: "drag", pointIndex: 1 },
    );
    assert.deepEqual(
        getBoundaryPointerAction({
            points: triangle,
            isClosed: true,
            nearPointIndex: -1,
            point: cursorPoint,
        }),
        { type: "none" },
    );
    assert.deepEqual(
        getBoundaryRenderState(triangle, false, cursorPoint),
        {
            pathPoints: [...triangle, cursorPoint],
            shouldDimOutside: false,
        },
    );
    assert.deepEqual(getBoundaryRenderState(triangle, true, cursorPoint), {
        pathPoints: triangle,
        shouldDimOutside: true,
    });
});

test("finishes touch drags when they end or are cancelled outside the canvas", () => {
    const target = new EventTarget();
    let completedDrags = 0;

    bindBoundaryDragEndListeners(target, () => {
        completedDrags += 1;
    });

    target.dispatchEvent(new Event("touchend"));
    target.dispatchEvent(new Event("touchcancel"));

    assert.equal(completedDrags, 2);
});
