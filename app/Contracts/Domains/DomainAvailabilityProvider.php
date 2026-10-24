<?php

declare(strict_types=1);

namespace App\Contracts\Domains;

interface DomainAvailabilityProvider
{
    /** @return array<string, array{domain:string,available:bool,premium:bool}> */
    public function check(array $domains): array;
}
