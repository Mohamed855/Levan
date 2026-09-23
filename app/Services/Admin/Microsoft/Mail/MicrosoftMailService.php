<?php

namespace App\Services\Admin\Microsoft\Mail;

use App\Services\Admin\Microsoft\Graph\MicrosoftGraphService;
use App\Services\Admin\Microsoft\Settings\MicrosoftSettingsService;
use Illuminate\Support\Facades\Log;

class MicrosoftMailService
{
    protected string $defaultFromEmail;

    public function __construct(private MicrosoftGraphService $graph, private MicrosoftSettingsService $settingsService)
    {
        $config                 = $this->settingsService->get();
        $this->defaultFromEmail = $config['main_email'];
    }


    public function sendEmail(string $to, string $subject, string $htmlBody, array $attachments = [], ?string $fromEmail = null): bool
    {
        $fromEmail = $fromEmail ?: $this->defaultFromEmail;

        $payload = [
            'message' => [
                'subject' => $subject,
                'body'    => [
                    'contentType' => 'HTML',
                    'content'     => $htmlBody,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $to,
                        ],
                    ],
                ],
            ],
            'saveToSentItems' => true,
        ];

        // if (!empty($attachments)) {
        //     $payload['message']['attachments'] = $this->prepareAttachments($attachments);
        // }

        try {
            $this->graph->request('POST', "users/{$fromEmail}/sendMail", [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('MicrosoftMailService::sendEmail error: ' . $e->getMessage());
            return false;
        }
    }


    // protected function prepareAttachments(array $files): array
    // {
    //     $attachments = [];

    //     foreach ($files as $file) {
    //         $attachments[] = [
    //             '@odata.type'  => '#microsoft.graph.fileAttachment',
    //             'name'         => $file['name'],
    //             'contentBytes' => base64_encode(file_get_contents($file['path'])),
    //         ];
    //     }

    //     return $attachments;
    // }
}
