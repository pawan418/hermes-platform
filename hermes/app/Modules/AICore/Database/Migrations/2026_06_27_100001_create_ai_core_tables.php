<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('provider')->default('openai'); // openai, anthropic, gemini, ollama
            $table->string('model')->default('gpt-4o');
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('max_tokens')->default(2048);
            $table->jsonb('allowed_tools')->nullable(); // e.g. ['crm_search', 'send_email', 'book_meeting']
            $table->text('system_prompt')->nullable(); // fallback or direct prompt
            $table->foreignId('prompt_template_id')->nullable(); // can point to versioned prompt
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->jsonb('variables')->nullable(); // array of template variables
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_template_id')->constrained()->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->text('content');
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // optional user owner
            $table->string('channel')->default('web'); // web, whatsapp, voice
            $table->string('channel_id')->nullable(); // external conversation reference
            $table->string('title')->nullable();
            $table->string('status')->default('active'); // active, archived, closed
            $table->jsonb('context_data')->nullable(); // extra session settings/state
            $table->timestamps();

            $table->index(['tenant_id', 'channel_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // system, user, assistant, tool
            $table->text('content')->nullable();
            $table->jsonb('tool_calls')->nullable();
            $table->decimal('cost', 8, 6)->nullable(); // record estimated LLM API cost
            $table->integer('latency_ms')->nullable(); // API response latency
            $table->timestamp('created_at')->nullable();
        });

        // Add index on agent prompt template reference
        Schema::table('agents', function (Blueprint $table) {
            $table->foreign('prompt_template_id')->references('id')->on('prompt_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['prompt_template_id']);
        });

        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('prompt_versions');
        Schema::dropIfExists('prompt_templates');
        Schema::dropIfExists('agents');
    }
};
