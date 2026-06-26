<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSmtpConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hermes:test-smtp {--to=admin@lspl.xyz}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verify the configured SMTP connection by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recipient = $this->option('to');

        $this->info("Attempting to send a test email to {$recipient}...");

        try {
            Mail::raw('This is a diagnostic email from the Hermes AI Enterprise Platform installation system to confirm that the SMTP settings are correct.', function ($message) use ($recipient) {
                $message->to($recipient)
                        ->subject('Hermes SMTP Configuration Test');
            });

            $this->info("SMTP connection and test email dispatch succeeded!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("SMTP connection failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
