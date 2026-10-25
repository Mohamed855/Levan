export function createDesignState(schema, type, createId, getCanvas) {
  const normalized = schema && typeof schema === 'object' ? structuredClone(schema) : {};
  normalized.canvas ||= getCanvas(type);
  normalized.pages = Array.isArray(normalized.pages) && normalized.pages.length
    ? normalized.pages
    : [{ id: createId(), background: { color: '#ffffff', image: null, fit: 'cover' }, elements: [] }];
  normalized.pages.forEach((page) => {
    page.background ||= {};
    page.background.color ||= '#ffffff';
    page.background.fit ||= 'cover';
    page.elements ||= [];
  });
  return { schema: normalized, pageIndex: 0, selectedId: null };
}

export function replaceSchema(state, schema, createId, getCanvas, type) {
  const next = createDesignState(schema, type, createId, getCanvas);
  state.schema = next.schema;
  state.pageIndex = 0;
  state.selectedId = null;
  return state;
}

export function activePage(state) {
  state.pageIndex = Math.max(0, Math.min(state.pageIndex, state.schema.pages.length - 1));
  return state.schema.pages[state.pageIndex];
}
