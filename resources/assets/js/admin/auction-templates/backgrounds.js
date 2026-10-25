export function backgroundStyle(background = {}) {
  const style = { backgroundColor: background.color || '#ffffff' };
  const image = background.image_preview || background.image;
  if (image) {
    style.backgroundImage = `url("${String(image).replace(/"/g, '%22')}")`;
    style.backgroundSize = background.fit || 'cover';
    style.backgroundPosition = 'center';
    style.backgroundRepeat = 'no-repeat';
  }
  return style;
}

export function setPageBackground(page, values) {
  page.background ||= {};
  Object.assign(page.background, values);
  page.background.color ||= '#ffffff';
  page.background.fit ||= 'cover';
  return page.background;
}
