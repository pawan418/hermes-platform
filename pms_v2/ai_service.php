<?php
// pms_v2/ai_service.php - Unified AI Service Gateway for multiple providers

class PmsAiService {
    /**
     * Generates text content based on provider configuration.
     * 
     * @param string $provider (Gemini, ChatGPT, Groq, Ollama)
     * @param string $key API key
     * @param string $model_id Target model name (e.g. gpt-4o-mini, gemini-1.5-flash)
     * @param string $prompt Prompt string
     * @param string $endpoint Custom Ollama or Gateway endpoint
     * @return string Generated text or empty string on failure
     */
    public static function generateText($provider, $key, $model_id, $prompt, $endpoint = '') {
        $provider = trim($provider);
        
        switch ($provider) {
            case 'Gemini':
                return self::callGemini($key, $model_id ?: 'gemini-1.5-flash', $prompt);
            case 'ChatGPT':
                return self::callChatGPT($key, $model_id ?: 'gpt-4o-mini', $prompt);
            case 'Groq':
                return self::callGroq($key, $model_id ?: 'llama3-8b-8192', $prompt);
            case 'Ollama':
                return self::callOllama($endpoint ?: 'http://localhost:11434/api/generate', $model_id ?: 'llama3', $prompt);
            default:
                return '';
        }
    }

    private static function callGemini($key, $model, $prompt) {
        if (empty($key)) {
            throw new Exception("Gemini API key is not configured in settings.");
        }
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($model) . ":generateContent?key=" . urlencode($key);
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];
        
        $headers = [
            "Content-Type: application/json"
        ];
        
        $response = self::curlPost($url, json_encode($payload), $headers);
        $data = json_decode($response, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        if (isset($data['error']['message'])) {
            throw new Exception("Gemini API Error: " . $data['error']['message']);
        }
        
        error_log("Gemini response parsing failed: " . $response);
        return '';
    }

    private static function callChatGPT($key, $model, $prompt) {
        if (empty($key)) {
            throw new Exception("ChatGPT API key is not configured in settings.");
        }
        
        $url = "https://api.openai.com/v1/chat/completions";
        
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $key
        ];
        
        $response = self::curlPost($url, json_encode($payload), $headers);
        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        if (isset($data['error']['message'])) {
            throw new Exception("OpenAI API Error: " . $data['error']['message']);
        }
        
        error_log("ChatGPT response parsing failed: " . $response);
        return '';
    }

    private static function callGroq($key, $model, $prompt) {
        if (empty($key)) {
            throw new Exception("Groq API key is not configured in settings.");
        }
        
        $url = "https://api.groq.com/openai/v1/chat/completions";
        
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $key
        ];
        
        $response = self::curlPost($url, json_encode($payload), $headers);
        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        if (isset($data['error']['message'])) {
            throw new Exception("Groq API Error: " . $data['error']['message']);
        }
        
        error_log("Groq response parsing failed: " . $response);
        return '';
    }

    private static function callOllama($url, $model, $prompt) {
        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false
        ];
        
        $headers = [
            "Content-Type: application/json"
        ];
        
        $response = self::curlPost($url, json_encode($payload), $headers);
        $data = json_decode($response, true);
        
        if (isset($data['response'])) {
            return $data['response'];
        }
        
        if (isset($data['error'])) {
            throw new Exception("Ollama Error: " . $data['error']);
        }
        
        error_log("Ollama response parsing failed: " . $response);
        return '';
    }

    private static function curlPost($url, $body, $headers) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Skip SSL check if local Ollama or custom gateway configuration
        if (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception("Connection Error: " . $err);
        }
        
        curl_close($ch);
        return $response;
    }
}
