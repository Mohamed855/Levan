<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Admin\Bank\Bank;
use Tests\TestCase;

class BankIconFallbackTest extends TestCase
{
    public function test_bank_icon_url_resolves_null_when_icon_is_empty(): void
    {
        $bank = new Bank;
        $bank->icon = null;
        $this->assertNull($bank->icon_url);

        $bank->icon = '';
        $this->assertNull($bank->icon_url);
    }

    public function test_bank_icon_url_returns_full_url_when_already_url(): void
    {
        $bank = new Bank;
        $bank->icon = 'https://example.com/logo.png';
        $this->assertSame('https://example.com/logo.png', $bank->icon_url);

        $bank->icon = 'data:image/png;base64,iVBORw0KGgo=';
        $this->assertSame('data:image/png;base64,iVBORw0KGgo=', $bank->icon_url);
    }

    public function test_bank_icon_url_prefixes_storage_when_relative_path(): void
    {
        $bank = new Bank;
        $bank->icon = 'banks/icons/bank_albilad.png';
        $this->assertStringContainsString('storage/banks/icons/bank_albilad.png', (string) $bank->icon_url);
    }
}
