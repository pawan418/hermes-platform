<?php

namespace Tests\Unit;

use App\Modules\Knowledge\Services\TextChunker;
use PHPUnit\Framework\TestCase;

class TextChunkerTest extends TestCase
{
    /** @test */
    public function it_can_chunk_short_text_into_single_chunk()
    {
        $text = "This is a simple short text.";
        $chunks = TextChunker::chunk($text, 100, 20);

        $this->assertCount(1, $chunks);
        $this->assertEquals("This is a simple short text.", $chunks[0]);
    }

    /** @test */
    public function it_can_chunk_long_text_with_sliding_window_overlap()
    {
        // 150 characters
        $text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.";
        
        $chunks = TextChunker::chunk($text, 50, 10);

        // Should return multiple chunks
        $this->assertGreaterThan(1, count($chunks));
        
        // Ensure whitespace formatting cleanup was executed
        foreach ($chunks as $chunk) {
            $this->assertNotContains('  ', $chunk);
        }
    }
}
