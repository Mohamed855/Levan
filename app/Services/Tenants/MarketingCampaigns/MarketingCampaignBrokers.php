<?php

declare(strict_types=1);

namespace App\Services\Tenants\MarketingCampaigns;

use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use Illuminate\Validation\ValidationException;

final class MarketingCampaignBrokers
{
    /** Existing files remain visible when editing campaigns saved before repeatable brokers. */
    public function rows(?MarketingCampaign $campaign): array
    {
        $metrics = $campaign?->metrics ?? [];
        $rows = $metrics['brokers'] ?? [];
        $files = $campaign?->evidence ?? collect();
        $used = [];

        foreach ($rows as &$row) {
            $file = $files->first(fn ($file): bool => ($file->report_metadata['broker_key'] ?? null) === $row['key']);
            $row['evidence_id'] = $file?->id;
            if ($file) {
                $used[] = $file->id;
            }
        }
        unset($row);

        $legacyNames = array_values(array_filter([$metrics['display_name'] ?? null, $metrics['broker_second_name'] ?? null]));
        foreach ($files->whereNotIn('id', $used)->values() as $index => $file) {
            $rows[] = [
                'key' => 'legacy-'.$file->id,
                'name' => filled($file->report_metadata['display_name'] ?? null) ? $file->report_metadata['display_name'] : ($legacyNames[min($index, count($legacyNames) - 1)] ?? ''),
                'evidence_id' => $file->id,
            ];
        }
        if (! isset($metrics['brokers'])) {
            foreach ($legacyNames as $index => $name) {
                if (! in_array($name, array_column($rows, 'name'), true)) {
                    $rows[] = ['key' => 'legacy-name-'.$index, 'name' => $name, 'evidence_id' => null];
                }
            }
        }

        return $rows ?: [['key' => 'broker-1', 'name' => '', 'evidence_id' => null]];
    }

    /** Build a campaign-scoped plan before any evidence is changed or uploaded. */
    public function plan(?MarketingCampaign $campaign, array $rows, array $files, string $prefix = 'metrics.brokers'): array
    {
        $existingFiles = $campaign?->evidence->keyBy('id') ?? collect();
        $plan = ['brokers' => [], 'existing' => [], 'uploads' => [], 'remove_ids' => []];
        $keys = [];
        $evidenceIds = [];
        foreach (array_values($rows) as $index => $row) {
            $key = $row['key'];
            $id = empty($row['evidence_id']) ? null : (int) $row['evidence_id'];
            if (in_array($key, $keys, true) || ($id && (! $existingFiles->has($id) || in_array($id, $evidenceIds, true)))) {
                throw ValidationException::withMessages([$prefix.'.'.$index.'.evidence_id' => 'المرفق المحدد لا يخص هذا الوسيط أو تم استخدامه أكثر من مرة.']);
            }
            if (! isset($files[$key]) && ! $id) {
                throw ValidationException::withMessages([$prefix.'.'.$index.'.name' => 'أرفق ملفًا لهذا الوسيط.']);
            }
            $keys[] = $key;
            $evidenceIds[] = $id;
            $name = trim($row['name']);
            $plan['brokers'][] = ['key' => $key, 'name' => $name];
            $metadata = array_replace($id ? ($existingFiles[$id]->report_metadata ?? []) : [], [
                'channel' => 'brokers', 'broker_key' => $key, 'display_name' => $name,
            ]);
            $entry = ['metadata' => $metadata, 'order' => $index];
            if (isset($files[$key])) {
                $plan['uploads'][$key] = $entry;
            } else {
                $plan['existing'][$id] = $entry;
            }
        }
        if (array_diff(array_keys($files), $keys) !== []) {
            throw ValidationException::withMessages([$prefix => 'اربط كل ملف باسم وسيط.']);
        }
        $plan['remove_ids'] = $existingFiles->keys()->diff(array_keys($plan['existing']))->values()->all();

        return $plan;
    }
}
