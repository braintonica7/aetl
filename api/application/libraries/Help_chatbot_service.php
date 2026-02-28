<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Help Chatbot Service
 * 
 * Provides semantic search capabilities for help documentation
 * using OpenAI embeddings and GPT-4 for generating responses.
 */
class Help_chatbot_service {
    
    private $CI;
    private $embeddings_file;
    private $embeddings_data;
    private $openai_api_key;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->embeddings_file = APPPATH . 'config/help_embeddings.json';
        $this->openai_api_key = getenv('OPENAI_API_KEY') ?: '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';
        
        // Load embeddings data
        $this->loadEmbeddings();
    }
    
    /**
     * Load embeddings from JSON file
     */
    private function loadEmbeddings() {
        if (!file_exists($this->embeddings_file)) {
            log_message('error', 'Help embeddings file not found: ' . $this->embeddings_file);
            throw new Exception('Help chatbot is not configured. Please generate embeddings first.');
        }
        
        $json = file_get_contents($this->embeddings_file);
        $this->embeddings_data = json_decode($json, true);
        
        if (!$this->embeddings_data || !isset($this->embeddings_data['embeddings'])) {
            log_message('error', 'Invalid embeddings data in file');
            throw new Exception('Help chatbot data is corrupted. Please regenerate embeddings.');
        }
        
        log_message('info', 'Loaded ' . count($this->embeddings_data['embeddings']) . ' help document chunks');
    }
    
    /**
     * Search for relevant context based on user query
     * 
     * @param string $query User's question
     * @param int $top_k Number of top results to return
     * @return array Array of relevant chunks with similarity scores
     */
    public function searchRelevantContext($query, $top_k = 3) {
        // Get query embedding
        $query_embedding = $this->getEmbedding($query);
        
        if (!$query_embedding) {
            log_message('error', 'Failed to generate embedding for query: ' . $query);
            return [];
        }
        
        // Calculate similarity scores for all chunks
        $scores = [];
        foreach ($this->embeddings_data['embeddings'] as $item) {
            $similarity = $this->cosineSimilarity($query_embedding, $item['embedding']);
            
            $scores[] = [
                'chunk' => $item,
                'score' => $similarity
            ];
        }
        
        // Sort by score (highest first)
        usort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return top K results
        return array_slice($scores, 0, $top_k);
    }
    
    /**
     * Get chatbot response for user query
     * 
     * @param string $user_query User's question
     * @param array $options Additional options (model, temperature, etc.)
     * @return array Response with answer and sources
     */
    public function getChatResponse($user_query, $options = []) {
        $start_time = microtime(true);
        
        // Default options
        $model = $options['model'] ?? 'gpt-4o-mini';
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 800;
        $top_k = $options['top_k'] ?? 3;
        
        // Find relevant context
        $relevant_chunks = $this->searchRelevantContext($user_query, $top_k);
        
        if (empty($relevant_chunks)) {
            return [
                'success' => false,
                'error' => 'Could not find relevant information.',
                'answer' => 'I apologize, but I could not find relevant information to answer your question. Please try rephrasing or contact support for assistance.'
            ];
        }
        
        // Build context from top chunks
        $context = $this->buildContextFromChunks($relevant_chunks);
        
        // Create system message
        $system_message = $this->getSystemPrompt();
        
        // Create user message with context
        $user_message = $this->buildUserMessage($context, $user_query);
        
        // Call OpenAI Chat API
        $messages = [
            ['role' => 'system', 'content' => $system_message],
            ['role' => 'user', 'content' => $user_message]
        ];
        
        $chat_response = $this->callChatAPI($messages, $model, $temperature, $max_tokens);
        
        if (!$chat_response['success']) {
            return $chat_response;
        }
        
        $processing_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Prepare response
        return [
            'success' => true,
            'answer' => $chat_response['answer'],
            'sources' => array_map(function($item) {
                return [
                    'document' => $item['chunk']['document'],
                    'section' => $item['chunk']['section'],
                    'subsection' => $item['chunk']['subsection'] ?? null,
                    'relevance_score' => round($item['score'], 3)
                ];
            }, $relevant_chunks),
            'metadata' => [
                'model' => $model,
                'processing_time_ms' => $processing_time,
                'chunks_used' => count($relevant_chunks),
                'tokens_used' => $chat_response['tokens_used'] ?? null
            ]
        ];
    }
    
    /**
     * Build context string from relevant chunks
     */
    private function buildContextFromChunks($chunks) {
        $context = "Relevant information from WiziAI documentation:\n\n";
        
        foreach ($chunks as $index => $item) {
            $chunk_num = $index + 1;
            $context .= "--- Source {$chunk_num}: {$item['chunk']['document']} ---\n";
            $context .= "Section: {$item['chunk']['section']}\n";
            
            if (isset($item['chunk']['subsection'])) {
                $context .= "Subsection: {$item['chunk']['subsection']}\n";
            }
            
            $context .= "\n{$item['chunk']['text']}\n\n";
        }
        
        return $context;
    }
    
    /**
     * Get system prompt for chatbot
     */
    private function getSystemPrompt() {
        return "You are WiziAI Help Assistant, a friendly and knowledgeable support agent for the WiziAI educational platform.

Your role:
- Answer questions accurately based on the provided documentation
- Be helpful, concise, and professional
- Use a friendly and encouraging tone suitable for students
- Format your responses with proper markdown for readability
- If information is not in the documentation, politely say so and suggest contacting support
- Provide step-by-step guidance when appropriate
- Highlight important information using **bold** text
- Use bullet points or numbered lists for clarity

Guidelines:
- Always base your answers on the provided context
- Don't make up information not present in the documentation
- If a question is unclear, ask for clarification
- Encourage users and maintain a positive tone
- Keep answers focused and relevant to the question";
    }
    
    /**
     * Build user message with context and query
     */
    private function buildUserMessage($context, $query) {
        return "{$context}\n\n---\n\nUser Question: {$query}\n\nPlease provide a helpful and accurate answer based on the documentation above:";
    }
    
    /**
     * Call OpenAI Chat Completion API
     */
    private function callChatAPI($messages, $model, $temperature, $max_tokens) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            log_message('error', 'OpenAI API cURL error: ' . $error);
            return [
                'success' => false,
                'error' => 'Failed to connect to AI service.'
            ];
        }
        
        if ($http_code !== 200) {
            log_message('error', 'OpenAI API error (HTTP ' . $http_code . '): ' . $response);
            return [
                'success' => false,
                'error' => 'AI service returned an error. Please try again.'
            ];
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            log_message('error', 'Invalid OpenAI API response: ' . $response);
            return [
                'success' => false,
                'error' => 'Invalid response from AI service.'
            ];
        }
        
        return [
            'success' => true,
            'answer' => trim($result['choices'][0]['message']['content']),
            'tokens_used' => $result['usage']['total_tokens'] ?? null
        ];
    }
    
    /**
     * Get embedding from OpenAI API
     */
    private function getEmbedding($text) {
        $url = 'https://api.openai.com/v1/embeddings';
        
        $data = [
            'model' => 'text-embedding-3-small',
            'input' => $text,
            'encoding_format' => 'float'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result['data'][0]['embedding'] ?? null;
        }
        
        log_message('error', 'Failed to get embedding (HTTP ' . $http_code . '): ' . $response);
        return null;
    }
    
    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosineSimilarity($vec1, $vec2) {
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;
        
        $length = count($vec1);
        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Get embeddings metadata
     */
    public function getMetadata() {
        return $this->embeddings_data['metadata'] ?? [];
    }
    
    /**
     * Check if embeddings are loaded and valid
     */
    public function isReady() {
        return !empty($this->embeddings_data) && 
               isset($this->embeddings_data['embeddings']) && 
               count($this->embeddings_data['embeddings']) > 0;
    }
}
