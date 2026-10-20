<?php

declare(strict_types=1);

namespace Tests\Unit;

use BackedEnum;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionEnum;

class TenantPersistedIdentifierContractTest extends TestCase
{
    public function test_tenant_backed_enums_use_non_arabic_persisted_values(): void
    {
        $enumFiles = $this->phpFilesIn('app/Enums/Tenants');

        foreach ($enumFiles as $file) {
            $class = 'App\\Enums\\Tenants\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen(dirname(__DIR__, 2).'/app/Enums/Tenants/')));

            if (! enum_exists($class)) {
                continue;
            }

            $reflection = new ReflectionEnum($class);
            if (! $reflection->isBacked()) {
                continue;
            }

            foreach ($class::cases() as $case) {
                $this->assertInstanceOf(BackedEnum::class, $case);
                $this->assertDoesNotMatchRegularExpression(
                    '/[\x{0600}-\x{06FF}]/u',
                    (string) $case->value,
                    $class.'::'.$case->name.' must keep its persisted value language-neutral.'
                );
            }
        }
    }

    public function test_tenant_blades_do_not_submit_literal_arabic_option_values(): void
    {
        $views = $this->filesIn('resources/views/tenants', 'blade.php');

        foreach ($views as $view) {
            $content = file_get_contents($view);

            $this->assertDoesNotMatchRegularExpression(
                '/<option\\b[^>]*\\bvalue\\s*=\\s*["\'][\x{0600}-\x{06FF}][^"\']*["\']/u',
                $content,
                $view.' must submit a stable value while rendering Arabic only as the label.'
            );
        }
    }

    /** @return list<string> */
    private function phpFilesIn(string $relativeDirectory): array
    {
        return $this->filesIn($relativeDirectory, 'php');
    }

    /** @return list<string> */
    private function filesIn(string $relativeDirectory, string $extension): array
    {
        $root = dirname(__DIR__, 2).'/'.$relativeDirectory;
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.'.$extension)) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
