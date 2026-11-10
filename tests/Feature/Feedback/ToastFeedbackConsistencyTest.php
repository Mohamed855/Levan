<?php

declare(strict_types=1);

namespace Tests\Feature\Feedback;

use App\Exceptions\Handler;
use App\Support\FlashFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ToastFeedbackConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_flash_feedback_maps_session_flashes_status_and_validation_errors(): void
    {
        Session::flash('status', 'verification-link-sent');
        $attrs = FlashFeedback::attributes();
        $this->assertSame(
            'تم إرسال رابط تحقق جديد إلى عنوان البريد الإلكتروني الذي قدمته أثناء التسجيل.',
            $attrs['data-flash-success']
        );
        $this->assertSame('false', $attrs['data-has-validation-errors']);

        Session::flush();
        Session::flash('status', 'password-updated');
        $this->assertSame('تم تحديث كلمة المرور بنجاح.', FlashFeedback::attributes()['data-flash-success']);

        Session::flush();
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => ['البريد الإلكتروني غير صحيح.']]));
        $errorAttrs = FlashFeedback::attributes($errors);
        $this->assertSame('البريد الإلكتروني غير صحيح.', $errorAttrs['data-flash-error']);
        $this->assertSame('true', $errorAttrs['data-has-validation-errors']);
    }

    public function test_auth_and_password_views_render_toast_attributes_without_regular_alerts(): void
    {
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['code' => ['رمز التحقق غير صحيح.']]));
        view()->share('errors', $errors);

        $adminLogin = $this->withSession([
            'success' => 'تم تغيير كلمة المرور بنجاح.',
        ])->view('admin.auth.login');

        $adminLogin->assertSee('data-flash-success="تم تغيير كلمة المرور بنجاح."', false);
        $adminLogin->assertSee('data-flash-error="رمز التحقق غير صحيح."', false);
        $adminLogin->assertDontSee('alert-success', false);
        $adminLogin->assertDontSee('alert-danger', false);

        $adminTwoFactor = $this->view('admin.auth.two-factor-challenge');
        $adminTwoFactor->assertSee('data-flash-error="رمز التحقق غير صحيح."', false);
        $adminTwoFactor->assertDontSee('alert-danger', false);

        $tenantTwoFactor = $this->view('tenant.auth.two-factor-challenge');
        $tenantTwoFactor->assertSee('data-flash-error="رمز التحقق غير صحيح."', false);
        $tenantTwoFactor->assertDontSee('alert-danger', false);

        $passwordForm = $this->view('shared.users.partials._password-form', [
            'action' => '/change-password',
            'validationUrl' => '/validate',
            'backUrl' => '/profile',
        ]);
        $passwordForm->assertDontSee('alert-danger', false);
    }

    public function test_verify_email_and_web_layout_render_toast_attributes_without_regular_alerts(): void
    {
        view()->share('errors', new ViewErrorBag());

        $verifyHtml = $this->withSession([
            'status' => 'verification-link-sent',
        ])->view('tenant.auth.verify-email');

        $verifyHtml->assertSee(
            'data-flash-success="تم إرسال رابط تحقق جديد إلى عنوان البريد الإلكتروني الذي قدمته أثناء التسجيل."',
            false
        );
        $verifyHtml->assertDontSee('alert-success', false);

        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['otp' => ['رمز التحقق منتهي الصلاحية.']]));
        view()->share('errors', $errors);

        $webLayoutHtml = $this->view('layouts.web');
        $webLayoutHtml->assertSee('data-flash-error="رمز التحقق منتهي الصلاحية."', false);
        $webLayoutHtml->assertSee('data-has-validation-errors="true"', false);
        $webLayoutHtml->assertDontSee('alert-danger', false);
    }

    public function test_validation_exception_json_returns_actual_first_error_message(): void
    {
        $request = Request::create('/test', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $exception = ValidationException::withMessages([
            'email' => ['البريد الإلكتروني مستخدم بالفعل.'],
        ]);

        $response = app(Handler::class)->render($request, $exception);
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('البريد الإلكتروني مستخدم بالفعل.', $payload['message']);
    }

    public function test_no_response_alert_boxes_remain_in_views_or_shared_scripts(): void
    {
        $root = dirname(__DIR__, 3);

        $datatableFeedback = file_get_contents($root.'/resources/assets/js/shared/datatable-feedback.js');
        $this->assertStringNotContainsString('alert-danger', $datatableFeedback);
        $this->assertStringContainsString('window.toastr?.error', $datatableFeedback);

        $applyCoupon = file_get_contents($root.'/resources/assets/js/shared/payment/apply-coupon.js');
        $this->assertStringNotContainsString('site-alert-danger', $applyCoupon);
        $this->assertStringContainsString('window.toastr?.[toastType]', $applyCoupon);

        $wizardEnhancer = file_get_contents($root.'/resources/assets/js/shared/wizard/create-wizard-enhancer.js');
        $this->assertStringNotContainsString('.alert-danger', $wizardEnhancer);
        $this->assertStringContainsString('hasValidationErrors', $wizardEnhancer);

        $this->assertFileDoesNotExist($root.'/resources/views/components/shared/ui/validation-errors.blade.php');
    }
}
