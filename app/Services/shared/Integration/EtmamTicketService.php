<?php

namespace App\Services\shared\Integration;

use Illuminate\Support\Facades\Log;

class EtmamTicketService
{
    protected ?EtmamConnector $connector = null;

    protected function getConnector(): EtmamConnector
    {
        if ($this->connector === null) {
            $this->connector = new EtmamConnector(tenant('id'));
        }
        return $this->connector;
    }

    public function isConfigured(): bool
    {
        return $this->getConnector()->isConfigured();
    }

    // list of tickets
    public function list(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $query = array_merge(
            $filters,
            [
                'tenant_id'      => $this->getConnector()->getTenantId(),
                'tenant_user_id' => auth()->id(),
                'page'           => $page,
                'per_page'       => $perPage,
            ]
        );

        return $this->getConnector()->get('tenants/tickets', $query);
    }


    public function create(array $data, array $files = []): array
    {
        if (empty($files)) {
            return $this->getConnector()->post('tickets', $data);
        }

        return $this->getConnector()->postWithFiles('tickets', $data, $files);
    }


    public function get(string $ticketNumber): array
    {
        return $this->getConnector()->get("tickets/{$ticketNumber}");
    }


    public function addReply(string $ticketNumber, string $message, array $files = []): array
    {
        $data = ['message' => $message];

        if (empty($files)) {
            return $this->getConnector()->post("tickets/{$ticketNumber}/replies", $data);
        }

        return $this->getConnector()->postWithFiles("tickets/{$ticketNumber}/replies", $data, $files);
    }


    public function getReplies(string $ticketNumber): array
    {
        return $this->getConnector()->get("tickets/{$ticketNumber}/replies");
    }


    // public function updateReply(string $ticketNumber, int $replyId, array $data): array
    // {
    //     $data['tenant_id'] = $this->getConnector()->getTenantId();
    //     $data['tenant_user_id'] = auth()->id();

    //     return $this->getConnector()->post("tickets/{$ticketNumber}/replies/{$replyId}", $data);
    // }


    // public function deleteReply(string $ticketNumber, int $replyId): array
    // {
    //     $query = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->delete("tickets/{$ticketNumber}/replies/{$replyId}", $query);
    // }



    // public function addAttachments(string $ticketNumber, array $files): array
    // {
    //     $data = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->postWithFiles("tickets/{$ticketNumber}/attachments", $data, $files);
    // }


    // public function deleteAttachment(string $ticketNumber, int $attachmentId): array
    // {
    //     $query = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->delete("tickets/{$ticketNumber}/attachments/{$attachmentId}", $query);
    // }


    // public function addReplyAttachments(string $ticketNumber, int $replyId, array $files): array
    // {
    //     $data = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->postWithFiles("tickets/{$ticketNumber}/replies/{$replyId}/attachments", $data, $files);
    // }



    // public function deleteReplyAttachment(string $ticketNumber, int $replyId, int $attachmentId): array
    // {
    //     $query = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->delete("tickets/{$ticketNumber}/replies/{$replyId}/attachments/{$attachmentId}", $query);
    // }


    public function rateReply(string $ticketNumber, int $replyId, int $rating): array
    {
        $data = [
            'stars' => $rating,
        ];

        return $this->getConnector()->post("tickets/{$ticketNumber}/replies/{$replyId}/rate", $data);
    }


    // public function getReplyRating(string $ticketNumber, int $replyId): array
    // {
    //     $query = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->get("tickets/{$ticketNumber}/replies/{$replyId}/rate", $query);
    // }


    // public function deleteReplyRating(string $ticketNumber, int $replyId): array
    // {
    //     $query = [
    //         'tenant_id' => $this->getConnector()->getTenantId(),
    //         'tenant_user_id' => auth()->id(),
    //     ];

    //     return $this->getConnector()->delete("tickets/{$ticketNumber}/replies/{$replyId}/rate", $query);
    // }


    //    التقييم النهائي
    public function answerSurvey(string $ticketNumber,  $data): array
    {
        return $this->getConnector()->post("tickets/{$ticketNumber}/survey", $data);
    }


    // جلب الاقسام
    public function getSections(): array
    {
        return $this->getConnector()->get('ticket-sections');
    }
}
