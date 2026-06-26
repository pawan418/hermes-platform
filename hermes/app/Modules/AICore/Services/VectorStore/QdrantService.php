<?php

namespace App\Modules\AICore\Services\VectorStore;

use App\Services\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QdrantService
{
    protected string $url;
    protected ?string $apiKey;
    protected string $collection = 'knowledge_chunks';

    public function __construct()
    {
        $qdrantHost = env('QDRANT_HOST', 'qdrant');
        $qdrantPort = env('QDRANT_PORT', 6333);
        $this->url = "http://{$qdrantHost}:{$qdrantPort}";
        $this->apiKey = env('QDRANT_API_KEY');
    }

    /**
     * Send HTTP request to Qdrant REST API with authentication headers.
     */
    protected function request()
    {
        $request = Http::withHeaders(['Content-Type' => 'application/json']);
        
        if ($this->apiKey) {
            $request->withHeaders(['api-key' => $this->apiKey]);
        }

        return $request;
    }

    /**
     * Upsert points (vector embeddings + payload metadata) into the collection.
     */
    public function upsertPoints(array $points): bool
    {
        $endpoint = "{$this->url}/collections/{$this->collection}/points?wait=true";
        
        $response = $this->request()->post($endpoint, [
            'points' => $points,
        ]);

        if (!$response->successful()) {
            Log::error('Qdrant Upsert Error: ' . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Search the vector store for similar vectors belonging to the active tenant.
     */
    public function searchSimilarity(array $vector, int $limit = 5): array
    {
        $tenantManager = app(TenantManager::class);
        $tenantId = $tenantManager->getTenantId();

        $endpoint = "{$this->url}/collections/{$this->collection}/points/search";

        // Query payload with tenant isolation filter
        $payload = [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
            'filter' => [
                'must' => [
                    [
                        'key' => 'tenant_id',
                        'match' => [
                            'value' => $tenantId,
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->request()->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error('Qdrant Vector Search Error: ' . $response->body());
            return [];
        }

        $results = [];
        $candidates = $response->json('result') ?? [];

        foreach ($candidates as $candidate) {
            $results[] = [
                'text' => $candidate['payload']['text'] ?? '',
                'document_id' => $candidate['payload']['document_id'] ?? null,
                'score' => $candidate['score'] ?? 0.0,
            ];
        }

        return $results;
    }

    /**
     * Delete all vector points belonging to a specific document.
     */
    public function deleteDocumentPoints(int $documentId): bool
    {
        $endpoint = "{$this->url}/collections/{$this->collection}/points/delete";

        $response = $this->request()->post($endpoint, [
            'filter' => [
                'must' => [
                    [
                        'key' => 'document_id',
                        'match' => [
                            'value' => $documentId,
                        ]
                    ]
                ]
            ]
        ]);

        if (!$response->successful()) {
            Log::error("Qdrant Delete Document Points Error for doc ID {$documentId}: " . $response->body());
            return false;
        }

        return true;
    }
}
