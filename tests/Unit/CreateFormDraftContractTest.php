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
        $this->assertTrue(
            str_contains($script, "addHiddenField(form, '_wizard_draft_id'") || str_contains($script, 'addHiddenField(form, "_wizard_draft_id"'),
            "form-validation.js must add _wizard_draft_id hidden field"
        );
        $this->assertStringContainsString('restoreDraft(form)', $script);
    }

    public function test_successful_create_requests_discard_the_completed_draft_globally(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrap = file_get_contents($root.'/bootstrap/app.php');

        $this->assertStringContainsString('DiscardCompletedWizardDraft::class', $bootstrap);
    }

    public function test_draft_restoration_strictly_requires_user_confirmation_modal_and_never_auto_restores(): void
    {
        $root = dirname(__DIR__, 2);
        $script = file_get_contents($root.'/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString('let shouldRestore = false;', $script);
        $this->assertTrue(
            str_contains($script, "title: 'مسودة محفوظة'") || str_contains($script, 'title: "مسودة محفوظة"'),
            "form-validation.js must set title to مسودة محفوظة"
        );
        $this->assertTrue(
            str_contains($script, "text: 'توجد مسودة محفوظة مسبقاً. هل ترغب في استعادتها؟'") || str_contains($script, 'text: "توجد مسودة محفوظة مسبقاً. هل ترغب في استعادتها؟"'),
            "form-validation.js must set confirmation text"
        );
        $this->assertTrue(
            str_contains($script, "confirmButtonText: 'نعم، استعادة المسودة'") || str_contains($script, 'confirmButtonText: "نعم، استعادة المسودة"'),
            "form-validation.js must set confirmButtonText"
        );
        $this->assertTrue(
            str_contains($script, "cancelButtonText: 'تجاهل والبدء من جديد'") || str_contains($script, 'cancelButtonText: "تجاهل والبدء من جديد"'),
            "form-validation.js must set cancelButtonText"
        );
        $this->assertStringNotContainsString('data-draft-auto-restore', $script);

        // Ensure no view has data-draft-auto-restore bypass
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/resources/views'));
        foreach ($views as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $content = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString('data-draft-auto-restore', $content, "Found forbidden data-draft-auto-restore in {$file->getPathname()}");
            }
        }
    }

    public function test_bootstrap_confirm_modal_is_loaded_in_shared_layout_master_scripts(): void
    {
        $root = dirname(__DIR__, 2);
        $scripts = file_get_contents($root.'/resources/views/components/shared/layout/scripts.blade.php');
        $this->assertStringContainsString('<x-bootstrap.confirm-modal />', $scripts);
    }

    public function test_step_one_previous_button_navigates_to_index_in_all_wizards(): void
    {
        $root = dirname(__DIR__, 2);
        $script = file_get_contents($root.'/resources/assets/js/shared/form-validation.js');

        $this->assertTrue(
            str_contains($script, "replace(/\/create\/?$/, '')") || str_contains($script, 'replace(/\/create\/?$/, "")'),
            "form-validation.js must strip create from URL"
        );
        $this->assertTrue(
            str_contains($script, "replace(/\/[^/]+\/edit\/?$/, '')") || str_contains($script, 'replace(/\/[^/]+\/edit\/?$/, "")'),
            "form-validation.js must strip edit from URL"
        );

        $wizardFiles = [
            $root.'/resources/views/tenant/auctions/create.blade.php',
            $root.'/resources/views/tenant/user-management/users/create.blade.php',
            $root.'/resources/views/tenant/user-management/users/edit.blade.php',
            $root.'/resources/views/tenant/assignments/create.blade.php',
            $root.'/resources/views/tenant/assignments/edit.blade.php',
            $root.'/resources/views/tenant/properties/create.blade.php',
            $root.'/resources/views/tenant/properties/edit.blade.php',
            $root.'/resources/views/admin/users/create.blade.php',
            $root.'/resources/views/admin/users/edit.blade.php',
            $root.'/resources/views/admin/clients/create.blade.php',
            $root.'/resources/views/admin/clients/edit.blade.php',
            $root.'/resources/views/admin/plans/create.blade.php',
            $root.'/resources/views/admin/plans/edit.blade.php',
            $root.'/resources/views/admin/coupons/create.blade.php',
            $root.'/resources/views/admin/coupons/edit.blade.php',
            $root.'/resources/views/admin/subscriptions/create.blade.php',
        ];

        foreach ($wizardFiles as $file) {
            $content = file_get_contents($file);
            $this->assertMatchesRegularExpression(
                '/<x-form\.wizard\.previous-button[^>]+:href="[^"]+\.index[^"]*"/',
                $content,
                "Wizard view {$file} must have previous-button on step 1 with :href pointing to an index route"
            );
        }
    }
}
