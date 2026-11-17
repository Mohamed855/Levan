<?php

declare(strict_types=1);

return static function (string $scenario): array {
    $snapshot = (require __DIR__.'/asir-native.php')('sparse');
    $asset = static fn (string $file): string => 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/asir/'.$file)));
    $snapshot['auction']['name'] = 'مزاد ريف الرياض الزراعي والسكني';
    $snapshot['branding'] = ['company_logo' => $asset('static-3ba2f8c81664a1845f61.png'), 'issuing_entity_logo' => $asset('static-ebbe1f2e0fdcb1dceaaf.png')];
    $snapshot['marketing']['contacts'] = ['phone' => '0551234567', 'unified_phone' => '920001234', 'social' => 'https://example.com/auctions'];
    switch ($scenario) {
        case 'name-99':
            $snapshot['auction']['name'] = 'مزاد ريف الرياض الزراعي والسكني والتجاري والصناعي للأصول العقارية المتنوعة في مدينة الرياض وضواحيها';
            break;
        case 'name-255-arabic':
            $snapshot['auction']['name'] = mb_substr(str_repeat('مزاد ريف الرياض الزراعي والسكني ', 12), 0, 255);
            break;
        case 'name-255-latin':
            $snapshot['auction']['name'] = str_repeat('W', 255);
            break;
        case 'contacts-long':
            $snapshot['marketing']['contacts'] = ['phone' => '123456789012345678901234567890', 'unified_phone' => '+966 55 123 4567', 'social' => 'https://example.com/'.str_repeat('a', 235)];
            break;
        case 'phones-30':
            $snapshot['marketing']['contacts']['phone'] = str_repeat('9', 30);
            $snapshot['marketing']['contacts']['unified_phone'] = str_repeat('8', 30);
            break;
        case 'social-255':
            $snapshot['marketing']['contacts']['social'] = 'https://example.com/'.str_repeat('a', 235);
            break;
        case 'contacts-unicode':
            $snapshot['marketing']['contacts'] = ['phone' => str_repeat('⑩', 30), 'unified_phone' => str_repeat('⑩', 30), 'social' => 'https://example.com/'.str_repeat('W', 235)];
            break;
        case 'social-multiline':
            $snapshot['marketing']['contacts']['social'] = str_repeat("\n", 200).'https://example.com/auctions';
            break;
        case 'contacts-empty':
            $snapshot['marketing']['contacts'] = ['phone' => null, 'unified_phone' => '', 'social' => null];
            break;
        case 'logos-empty':
            $snapshot['branding'] = ['company_logo' => null, 'issuing_entity_logo' => null];
            break;
        case 'legacy':
            unset($snapshot['branding']);
            $snapshot['auction']['name'] = 'واجهة عسير';
            break;
        case 'white-logo':
        case 'dark-logo':
        case 'tall-logo':
            $tall = $scenario === 'tall-logo';
            $image = imagecreatetruecolor($tall ? 100 : 800, $tall ? 800 : 100);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
            $color = $scenario === 'white-logo' ? 255 : 17;
            imagefilledrectangle($image, 10, 10, imagesx($image) - 11, imagesy($image) - 11, imagecolorallocate($image, $color, $color, $color));
            ob_start();
            imagepng($image);
            $uri = 'data:image/png;base64,'.base64_encode(ob_get_clean());
            imagedestroy($image);
            $snapshot['branding'] = array_fill_keys(['company_logo', 'issuing_entity_logo'], $uri);
            break;
        case 'normal':
            break;
        default:
            throw new InvalidArgumentException('Unknown Asir cover proof scenario.');
    }

    return $snapshot;
};
