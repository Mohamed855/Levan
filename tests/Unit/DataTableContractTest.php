<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DataTableContractTest extends TestCase
{
    public function test_shared_layout_loads_the_single_status_toggle_handler(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/shared/layout/scripts.blade.php');
        $handler = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/js/shared/table-status-toggle.js');
        $feedback = file_get_contents(dirname(__DIR__, 2) . '/resources/js/shared/datatable-feedback.js');

        $this->assertStringContainsString("'resources/assets/js/shared/table-status-toggle.js'", $layout);
        $this->assertStringContainsString("'resources/js/shared/datatable-feedback.js'", $layout);
        $this->assertStringContainsString("\ninitializeTableStatusToggles();", $handler);
        $this->assertStringContainsString('response?.is_active ?? response?.status ?? response?.new_status', $handler);
        $this->assertStringContainsString('processing.dt.sharedDataTableFeedback', $feedback);
        $this->assertStringContainsString('error.dt.sharedDataTableFeedback', $feedback);
    }

    public function test_status_toggle_columns_precede_actions_in_every_active_data_table(): void
    {
        $dataTablesPath = dirname(__DIR__, 2) . '/app/DataTables';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataTablesPath));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'js-table-status-toggle')) {
                continue;
            }

            $columns = $this->columnNames($content, $file->getPathname());
            $statusIndex = array_search('status', $columns, true);

            if ($statusIndex === false) {
                $statusIndex = array_search('is_active', $columns, true);
            }

            $this->assertNotFalse($statusIndex, "{$file->getPathname()} must define a status column for its toggle.");
            $this->assertContains(
                $columns[$statusIndex + 1] ?? null,
                ['action', 'actions'],
                "{$file->getPathname()} must place the actions column immediately after its status column.",
            );
        }
    }

    public function test_status_toggle_endpoints_return_safe_messages(): void
    {
        $controllersPath = dirname(__DIR__, 2) . '/app/Http/Controllers';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersPath));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            foreach ($this->toggleMethodBodies(file_get_contents($file->getPathname())) as $method => $body) {
                $prefix = sprintf('%s::%s', $file->getPathname(), $method);

                $this->assertStringContainsString("'success'", $body, "{$prefix} must return a success flag.");
                $this->assertStringContainsString("'message'", $body, "{$prefix} must return a user-facing message.");
                $this->assertTrue(
                    str_contains($body, "'status'") || str_contains($body, "'is_active'"),
                    "{$prefix} must return the resulting status.",
                );
                $this->assertDoesNotMatchRegularExpression(
                    "/['\"]message['\"]\\s*=>[^;\\n]*getMessage\\(/",
                    $body,
                    "{$prefix} must not expose an exception message to the client.",
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function columnNames(string $content, string $path): array
    {
        $start = strpos($content, 'function getColumns');
        $end = strpos($content, 'function addCustomColumns', $start === false ? 0 : $start);

        $this->assertNotFalse($start, "{$path} must define getColumns().");
        $this->assertNotFalse($end, "{$path} must define addCustomColumns().");

        $columnsBlock = substr($content, $start, $end - $start);
        $columnsBlock = preg_replace('/^\s*\/\/.*$/m', '', $columnsBlock) ?? $columnsBlock;

        preg_match_all("/['\"]data['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $columnsBlock, $matches);

        return $matches[1];
    }

    /**
     * @return array<string, string>
     */
    private function toggleMethodBodies(string $content): array
    {
        preg_match_all('/function\\s+(toggleStatus(?:_trait)?|toggleActive)\\s*\\(/', $content, $matches, PREG_OFFSET_CAPTURE);

        $bodies = [];

        foreach ($matches[1] as [$method, $offset]) {
            $openingBrace = strpos($content, '{', $offset);
            $depth = 0;

            for ($position = $openingBrace; $position < strlen($content); $position++) {
                if ($content[$position] === '{') {
                    $depth++;
                } elseif ($content[$position] === '}' && --$depth === 0) {
                    $bodies[$method] = substr($content, $openingBrace, $position - $openingBrace + 1);
                    break;
                }
            }
        }

        return $bodies;
    }
}
