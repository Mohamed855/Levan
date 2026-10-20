<?php

namespace App\Jobs\Admin\Mail;

use App\Services\Admin\Microsoft\Mail\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendContactMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public $timeout = 60;

    public function __construct(public string $email, public string $name, public string $type) {}

    public function handle(MailService $mailService): void
    {
        try {
            match ($this->type) {
                'trial' => $mailService->TrialRequestMail($this->email, $this->name),
                'contact' => $mailService->ContactRequestMail($this->email, $this->name),
                default => Log::warning("Unknown mail type: {$this->type}")
            };
        } catch (\Throwable $e) {
            //
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('SendSubscriptionEmail Job failed permanently', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
