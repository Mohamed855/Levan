<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

final class AuctionReportAcceptanceReceipt
{
    public function documents(array $receipt): array
    {
        $documents = [];
        foreach ($receipt['auctions'] ?? [] as $item) {
            if (empty($item['id']) || empty($item['generation']) || empty($item['revision']) || !preg_match('/^[a-f0-9]{64}$/D', $item['snapshot_sha256'] ?? '')) {
                throw new InvalidArgumentException('Acceptance requires generation, revision and snapshot identity for every auction.');
            }
            foreach (['outputs', 'report'] as $type) {
                $documents[] = array_replace($item, ['type' => $type]);
            }
        }
        if ($documents === []) {
            throw new InvalidArgumentException('The acceptance receipt contains no auctions.');
        }

        return $documents;
    }
}
