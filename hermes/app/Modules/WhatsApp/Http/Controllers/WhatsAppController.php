<?php

namespace App\Modules\WhatsApp\Http\Controllers;

use App\Models\Tenant;
use App\Modules\CRM\Models\Contact;
use App\Modules\AICore\Models\Conversation;
use App\Modules\AICore\Models\Agent;
use App\Modules\WhatsApp\Jobs\ProcessWhatsAppMessageJob;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * Handshake verification with Meta Cloud API webhook setups.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        
        $configuredToken = env('WHATSAPP_VERIFY_TOKEN', 'hermes_webhook_verification_token');

        if ($mode === 'subscribe' && $token === $configuredToken) {
            Log::info('WhatsApp Webhook Handshake verified successfully.');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp Webhook Handshake failed token verification.');
        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhooks (messages, status updates) from Meta.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Check if it is a message entry
        $entry = $payload['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        
        if (!$changes || ($changes['field'] ?? '') !== 'messages') {
            return response('Ignore non-messages webhook events', 200);
        }

        $value = $changes['value'] ?? null;
        $message = $value['messages'][0] ?? null;

        // Skip if not an actual message text payload (e.g. read receipts or typing indicators)
        if (!$message) {
            return response('No actionable message payload', 200);
        }

        $from = $message['from']; // User's phone number
        $text = $message['text']['body'] ?? '';
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if (empty($text)) {
            return response('Ignore empty message text', 200);
        }

        try {
            // 1. Resolve Tenant Context
            // Find the tenant matching this whatsapp phone number ID, or fall back to first tenant
            $tenant = Tenant::where('settings->whatsapp->phone_number_id', $phoneNumberId)->first() 
                ?? Tenant::first();

            if (!$tenant) {
                return response('Tenant context not found', 200);
            }

            // Set request-scoped tenant
            app(TenantManager::class)->setTenant($tenant);

            // 2. Locate or create Contact
            $contact = Contact::firstOrCreate(
                ['phone' => $from, 'tenant_id' => $tenant->id],
                ['first_name' => 'WhatsApp User', 'last_name' => $from]
            );

            // 3. Locate or create active Conversation
            $defaultAgent = Agent::where('tenant_id', $tenant->id)->first();

            $conversation = Conversation::firstOrCreate([
                'tenant_id' => $tenant->id,
                'channel' => 'whatsapp',
                'channel_id' => $from,
            ], [
                'agent_id' => $defaultAgent?->id ?? 1,
                'title' => 'WhatsApp Conversation: ' . $from,
                'status' => 'active',
            ]);

            // 4. Dispatch Asynchronous Ingestion Job
            // Processing LLMs and semantic retrieval in a job guarantees returning 200 to Meta instantly
            ProcessWhatsAppMessageJob::dispatch($conversation, $text);

        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Ingestion Exception: ' . $e->getMessage());
        }

        return response('EVENT_RECEIVED', 200);
    }
}
