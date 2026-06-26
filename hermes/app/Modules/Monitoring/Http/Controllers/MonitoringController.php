<?php

namespace App\Modules\Monitoring\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\AICore\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class MonitoringController extends Controller
{
    /**
     * Perform health check checks for core platform dependencies.
     */
    public function health()
    {
        $status = 'healthy';
        $checks = [
            'database' => 'ok',
            'redis' => 'ok',
            'minio' => 'ok',
            'qdrant' => 'ok',
        ];

        // 1. Check Database
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $checks['database'] = 'fail: ' . $e->getMessage();
            $status = 'unhealthy';
        }

        // 2. Check Redis
        try {
            Redis::ping();
        } catch (\Exception $e) {
            $checks['redis'] = 'fail: ' . $e->getMessage();
            $status = 'unhealthy';
        }

        // 3. Check MinIO S3
        try {
            Storage::disk('s3')->allFiles('/');
        } catch (\Exception $e) {
            $checks['minio'] = 'fail: ' . $e->getMessage();
            $status = 'unhealthy';
        }

        // 4. Check Qdrant REST API
        try {
            $qHost = env('QDRANT_HOST', 'qdrant');
            $qPort = env('QDRANT_PORT', 6333);
            $response = Http::timeout(3)->get("http://{$qHost}:{$qPort}/collections");
            
            if (!$response->successful()) {
                throw new \Exception('Qdrant returned status code: ' . $response->status());
            }
        } catch (\Exception $e) {
            $checks['qdrant'] = 'fail: ' . $e->getMessage();
            $status = 'unhealthy';
        }

        $code = ($status === 'healthy') ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'dependencies' => $checks,
        ], $code);
    }

    /**
     * Output system statistics in standard Prometheus formatting.
     */
    public function metrics()
    {
        // 1. Fetch system aggregates (bypassing tenant scopes for global statistics)
        $tenantsCount = Tenant::count();
        $usersCount = User::count();
        
        $messagesByChannel = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->select('conversations.channel', DB::raw('count(*) as count'))
            ->groupBy('conversations.channel')
            ->pluck('count', 'channel')
            ->toArray();

        $totalLlmCost = Message::sum('cost');
        $averageLatency = Message::whereNotNull('latency_ms')->avg('latency_ms') ?? 0;

        // 2. Format metrics string in Prometheus text format
        $output = "# HELP hermes_tenants_total The number of tenants registered in Hermes SaaS.\n";
        $output .= "# TYPE hermes_tenants_total gauge\n";
        $output .= "hermes_tenants_total {$tenantsCount}\n\n";

        $output .= "# HELP hermes_users_total The total number of users across all companies.\n";
        $output .= "# TYPE hermes_users_total gauge\n";
        $output .= "hermes_users_total {$usersCount}\n\n";

        $output .= "# HELP hermes_llm_cost_total Total cumulative LLM cost in USD.\n";
        $output .= "# TYPE hermes_llm_cost_total counter\n";
        $output .= "hermes_llm_cost_total " . sprintf("%.6f", $totalLlmCost) . "\n\n";

        $output .= "# HELP hermes_llm_latency_average_ms Average response time of AI agents in milliseconds.\n";
        $output .= "# TYPE hermes_llm_latency_average_ms gauge\n";
        $output .= "hermes_llm_latency_average_ms " . (int) $averageLatency . "\n\n";

        $output .= "# HELP hermes_messages_total Cumulative count of messages routed through AI agents.\n";
        $output .= "# TYPE hermes_messages_total counter\n";
        
        $webMessages = $messagesByChannel['web'] ?? 0;
        $whatsappMessages = $messagesByChannel['whatsapp'] ?? 0;
        $voiceMessages = $messagesByChannel['voice'] ?? 0;
        
        $output .= "hermes_messages_total{channel=\"web\"} {$webMessages}\n";
        $output .= "hermes_messages_total{channel=\"whatsapp\"} {$whatsappMessages}\n";
        $output .= "hermes_messages_total{channel=\"voice\"} {$voiceMessages}\n";

        return response($output, 200)->header('Content-Type', 'text/plain; version=0.0.4');
    }
}
