<?php

declare(strict_types=1);

namespace App\Jobs\Admin\Mail;

use App\Services\Admin\Microsoft\Mail\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendContactReplyMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $email, public string $name, public string $message, public string $reply) {}

    public function handle(MailService $mailService): void
    {
        $mailService->sendContactReplyMail($this->email, $this->name, $this->message, $this->reply);
    }
}
