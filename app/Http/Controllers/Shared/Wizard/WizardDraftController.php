<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared\Wizard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenant\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenant\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenant\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenant\Properties\StorePropertyRequest;
use App\Http\Requests\Tenant\Properties\UpdatePropertyRequest;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WizardDraftController extends Controller
{
    public function __construct(private readonly WizardDraftService $drafts) {}

    public function show(Request $request): JsonResponse
    {
        $key = $request->validate(['resource_key' => ['required', 'string', 'max:255']])['resource_key'];
        $draft = $this->drafts->find($request, $key);
        return response()->json([
            'draft_id' => $draft?->id,
            'data' => $draft?->draft_data ?? [],
            'files' => $this->filesForClient($draft?->draft_files ?? []),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['resource_key' => ['required', 'string', 'max:255'], 'step' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $draft = $this->drafts->save($request, $validated['resource_key'], (int) ($validated['step'] ?? 1));
        return response()->json(['success' => true, 'draft_id' => $draft->id]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $key = $request->validate(['resource_key' => ['required', 'string', 'max:255']])['resource_key'];
        $this->drafts->discard($request, $key);
        return response()->json(['success' => true]);
    }

    public function destroyFile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_key' => ['required', 'string', 'max:255'],
            'file_key' => ['required', 'string', 'max:255'],
        ]);

        abort_unless($this->drafts->removeFile($request, $validated['resource_key'], $validated['file_key']), 404);

        return response()->json(['success' => true]);
    }

    public function validateStep(Request $request): JsonResponse
    {
        $request->validate([
            'target_path' => ['required', 'string', 'max:2048'],
            'target_method' => ['required', 'in:POST,PUT,PATCH'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'max:255'],
        ]);

        $formRequest = $this->formRequestFor($request);
        abort_unless($formRequest->authorize(), 403);
        (function (): void { $this->prepareForValidation(); })->call($formRequest);

        $requestedRoots = collect($request->input('fields'))
            ->map(static fn (string $field): string => explode('.', str_replace('[', '.', $field))[0])
            ->unique();
        $rules = collect($formRequest->rules())
            ->filter(static fn (mixed $_, string $field): bool => $requestedRoots->contains(explode('.', $field)[0]))
            ->all();
        $validator = Validator::make(
            $formRequest->validationData(),
            $rules,
            method_exists($formRequest, 'messages') ? $formRequest->messages() : [],
            method_exists($formRequest, 'attributes') ? $formRequest->attributes() : [],
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(['success' => true]);
    }

    private function formRequestFor(Request $request): FormRequest
    {
        $path = parse_url((string) $request->input('target_path'), PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/')) {
            throw new NotFoundHttpException;
        }

        $route = app('router')->getRoutes()->match(Request::create($path, (string) $request->input('target_method')));
        $requestClass = match ($route->getName()) {
            'tenant.assignments.store' => StoreAssignmentRequest::class,
            'tenant.assignments.update' => UpdateAssignmentRequest::class,
            'tenant.auctions.store' => StoreAuctionRequest::class,
            'tenant.auctions.update' => UpdateAuctionRequest::class,
            'tenant.properties.store' => StorePropertyRequest::class,
            'tenant.properties.update' => UpdatePropertyRequest::class,
            default => throw new NotFoundHttpException,
        };

        /** @var FormRequest $formRequest */
        $formRequest = $requestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setRouteResolver(static fn () => $route);
        $formRequest->setUserResolver(static fn () => $request->user('tenant'));

        return $formRequest;
    }

    /** @return array<string, mixed> */
    private function filesForClient(array $files, string $prefix = ''): array
    {
        $result = [];
        foreach ($files as $key => $file) {
            $fileKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($file) && array_key_exists('path', $file)) {
                $result[$key] = $file + ['key' => $fileKey];
                continue;
            }

            $result[$key] = is_array($file) ? $this->filesForClient($file, $fileKey) : $file;
        }

        return $result;
    }
}
