<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CreateFormDraftContractTest extends TestCase
{
    public function test_admin_and_tenant_layouts_publish_the_shared_draft_contract(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            $root.'/resources/views/admin/layouts/commonMaster.blade.php',
            $root.'/resources/views/tenant/layouts/commonMaster.blade.php',
        ] as $layout) {
            $content = file_get_contents($layout);

            $this->assertStringContainsString('data-wizard-drafts-url=', $content);
            $this->assertStringContainsString('<x-form.wizard.draft-template', $content);
        }
    }

    public function test_shared_validation_upgrades_every_post_create_form_to_a_draft_form(): void
    {
        $root = dirname(__DIR__, 2);
        $script = file_get_contents($root.'/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString('function prepareCreateFormContract(form)', $script);
        $this->assertStringContainsString('form.dataset.draftUrl ||= draftUrl', $script);
        $this->assertStringContainsString('installDraftButton(submitButton.parentElement, submitButton)', $script);
        $this->assertStringContainsString("addHiddenField(form, '_wizard_draft_id'", $script);
        $this->assertStringContainsString('restoreDraft(form)', $script);
    }

    public function test_successful_create_requests_discard_the_completed_draft_globally(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrap = file_get_contents($root.'/bootstrap/app.php');

        $this->assertStringContainsString('DiscardCompletedWizardDraft::class', $bootstrap);
    }
}
