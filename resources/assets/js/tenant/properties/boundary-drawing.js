export function getBoundaryPointerAction({
    points,
    isClosed,
    nearPointIndex,
    point,
}) {
    if (!isClosed && points.length >= 3 && nearPointIndex === 0) {
        return { type: "close" };
    }

    if (nearPointIndex >= 0) {
        return { type: "drag", pointIndex: nearPointIndex };
    }

    if (isClosed) {
        return { type: "none" };
    }

    return { type: "add", point, previewPoint: point };
}

export function getBoundaryRenderState(points, isClosed, previewPoint) {
    return {
        pathPoints:
            !isClosed && previewPoint ? [...points, previewPoint] : points,
        shouldDimOutside: isClosed && points.length >= 3,
    };
}

export function bindBoundaryDragEndListeners(target, finishDragging) {
    target.addEventListener("touchend", finishDragging);
    target.addEventListener("touchcancel", finishDragging);
}
