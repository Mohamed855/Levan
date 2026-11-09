<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Forms;

use App\Models\Tenants\Auctions\Auction;
use DomainException;
use InvalidArgumentException;

final class AuctionRegulatoryFormDataBuilder
{
    public function build(Auction $auction, string $type): array
    {
        $this->assertType($type);
        $request = $auction->establishmentRequest;
        if (! $request?->exists || ! is_array($request->data) || $request->data === []) {
            throw new DomainException('يجب حفظ طلب إقامة المزاد أولاً.');
        }

        $data = $request->data;
        if ($type === 'establishment') {
            return $data;
        }
        if (! $request->isApproved() || ! $auction->closing?->exists || ! $auction->closing->saved_at) {
            throw new DomainException('يجب اعتماد طلب إقامة المزاد وحفظ الإقفال أولاً.');
        }

        $properties = $auction->properties->keyBy('id');
        $rows = $data['properties'] ?? [];
        $savedIds = array_map(static fn (array $row): string => (string) ($row['property_id'] ?? ''), $rows);
        if ($properties->isEmpty() || count($savedIds) !== count(array_unique($savedIds)) ||
            count($rows) !== $properties->count() || array_diff($savedIds, array_map('strval', $properties->keys()->all())) !== []) {
            throw new DomainException('عقارات الإقفال لا تطابق طلب إقامة المزاد المعتمد.');
        }

        foreach ($rows as &$row) {
            $result = $properties->get($row['property_id'])->pivot;
            if (! in_array($result?->result_status, ['sold', 'unsold'], true)) {
                throw new DomainException('يجب حفظ نتيجة كل عقار قبل إعداد نموذج الإقفال.');
            }
            $sold = $result->result_status === 'sold';
            $amount = (string) ($result->sale_price ?? '');
            if ($sold && (blank($result->buyer_name) || ! preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount))) {
                throw new DomainException('بيانات الترسية غير مكتملة.');
            }
            if (! $sold && blank($result->unsold_reason)) {
                throw new DomainException('سبب عدم الترسية مطلوب.');
            }
            $row['result'] = $sold ? 'awarded' : 'not_awarded';
            $row['awardee'] = $sold ? (string) $result->buyer_name : '';
            $row['award_value'] = $sold ? $amount : '';
            $row['unawarded_reason'] = $sold ? '' : (string) $result->unsold_reason;
        }
        unset($row);
        $data['properties'] = $rows;
        $data['closing'] = array_replace($data['closing'] ?? [], [
            'approval_number' => (string) $request->approval_number,
            'approval_date' => $request->approved_at->format('Y/m/d'),
            'date' => $auction->closing->closing_date?->format('Y/m/d') ?? '',
        ]);

        return $data;
    }

    public function assertType(string $type): void
    {
        if (! in_array($type, ['establishment', 'closing'], true)) {
            throw new InvalidArgumentException('Unsupported regulatory form type.');
        }
    }
}
