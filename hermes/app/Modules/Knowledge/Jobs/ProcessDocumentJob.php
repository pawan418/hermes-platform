<?php

namespace App\Modules\Knowledge\Jobs;

use App\Modules\AICore\Services\LLM\LLMService;
use App\Modules\AICore\Services\VectorStore\QdrantService;
use App\Modules\Knowledge\Models\Document;
use App\Modules\Knowledge\Services\DocumentParser;
use App\Modules\Knowledge\Services\TextChunker;
use App\Services\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Document $document;

    /**
     * Create a new job instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(LLMService $llm, QdrantService $qdrant): void
    {
        $this->document->update(['status' => 'processing', 'error_message' => null]);

        try {
            // 1. Resolve tenant context for scoping database queries inside job execution
            $tenantManager = app(TenantManager::class);
            $tenant = $this->document->knowledgeBase->tenant;
            $tenantManager->setTenant($tenant);

            // 2. Fetch file content from MinIO
            if (!Storage::disk('s3')->exists($this->document->file_path)) {
                throw new \Exception("File not found on MinIO storage: {$this->document->file_path}");
            }

            $rawContent = Storage::disk('s3')->get($this->document->file_path);

            if (empty($rawContent)) {
                throw new \Exception("Document is empty or could not be read.");
            }

            // 3. Extract plain text
            $plainText = DocumentParser::parse($rawContent, $this->document->file_type);

            if (empty(trim($plainText))) {
                throw new \Exception("Document parser failed to extract readable text.");
            }

            // 4. Chunk text (1000 characters size, 200 overlap)
            $chunks = TextChunker::chunk($plainText, 1000, 200);
            
            if (empty($chunks)) {
                throw new \Exception("Text chunker split returned 0 chunks.");
            }

            // 5. Generate embeddings and upsert into Qdrant in batches
            $points = [];
            $batchSize = 25;

            foreach ($chunks as $index => $chunkText) {
                // Generate vector embedding
                $embedding = $llm->embed($chunkText);

                if (empty($embedding)) {
                    throw new \Exception("LLM service returned empty vector embedding for chunk index {$index}.");
                }

                $points[] = [
                    'id' => Str::uuid()->toString(), // Qdrant expects standard UUID string for keys
                    'vector' => $embedding,
                    'payload' => [
                        'tenant_id' => $tenant->id,
                        'document_id' => $this->document->id,
                        'text' => $chunkText,
                    ]
                ];

                // Upsert batch to Qdrant to conserve memory
                if (count($points) >= $batchSize) {
                    $qdrant->upsertPoints($points);
                    $points = [];
                }
            }

            // Upsert any remaining points
            if (!empty($points)) {
                $qdrant->upsertPoints($points);
            }

            // 6. Update document state to completed
            $this->document->update([
                'status' => 'indexed',
                'chunks_count' => count($chunks),
            ]);

            Log::info("Document ID {$this->document->id} ('{$this->document->name}') indexed successfully with " . count($chunks) . " vector chunks.");

        } catch (\Exception $e) {
            Log::error("Failed to index document ID {$this->document->id}: " . $e->getMessage());

            $this->document->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);
        }
    }
}
