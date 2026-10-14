<?php

declare(strict_types=1);

namespace App\Data\Tenants\Versions;

use App\Enums\Admin\Version\VersionStage;
use Carbon\CarbonInterface;

final readonly class VersionReleaseData
{
    public function __construct(
        public int $id,
        public string $version,
        public ?string $changelog,
        public VersionStage $stage,
        public ?CarbonInterface $released_at,
    ) {}
}
