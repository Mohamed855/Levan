<?php

declare(strict_types=1);

namespace App\Services\Tenants\Settings;

use App\Models\Tenants\Settings\Settings;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class EstablishmentSigningAssets
{
    public function snapshot(): array
    {
        if (! tenancy()->initialized) {
            return [];
        }

        $settings = Settings::query()->first();
        $assets = [];
        foreach (['signature', 'stamp'] as $asset) {
            $contents = $this->contents($settings, $asset);
            if ($contents !== null) {
                $assets[$asset] = 'data:image/png;base64,'.base64_encode($contents);
            }
        }

        return $assets;
    }

    public function contents(?Settings $settings, string $asset): ?string
    {
        if (! in_array($asset, ['signature', 'stamp'], true)) {
            throw new InvalidArgumentException('Unknown establishment signing asset.');
        }
        $path = $settings?->getAttribute('establishment_'.$asset);
        if (! is_string($path) || ! preg_match('#^settings/signing-assets/[a-zA-Z0-9]+\.png$#D', $path)) {
            return null;
        }
        $disk = Storage::disk('tenant_private');
        if (! $disk->exists($path)) {
            return null;
        }
        $contents = $disk->get($path);
        if (! is_string($contents) || strlen($contents) > 10240 * 1024 || (@getimagesizefromstring($contents)['mime'] ?? null) !== 'image/png') {
            return null;
        }

        return $contents;
    }
}
