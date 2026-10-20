<?php

namespace App\Jobs\Admin\Mail;

use App\Services\Admin\Microsoft\Mail\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSubscriptionMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public $timeout = 60;

    public function __construct(public string $email, public $subscription, public string $type = 'new') {}

    public function handle(MailService $mailService): void
    {
        try {
            match ($this->type) {
                'new' => $mailService->NewSubscribtionMail($this->email, $this->subscription),
                'renew' => $mailService->RenewSubscriptionMail($this->email, $this->subscription),
                'upgrade' => $mailService->UpgradeSubscriptionMail($this->email, $this->subscription),
                'new_client' => $mailService->NewClientMail($this->email, $this->subscription),
                default => Log::warning("Unknown subscription email type: {$this->type} ")
            };
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('SendSubscriptionEmail Job failed permanently', [
            'email' => $this->email,
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'subscription' => $this->subscription->id ?? null,
        ]);
    }
}
