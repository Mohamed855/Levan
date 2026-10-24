<?php

declare(strict_types=1);

namespace App\Actions\Admin\Faqs;

use App\Models\Admin\Faq;
use Illuminate\Support\Facades\DB;

final class SaveFaqAction
{
    public function create(array $data): Faq
    {
        return DB::transaction(fn (): Faq => Faq::query()->create([
            'question'  => $data['question'],
            'answer'    => $data['answer'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]));
    }

    public function update(Faq $faq, array $data): Faq
    {
        return DB::transaction(function () use ($faq, $data): Faq {
            $faq->update([
                'question'  => $data['question'],
                'answer'    => $data['answer'],
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $faq->is_active,
            ]);

            return $faq->refresh();
        });
    }

    public function updateStatus(Faq $faq, bool $isActive): bool
    {
        return DB::transaction(function () use ($faq, $isActive): bool {
            $faq->update(['is_active' => $isActive]);

            return $isActive;
        });
    }

    public function delete(Faq $faq): void
    {
        DB::transaction(fn () => $faq->delete());
    }
}
