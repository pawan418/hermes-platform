<?php

namespace App\Modules\WhatsApp\Jobs;

use App\Modules\AICore\Models\Conversation;
use App\Modules\AICore\Services\OrchestratorService;
use App\Services\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Conversation $conversation;
    protected string $messageText;

    /**
     * Create a new job instance.
     */
    public function __construct(Conversation $conversation, string $messageText)
    {
        $this->conversation = $conversation;
        $this->messageText = $messageText;
    }

    /**
     * Execute the job.
     */
    public function handle(OrchestratorService $orchestrator): void
    {
        try {
            // 1. Resolve Tenant Context
            $tenant = $this->conversation->tenant;
            app(TenantManager::class)->setTenant($tenant);

            // 2. Dispatch to AI Orchestrator to generate response (RAG, Chat, etc.)
            $replyMessage = $orchestrator->handleMessage($this->conversation, $this->messageText);

            // 3. Dispatch HTTP Post back to Meta Cloud API
            $phoneId = $tenant->getSetting('whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_NUMBER_ID');
            $token = $tenant->getSetting('whatsapp.token') ?? env('WHATSAPP_TOKEN');

            if (empty($phoneId) || empty($token)) {
                Log::warning("WhatsApp credentials missing for tenant ID {$tenant->id}. Message logged only.");
                return;
            }

            $endpoint = "https://graph.facebook.com/v19.0/{$phoneId}/messages";

            $response = Http::withToken($token)->post($endpoint, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->conversation->channel_id,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $replyMessage->content,
                ],
            ]);

            if (!$response->successful()) {
                Log::error("Meta WhatsApp Send Message Error for conversation ID {$this->conversation->id}: " . $response->body());
            } else {
                Log::info("WhatsApp message reply dispatched successfully to {$this->conversation->channel_id}.");
            }

        } catch (\Exception $e) {
            Log::error("Failed to process queued WhatsApp message: " . $e->getMessage());
        }
    }
}
