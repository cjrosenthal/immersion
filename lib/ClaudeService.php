<?php

class ClaudeService {
    
    private $apiKey;
    private $model;
    private $apiUrl = 'https://api.anthropic.com/v1/messages';
    
    public function __construct($apiKey, $model = 'claude-3-sonnet-20240229') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }
    
    /**
     * Stream chat responses from Claude API
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param callable $callback Function to call for each chunk
     * @return string Full response text
     */
    public function streamChat($messages, $callback = null) {
        $data = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => $messages,
            'stream' => true
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        
        $fullResponse = '';
        $errorResponse = '';
        
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$fullResponse, &$errorResponse, $callback) {
            $length = strlen($data);
            
            // Check HTTP code
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // If error, capture full response
            if ($httpCode !== 200) {
                $errorResponse .= $data;
                return $length;
            }
            
            // Parse SSE data
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                if (strpos($line, 'data: ') === 0) {
                    $jsonData = substr($line, 6);
                    
                    if ($jsonData === '[DONE]') {
                        continue;
                    }
                    
                    $decoded = json_decode($jsonData, true);
                    
                    if ($decoded && isset($decoded['type'])) {
                        if ($decoded['type'] === 'content_block_delta') {
                            if (isset($decoded['delta']['text'])) {
                                $text = $decoded['delta']['text'];
                                $fullResponse .= $text;
                                
                                if ($callback) {
                                    call_user_func($callback, $text);
                                }
                            }
                        } else if ($decoded['type'] === 'error') {
                            $errorResponse .= json_encode($decoded);
                        }
                    }
                }
            }
            
            return $length;
        });
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Claude API Error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorMessage = 'Claude API returned status code: ' . $httpCode;
            if (!empty($errorResponse)) {
                $decoded = json_decode($errorResponse, true);
                if ($decoded && isset($decoded['error']['message'])) {
                    $errorMessage .= ' - ' . $decoded['error']['message'];
                } else {
                    $errorMessage .= ' - ' . $errorResponse;
                }
            }
            throw new Exception($errorMessage);
        }
        
        return $fullResponse;
    }
    
    /**
     * Non-streaming chat (simpler, returns full response)
     */
    public function chat($messages) {
        $data = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => $messages
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Claude API Error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Claude API returned status code: ' . $httpCode . ' - ' . $response);
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded || !isset($decoded['content'][0]['text'])) {
            throw new Exception('Invalid response from Claude API');
        }
        
        return $decoded['content'][0]['text'];
    }
}
