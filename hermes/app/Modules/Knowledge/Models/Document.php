<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\AICore\Services\VectorStore\QdrantService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'knowledge_base_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'status',
        'error_message',
        'chunks_count',
    ];

    /**
     * Parent knowledge base relation.
     */
    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    /**
     * Automatic deletion hooks to scrub MinIO and Qdrant storage.
     */
    protected static function booted(): void
    {
        static::deleted(function ($document) {
            // 1. Delete from MinIO
            try {
                if (Storage::disk('s3')->exists($document->file_path)) {
                    Storage::disk('s3')->delete($document->file_path);
                }
            } catch (\Exception $e) {
                Log::error("Failed to purge MinIO storage for doc path {$document->file_path}: " . $e->getMessage());
            }

            // 2. Delete from Qdrant
            try {
                $qdrant = app(QdrantService::class);
                $qdrant->deleteDocumentPoints($document->id);
            } catch (\Exception $e) {
                Log::error("Failed to purge Qdrant vectors for doc ID {$document->id}: " . $e->getMessage());
            }
        });
    }
}
