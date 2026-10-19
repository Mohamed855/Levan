<?php

declare(strict_types=1);

namespace App\Services\Admin\Faqs;

use App\Models\Admin\Faq;
use Illuminate\Support\Facades\DB;

class FaqService
{
    public function create(array $data): Faq
    {
        return DB::transaction(function () use ($data) {
            return Faq::create($data);
        });
    }

    public function update(Faq $faq, array $data): Faq
    {
        return DB::transaction(function () use ($faq, $data) {
            $faq->update($data);

            return $faq;
        });
    }

    public function delete(Faq $faq): bool
    {
        return DB::transaction(function () use ($faq) {
            return (bool) $faq->delete();
        });
    }
}
