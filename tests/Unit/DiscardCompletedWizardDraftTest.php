<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\DiscardCompletedWizardDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class DiscardCompletedWizardDraftTest extends TestCase
{
    public function test_a_successful_store_redirect_discards_its_wizard_draft(): void
    {
        $request = Request::create('/admin/companies', 'POST');
        $request->attributes->set('wizard_draft_id', 'draft-id');

        $response = new RedirectResponse('/admin/companies');
        $response->setSession($this->app['session']->driver());
        $response->getSession()->flash('success', 'Created successfully.');

        $this->assertTrue($this->wasStoredSuccessfully($request, $response));
    }

    public function test_an_unsuccessful_store_redirect_keeps_its_wizard_draft(): void
    {
        $request = Request::create('/admin/companies', 'POST');
        $request->attributes->set('wizard_draft_id', 'draft-id');

        $response = new RedirectResponse('/admin/companies/create');
        $response->setSession($this->app['session']->driver());
        $response->getSession()->flash('error', 'Could not create the company.');

        $this->assertFalse($this->wasStoredSuccessfully($request, $response));
    }

    private function wasStoredSuccessfully(Request $request, RedirectResponse $response): bool
    {
        $method = new \ReflectionMethod(DiscardCompletedWizardDraft::class, 'wasStoredSuccessfully');

        return $method->invoke(new DiscardCompletedWizardDraft(), $request, $response);
    }
}
