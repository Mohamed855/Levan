<?php

declare(strict_types=1);

namespace App\Services\Admin\AuctionTemplates;

use InvalidArgumentException;

final class AuctionTemplateFieldDefinitionService
{
    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @return array<int, array{key: string, page: int, x: float, y: float, width: float, height: float}>
     */
    public function normalize(array $definitions): array
    {
        $normalized = [];

        foreach ($definitions as $definition) {
            $key = trim((string) ($definition['key'] ?? ''));
            $page = (int) ($definition['page'] ?? 1);
            $x = $this->coordinate($definition, 'x');
            $y = $this->coordinate($definition, 'y');
            $width = $this->coordinate($definition, 'width');
            $height = $this->coordinate($definition, 'height');

            if ($key === '' || $page < 1 || $width <= 0 || $height <= 0 || $x + $width > 1 || $y + $height > 1) {
                throw new InvalidArgumentException('Invalid template field definition.');
            }

            $normalized[] = compact('key', 'page', 'x', 'y', 'width', 'height');
        }

        return array_values($normalized);
    }

    /** @param array<string, mixed> $definition */
    private function coordinate(array $definition, string $key): float
    {
        $value = filter_var($definition[$key] ?? null, FILTER_VALIDATE_FLOAT);

        if ($value === false || $value < 0 || $value > 1) {
            throw new InvalidArgumentException('Invalid template field coordinate.');
        }

        return round((float) $value, 6);
    }
}
