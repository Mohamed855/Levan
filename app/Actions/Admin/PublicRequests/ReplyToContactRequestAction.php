<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Jobs\Admin\Mail\SendContactReplyMailJob;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\ContactRequest;
use Illuminate\Support\Facades\DB;

final class ReplyToContactRequestAction
{
    public function execute(ContactRequest $request, string $reply, AdminUser $admin): ContactRequest
    {
        $updated = DB::transaction(function () use ($request, $reply, $admin): ContactRequest {
            abort_unless($request->status === ContactRequestStatus::PENDING, 409, 'Request already processed.');
            $request->update(['reply' => $reply, 'status' => ContactRequestStatus::REPLIED, 'replied_at' => now(), 'replied_by' => $admin->getKey()]);
            return $request->refresh();
        });

        SendContactReplyMailJob::dispatch($updated->customer_email, $updated->customer_name, $updated->message, $updated->reply)->onQueue('email');
        return $updated;
    }
}
