<?php

namespace App\Modules\Voice\Http\Controllers;

use App\Models\Tenant;
use App\Modules\AICore\Models\Conversation;
use App\Modules\AICore\Models\Agent;
use App\Modules\AICore\Services\OrchestratorService;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    protected OrchestratorService $orchestrator;

    public function __construct(OrchestratorService $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Handle Twilio call initiation webhook (incoming call).
     */
    public function incoming(Request $request)
    {
        $callSid = $request->input('CallSid');
        $toPhoneNumber = $request->input('To');

        // Resolve Tenant Context
        $tenant = Tenant::where('settings->voice->phone_number', $toPhoneNumber)->first() 
            ?? Tenant::first();

        if (!$tenant) {
            return response("<Response><Say>System configuration error. Goodbye.</Say><Hangup/></Response>", 200)
                ->header('Content-Type', 'text/xml');
        }

        app(TenantManager::class)->setTenant($tenant);

        // Find or create voice agent conversation
        $defaultAgent = Agent::where('tenant_id', $tenant->id)->first();
        
        $conversation = Conversation::create([
            'tenant_id' => $tenant->id,
            'agent_id' => $defaultAgent?->id ?? 1,
            'channel' => 'voice',
            'channel_id' => $callSid,
            'title' => 'Voice Call from ' . $request->input('From', 'Unknown'),
            'status' => 'active',
        ]);

        $recordingUrl = route('voice.recording', ['conversation_id' => $conversation->id]);

        // Output TwiML asking user for input and triggering the recording callback
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $twiml .= "<Response>";
        $twiml .= "<Say voice=\"Polly.Joey-Neural\">Welcome to Longway Softronix AI assistant. How can I help you today?</Say>";
        $twiml .= "<Record action=\"{$recordingUrl}\" method=\"POST\" maxLength=\"15\" playBeep=\"true\" trim=\"trim-silence\" />";
        $twiml .= "</Response>";

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Handle user recording callback from Twilio.
     */
    public function recording(Request $request)
    {
        $conversationId = $request->query('conversation_id');
        $recordingUrl = $request->input('RecordingUrl');
        
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response("<Response><Say>Session not found. Goodbye.</Say><Hangup/></Response>", 200)
                ->header('Content-Type', 'text/xml');
        }

        $tenant = $conversation->tenant;
        app(TenantManager::class)->setTenant($tenant);

        $openAiKey = $tenant->getSetting('keys.openai_api_key') ?? env('OPENAI_API_KEY', '');
        
        if (empty($recordingUrl) || empty($openAiKey)) {
            $fallbackUrl = route('voice.recording', ['conversation_id' => $conversation->id]);
            $twiml = "<Response>";
            $twiml .= "<Say>I'm sorry, I encountered a communication issue. Could you repeat that?</Say>";
            $twiml .= "<Record action=\"{$fallbackUrl}\" method=\"POST\" maxLength=\"15\" playBeep=\"true\" trim=\"trim-silence\" />";
            $twiml .= "</Response>";
            return response($twiml, 200)->header('Content-Type', 'text/xml');
        }

        try {
            // 1. Download User Audio from Twilio
            $audioData = Http::get($recordingUrl)->body();
            
            // 2. Call OpenAI Whisper to Transcribe
            $transcriptionResponse = Http::withToken($openAiKey)
                ->attach('file', $audioData, 'recording.wav')
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                ]);

            if (!$transcriptionResponse->successful()) {
                throw new \Exception('Whisper transcription failed: ' . $transcriptionResponse->body());
            }

            $userQuery = trim($transcriptionResponse->json('text') ?? '');
            Log::info("Call {$conversation->channel_id} Transcribed Query: \"{$userQuery}\"");

            // Check if user wants to hang up
            if (preg_match('/(goodbye|bye bye|talk to you later|hang up)/i', $userQuery)) {
                $twiml = "<Response><Say>Thank you for calling. Goodbye.</Say><Hangup/></Response>";
                return response($twiml, 200)->header('Content-Type', 'text/xml');
            }

            // 3. Dispatch to AI Orchestrator to generate response (RAG context included)
            $replyMessage = $this->orchestrator->handleMessage($conversation, $userQuery);

            // 4. Generate Text-to-Speech Audio via OpenAI TTS
            $speechResponse = Http::withToken($openAiKey)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => 'tts-1',
                    'input' => $replyMessage->content,
                    'voice' => 'alloy', // options: alloy, echo, fable, onyx, nova, shimmer
                ]);

            if (!$speechResponse->successful()) {
                throw new \Exception('OpenAI Speech TTS failed: ' . $speechResponse->body());
            }

            // 5. Save generated response audio file to public storage
            $filename = 'voice/reply_' . Str::random(12) . '.mp3';
            Storage::disk('public')->put($filename, $speechResponse->body());
            $audioUrl = asset('storage/' . $filename);

            // 6. Return TwiML playing the reply audio and opening microphone again
            $callbackUrl = route('voice.recording', ['conversation_id' => $conversation->id]);
            
            $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $twiml .= "<Response>";
            $twiml .= "<Play>{$audioUrl}</Play>";
            $twiml .= "<Record action=\"{$callbackUrl}\" method=\"POST\" maxLength=\"15\" playBeep=\"true\" trim=\"trim-silence\" />";
            $twiml .= "</Response>";

            return response($twiml, 200)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Voice agent runtime error: ' . $e->getMessage());

            $fallbackUrl = route('voice.recording', ['conversation_id' => $conversation->id]);
            $twiml = "<Response>";
            $twiml .= "<Say>I experienced a system error. Please speak your inquiry again.</Say>";
            $twiml .= "<Record action=\"{$fallbackUrl}\" method=\"POST\" maxLength=\"15\" playBeep=\"true\" trim=\"trim-silence\" />";
            $twiml .= "</Response>";
            
            return response($twiml, 200)->header('Content-Type', 'text/xml');
        }
    }
}
