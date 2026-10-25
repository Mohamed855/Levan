export function normalizeBackgroundPosition(value) {
  if (value === null || value === undefined || value === '') return 50;
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) return 50;
  return Math.max(0, Math.min(100, numeric));
}

export function backgroundStyle(background = {}) {
  const style = {
    backgroundColor: background.color || '#ffffff',
    backgroundImage: 'none',
    backgroundSize: 'cover',
    backgroundPosition: '50% 50%',
    backgroundRepeat: 'no-repeat',
  };
  const image = background.image_preview || background.image;
  if (image) {
    style.backgroundImage = `url("${String(image).replace(/"/g, '%22')}")`;
    style.backgroundPosition = `${normalizeBackgroundPosition(background.position_x)}% ${normalizeBackgroundPosition(background.position_y)}%`;
  }
  return style;
}

export function setPageBackground(page, values) {
  page.background ||= {};
  Object.assign(page.background, values);
  page.background.color ||= '#ffffff';
  page.background.fit = 'cover';
  page.background.position_x = normalizeBackgroundPosition(page.background.position_x);
  page.background.position_y = normalizeBackgroundPosition(page.background.position_y);
  return page.background;
}
