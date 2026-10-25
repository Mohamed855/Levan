<?php

$families = [
    'cairo' => ['label' => 'Cairo', 'css_family' => 'Cairo'],
    'tajawal' => ['label' => 'Tajawal', 'css_family' => 'Tajawal'],
    'almarai' => ['label' => 'Almarai', 'css_family' => 'Almarai'],
    'alexandria' => ['label' => 'Alexandria', 'css_family' => 'Alexandria'],
    'ibm-plex-sans-arabic' => ['label' => 'IBM Plex Sans Arabic', 'css_family' => 'IBM Plex Sans Arabic'],
    'noto-sans-arabic' => ['label' => 'Noto Sans Arabic', 'css_family' => 'Noto Sans Arabic'],
    'noto-kufi-arabic' => ['label' => 'Noto Kufi Arabic', 'css_family' => 'Noto Kufi Arabic'],
    'noto-naskh-arabic' => ['label' => 'Noto Naskh Arabic', 'css_family' => 'Noto Naskh Arabic'],
    'amiri' => ['label' => 'Amiri', 'css_family' => 'Amiri'],
    'reem-kufi' => ['label' => 'Reem Kufi', 'css_family' => 'Reem Kufi'],
    'changa' => ['label' => 'Changa', 'css_family' => 'Changa'],
    'markazi-text' => ['label' => 'Markazi Text', 'css_family' => 'Markazi Text'],
    'mada' => ['label' => 'Mada', 'css_family' => 'Mada'],
    'aref-ruqaa' => ['label' => 'Aref Ruqaa', 'css_family' => 'Aref Ruqaa'],
];

foreach ($families as $slug => &$font) {
    $font['weights'] = [400, 700];
    $font['files'] = [
        400 => "resources/assets/fonts/auction-designer/{$slug}/400.woff2",
        700 => "resources/assets/fonts/auction-designer/{$slug}/700.woff2",
    ];
    $font['license'] = "resources/assets/fonts/auction-designer/licenses/{$slug}-OFL.txt";
}
unset($font);

return [
    'default' => 'almarai',
    'families' => $families,
];
