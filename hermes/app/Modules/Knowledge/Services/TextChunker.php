<?php

namespace App\Modules\Knowledge\Services;

class TextChunker
{
    /**
     * Chunk text into fixed sizes with overlapping words or characters.
     */
    public static function chunk(string $text, int $size = 1000, int $overlap = 200): array
    {
        $chunks = [];
        $textLength = mb_strlen($text);
        
        // Return whole text if it fits in a single chunk
        if ($textLength <= $size) {
            return [trim($text)];
        }
        
        $start = 0;
        while ($start < $textLength) {
            $chunk = mb_substr($text, $start, $size);
            
            // Clean up double spaces or line breaks
            $chunk = preg_replace('/\s+/', ' ', $chunk);
            
            $chunks[] = trim($chunk);
            
            // Slide window forward by chunk size minus overlap
            $start += ($size - $overlap);
            
            // Break if the slide does not advance (avoid infinite loops)
            if ($size <= $overlap) {
                break;
            }
        }
        
        return array_filter($chunks);
    }
}
