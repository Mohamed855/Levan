<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SelectOptionsSourceContractTest extends TestCase
{
    #[Test]
    public function blade_and_javascript_do_not_define_literal_business_options(): void
    {
        $violations = [];

        foreach ($this->filesIn(resource_path('views'), 'blade.php') as $file) {
            $contents = file_get_contents($file);

            if (preg_match('/<option\b(?=[^>]*\bvalue\s*=\s*["\'][^"\'{}]+["\'])[^>]*>/i', $contents)) {
                $violations[] = $this->relativePath($file);
            }

            if (preg_match('/\:options\s*=\s*["\']\s*\[/i', $contents)) {
                $violations[] = $this->relativePath($file) . ' (inline options array)';
            }
        }

        foreach ($this->filesIn(resource_path('assets/js'), 'js') as $file) {
            $contents = file_get_contents($file);

            if (preg_match('/<option\b(?=[^>]*\bvalue\s*=\s*["\'][A-Za-z0-9_-]+["\'])[^>]*>/i', $contents)) {
                $violations[] = $this->relativePath($file);
            }
        }

        self::assertSame([], array_values(array_unique($violations)), implode(PHP_EOL, $violations));
    }

    /** @return list<string> */
    private function filesIn(string $directory, string $suffix): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path));
    }
}
