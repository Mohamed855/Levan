<?php

declare(strict_types=1);

namespace App\Services\Shared\Mail;

use App\Mail\Auth\OtpMail;
use App\Mail\Auth\PasswordResetMail;
use App\Mail\Auth\UserWelcomeMail;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Billing\BankTransferAdminAlertMail;
use App\Mail\Billing\BankTransferApprovedMail;
use App\Mail\Billing\BankTransferPendingMail;
use App\Mail\Billing\BankTransferRejectedMail;
use App\Mail\PublicRequests\ContactReplyMail;
use App\Mail\PublicRequests\ContactRequestAdminAlertMail;
use App\Mail\PublicRequests\ContactRequestReceivedMail;
use App\Mail\PublicRequests\TrialRequestAdminAlertMail;
use App\Mail\PublicRequests\TrialRequestApprovedMail;
use App\Mail\PublicRequests\TrialRequestReceivedMail;
use App\Mail\PublicRequests\TrialRequestRejectedMail;
use App\Mail\Subscriptions\NewSubscriptionMail;
use App\Mail\Subscriptions\RenewSubscriptionMail;
use App\Mail\Subscriptions\SubscriptionExpiredMail;
use App\Mail\Subscriptions\SubscriptionExpiringMail;
use App\Mail\Subscriptions\UpgradeSubscriptionMail;
use App\Mail\Tenants\TenantWelcomeMail;
use App\Mail\Tickets\TicketCreatedAdminMail;
use App\Mail\Tickets\TicketCreatedClientMail;
use App\Mail\Tickets\TicketReplyMail;
use App\Mail\Tickets\TicketStatusUpdatedMail;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SystemMailService
{
    /**
     * Send an email safely with error handling and delivery strategy.
     */
    public static function sendSafely(string $recipientEmail, Mailable $mailable, bool $forceSync = false): bool
    {
        $recipientEmail = trim($recipientEmail);
        if (empty($recipientEmail) || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning("SystemMailService: Invalid or empty email recipient: '{$recipientEmail}'");

            return false;
        }

        try {
            $queueConnection = config('queue.default', 'sync');

            // If sync queue or forced immediate send (e.g. OTP/Security), send synchronously
            if ($forceSync) {
                $mailable->onConnection('sync');
            }

            if ($forceSync || $queueConnection === 'sync') {
                Mail::to($recipientEmail)->send($mailable);
            } else {
                Mail::to($recipientEmail)->queue($mailable);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SystemMailService failed to send email', [
                'recipient' => $recipientEmail,
                'mailable'  => get_class($mailable),
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get primary admin notification email.
     */
    public static function getAdminEmail(): string
    {
        try {
            $settingEmail = Settings::first()?->email;
            if (! empty($settingEmail) && filter_var($settingEmail, FILTER_VALIDATE_EMAIL)) {
                return $settingEmail;
            }
        } catch (\Throwable $e) {
            // Ignore settings fetch error
        }

        return (string) config('mail.from.address', 'info@levan.sa');
    }

    /*
    |--------------------------------------------------------------------------
    | Auth & Verification
    |--------------------------------------------------------------------------
    */

    public static function sendOtp(string $email, string $otp, string $purpose = 'التحقق من البريد الإلكتروني'): bool
    {
        return self::sendSafely($email, new OtpMail($otp, $purpose), true);
    }

    public static function sendPasswordReset(string $email, string $resetUrl, ?string $userName = null): bool
    {
        return self::sendSafely($email, new PasswordResetMail($resetUrl, $userName), true);
    }

    public static function sendUserWelcome(
        string $email,
        string $userName,
        ?string $temporaryPassword,
        string $loginUrl,
        ?string $companyName = null,
        ?string $roleName = null
    ): bool {
        return self::sendSafely(
            $email,
            new UserWelcomeMail($userName, $email, $temporaryPassword, $loginUrl, $companyName, $roleName)
        );
    }

    public static function sendVerifyEmail(string $email, string $verifyUrl, ?string $userName = null): bool
    {
        return self::sendSafely($email, new VerifyEmailMail($verifyUrl, $userName), true);
    }

    public static function sendTenantWelcome(
        Tenant $tenant,
        ?string $loginEmail = null,
        string $source = 'subscription',
        ?int $trialDays = null,
        ?string $trialEndDate = null,
        ?string $temporaryPassword = null
    ): bool {
        $loginEmail = $loginEmail ?: $tenant->primary_contact_email;
        if (empty($loginEmail)) {
            Log::warning("SystemMailService::sendTenantWelcome: Tenant {$tenant->id} has no valid contact email.");

            return false;
        }

        $loginUrl = $tenant->getLoginUrl();

        return self::sendSafely(
            $loginEmail,
            new TenantWelcomeMail(
                tenant: $tenant,
                loginUrl: $loginUrl,
                loginEmail: $loginEmail,
                creationSource: $source,
                temporaryPassword: $temporaryPassword,
                trialDays: $trialDays,
                trialEndDate: $trialEndDate
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Public Requests (Trial & Contact)
    |--------------------------------------------------------------------------
    */

    public static function sendTrialRequestReceived(string $email, string $customerName, ?string $companyName = null): bool
    {
        return self::sendSafely($email, new TrialRequestReceivedMail($customerName, $companyName));
    }

    public static function sendTrialRequestAdminAlert(TrialRequest $trialRequest): bool
    {
        return self::sendSafely(self::getAdminEmail(), new TrialRequestAdminAlertMail($trialRequest));
    }

    public static function sendTrialRequestApproved(
        string $email,
        string $customerName,
        string $loginUrl,
        int $trialDays = 7,
        ?string $endDate = null
    ): bool {
        return self::sendSafely(
            $email,
            new TrialRequestApprovedMail($customerName, $email, $loginUrl, $trialDays, $endDate),
            true
        );
    }

    public static function sendTrialRequestRejected(string $email, string $customerName, ?string $reason = null): bool
    {
        return self::sendSafely($email, new TrialRequestRejectedMail($customerName, $reason));
    }

    public static function sendContactRequestReceived(
        string $email,
        string $customerName,
        ?string $subject = null,
        ?string $message = null
    ): bool {
        return self::sendSafely($email, new ContactRequestReceivedMail($customerName, $subject, $message));
    }

    public static function sendContactRequestAdminAlert(
        string $customerName,
        string $customerEmail,
        string $messageContent,
        ?string $customerPhone = null,
        ?string $subjectText = null,
        ?string $adminViewUrl = null
    ): bool {
        return self::sendSafely(
            self::getAdminEmail(),
            new ContactRequestAdminAlertMail($customerName, $customerEmail, $messageContent, $customerPhone, $subjectText, $adminViewUrl)
        );
    }

    public static function sendContactReply(
        string $email,
        string $customerName,
        string $originalMessage,
        string $replyMessage
    ): bool {
        return self::sendSafely($email, new ContactReplyMail($customerName, $originalMessage, $replyMessage));
    }

    /*
    |--------------------------------------------------------------------------
    | Subscriptions
    |--------------------------------------------------------------------------
    */

    public static function sendNewSubscription(string $email, Subscription $subscription, ?Bill $bill = null): bool
    {
        return self::sendSafely($email, new NewSubscriptionMail($subscription, $bill));
    }

    public static function sendRenewSubscription(string $email, Subscription $subscription, ?Bill $bill = null): bool
    {
        return self::sendSafely($email, new RenewSubscriptionMail($subscription, $bill));
    }

    public static function sendUpgradeSubscription(string $email, Subscription $subscription, int $newLicensesCount = 0): bool
    {
        return self::sendSafely($email, new UpgradeSubscriptionMail($subscription, $newLicensesCount));
    }

    public static function sendSubscriptionExpiring(string $email, Subscription $subscription, int $daysRemaining): bool
    {
        return self::sendSafely($email, new SubscriptionExpiringMail($subscription, $daysRemaining));
    }

    public static function sendSubscriptionExpired(string $email, Subscription $subscription): bool
    {
        return self::sendSafely($email, new SubscriptionExpiredMail($subscription));
    }

    /*
    |--------------------------------------------------------------------------
    | Billing & Bank Transfers
    |--------------------------------------------------------------------------
    */

    public static function sendBankTransferPending(string $email, Bill $bill, ?string $customerName = null): bool
    {
        return self::sendSafely($email, new BankTransferPendingMail($bill, $customerName));
    }

    public static function sendBankTransferAdminAlert(Bill $bill): bool
    {
        return self::sendSafely(self::getAdminEmail(), new BankTransferAdminAlertMail($bill));
    }

    public static function sendBankTransferApproved(string $email, Bill $bill): bool
    {
        return self::sendSafely($email, new BankTransferApprovedMail($bill));
    }

    public static function sendBankTransferRejected(
        string $email,
        Bill $bill,
        ?string $reason = null,
        ?string $uploadUrl = null
    ): bool {
        return self::sendSafely($email, new BankTransferRejectedMail($bill, $reason, $uploadUrl));
    }

    /*
    |--------------------------------------------------------------------------
    | Support Tickets
    |--------------------------------------------------------------------------
    */

    public static function sendTicketCreatedClient(Ticket $ticket, string $clientEmail, ?string $customerName = null): bool
    {
        return self::sendSafely($clientEmail, new TicketCreatedClientMail($ticket, $customerName));
    }

    public static function sendTicketCreatedAdmin(Ticket $ticket): bool
    {
        return self::sendSafely(self::getAdminEmail(), new TicketCreatedAdminMail($ticket));
    }

    public static function sendTicketReply(
        Ticket $ticket,
        TicketReply $reply,
        string $recipientEmail,
        string $recipientName,
        bool $isSupportReply
    ): bool {
        return self::sendSafely($recipientEmail, new TicketReplyMail($ticket, $reply, $recipientName, $isSupportReply));
    }

    public static function sendTicketStatusUpdated(
        Ticket $ticket,
        string $clientEmail,
        string $newStatus,
        ?string $customerName = null
    ): bool {
        return self::sendSafely($clientEmail, new TicketStatusUpdatedMail($ticket, $newStatus, $customerName));
    }
}
