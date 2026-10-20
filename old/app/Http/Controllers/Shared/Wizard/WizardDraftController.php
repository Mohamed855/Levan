<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared\Wizard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Wizard\DiscardWizardDraftRequest;
use App\Http\Requests\Shared\Wizard\RemoveWizardDraftFileRequest;
use App\Http\Requests\Shared\Wizard\StoreWizardDraftRequest;
use App\Http\Requests\Shared\Wizard\ValidateWizardStepRequest;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WizardDraftController extends Controller
{
    public function __construct(private readonly WizardDraftService $drafts) {}

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_key' => ['required', 'string', 'max:255'],
        ]);
        $draft = $this->drafts->find($request, $validated['resource_key']);

        return response()->json([
            'data' => $draft?->draft_data ?? [],
            'draft_id' => $draft?->getKey(),
            'step' => $draft?->last_step,
            'files' => $this->fileManifest($draft?->draft_files ?? []),
        ]);
    }

    public function store(StoreWizardDraftRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $draft = $this->drafts->save($request, $validated['resource_key'], (int) $validated['step']);

        return response()->json([
            'success' => true,
            'draft_id' => $draft->getKey(),
            'message' => 'تم حفظ المسودة بنجاح.',
        ]);
    }

    public function destroy(DiscardWizardDraftRequest $request): JsonResponse
    {
        $this->drafts->discard($request, $request->validated('resource_key'));

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المسودة.',
        ]);
    }

    public function destroyFile(RemoveWizardDraftFileRequest $request): JsonResponse
    {
        $removed = $this->drafts->removeFile(
            $request,
            $request->validated('resource_key'),
            $request->validated('file_key'),
        );

        return response()->json([
            'success' => $removed,
            'message' => $removed ? 'تم حذف الملف من المسودة.' : 'الملف غير موجود في المسودة.',
        ], $removed ? 200 : 404);
    }

    public function validateStep(ValidateWizardStepRequest $request): JsonResponse
    {
        $targetMethod = $request->validated('target_method');
        if (! $targetMethod) {
            $targetMethod = $request->input('_method') ?: ($request->isMethod('POST') ? 'POST' : $request->method());
        }
        $targetMethod = strtoupper((string) $targetMethod);
        if (! in_array($targetMethod, ['POST', 'PUT', 'PATCH'], true)) {
            $targetMethod = 'POST';
        }

        $targetRoute = $this->resolveFormRoute(
            $request->validated('target_path'),
            $targetMethod,
            $request,
            $request->route()?->defaults['shared_form_validation_actions'] ?? [],
        );
        $formRequest = $this->makeResourceRequest($request, $targetRoute, $targetMethod);

        $authorized = (function (): bool {
            return $this->passesAuthorization();
        })->call($formRequest);
        abort_unless($authorized, 403);
        $this->prepareForValidation($formRequest);

        $rules = $this->rulesForFields($formRequest->rules(), $request->validated('fields'));
        $validator = Validator::make(
            $this->validationData($formRequest),
            $rules,
            method_exists($formRequest, 'messages') ? $formRequest->messages() : [],
            method_exists($formRequest, 'attributes') ? $formRequest->attributes() : [],
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * @param  list<string>  $allowedActions
     */
    private function resolveFormRoute(string $targetPath, string $targetMethod, Request $sourceRequest, array $allowedActions = []): Route
    {
        $path = parse_url($targetPath, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/')) {
            throw new NotFoundHttpException;
        }

        try {
            $routeRequest = Request::create($path, $targetMethod, [], [], [], [
                'HTTP_HOST' => $sourceRequest->getHost(),
                'HTTPS' => $sourceRequest->isSecure() ? 'on' : 'off',
            ]);
            $route = app('router')->getRoutes()->match($routeRequest);
            $route->bind($routeRequest);
            app('router')->substituteBindings($route);
        } catch (\Throwable) {
            throw new NotFoundHttpException;
        }

        $action = $route->getActionMethod();
        $allowsSharedValidation = (bool) ($route->defaults['shared_form_validation'] ?? false);
        abort_unless(in_array($action, ['store', 'update'], true) || $allowsSharedValidation, 404);

        $normalizedRouteAction = ltrim($route->getActionName(), '\\');
        $normalizedAllowedActions = array_map(
            static fn (string $allowedAction): string => ltrim($allowedAction, '\\'),
            $allowedActions,
        );

        abort_if($normalizedAllowedActions !== [] && ! in_array($normalizedRouteAction, $normalizedAllowedActions, true), 404);

        return $route;
    }

    private function makeResourceRequest(Request $request, Route $route, string $targetMethod = 'POST'): FormRequest
    {
        [$controller, $method] = explode('@', ltrim($route->getActionName(), '\\'));
        $reflection = new \ReflectionMethod($controller, $method);

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $class = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($class && is_a($class, FormRequest::class, true)) {
                /** @var FormRequest $formRequest */
                // Preserve the authenticated user, session, files and input from the
                // validation request. createFromBase() drops Laravel's user resolver,
                // causing resource FormRequest::authorize() to fail for valid users.
                $formRequest = $class::createFrom($request);
                $formRequest->setContainer(app());
                $formRequest->setRedirector(app('redirect'));
                $formRequest->setRouteResolver(fn (): Route => $route);
                $formRequest->setMethod($targetMethod);
                $formRequest->server->set('REQUEST_METHOD', $targetMethod);

                return $formRequest;
            }
        }

        abort(422, 'This wizard does not have a dedicated store request.');
    }

    private function prepareForValidation(FormRequest $request): void
    {
        (function (): void {
            $this->prepareForValidation();
        })->call($request);
    }

    private function validationData(FormRequest $request): array
    {
        return (function (): array {
            return $this->validationData();
        })->call($request);
    }

    private function rulesForFields(array $rules, array $fields): array
    {
        $roots = array_unique(array_map(
            static fn (string $field): string => preg_split('/[.\[]/', $field)[0],
            $fields,
        ));

        return array_filter(
            $rules,
            static fn (string $ruleKey): bool => in_array(strtok($ruleKey, '.'), $roots, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function fileManifest(array $files, string $prefix = ''): array
    {
        $names = [];

        foreach ($files as $key => $file) {
            if (is_array($file) && ! array_key_exists('name', $file)) {
                $names[$key] = $this->fileManifest($file, $prefix === '' ? (string) $key : $prefix.'.'.$key);

                continue;
            }

            if (is_array($file) && isset($file['name'])) {
                $names[$key] = [
                    'key' => $prefix === '' ? (string) $key : $prefix.'.'.$key,
                    'name' => $file['name'],
                    'mime' => $file['mime'] ?? null,
                    'size' => $file['size'] ?? null,
                ];
            }
        }

        return $names;
    }
}
