<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreCheckoutRequest;
use App\Http\Requests\Web\StoreContactRequest;
use App\Http\Requests\Web\StoreTrialRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormValidationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'target_path' => ['required', 'string'],
            'target_method' => ['required', 'in:POST,PUT,PATCH'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string'],
        ]);

        $formRequest = $this->formRequestFor($request);
        (function (): void { $this->prepareForValidation(); })->call($formRequest);

        $requestedRoots = collect($request->input('fields'))->map(static fn (string $field): string => explode('.', str_replace('[', '.', $field))[0])->unique();
        $rules = collect($formRequest->rules())->filter(
            static fn (mixed $_, string $field): bool => $requestedRoots->contains(explode('.', $field)[0]),
        )->all();

        $validator = Validator::make(
            $formRequest->all(),
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

        $routeRequest = Request::create($path, (string) $request->input('target_method'));
        $route = app('router')->getRoutes()->match($routeRequest);
        $requestClass = match ($route->getName()) {
            'site.contact.store' => StoreContactRequest::class,
            'site.trial.store' => StoreTrialRequest::class,
            'site.plans.checkout.store' => StoreCheckoutRequest::class,
            default => throw new NotFoundHttpException,
        };

        /** @var FormRequest $formRequest */
        $formRequest = $requestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));

        return $formRequest;
    }
}
