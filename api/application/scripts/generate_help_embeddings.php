<?php
/**
 * Generate Help Document Embeddings
 * 
 * This script processes the help documentation files (Wizi_QA.md and WiziAI_QA_Functional_Document.md)
 * and generates embeddings using OpenAI's text-embedding-3-small model.
 * 
 * The embeddings are stored in a JSON file for efficient semantic search.
 * 
 * Usage: php generate_help_embeddings.php
 */

// Set the working directory to the project root
chdir(dirname(__FILE__) . '/../../..');

// Load environment variables if using .env file
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            putenv(trim($line));
        }
    }
}

// Configuration
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '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');
define('OPENAI_MODEL', 'text-embedding-3-small');
define('OUTPUT_FILE', 'api/application/config/help_embeddings.json');
define('WIZI_QA_FILE', 'Wizi_QA.md');
define('FUNCTIONAL_DOC_FILE', 'WiziAI_QA_Functional_Document.md');

class Embedding_Generator {
    private $api_key;
    private $chunks = [];
    private $stats = [
        'total_chunks' => 0,
        'wizi_qa_chunks' => 0,
        'functional_doc_chunks' => 0,
        'errors' => 0
    ];

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Main generation process
     */
    public function generate() {
        echo "🚀 Starting embedding generation process...\n\n";
        
        // Step 1: Load and chunk documents
        echo "📚 Step 1: Loading and chunking documents...\n";
        $this->chunkWiziQA();
        $this->chunkFunctionalDoc();
        echo "   ✓ Total chunks created: " . count($this->chunks) . "\n\n";

        // Step 2: Generate embeddings
        echo "🤖 Step 2: Generating embeddings with OpenAI...\n";
        $embeddings = $this->generateEmbeddings();
        echo "   ✓ Embeddings generated: " . count($embeddings) . "\n\n";

        // Step 3: Save to JSON file
        echo "💾 Step 3: Saving embeddings to JSON file...\n";
        $this->saveEmbeddings($embeddings);
        echo "   ✓ Saved to: " . OUTPUT_FILE . "\n\n";

        // Step 4: Display statistics
        $this->displayStatistics();

        echo "✅ Embedding generation completed successfully!\n";
    }

    /**
     * Chunk Wizi_QA.md by individual Q&A pairs
     */
    private function chunkWiziQA() {
        $file_path = WIZI_QA_FILE;
        
        if (!file_exists($file_path)) {
            echo "   ⚠️  Warning: {$file_path} not found. Skipping...\n";
            return;
        }

        $content = file_get_contents($file_path);
        
        // Extract section headers (e.g., "1. Getting Started & Account Setup")
        preg_match_all('/^(\d+)\.\s+(.+)$/m', $content, $section_matches, PREG_OFFSET_CAPTURE);
        
        // Extract Q&A pairs (e.g., "Q1.1 — What is WiZiAi?")
        preg_match_all('/^(Q\d+\.\d+)\s*—\s*(.+?)$/m', $content, $qa_matches, PREG_OFFSET_CAPTURE);
        
        $current_section = "General";
        $section_index = 0;
        
        foreach ($qa_matches[0] as $index => $match) {
            $qa_start_pos = $match[1];
            
            // Determine current section
            while ($section_index < count($section_matches[0]) && 
                   $section_matches[0][$section_index][1] < $qa_start_pos) {
                $current_section = trim($section_matches[2][$section_index][0]);
                $section_index++;
            }
            
            $question_id = $qa_matches[1][$index][0];
            $question_text = trim($qa_matches[2][$index][0]);
            
            // Find the answer (text after "A:")
            $next_q_pos = isset($qa_matches[0][$index + 1]) ? $qa_matches[0][$index + 1][1] : strlen($content);
            $qa_block = substr($content, $qa_start_pos, $next_q_pos - $qa_start_pos);
            
            // Extract answer
            if (preg_match('/A:\s*(.+?)(?=\n(?:Q\d+\.\d+|_{10,}|\z))/s', $qa_block, $answer_match)) {
                $answer_text = trim($answer_match[1]);
                
                $full_text = "{$question_id} — {$question_text}\n\nAnswer: {$answer_text}";
                
                $this->chunks[] = [
                    'id' => count($this->chunks) + 1,
                    'document' => 'Wizi_QA.md',
                    'section' => $current_section,
                    'question_id' => $question_id,
                    'text' => $full_text,
                    'metadata' => [
                        'type' => 'faq',
                        'question' => $question_text,
                        'has_answer' => true
                    ]
                ];
                
                $this->stats['wizi_qa_chunks']++;
            }
        }
        
        echo "   ✓ Wizi_QA.md: {$this->stats['wizi_qa_chunks']} Q&A pairs extracted\n";
    }

    /**
     * Chunk WiziAI_QA_Functional_Document.md by sections (## headers)
     */
    private function chunkFunctionalDoc() {
        $file_path = FUNCTIONAL_DOC_FILE;
        
        if (!file_exists($file_path)) {
            echo "   ⚠️  Warning: {$file_path} not found. Skipping...\n";
            return;
        }

        $content = file_get_contents($file_path);
        
        // Split by ## headers (level 2 headings)
        $sections = preg_split('/^##\s+/m', $content);
        
        // Remove the document header (first element)
        array_shift($sections);
        
        foreach ($sections as $section) {
            $lines = explode("\n", $section, 2);
            $section_title = trim($lines[0]);
            $section_content = isset($lines[1]) ? trim($lines[1]) : '';
            
            if (empty($section_content)) {
                continue;
            }
            
            // Further split large sections by ### headers (level 3 headings)
            if (strlen($section_content) > 2000) {
                $subsections = preg_split('/^###\s+/m', $section_content);
                
                // Process subsections
                foreach ($subsections as $sub_index => $subsection) {
                    $sub_lines = explode("\n", $subsection, 2);
                    $subsection_title = trim($sub_lines[0]);
                    $subsection_content = isset($sub_lines[1]) ? trim($sub_lines[1]) : '';
                    
                    if (empty($subsection_content)) {
                        continue;
                    }
                    
                    // Limit chunk size to ~1500 characters
                    $chunks_needed = ceil(strlen($subsection_content) / 1500);
                    
                    if ($chunks_needed > 1) {
                        // Split by paragraphs
                        $paragraphs = preg_split('/\n\n+/', $subsection_content);
                        $current_chunk = '';
                        $chunk_num = 1;
                        
                        foreach ($paragraphs as $para) {
                            if (strlen($current_chunk . $para) > 1500 && !empty($current_chunk)) {
                                $this->addFunctionalChunk(
                                    $section_title,
                                    $subsection_title,
                                    $current_chunk,
                                    $chunk_num
                                );
                                $current_chunk = $para;
                                $chunk_num++;
                            } else {
                                $current_chunk .= ($current_chunk ? "\n\n" : "") . $para;
                            }
                        }
                        
                        if (!empty($current_chunk)) {
                            $this->addFunctionalChunk(
                                $section_title,
                                $subsection_title,
                                $current_chunk,
                                $chunk_num
                            );
                        }
                    } else {
                        $this->addFunctionalChunk(
                            $section_title,
                            $subsection_title,
                            $subsection_content
                        );
                    }
                }
            } else {
                // Section is small enough, add as single chunk
                $this->addFunctionalChunk($section_title, null, $section_content);
            }
        }
        
        echo "   ✓ WiziAI_QA_Functional_Document.md: {$this->stats['functional_doc_chunks']} sections extracted\n";
    }

    /**
     * Add a functional document chunk
     */
    private function addFunctionalChunk($section, $subsection, $content, $part = null) {
        $title = $section;
        if ($subsection) {
            $title .= " - " . $subsection;
        }
        if ($part) {
            $title .= " (Part {$part})";
        }
        
        $full_text = "## {$title}\n\n{$content}";
        
        $this->chunks[] = [
            'id' => count($this->chunks) + 1,
            'document' => 'WiziAI_QA_Functional_Document.md',
            'section' => $section,
            'subsection' => $subsection,
            'text' => $full_text,
            'metadata' => [
                'type' => 'functional_doc',
                'part' => $part,
                'word_count' => str_word_count($content)
            ]
        ];
        
        $this->stats['functional_doc_chunks']++;
    }

    /**
     * Generate embeddings for all chunks using OpenAI API
     */
    private function generateEmbeddings() {
        $embeddings = [];
        $total = count($this->chunks);
        $batch_size = 20; // Process in batches to show progress
        
        for ($i = 0; $i < $total; $i += $batch_size) {
            $batch = array_slice($this->chunks, $i, $batch_size);
            
            foreach ($batch as $chunk) {
                $embedding = $this->getEmbedding($chunk['text']);
                
                if ($embedding) {
                    $embeddings[] = array_merge($chunk, ['embedding' => $embedding]);
                    $progress = $i + count($embeddings) % $batch_size;
                    echo "   Processing: {$progress}/{$total} chunks...\r";
                } else {
                    $this->stats['errors']++;
                    echo "\n   ⚠️  Failed to generate embedding for chunk ID: {$chunk['id']}\n";
                }
                
                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second
            }
        }
        
        echo "\n";
        return $embeddings;
    }

    /**
     * Get embedding from OpenAI API
     */
    private function getEmbedding($text) {
        $url = 'https://api.openai.com/v1/embeddings';
        
        $data = [
            'model' => OPENAI_MODEL,
            'input' => $text,
            'encoding_format' => 'float'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local dev
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo "\n   ❌ cURL Error: {$curl_error}\n";
            return null;
        }
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result['data'][0]['embedding'] ?? null;
        } else {
            // Decode error response for debugging
            $error_data = json_decode($response, true);
            $error_message = $error_data['error']['message'] ?? 'Unknown error';
            echo "\n   ❌ OpenAI API Error (HTTP {$http_code}): {$error_message}\n";
            
            // If it's an API key error, show helpful message
            if ($http_code === 401) {
                echo "   💡 Hint: Check your OPENAI_API_KEY is valid and not expired\n";
            } elseif ($http_code === 429) {
                echo "   💡 Hint: Rate limit exceeded. Waiting 5 seconds...\n";
                sleep(5);
            }
        }
        
        return null;
    }

    /**
     * Save embeddings to JSON file
     */
    private function saveEmbeddings($embeddings) {
        $data = [
            'embeddings' => $embeddings,
            'metadata' => [
                'model' => OPENAI_MODEL,
                'dimension' => 1536,
                'generated_at' => date('c'),
                'total_chunks' => count($embeddings),
                'documents' => [
                    'Wizi_QA.md' => $this->stats['wizi_qa_chunks'],
                    'WiziAI_QA_Functional_Document.md' => $this->stats['functional_doc_chunks']
                ]
            ],
            'statistics' => $this->stats
        ];
        
        // Ensure the directory exists
        $dir = dirname(OUTPUT_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(OUTPUT_FILE, $json);
        
        $file_size = filesize(OUTPUT_FILE);
        $file_size_mb = round($file_size / 1024 / 1024, 2);
        echo "   ✓ File size: {$file_size_mb} MB\n";
    }

    /**
     * Display generation statistics
     */
    private function displayStatistics() {
        echo "📊 Generation Statistics:\n";
        echo "   • Wizi_QA.md chunks: {$this->stats['wizi_qa_chunks']}\n";
        echo "   • Functional Doc chunks: {$this->stats['functional_doc_chunks']}\n";
        echo "   • Total chunks: " . ($this->stats['wizi_qa_chunks'] + $this->stats['functional_doc_chunks']) . "\n";
        echo "   • Errors: {$this->stats['errors']}\n\n";
    }
}

// Run the generator
try {
    // Validate API key
    $api_key = OPENAI_API_KEY;
    
    if (empty($api_key) || $api_key === 'your-openai-api-key-here') {
        die("❌ Error: Please set your OPENAI_API_KEY in the script or environment variable.\n");
    }
    
    // Check API key format (OpenAI keys start with 'sk-')
    if (!preg_match('/^sk-[a-zA-Z0-9\-_]+$/', $api_key)) {
        echo "⚠️  Warning: API key format looks unusual. OpenAI keys typically start with 'sk-'\n";
        echo "   Key prefix: " . substr($api_key, 0, 10) . "...\n\n";
    }
    
    // Test API connection before processing
    echo "🔑 Testing OpenAI API connection...\n";
    $test_url = 'https://api.openai.com/v1/models';
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local dev
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $test_response = curl_exec($ch);
    $test_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $test_error = curl_error($ch);
    curl_close($ch);
    
    if ($test_error) {
        die("❌ Error: Cannot connect to OpenAI API. cURL error: {$test_error}\n");
    }
    
    if ($test_http_code === 401) {
        die("❌ Error: Invalid API key. Please check your OPENAI_API_KEY.\n");
    }
    
    if ($test_http_code !== 200) {
        $error_data = json_decode($test_response, true);
        $error_msg = $error_data['error']['message'] ?? 'Unknown error';
        die("❌ Error: OpenAI API returned HTTP {$test_http_code}: {$error_msg}\n");
    }
    
    echo "   ✓ API connection successful!\n\n";
    
    $generator = new Embedding_Generator($api_key);
    $generator->generate();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
