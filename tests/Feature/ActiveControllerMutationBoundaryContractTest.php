<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ActiveControllerMutationBoundaryContractTest extends TestCase
{
    public function test_active_admin_and_tenant_controllers_do_not_persist_models_directly(): void
    {
        $controllers = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $this->isAdminOrTenantControllerRoute($route->getActionName()))
            ->map(fn ($route) => explode('@', $route->getActionName(), 2)[0])
            ->unique();

        foreach ($controllers as $controller) {
            $source = $this->sourceWithoutComments(
                File::get((new ReflectionClass($controller))->getFileName()),
            );

            $this->assertDoesNotMatchRegularExpression(
                $this->directModelPersistencePattern($source),
                $source,
                "Active controller {$controller} must delegate persistence to an Action or Service.",
            );
        }
    }

    private function isAdminOrTenantControllerRoute(string $action): bool
    {
        return str_contains($action, '@')
            && (str_starts_with($action, 'App\\Http\\Controllers\\Admin\\')
                || str_starts_with($action, 'App\\Http\\Controllers\\Tenants\\'));
    }

    private function sourceWithoutComments(string $source): string
    {
        return collect(token_get_all($source))
            ->reject(fn (array|string $token) => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn (array|string $token) => is_array($token) ? $token[1] : $token)
            ->implode('');
    }

    private function directModelPersistencePattern(string $source): string
    {
        preg_match_all('/^use\\s+App\\\\Models\\\\[^;\\s]+(?:\\s+as\\s+(\\w+))?;/m', $source, $matches, PREG_SET_ORDER);

        $modelReferences = collect($matches)
            ->map(function (array $match): array {
                $import = preg_replace('/^use\\s+|(?:\\s+as\\s+\\w+)?;$/', '', $match[0]);
                $class = ($match[1] ?? '') ?: class_basename($import);

                return [$class, '$'.lcfirst($class)];
            })
            ->flatten()
            ->map(fn (string $reference) => preg_quote($reference, '/'))
            ->implode('|');

        return $modelReferences === ''
            ? '/(?!)/'
            : '/(?:'.$modelReferences.')(?:->|::)(?:create|save|update|delete|restore|forceDelete)\\s*\\(/';
    }
}
