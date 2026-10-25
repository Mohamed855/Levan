export const MIN_SIZE = 20;

export function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

export function clampRect(rect, canvas, minSize = MIN_SIZE) {
  const width = clamp(Number(rect.width) || minSize, minSize, canvas.width);
  const height = clamp(Number(rect.height) || minSize, minSize, canvas.height);
  return {
    ...rect,
    width,
    height,
    x: clamp(Number(rect.x) || 0, 0, Math.max(0, canvas.width - width)),
    y: clamp(Number(rect.y) || 0, 0, Math.max(0, canvas.height - height)),
  };
}

export function resizeRect(rect, handle, delta, canvas, options = {}) {
  const minSize = options.minSize || MIN_SIZE;
  const lockAspect = Boolean(options.lockAspect);
  const ratio = rect.width / Math.max(rect.height, 1);
  let next = { ...rect };
  const right = rect.x + rect.width;
  const bottom = rect.y + rect.height;

  if (handle.includes('e')) next.width = rect.width + delta.x;
  if (handle.includes('s')) next.height = rect.height + delta.y;
  if (handle.includes('w')) {
    next.x = rect.x + delta.x;
    next.width = rect.width - delta.x;
  }
  if (handle.includes('n')) {
    next.y = rect.y + delta.y;
    next.height = rect.height - delta.y;
  }

  if (lockAspect) {
    if (Math.abs(delta.x) >= Math.abs(delta.y)) {
      next.height = next.width / ratio;
      if (handle.includes('n')) next.y = bottom - next.height;
    } else {
      next.width = next.height * ratio;
      if (handle.includes('w')) next.x = right - next.width;
    }
  }

  if (next.width < minSize) {
    next.width = minSize;
    if (handle.includes('w')) next.x = right - minSize;
  }
  if (next.height < minSize) {
    next.height = minSize;
    if (handle.includes('n')) next.y = bottom - minSize;
  }
  return clampRect(next, canvas, minSize);
}

export function pointerDelta(start, point, scale = 1) {
  return { x: (point.x - start.x) / scale, y: (point.y - start.y) / scale };
}
