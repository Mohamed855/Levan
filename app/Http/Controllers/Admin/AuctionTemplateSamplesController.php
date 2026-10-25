<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuctionTemplateSamples\AuctionTemplateSampleCardResource;
use App\Http\Resources\Admin\AuctionTemplateSamples\AuctionTemplateSampleDesignResource;
use App\Models\Admin\AuctionTemplates\AuctionTemplateSample;
use App\Support\Auctions\TemplateSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuctionTemplateSamplesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $this->authorize('viewAny', AuctionTemplateSample::class);

        return view('admin.auction-template-samples.index', ['types' => AuctionTemplateType::options()]);
    }

    public function create()
    {
        abort_unless(request()->user('admin')->can('auction_template_samples.create'), 403);

        return view('admin.auction-template-samples.form', ['sample' => new AuctionTemplateSample, 'typeOptions' => AuctionTemplateType::options()]);
    }

    public function edit(AuctionTemplateSample $sample)
    {
        abort_unless(request()->user('admin')->can('auction_template_samples.edit'), 403);

        return view('admin.auction-template-samples.form', ['sample' => $sample, 'typeOptions' => AuctionTemplateType::options()]);
    }

    public function library(Request $request)
    {
        abort_unless($request->user('admin')->can('auction_template_samples.view'), 403);
        $type = $request->string('type')->toString();

        return AuctionTemplateSampleCardResource::collection(AuctionTemplateSample::active()->when($type, fn ($q) => $q->byType($type))->get());
    }

    public function design(Request $request, AuctionTemplateSample $sample)
    {
        abort_unless($request->user('admin')->can('auction_template_samples.view') && $sample->is_active, 403);

        return new AuctionTemplateSampleDesignResource($sample);
    }

    public function store(Request $request, TemplateSchema $schemas): JsonResponse
    {
        abort_unless($request->user('admin')->can('auction_template_samples.create'), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'type' => 'required|in:brochure,billboard,social', 'description' => 'nullable|string', 'schema' => 'required']);
        $data['schema'] = $schemas->validate($data['schema'], $data['type']);
        $data['created_by'] = $request->user('admin')->id;
        $sample = AuctionTemplateSample::create($data);

        return response()->json(['data' => new AuctionTemplateSampleDesignResource($sample)], 201);
    }

    public function update(Request $request, AuctionTemplateSample $sample, TemplateSchema $schemas): JsonResponse
    {
        abort_unless($request->user('admin')->can('auction_template_samples.edit'), 403);
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'description' => 'nullable|string', 'is_active' => 'sometimes|boolean', 'schema' => 'sometimes']);
        if (isset($data['schema'])) {
            $data['schema'] = $schemas->validate($data['schema'], $sample->type->value);
        } $sample->update($data);

        return response()->json(['data' => new AuctionTemplateSampleDesignResource($sample->fresh())]);
    }

    public function destroy(Request $request, AuctionTemplateSample $sample): JsonResponse
    {
        abort_unless($request->user('admin')->can('auction_template_samples.delete'), 403);
        $sample->delete();

        return response()->json(['success' => true]);
    }
}
