<?php

namespace App\Modules\Knowledge\Services;

use Illuminate\Support\Facades\Log;

class DocumentParser
{
    /**
     * Parse file content based on type and return plain text.
     */
    public static function parse(string $content, string $type): string
    {
        $type = strtolower($type);

        return match ($type) {
            'txt', 'md', 'markdown' => self::parseText($content),
            'html', 'htm' => self::parseHtml($content),
            'docx' => self::parseDocx($content),
            'pdf' => self::parsePdf($content),
            default => self::parseFallback($content),
        };
    }

    protected static function parseText(string $content): string
    {
        return trim($content);
    }

    protected static function parseHtml(string $content): string
    {
        // Strip scripts and styles first
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        
        // Strip HTML tags and decode HTML entities
        $text = strip_tags($content);
        return html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected static function parseDocx(string $content): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'docx_');
        file_put_contents($temp, $content);
        
        $zip = new \ZipArchive();
        $text = '';

        if ($zip->open($temp) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xml = $zip->getFromIndex($index);
                // Strip XML tags to retrieve content
                $text = strip_tags($xml);
            }
            $zip->close();
        } else {
            Log::warning('Docx parser failed to unzip file archive.');
        }

        unlink($temp);
        return html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected static function parsePdf(string $content): string
    {
        $text = '';

        // Find all FlateDecode streams inside PDF structure
        preg_match_all("/stream([\s\S]*?)endstream/i", $content, $matches);

        foreach ($matches[1] as $stream) {
            $stream = trim($stream);
            
            // Try to decompress PDF zlib/gz streams
            $decompressed = @gzuncompress($stream);
            
            if ($decompressed === false) {
                // If it fails with leading whitespaces, strip and try again
                $decompressed = @gzuncompress(preg_replace('/^\r?\n/', '', $stream));
            }

            if ($decompressed !== false) {
                // Parse text chunks out of PDF text rendering operators Tj or TJ
                preg_match_all("/\((.*?)\)\s*(Tj|TJ)/i", $decompressed, $textMatches);
                foreach ($textMatches[1] as $line) {
                    // Strip PDF escape backslashes
                    $line = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $line);
                    $text .= $line . ' ';
                }
            }
        }

        // Fallback: If decompression extracts no text, extract ASCII characters
        if (empty(trim($text))) {
            $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $content);
        }

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected static function parseFallback(string $content): string
    {
        // Default parser removes binary characters and keeps readable text
        return trim(preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $content));
    }
}
