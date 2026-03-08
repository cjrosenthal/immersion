<?php

class SpeechService {
    
    private $apiKey;
    private $ttsUrl = 'https://api.openai.com/v1/audio/speech';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Generate speech from text using OpenAI TTS
     * @param string $text Text to convert to speech
     * @param string $voice Voice to use (alloy, echo, fable, onyx, nova, shimmer)
     * @return string Binary audio data (MP3)
     */
    public function generateSpeech($text, $voice = 'alloy') {
        $data = [
            'model' => 'tts-1',
            'input' => $text,
            'voice' => $voice,
            'response_format' => 'mp3'
        ];
        
        $ch = curl_init($this->ttsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('OpenAI TTS Error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('OpenAI TTS returned status code: ' . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * Break text into chunks for incremental TTS
     * Combines short sentences to meet minimum length threshold
     * @param string $text Full text to break into chunks
     * @param int $minLength Minimum characters per chunk
     * @return array Array of text chunks
     */
    public function breakIntoChunks($text, $minLength = 50) {
        // Split on sentence boundaries
        $sentences = preg_split('/([.!?]+\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $chunks = [];
        $currentChunk = '';
        
        for ($i = 0; $i < count($sentences); $i++) {
            $sentence = $sentences[$i];
            
            // Skip empty strings
            if (empty(trim($sentence))) {
                continue;
            }
            
            $currentChunk .= $sentence;
            
            // If we have a sentence ending (. ! ?) and meet minimum length, create chunk
            if (preg_match('/[.!?]+\s*$/', $sentence) && strlen($currentChunk) >= $minLength) {
                $chunks[] = trim($currentChunk);
                $currentChunk = '';
            }
        }
        
        // Add any remaining text as final chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }
    
    /**
     * Get available TTS voices
     * @return array Available voice names
     */
    public static function getAvailableVoices() {
        return ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    }
    
    /**
     * Validate voice name
     * @param string $voice Voice to validate
     * @return bool True if valid
     */
    public static function isValidVoice($voice) {
        return in_array($voice, self::getAvailableVoices());
    }
}
