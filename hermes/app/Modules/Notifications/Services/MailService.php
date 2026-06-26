<?php

namespace App\Modules\Notifications\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send email notifications with raw or HTML layouts.
     */
    public function sendEmail(string $toEmail, string $subject, string $body, array $attachments = []): bool
    {
        try {
            Mail::raw($body, function ($message) use ($toEmail, $subject, $attachments) {
                $message->to($toEmail)
                    ->subject($subject);

                foreach ($attachments as $filePath => $fileName) {
                    $message->attach($filePath, [
                        'as' => $fileName,
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SMTP Mail Transmission Error: ' . $e->getMessage());
            return false;
        }
    }
}
