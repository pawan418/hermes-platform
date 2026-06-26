<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BootstrapHermes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hermes:bootstrap';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Bootstrap external systems like MinIO buckets and Qdrant collections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bootstrapping Hermes external dependencies...');

        // 1. Bootstrap MinIO Bucket
        $this->info('Bootstrapping MinIO storage...');
        try {
            $bucket = env('AWS_BUCKET', 'hermes-storage');
            $s3Disk = Storage::disk('s3');
            
            // Check connectivity and try to create the bucket
            // In Flysystem v3, we can check directory existence or list files to verify connection
            $s3Disk->allFiles('/');
            
            // Create directory structure or bootstrap file to trigger auto bucket creation in some S3 layers,
            // or use standard S3 client
            $s3Client = $s3Disk->getClient();
            if (!$s3Client->doesBucketExist($bucket)) {
                $s3Client->createBucket([
                    'Bucket' => $bucket,
                ]);
                $this->info("MinIO bucket '{$bucket}' created successfully.");
            } else {
                $this->info("MinIO bucket '{$bucket}' already exists.");
            }
        } catch (\Exception $e) {
            $this->error('Failed to bootstrap MinIO: ' . $e->getMessage());
        }

        // 2. Bootstrap Qdrant Collections
        $this->info('Bootstrapping Qdrant collections...');
        try {
            $qdrantHost = env('QDRANT_HOST', 'qdrant');
            $qdrantPort = env('QDRANT_PORT', 6333);
            $apiKey = env('QDRANT_API_KEY');

            $url = "http://{$qdrantHost}:{$qdrantPort}/collections/knowledge_chunks";
            
            $headers = ['Content-Type' => 'application/json'];
            if ($apiKey) {
                $headers['api-key'] = $apiKey;
            }

            // Check if collection exists
            $response = Http::withHeaders($headers)->get($url);

            if ($response->status() === 404) {
                $this->info('Qdrant collection not found. Creating collection...');
                
                $createResponse = Http::withHeaders($headers)->put($url, [
                    'vectors' => [
                        'size' => 3072, // Configured for OpenAI text-embedding-3-large
                        'distance' => 'Cosine'
                    ]
                ]);

                if ($createResponse->successful()) {
                    $this->info("Qdrant collection 'knowledge_chunks' created successfully.");
                } else {
                    $this->error('Failed to create Qdrant collection: ' . $createResponse->body());
                }
            } else {
                $this->info("Qdrant collection 'knowledge_chunks' already exists.");
            }
        } catch (\Exception $e) {
            $this->error('Failed to bootstrap Qdrant: ' . $e->getMessage());
        }

        $this->info('Hermes dependency bootstrap completed.');
        return Command::SUCCESS;
    }
}
