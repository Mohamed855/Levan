<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class AuctionBrokersManager
{
    /**
     * Prepare broker rows for UI rendering (Edit/Create).
     *
     * @return list<array{key: string, name: string, evidence_id: int|null}>
     */
    public function rows(?Auction $auction): array
    {
        if (! $auction?->exists) {
            return [];
        }

        $marketing = is_array($auction->marketing_data) ? $auction->marketing_data : [];
        $rows = is_array($marketing['brokers'] ?? null) ? $marketing['brokers'] : [];
        $files = $auction->attachments->where('report_section', 'brokers')->values();
        $used = [];
        $result = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = is_string($row['key'] ?? null) && $row['key'] !== '' ? $row['key'] : 'broker-'.($index + 1);
            $file = $files->first(static fn ($attachment): bool => ($attachment->report_metadata['broker_key'] ?? null) === $key);
            if ($file) {
                $used[] = $file->id;
            }
            $result[] = [
                'key' => $key,
                'name' => trim((string) ($row['name'] ?? '')),
                'evidence_id' => $file?->id,
            ];
        }

        foreach ($files->whereNotIn('id', $used)->values() as $file) {
            $result[] = [
                'key' => 'legacy-'.$file->id,
                'name' => trim((string) ($file->report_metadata['display_name'] ?? '')),
                'evidence_id' => $file->id,
            ];
        }

        return $result;
    }

    /**
     * Validate and plan broker persistence & file operations.
     *
     * @param  array<int|string, mixed>  $rawRows
     * @param  array<string, UploadedFile|null>  $files
     * @return array{brokers: list<array{key: string, name: string}>, existing: array<int, array{metadata: array<string, mixed>, order: int}>, uploads: array<string, array{file: UploadedFile, metadata: array<string, mixed>, order: int}>, remove_ids: list<int>}
     */
    public function plan(?Auction $auction, array $rawRows, array $files, string $prefix = 'marketing.brokers'): array
    {
        $existingFiles = $auction?->exists
            ? $auction->attachments()->where('report_section', 'brokers')->get()->keyBy('id')
            : collect();

        $plan = ['brokers' => [], 'existing' => [], 'uploads' => [], 'remove_ids' => []];
        $keys = [];
        $evidenceIds = [];

        foreach (array_values($rawRows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $id = empty($row['evidence_id']) ? null : (int) $row['evidence_id'];
            $uploaded = $key !== '' && ($files[$key] ?? null) instanceof UploadedFile ? $files[$key] : null;

            // Skip completely empty placeholder rows (since Step 4 is optional)
            if ($name === '' && $id === null && $uploaded === null) {
                continue;
            }

            if ($key === '' || ! preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $key) || in_array($key, $keys, true)) {
                throw ValidationException::withMessages([$prefix.'.'.$index.'.key' => 'معرف الوسيط غير صالح أو مكرر.']);
            }
            if ($name === '') {
                throw ValidationException::withMessages([$prefix.'.'.$index.'.name' => 'اسم الوسيط مطلوب عند إرفاق ملف له.']);
            }
            if ($id !== null && (! $existingFiles->has($id) || in_array($id, $evidenceIds, true))) {
                throw ValidationException::withMessages([$prefix.'.'.$index.'.evidence_id' => 'المرفق المحدد لا يخص هذا المزاد أو تم استخدامه أكثر من مرة.']);
            }
            if ($uploaded === null && $id === null) {
                throw ValidationException::withMessages(['broker_evidence.'.$key => 'أرفق ملف عقد الوسيط لهذا الوسيط.']);
            }

            $keys[] = $key;
            if ($id !== null) {
                $evidenceIds[] = $id;
            }

            $plan['brokers'][] = ['key' => $key, 'name' => $name];
            $baseMeta = $id !== null ? (array) ($existingFiles[$id]->report_metadata ?? []) : [];
            $metadata = array_replace($baseMeta, [
                'channel' => 'brokers',
                'broker_key' => $key,
                'display_name' => $name,
            ]);

            if ($uploaded !== null) {
                $plan['uploads'][$key] = [
                    'file' => $uploaded,
                    'metadata' => array_replace($metadata, [
                        'kind' => $uploaded->getMimeType() === 'application/pdf' ? 'document' : 'screenshot',
                    ]),
                    'order' => count($plan['brokers']) - 1,
                ];
            } else {
                $plan['existing'][$id] = [
                    'metadata' => $metadata,
                    'order' => count($plan['brokers']) - 1,
                ];
            }
        }

        $uploadedKeys = array_keys(array_filter($files, static fn (mixed $file): bool => $file instanceof UploadedFile));
        if (array_diff($uploadedKeys, $keys) !== []) {
            throw ValidationException::withMessages([$prefix => 'اربط كل ملف وسيط باسم وسيط صالح.']);
        }

        $plan['remove_ids'] = $existingFiles->keys()->diff(array_keys($plan['existing']))->map(static fn (mixed $id): int => (int) $id)->values()->all();

        return $plan;
    }
}
