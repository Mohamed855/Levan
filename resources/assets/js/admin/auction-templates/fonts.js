export const DEFAULT_FONT = 'almarai';
export const DESIGNER_FONTS = [
  ['cairo', 'Cairo'], ['tajawal', 'Tajawal'], ['almarai', 'Almarai'],
  ['alexandria', 'Alexandria'], ['ibm-plex-sans-arabic', 'IBM Plex Sans Arabic'],
  ['noto-sans-arabic', 'Noto Sans Arabic'], ['noto-kufi-arabic', 'Noto Kufi Arabic'],
  ['noto-naskh-arabic', 'Noto Naskh Arabic'], ['amiri', 'Amiri'], ['reem-kufi', 'Reem Kufi'],
  ['changa', 'Changa'], ['markazi-text', 'Markazi Text'], ['mada', 'Mada'], ['aref-ruqaa', 'Aref Ruqaa'],
];

export function normalizeFont(font) {
  return DESIGNER_FONTS.some(([value]) => value === font) ? font : DEFAULT_FONT;
}
