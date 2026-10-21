<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared\Wizard;

use App\Http\Controllers\Controller;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WizardDraftController extends Controller
{
    public function __construct(private readonly WizardDraftService $drafts) {}

    public function show(Request $request): JsonResponse
    {
        $key = $request->validate(['resource_key' => ['required', 'string', 'max:255']])['resource_key'];
        $draft = $this->drafts->find($request, $key);
        return response()->json(['draft_id' => $draft?->id, 'data' => $draft?->draft_data ?? [], 'files' => $draft?->draft_files ?? []]);
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
}