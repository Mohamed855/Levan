<?php

namespace App\Providers;

use App\Contracts\Tenants\BankTransferReceiptGateway;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Domains\DomainAvailabilityProvider;
use App\Contracts\Payments\PaidCheckoutProvisioner;
use App\Services\Payments\MoyasarGateway;
use App\Services\Domains\NamecheapAvailabilityProvider;
use App\Actions\Web\ProvisionPaidCheckoutAction;
use App\Services\Tenants\Shared\CentralBillingGateway;
use App\Traits\HasRolesAndPermissions;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Helpers/TenantHelper.php');
        $this->app->bind(BankTransferReceiptGateway::class, CentralBillingGateway::class);
        $this->app->bind(PaymentGateway::class, MoyasarGateway::class);
        $this->app->bind(DomainAvailabilityProvider::class, NamecheapAvailabilityProvider::class);
        $this->app->bind(PaidCheckoutProvisioner::class, ProvisionPaidCheckoutAction::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Blade::anonymousComponentPath(resource_path('views/components/shared'));
        \Illuminate\Support\Facades\Blade::anonymousComponentPath(resource_path('views/components/shared'), 'shared');

        Gate::before(function (object $user, string $ability, array $arguments = []): ?bool {
            // Record abilities must reach their policy so ownership checks cannot be bypassed.
            if ($arguments !== [] || ! str_contains($ability, '.')) {
                return null;
            }

            return in_array(HasRolesAndPermissions::class, class_uses_recursive($user), true)
                ? $user->hasPermissionTo($ability)
                : null;
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return route('tenant.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = route('tenant.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            $expireMinutes = config('auth.passwords.users.expire', 60);

            return (new MailMessage)
                ->subject('إعادة تعيين كلمة المرور | لوت LOT')
                ->greeting('مرحباً ' . ($notifiable->name ?? '') . '،')
                ->line('لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في منصة لوت LOT.')
                ->action('إعادة تعيين كلمة المرور', $url)
                ->line("صلاحية هذا الرابط تنتهي خلال {$expireMinutes} دقيقة.")
                ->line('إذا لم تكن قد طلبت إعادة تعيين كلمة المرور، فلا داعي لاتخاذ أي إجراء آخر.')
                ->salutation('مع أطيب التحيات، فريق منصة لوت LOT');
        });
    }
}
