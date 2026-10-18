<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DataTableDetailsControlContractTest extends TestCase
{
    public function test_shared_datatable_configuration_does_not_add_a_first_column_details_control(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/DataTables/Abstract/AbstractDataTable.php');

        $this->assertStringNotContainsString("'className' => 'control'", $source);
        $this->assertStringNotContainsString("'targets' => 0", $source);
        $this->assertStringNotContainsString("'type' => 'column'", $source);
    }

    public function test_dashboard_sources_do_not_define_legacy_first_column_details_controls(): void
    {
        $root = dirname(__DIR__, 2);
        $sources = [
            $root.'/resources/js',
            $root.'/resources/assets/js',
            $root.'/app/DataTables',
        ];

        foreach ($sources as $source) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source));

            foreach ($files as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['js', 'php'], true)) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());

                $this->assertDoesNotMatchRegularExpression(
                    "/className\\s*[:=]\\s*['\"]control['\"]/",
                    $content,
                    "{$file->getPathname()} must not add a legacy details-control column.",
                );
                $this->assertDoesNotMatchRegularExpression(
                    "/type\\s*:\\s*['\"]column['\"]/",
                    $content,
                    "{$file->getPathname()} must not use the legacy responsive details column.",
                );
            }
        }
    }

    public function test_admin_and_tenant_datatables_do_not_define_blank_legacy_columns(): void
    {
        $root = dirname(__DIR__, 2).'/app/DataTables';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString(
                "'data' => ''",
                $content,
                "{$file->getPathname()} must not define an empty legacy DataTable column.",
            );
        }
    }

    public function test_mass_delete_tables_use_the_shared_checkbox_contract(): void
    {
        foreach ($this->dataTableFiles() as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $executableContent = $this->withoutComments($content);

            if (! str_contains($executableContent, 'btn-delete-selected')) {
                continue;
            }

            $this->assertStringContainsString("'data' => 'checkbox'", $executableContent, "{$file->getPathname()} is missing its checkbox column.");
            $this->assertStringContainsString('class="row-checkbox"', $executableContent, "{$file->getPathname()} is missing its row checkbox.");
            $this->assertStringContainsString("'className' => 'custom-checkbox'", $executableContent, "{$file->getPathname()} is missing the shared checkbox class.");
        }
    }

    public function test_status_toggle_tables_place_status_immediately_before_actions(): void
    {
        foreach ($this->dataTableFiles() as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'js-table-status-toggle')) {
                continue;
            }

            preg_match('/protected function getColumns\(\): array\s*\{\s*return \[(.*?)\];/s', $content, $matches);
            $this->assertNotEmpty($matches, "{$file->getPathname()} must expose getColumns().");

            preg_match_all("/'data'\s*=>\s*'([^']+)'/", $matches[1], $columns);
            $statusIndex = false;
            foreach (['status', 'is_active'] as $statusColumn) {
                $statusIndex = array_search($statusColumn, $columns[1], true);
                if ($statusIndex !== false) {
                    break;
                }
            }
            $this->assertNotFalse($statusIndex, "{$file->getPathname()} must expose a status column.");
            $this->assertContains($columns[1][$statusIndex + 1] ?? null, ['actions', 'action'], "{$file->getPathname()} must place status immediately before actions.");
        }
    }

    private function dataTableFiles(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/DataTables'));
    }

    private function withoutComments(string $content): string
    {
        return preg_replace('!//[^\r\n]*|/\*.*?\*/!s', '', $content) ?? $content;
    }
}
