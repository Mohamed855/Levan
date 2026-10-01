<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EnglishAuthorizationKeysTest extends TestCase
{
    public function test_blade_authorization_checks_use_english_keys(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = preg_replace('/\{\{--.*?--\}\}/s', '', File::get($file->getPathname()));

            $this->assertDoesNotMatchRegularExpression(
                "/^\\s*@can\\('[^']*\\p{Arabic}[^']*'\\)/mu",
                $contents,
                "Blade authorization key must be English: {$file->getPathname()}",
            );
        }
    }

    public function test_php_authorization_checks_use_english_keys(): void
    {
        foreach ([app_path(), base_path('routes')] as $path) {
            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $this->sourceWithoutComments(File::get($file->getPathname()));

                $this->assertDoesNotMatchRegularExpression(
                    "/(?:->can\\(\\s*['\"]|middleware\\(\\s*['\"]can:)[^'\"]*\\p{Arabic}/u",
                    $contents,
                    "PHP authorization key must be English: {$file->getPathname()}",
                );
            }
        }
    }

    private function sourceWithoutComments(string $source): string
    {
        return collect(token_get_all($source))
            ->reject(fn (array|string $token) => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn (array|string $token) => is_array($token) ? $token[1] : $token)
            ->implode('');
    }
}
