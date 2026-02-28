<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI Image Analysis Service
 * Provides OpenAI Vision API integration for analyzing question images
 * Extracts question text, options, subject, chapter, and topic information
 */
class AI_Image_Analysis_Service {
    
    private $openai_api_key;
    private $openai_model;
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        
        // Load configuration - try environment first, then config file
        $this->openai_api_key = getenv('OPENAI_API_KEY') ?: 
                               $this->CI->config->item('openai_api_key') ?: 
                               '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';
        
        $this->openai_model = getenv('OPENAI_VISION_MODEL') ?: 
                             $this->CI->config->item('openai_vision_model') ?: 
                             'gpt-4o-mini';
        
        if ($this->openai_api_key === 'your-openai-api-key-here') {
            log_message('error', 'OPENAI_API_KEY not configured in environment or config file');
        }
    }
    
    /**
     * Analyze question image to extract text, options, and educational metadata
     * 
     * @param string $image_url The HTTP/HTTPS URL of the image to analyze
     * @param string $additional_context Optional additional context for the analysis
     * @return array Analysis results or FALSE on failure
     */
    public function analyze_question_image($image_url, $additional_context = '') {
        try {
            // Validate the image URL
            if (!$this->validate_image_url($image_url)) {
                return FALSE;
            }
            //log message if this functions is called
            //log_message('debug', 'analyze_question_image called with URL: ' . $image_url);
            // Build the analysis prompt
            $prompt = $this->build_image_analysis_prompt($additional_context);
            // log the prompt for debugging
            //log_message('debug', 'Image analysis prompt: ' . $prompt);
            // Call OpenAI Vision API
            $response = $this->call_openai_vision($image_url, $prompt);
            //log_message('debug', 'OpenAI Vision API response: ' . $response);
            if ($response === FALSE) {
                return FALSE;
            }
            
            // Parse the response
            $parsed_result = $this->parse_analysis_response($response);
            
            if ($parsed_result === FALSE) {
                return FALSE;
            }
            
            // Add metadata
            $parsed_result['metadata'] = array(
                'analysis_timestamp' => date('Y-m-d H:i:s'),
                'model_used' => $this->openai_model,
                'image_url' => $image_url,
                'has_additional_context' => !empty($additional_context)
            );
            
            return $parsed_result;
            
        } catch (Exception $e) {
            log_message('error', "Error in analyze_question_image: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Generate step-by-step solution for a question image
     * 
     * @param string $image_url The HTTP/HTTPS URL of the question image
     * @param string $correct_option The correct answer option (optional)
     * @param string $additional_context Optional additional context for solution generation
     * @return array Solution results or FALSE on failure
     */
    public function generate_solution_for_image($image_url, $correct_option = '', $additional_context = '') {
        try {
            // Validate the image URL
            if (!$this->validate_image_url($image_url)) {
                return FALSE;
            }
            
            // Log function call for debugging
            //log_message('debug', 'generate_solution_for_image called with URL: ' . $image_url);
            
            // Build the solution generation prompt
            $prompt = $this->build_solution_generation_prompt($correct_option, $additional_context);
            
            // Log the prompt for debugging
            //log_message('debug', 'Solution generation prompt: ' . $prompt);
            
            // Call OpenAI Vision API
            $response = $this->call_openai_vision($image_url, $prompt);
            //log_message('debug', 'OpenAI Vision API response for solution: ' . $response);
            
            if ($response === FALSE) {
                return FALSE;
            }
           // log_message('debug', 'Parsing the response:');
            // Parse the response
            $parsed_result = $this->parse_solution_response($response);
           // log_message('debug', 'Parsed solution result: ' . print_r($parsed_result, true));
            if ($parsed_result === FALSE) {
                return FALSE;
            }
            
            // Add metadata
            $parsed_result['metadata'] = array(
                'solution_timestamp' => date('Y-m-d H:i:s'),
                'model_used' => $this->openai_model,
                'image_url' => $image_url,
                'correct_option_provided' => !empty($correct_option),
                'has_additional_context' => !empty($additional_context)
            );
            
            return $parsed_result;
            
        } catch (Exception $e) {
            log_message('error', "Error in generate_solution_for_image: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Build comprehensive prompt for image analysis
     */
    private function build_image_analysis_prompt($additional_context = '') {
        $base_prompt = "
        Please analyze this image and extract the following information:
        1. The main question text (extract EXACTLY as written)
        2. All answer options with their complete text (not just A, B, C, D)
        3. The subject area (e.g., Physics, Mathematics, Chemistry, Biology, etc.)
        4. The chapter within that subject (e.g., Mechanics, Thermodynamics, Algebra, etc.)
        5. The specific topic within that chapter (e.g., Collision, Heat Transfer, Linear Equations, etc.)
        6. Any additional context, instructions, or diagrams
        
        **CRITICAL:** Respond ONLY with a valid JSON object (no markdown, no code blocks) with this EXACT structure:
        {
            \"questionText\": \"The complete question text from the image\",
            \"extractedOptions\": [
                \"Complete text of option A (not just 'A')\",
                \"Complete text of option B (not just 'B')\",
                \"Complete text of option C (not just 'C')\",
                \"Complete text of option D (not just 'D')\"
            ],
            \"subject\": \"Primary subject (Physics, Mathematics, Chemistry, Biology, etc.)\",
            \"chapter\": \"Chapter within the subject (Mechanics, Algebra, Organic Chemistry, etc.)\",
            \"topic\": \"Specific topic (Collision, Linear Equations, Chemical Bonding, etc.)\",
            \"difficulty\": \"Easy|Medium|Hard\",
            \"additionalInfo\": \"Any diagrams, formulas, or special instructions\",
            \"confidence\": 0.95,
            \"hasFormulas\": true,
            \"hasDiagrams\": false,
            \"language\": \"English\",
            \"aiSummary\": \"Physics mechanics problem about projectile motion calculating maximum height with given initial velocity\"
        }
        
        **EXTRACTION GUIDELINES:**
        - Extract the COMPLETE text of each option, not abbreviations
        - If no clear options exist, set extractedOptions to empty array
        - Be specific with subject/chapter/topic classification
        - Set confidence between 0-1 based on image clarity
        - Identify if mathematical formulas or diagrams are present
        - Determine appropriate difficulty level
        - Generate a CONCISE summary for performance analysis (max 150 characters)
        - Return pure JSON only, no formatting or code blocks
        
        **LaTeX FORMATTING FOR EXTRACTED TEXT:**
        - When extracting questions with equations, format them with proper LaTeX:
          * Chemical equations: \"(A) \$\\text{CO}_2(g) + \\text{C}(s) \\leftrightarrow 2\\text{CO}(g); \\Delta H^\\circ = 172.5 \\text{ kJ}\$\"
          * Math expressions: \$\\frac{1}{2}mv^2\$, \$\\sqrt{x^2 + y^2}\$
          * Greek letters: \$\\alpha\$, \$\\beta\$, \$\\Delta H\$
        - Use \\n for line breaks in multi-line questions
        - Always wrap equations in \$ tags, never output raw LaTeX
        - For chemistry: use \\text{} around chemical formulas and proper arrow symbols
        
        **SUMMARY GUIDELINES:**
        The summary should be a brief, analytical description that captures:
        - Subject and topic
        - Key concept being tested
        - Type of problem (calculation, conceptual, application)
        - Any special elements (formulas, diagrams, etc.)
        
        Example summary: \"Physics mechanics problem about projectile motion calculating maximum height with given initial velocity\"
        ";
        
        if (!empty($additional_context)) {
            $base_prompt .= "\n\n**ADDITIONAL CONTEXT:** " . $additional_context;
        }
        
        return $base_prompt;
    }
    
    /**
     * Build comprehensive prompt for solution generation
     */
    private function build_solution_generation_prompt($correct_option = '', $additional_context = '') {
        $base_prompt = "
        Please analyze this question image and provide a detailed step-by-step solution that is easy for students to understand.";
        
        // Add critical additional context section if provided - this takes precedence
        if (!empty($additional_context)) {
            $base_prompt .= "
        
        🚨 **CRITICAL USER FEEDBACK - MUST FOLLOW THESE INSTRUCTIONS:**
        The user has provided specific feedback or corrections that you MUST address in your solution:
        
        " . $additional_context . "
        
        **MANDATORY ACTIONS BASED ON USER FEEDBACK:**
        - Carefully read and understand the user's specific concerns or corrections
        - If the user mentions errors in previous output, identify and correct those exact issues
        - If the user requests specific formatting changes, implement them precisely
        - If the user points out mathematical errors, double-check all calculations
        - If the user requests different explanation style, adjust your approach accordingly
        - If the user mentions missing steps, ensure comprehensive coverage
        - Address every point mentioned in the user feedback
        
        ⚠️ FAILURE TO FOLLOW USER FEEDBACK WILL RESULT IN INADEQUATE SOLUTION ⚠️
        ";
        }
        
        $base_prompt .= "
        
        **SOLUTION REQUIREMENTS:**
        1. Read and understand the question from the image
        2. Identify what is being asked
        3. List the given information and data
        4. Show the step-by-step solution process
        5. Explain each step clearly with reasoning
        6. Include any formulas or concepts used
        7. Provide the final answer
        8. Add helpful tips or common mistakes to avoid
        9. solutionSteps should be an array of steps and can go beyond 2 steps if needed, each with stepNumber, title, explanation, formula (if any), calculation (if any), and result (if any)
        10. If the question is theoretical, focus on key concepts and explanations
        11: if the questionText or equation contains equations, use LaTeX formatting for all equations and formulas

        **EQUATIONS REQUIREMENTS:**
        - For all equations, formulas, and expressions across ALL SUBJECTS, use LaTeX notation
        - Wrap inline equations in single dollar signs: \$equation\$
        - Wrap display equations in double dollar signs: \$\$equation\$\$
        - Use proper LaTeX syntax for fractions: \\frac{numerator}{denominator}
        - Use LaTeX for superscripts: x^{2}, subscripts: x_{1}, square roots: \\sqrt{x}
        - Use LaTeX for Greek letters: \\alpha, \\beta, \\gamma, \\pi, \\theta, \\Delta, \\Sigma, etc.
        - Use LaTeX for special symbols: \\times, \\div, \\pm, \\infty, \\sum, \\int, \\rightarrow, \\leftrightarrow, etc.
        
        **CRITICAL LaTeX FORMATTING RULES:**
        1. **Chemical Equations:** Always wrap complete chemical equations in single \$ tags:
           - Correct: \$\\text{H}_2\\text{O}_2(g) \\rightleftharpoons \\text{H}_2(g) + \\frac{1}{2}\\text{O}_2(g)\$
           - Correct: \$\\text{CO}_2(g) + \\text{C}(s) \\leftrightarrow 2\\text{CO}(g)\$
        
        2. **Thermodynamic Expressions:** Always wrap in \$ tags:
           - Correct: \$\\Delta H^\\circ = 172.5 \\text{ kJ}\$
           - Correct: \$\\Delta G^\\circ = -21.7 \\text{ kJ}\$
        
        3. **Multiple Choice Questions:** Format each option properly:
           - Correct: \"(A) \$\\text{CO}_2(g) + \\text{C}(s) \\leftrightarrow 2\\text{CO}(g); \\Delta H^\\circ = 172.5 \\text{ kJ}\$\"
        
        4. **Chemical Formulas with States:** Use \\text{} for chemical formulas:
           - Correct: \$\\text{H}_2\\text{O}(l)\$, \$\\text{CO}_2(g)\$, \$\\text{NaCl}(s)\$
        
        5. **Reaction Arrows:** Use proper LaTeX arrow commands:
           - \$\\rightarrow\$ for forward reaction
           - \$\\leftarrow\$ for reverse reaction  
           - \$\\leftrightarrow\$ for equilibrium
           - \$\\rightleftharpoons\$ for chemical equilibrium
        
        6. **Never use double backslashes (\\\\) in your output** - use single backslashes only
        
        7. **Multi-line content:** If content spans multiple lines, each line with LaTeX should be wrapped separately
        
        **SUBJECT-SPECIFIC LaTeX EXAMPLES:**
        
        **Mathematics:**
        * Quadratic formula: \$\$x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}\$\$
        * Derivatives: \$\\frac{dy}{dx}\$, Integrals: \$\\int_{a}^{b} f(x)dx\$
        * Trigonometry: \$\\sin\\theta\$, \$\\cos\\theta\$, \$\\tan\\theta\$
        
        **Physics:**
        * Force: \$F = ma\$
        * Energy: \$\$E = \\frac{1}{2}mv^2 + mgh\$\$
        * Ohm's Law: \$V = IR\$
        * Wave equation: \$v = f\\lambda\$
        * Einstein's equation: \$E = mc^2\$
        * Electric field: \$\\vec{E} = \\frac{\\vec{F}}{q}\$
        
        **Chemistry:**
        * Chemical equations: \$\\text{H}_2 + \\text{Cl}_2 \\rightarrow 2\\text{HCl}\$
        * Complex reactions: \$\\text{H}_2\\text{O}_2(g) \\rightleftharpoons \\text{H}_2(g) + \\frac{1}{2}\\text{O}_2(g)\$
        * Thermodynamics: \$\\Delta H^\\circ = 172.5 \\text{ kJ}\$, \$\\Delta G^\\circ = -21.7 \\text{ kJ}\$
        * Ideal gas law: \$PV = nRT\$
        * pH formula: \$\\text{pH} = -\\log[\\text{H}^+]\$
        * Rate law: \$\\text{rate} = k[\\text{A}]^m[\\text{B}]^n\$
        * Equilibrium constant: \$K_c = \\frac{[\\text{C}]^c[\\text{D}]^d}{[\\text{A}]^a[\\text{B}]^b}\$
        * Multiple choice format: \"(A) \$\\text{CO}_2(g) + \\text{C}(s) \\leftrightarrow 2\\text{CO}(g); \\Delta H^\\circ = 172.5 \\text{ kJ}\$\"
        
        **Biology:**
        * Hardy-Weinberg: \$p^2 + 2pq + q^2 = 1\$
        * Population growth: \$\\frac{dN}{dt} = rN\$
        * Michaelis-Menten: \$v = \\frac{V_{\\text{max}}[S]}{K_m + [S]}\$
        * Photosynthesis: \$6\\text{CO}_2 + 6\\text{H}_2\\text{O} \\xrightarrow{\\text{light}} \\text{C}_6\\text{H}_{12}\\text{O}_6 + 6\\text{O}_2\$

        **CRITICAL: RESPONSE FORMAT REQUIREMENT**
        You MUST respond with ONLY a valid JSON object. Do NOT include:
        - Markdown formatting (```json)
        - Code blocks
        - Explanatory text before or after the JSON
        - Nested JSON objects within string fields
        
        Respond with this EXACT structure as a single JSON object:
        {
            \"questionText\": \"The question text from the image\",
            \"questionType\": \"Multiple Choice|Numerical|Theoretical|Practical\",
            \"subject\": \"Subject name (Physics, Mathematics, Chemistry, etc.)\",
            \"topic\": \"Specific topic or concept\",
            \"difficulty\": \"Easy|Medium|Hard\",
            \"givenInformation\": [
                \"Given data point 1\",
                \"Given data point 2\",
                \"Given data point 3\"
            ],
            \"conceptsUsed\": [
                \"Key concept 1\",
                \"Key concept 2\", 
                \"Formula or principle used (with LaTeX)\"
            ],
            \"solutionSteps\": [
                {
                    \"stepNumber\": 1,
                    \"title\": \"Step title (e.g., 'Identify the given data')\",
                    \"explanation\": \"Clear explanation with LaTeX for any math: \$F = ma\$\",
                    \"formula\": \"LaTeX formatted formula: \$\$F = ma\$\$ (if applicable)\",
                    \"calculation\": \"LaTeX formatted calculation: \$F = 10 \\times 2 = 20\\text{ N}\$ (if any)\",
                    \"result\": \"Result with LaTeX: \$F = 20\\text{ N}\$\"
                },
                {
                    \"stepNumber\": 2,
                    \"title\": \"Next step title\",
                    \"explanation\": \"Explanation for step 2 with LaTeX equations\",
                    \"formula\": \"LaTeX formula if used\",
                    \"calculation\": \"LaTeX calculation if any\",
                    \"result\": \"LaTeX formatted result of step 2\"
                }    
            ],
            \"finalAnswer\": \"The complete final answer with LaTeX for equations and units if applicable\",
            \"keyInsights\": [
                \"Important insight 1 for student understanding (use LaTeX for equations)\",
                \"Key concept explanation with LaTeX\",
                \"Why this approach works\"
            ],
            \"commonMistakes\": [
                \"Common mistake students make (use LaTeX for expressions)\",
                \"Another frequent error to avoid\"
            ],
            \"alternativeApproaches\": [
                \"Alternative method 1 with LaTeX equations (brief description)\",
                \"Another way to solve with proper LaTeX formatting (if applicable)\"
            ],
            \"confidence\": 0.95,
            \"estimatedTimeToSolve\": \"5-10 minutes\",
            \"studyTips\": \"Additional tips for mastering this type of problem (use LaTeX for equations)\"
        }
        
        **SOLUTION GUIDELINES:**
        - Use clear, simple language suitable for students
        - Break down complex steps into smaller, manageable parts
        - Always explain WHY we do each step, not just HOW
        - Include units in calculations and final answers using LaTeX: \\text{units}
        - Make connections to fundamental concepts
        - Provide practical insights that help with similar problems
        - If multiple solutions exist, mention the most efficient approach first
        - Ensure mathematical accuracy in all calculations
        - ALWAYS use LaTeX formatting for ANY equation or formulas content
        - Test that LaTeX syntax is valid (escape backslashes properly in JSON)
        
        **SPECIAL FORMATTING FOR MULTI-LINE CONTENT:**
        - For multiple choice questions with chemistry, format each option clearly:
          Example: \"For which of the following reaction is product formation favoured by low pressure and low temperature?\\n\\n(A) \$\\text{CO}_2(g) + \\text{C}(s) \\leftrightarrow 2\\text{CO}(g); \\Delta H^\\circ = 172.5 \\text{ kJ}\$\\n(B) \$\\text{CO}(g) + 2\\text{H}_2(g) \\leftrightarrow \\text{CH}_3\\text{OH}; \\Delta H^\\circ = -21.7 \\text{ kJ}\$\"
        - Use \\n for line breaks in JSON strings
        - Always wrap complete chemical equations and thermodynamic expressions in \$ tags
        - Never use raw LaTeX without \$ delimiters in your output
        - If a line contains both text and equations, wrap only the equation parts in \$ tags
        ";
        
        if (!empty($correct_option)) {
            $base_prompt .= "\n\n**CORRECT ANSWER:** The correct answer is: " . $correct_option;
            $base_prompt .= "\nPlease verify your solution leads to this answer and explain why other options (if any) are incorrect.";
        }
        
        // Additional reminder about user feedback if provided
        if (!empty($additional_context)) {
            $base_prompt .= "\n\n🔄 **FINAL REMINDER:** Review the user feedback above and ensure ALL points are addressed in your solution.";
        }
        
        return $base_prompt;
    }
    
    /**
     * Generate AI summary for a question
     * Separate method for generating summaries of existing questions
     */
    public function generate_question_summary($question_text, $subject = '', $topic = '', $difficulty = '') {
        try {
            $prompt = $this->build_summary_prompt($question_text, $subject, $topic, $difficulty);
            
            $response = $this->call_openai_text($prompt);
            
            if ($response === FALSE) {
                return FALSE;
            }
            
            // Parse and clean the response
            $summary = $this->parse_summary_response($response);
            
            return array(
                'summary' => $summary,
                'generated_at' => date('Y-m-d H:i:s'),
                'confidence' => $this->estimate_summary_confidence($summary, $question_text)
            );
            
        } catch (Exception $e) {
            log_message('error', "Error generating question summary: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Build prompt specifically for question summarization
     */
    private function build_summary_prompt($question_text, $subject = '', $topic = '', $difficulty = '') {
        $context = '';
        if (!empty($subject)) $context .= "Subject: {$subject}. ";
        if (!empty($topic)) $context .= "Topic: {$topic}. ";
        if (!empty($difficulty)) $context .= "Difficulty: {$difficulty}. ";
        
        $prompt = "
        Generate a concise analytical summary (max 150 characters) for this educational question.
        
        {$context}
        
        **QUESTION:**
        {$question_text}
        
        **SUMMARY REQUIREMENTS:**
        1. Include subject and key topic
        2. Describe the type of problem (calculation, conceptual, application)
        3. Mention key concepts being tested
        4. Note any special elements (formulas, diagrams, real-world applications)
        5. Keep it under 150 characters
        6. Make it useful for performance analysis
        
        **FORMAT:** Return only the summary text, no additional formatting or explanations.
        
        **EXAMPLE:** \"Physics mechanics problem about projectile motion calculating maximum height with given initial velocity\"
        ";
        
        return $prompt;
    }
    
    /**
     * Call OpenAI for text-only requests (summaries)
     */
    private function call_openai_text($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-4o-mini', // Use standard GPT-5 Nano for text-only tasks
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 100, // Short response for summaries
            'temperature' => 0.1 // Consistent results
        );
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            log_message('error', "cURL error in summary generation: " . $curl_error);
            return FALSE;
        }
        
        if ($http_code !== 200) {
            log_message('error', "OpenAI API error for summary - HTTP {$http_code}: " . $response);
            return FALSE;
        }
        
        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "JSON decode error in summary response: " . json_last_error_msg());
            return FALSE;
        }
        
        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            log_message('error', "Invalid response structure from OpenAI for summary");
            return FALSE;
        }
        
        return $decoded_response['choices'][0]['message']['content'];
    }
    
    /**
     * Parse and clean summary response
     */
    private function parse_summary_response($response) {
        // Clean the response
        $summary = trim($response);
        
        // Remove any quotes or extra formatting
        $summary = trim($summary, '"\'');
        
        // Ensure it's within character limit
        if (strlen($summary) > 150) {
            $summary = substr($summary, 0, 147) . '...';
        }
        
        return $summary;
    }
    
    /**
     * Estimate confidence of summary quality
     */
    private function estimate_summary_confidence($summary, $original_text) {
        $confidence = 0.5; // Base confidence
        
        // Check if summary contains key educational elements
        $educational_keywords = array('problem', 'calculate', 'find', 'determine', 'analyze', 'concept', 'formula', 'equation');
        foreach ($educational_keywords as $keyword) {
            if (stripos($summary, $keyword) !== false) {
                $confidence += 0.1;
            }
        }
        
        // Check if summary mentions subject areas
        $subjects = array('physics', 'mathematics', 'chemistry', 'biology', 'math', 'science');
        foreach ($subjects as $subject) {
            if (stripos($summary, $subject) !== false) {
                $confidence += 0.1;
            }
        }
        
        // Check length appropriateness
        if (strlen($summary) >= 50 && strlen($summary) <= 150) {
            $confidence += 0.1;
        }
        
        // Ensure confidence doesn't exceed 1.0
        return min($confidence, 1.0);
    }
    
    /**
     * Generate fallback summary when AI summary is not available
     */
    private function generate_fallback_summary($parsed_data) {
        $subject = $parsed_data['subject'] ?? 'Unknown';
        $topic = $parsed_data['topic'] ?? 'Unknown';
        $difficulty = $parsed_data['difficulty'] ?? 'Medium';
        
        $summary = "{$subject}";
        if ($topic !== 'Unknown') {
            $summary .= " {$topic}";
        }
        $summary .= " question";
        if ($difficulty !== 'Medium') {
            $summary .= " ({$difficulty} level)";
        }
        
        // Add context based on available information
        if (!empty($parsed_data['hasFormulas'])) {
            $summary .= " with formulas";
        }
        if (!empty($parsed_data['hasDiagrams'])) {
            $summary .= " with diagrams";
        }
        
        return substr($summary, 0, 150);
    }
    
    /**
     * Batch generate summaries for existing questions
     */
    public function batch_generate_summaries($questions_data) {
        $results = array();
        $successful = 0;
        $failed = 0;
        
        foreach ($questions_data as $index => $question) {
            $question_text = $question['question_text'] ?? '';
            $subject = $question['subject'] ?? '';
            $topic = $question['topic'] ?? '';
            $difficulty = $question['difficulty'] ?? '';
            
            if (empty($question_text)) {
                $results[] = array(
                    'index' => $index,
                    'question_id' => $question['id'] ?? null,
                    'status' => 'skipped',
                    'reason' => 'No question text available'
                );
                continue;
            }
            
            $summary_result = $this->generate_question_summary($question_text, $subject, $topic, $difficulty);
            
            if ($summary_result !== FALSE) {
                $successful++;
                $results[] = array(
                    'index' => $index,
                    'question_id' => $question['id'] ?? null,
                    'status' => 'success',
                    'summary' => $summary_result['summary'],
                    'confidence' => $summary_result['confidence'],
                    'generated_at' => $summary_result['generated_at']
                );
            } else {
                $failed++;
                $results[] = array(
                    'index' => $index,
                    'question_id' => $question['id'] ?? null,
                    'status' => 'failed',
                    'error' => 'Summary generation failed'
                );
            }
            
            // Add delay to avoid rate limiting
            if ($index < count($questions_data) - 1) {
                sleep(1);
            }
        }
        
        return array(
            'total_processed' => count($questions_data),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
            'batch_timestamp' => date('Y-m-d H:i:s')
        );
    }
    
    /**
     * Call OpenAI Vision API
     */
    private function call_openai_vision($image_url, $prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => $this->openai_model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $prompt
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => $image_url,
                                'detail' => 'high'
                            )
                        )
                    )
                )
            ),
            'max_tokens' => 3000,
            'temperature' => 0.1 // Low temperature for consistent results
        );
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Longer timeout for vision API
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            log_message('error',"cURL error in vision API call: " . $curl_error);
            return FALSE;
        }
        
        if ($http_code !== 200) {
            log_message('error',"OpenAI Vision API error - HTTP {$http_code}: " . $response);
            return FALSE;
        }
        
        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error',"JSON decode error in vision API response: " . json_last_error_msg());
            return FALSE;
        }
        
        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            log_message('error',"Invalid response structure from OpenAI Vision API");
            return FALSE;
        }
        
        return $decoded_response['choices'][0]['message']['content'];
    }
    
    /**
     * Parse the AI response and extract structured data
     */
    private function parse_analysis_response($response) {
        try {
            // Clean the response - remove markdown code blocks if present
            $json_content = $response;
            
            // Check for markdown code blocks
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $response, $matches)) {
                $json_content = $matches[1];
            }
            
            // Remove any leading/trailing whitespace
            $json_content = trim($json_content);
            
            // Parse JSON
            $parsed = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "JSON parsing error: " . json_last_error_msg());
                log_message('error', "Raw response: " . $response);
                
                // Fallback - return basic structure with raw content
                return array(
                    'questionText' => $response,
                    'extractedOptions' => array(),
                    'subject' => 'Unknown',
                    'chapter' => 'Unknown',
                    'topic' => 'Unknown',
                    'difficulty' => 'Medium',
                    'additionalInfo' => 'Response was not in expected JSON format',
                    'confidence' => 0.3,
                    'hasFormulas' => false,
                    'hasDiagrams' => false,
                    'language' => 'English',
                    'aiSummary' => 'Question analysis failed - unable to generate summary'
                );
            }
            
            // Validate and normalize the parsed response
            return array(
                'questionText' => $parsed['questionText'] ?? 'Could not extract question text',
                'extractedOptions' => $parsed['extractedOptions'] ?? array(),
                'subject' => $parsed['subject'] ?? 'Unknown',
                'chapter' => $parsed['chapter'] ?? 'Unknown',
                'topic' => $parsed['topic'] ?? 'Unknown',
                'difficulty' => $parsed['difficulty'] ?? 'Medium',
                'additionalInfo' => $parsed['additionalInfo'] ?? '',
                'confidence' => (float)($parsed['confidence'] ?? 0.5),
                'hasFormulas' => (bool)($parsed['hasFormulas'] ?? false),
                'hasDiagrams' => (bool)($parsed['hasDiagrams'] ?? false),
                'language' => $parsed['language'] ?? 'English',
                'aiSummary' => $parsed['aiSummary'] ?? $this->generate_fallback_summary($parsed)
            );
            
        } catch (Exception $e) {
            log_message('error', "Error parsing analysis response: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Parse the AI solution response and extract structured data
     */
    private function parse_solution_response($response) {
        try {
            // Clean the response - remove markdown code blocks if present
            $json_content = $response;
            
            // Check for markdown code blocks
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $response, $matches)) {
                $json_content = $matches[1];
            }
            
            // Remove any leading/trailing whitespace
            $json_content = trim($json_content);
            
            // Parse JSON
            $parsed = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "JSON parsing error in solution response: " . json_last_error_msg());
                log_message('error', "Raw response: " . $response);
                
                // Fallback - return basic structure with raw content
                return array(
                    'questionText' => $response,
                    'questionType' => 'Unknown',
                    'subject' => 'Unknown',
                    'topic' => 'Unknown',
                    'difficulty' => 'Medium',
                    'givenInformation' => array(),
                    'conceptsUsed' => array(),
                    'solutionSteps' => array(
                        array(
                            'stepNumber' => 1,
                            'title' => 'Solution parsing failed',
                            'explanation' => 'Unable to parse the solution response into structured format',
                            'formula' => '',
                            'calculation' => '',
                            'result' => 'Response was not in expected JSON format'
                        )
                    ),
                    'finalAnswer' => 'Could not extract final answer',
                    'keyInsights' => array('Solution parsing failed - response format issue'),
                    'commonMistakes' => array(),
                    'alternativeApproaches' => array(),
                    'confidence' => 0.2,
                    'estimatedTimeToSolve' => 'Unknown',
                    'studyTips' => 'Please try again or contact support'
                );
            }
            
            // Check if the AI returned nested JSON (where questionText contains the full response)
            if (isset($parsed['questionText']) && is_string($parsed['questionText'])) {
                // Try to parse the questionText as JSON
                $nested_json = trim($parsed['questionText']);
                $nested_parsed = json_decode($nested_json, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($nested_parsed['questionText'])) {
                    log_message('error', "Detected nested JSON response - extracting inner structure");
                    // Use the nested structure as the main parsed data
                    $parsed = $nested_parsed;
                }
            }
            
            // Validate and normalize the parsed response
            return array(
                'questionText' => $parsed['questionText'] ?? 'Could not extract question text',
                'questionType' => $parsed['questionType'] ?? 'Unknown',
                'subject' => $parsed['subject'] ?? 'Unknown',
                'topic' => $parsed['topic'] ?? 'Unknown',
                'difficulty' => $parsed['difficulty'] ?? 'Medium',
                'givenInformation' => $parsed['givenInformation'] ?? array(),
                'conceptsUsed' => $parsed['conceptsUsed'] ?? array(),
                'solutionSteps' => $this->validate_solution_steps($parsed['solutionSteps'] ?? array()),
                'finalAnswer' => $parsed['finalAnswer'] ?? 'Could not extract final answer',
                'keyInsights' => $parsed['keyInsights'] ?? array(),
                'commonMistakes' => $parsed['commonMistakes'] ?? array(),
                'alternativeApproaches' => $parsed['alternativeApproaches'] ?? array(),
                'confidence' => (float)($parsed['confidence'] ?? 0.5),
                'estimatedTimeToSolve' => $parsed['estimatedTimeToSolve'] ?? 'Unknown',
                'studyTips' => $parsed['studyTips'] ?? ''
            );
            
        } catch (Exception $e) {
            log_message('error', "Error parsing solution response: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Validate and normalize solution steps
     */
    private function validate_solution_steps($steps) {
        if (!is_array($steps)) {
            return array();
        }
        
        $validated_steps = array();
        foreach ($steps as $index => $step) {
            $validated_steps[] = array(
                'stepNumber' => isset($step['stepNumber']) ? (int)$step['stepNumber'] : ($index + 1),
                'title' => $step['title'] ?? "Step " . ($index + 1),
                'explanation' => $step['explanation'] ?? '',
                'formula' => $step['formula'] ?? '',
                'calculation' => $step['calculation'] ?? '',
                'result' => $step['result'] ?? ''
            );
        }
        
        return $validated_steps;
    }
    
    /**
     * Validate image URL
     */
    private function validate_image_url($url) {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            log_message('error', "Invalid URL format: " . $url);
            return FALSE;
        }
        
        // Check if URL uses HTTP or HTTPS
        $parsed_url = parse_url($url);
        if (!in_array($parsed_url['scheme'], array('http', 'https'))) {
            log_message('error', "Invalid URL scheme (must be http or https): " . $url);
            return FALSE;
        }
        
        // Check if URL has image-like extension (optional warning)
        $path = strtolower($parsed_url['path'] ?? '');
        $image_extensions = array('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp');
        $has_image_extension = false;
        
        foreach ($image_extensions as $ext) {
            if (substr($path, -strlen($ext)) === $ext) {
                $has_image_extension = true;
                break;
            }
        }
        
        if (!$has_image_extension) {
            log_message('error', "Warning: URL doesn't appear to have image extension: " . $url);
        }
        
        return TRUE;
    }
    
    /**
     * Batch analyze multiple images
     */
    public function batch_analyze_images($image_urls, $additional_context = '') {
        $results = array();
        $successful = 0;
        $failed = 0;
        
        foreach ($image_urls as $index => $url) {
            $result = $this->analyze_question_image($url, $additional_context);
            
            if ($result !== FALSE) {
                $successful++;
                $results[] = array(
                    'index' => $index,
                    'url' => $url,
                    'status' => 'success',
                    'analysis' => $result
                );
            } else {
                $failed++;
                $results[] = array(
                    'index' => $index,
                    'url' => $url,
                    'status' => 'failed',
                    'error' => 'Analysis failed'
                );
            }
            
            // Add small delay to avoid rate limiting
            if ($index < count($image_urls) - 1) {
                sleep(1);
            }
        }
        
        return array(
            'total_processed' => count($image_urls),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
            'batch_timestamp' => date('Y-m-d H:i:s')
        );
    }
    
    /**
     * Health check for OpenAI Vision service
     */
    public function health_check() {
        try {
            // Test with a simple API call (models list)
            $url = 'https://api.openai.com/v1/models';
            
            $headers = array(
                'Authorization: Bearer ' . $this->openai_api_key
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $http_code === 200;
            
        } catch (Exception $e) {
            log_message('error', "OpenAI Vision health check failed: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Get service status and configuration
     */
    public function get_service_status() {
        return array(
            'service_name' => 'AI Image Analysis Service',
            'model' => $this->openai_model,
            'api_key_configured' => $this->openai_api_key !== 'your-openai-api-key-here',
            'health_status' => $this->health_check(),
            'supported_formats' => array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'),
            'max_image_size' => '20MB',
            'timeout' => '60 seconds',
            'version' => '1.0.0'
        );
    }
}

?>
