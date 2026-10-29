<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CentralClientsGoldenPatternTest extends TestCase
{
    public function test_clients_index_uses_the_five_card_and_filter_contract(): void
    {
        $root = dirname(__DIR__, 2);
        $index = file_get_contents($root.'/resources/views/admin/clients/index.blade.php');
        $dataTable = file_get_contents($root.'/app/DataTables/Admin/Clients/ClientsDataTable.php');

        self::assertSame(5, substr_count($index, '<x-ui.stat-card'));
        self::assertSame(5, substr_count($index, 'col="col-sm-6 col-xl"'));
        self::assertStringContainsString('data-filter-key="created_from"', $index);
        self::assertStringContainsString('data-filter-key="created_to"', $index);
        self::assertStringContainsString("'data' => 'checkbox'", $dataTable);
        self::assertStringContainsString("'className' => 'btn btn-export'", $dataTable);
        self::assertStringContainsString("'text' => 'حذف المحدد'", $dataTable);
        self::assertStringContainsString("'data' => 'subscription_status'", $dataTable);
    }

    public function test_client_forms_use_shared_form_partial_and_draft_contract(): void
    {
        $root = dirname(__DIR__, 2);
        $create = file_get_contents($root.'/resources/views/admin/clients/create.blade.php');
        $edit = file_get_contents($root.'/resources/views/admin/clients/edit.blade.php');
        $formPartial = file_get_contents($root.'/resources/views/admin/clients/partials/_form.blade.php');

        self::assertStringContainsString("admin.clients.partials._form", $create);
        self::assertStringContainsString("admin.clients.partials._form", $edit);
        self::assertStringContainsString("'is_edit' => false", $create);
        self::assertStringContainsString("'is_edit' => true", $edit);

        self::assertStringContainsString('data-form-validate', $formPartial);
        self::assertStringContainsString('data-draft-url', $formPartial);
        self::assertStringContainsString('data-draft-resource-key="admin.client-create"', $formPartial);
        self::assertStringContainsString('<div class="d-flex gap-2">', $formPartial);
        self::assertStringContainsString('<x-form.wizard.draft-button', $formPartial);
        self::assertStringNotContainsString(':disabled="true"', $formPartial);
    }
}
