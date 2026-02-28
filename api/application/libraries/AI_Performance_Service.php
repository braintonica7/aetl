<?php

class AI_Performance_Service {
    
    private $openai_api_key;
    private $openai_model;
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        
        // Load configuration - try environment first, then config file
        $this->openai_api_key = getenv('OPENAI_API_KEY') ?: 
                               $this->CI->config->item('openai_api_key') ?: 
                               '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';
        
        $this->openai_model = getenv('OPENAI_MODEL') ?: 
                             $this->CI->config->item('openai_model') ?: 
                             'gpt-4o-mini';
        
        if ($this->openai_api_key === 'your-openai-api-key-here') {
            log_message('error', 'OPENAI_API_KEY not configured in environment or config file for AI Performance Service');
        }
    }
    
    /**
     * Generate comprehensive performance analysis using AI
     */
    public function generatePerformanceAnalysis($analysisData) {
        $current = $analysisData['current_performance'];
        $questions = $analysisData['questions_analysis'];
        $history = $analysisData['historical_performance'];
        
        // Build comprehensive prompt for AI analysis
        $prompt = $this->buildPerformancePrompt($current, $questions, $history);
        //log the prompt
        //log_message('debug', "AI Performance Analysis Prompt: " . json_encode($prompt));
        log_message('debug', "AI Performance Analysis Prompt Built for User ID: {$current['user_id']}, Quiz ID: {$current['quiz_id']}"); 
        try {
            // Call OpenAI API
            $response = $this->callOpenAI($prompt);
            log_message('debug', "AI Performance Analysis Response Received for User ID: {$current['user_id']}, Quiz ID: {$current['quiz_id']}");
            
            // Parse the AI response
            $parsedAnalysis = $this->parseAIResponse($response);
            
            // Add the raw prompt to the parsed analysis
            $parsedAnalysis['ai_prompt'] = $prompt;
            
            // log the parsed analysis
            log_message('info', "AI Performance Analysis Parsed for User ID: {$current['user_id']}, Quiz ID: {$current['quiz_id']}");       
            return $parsedAnalysis;
            
        } catch (Exception $e) {
            log_message('error', "AI Performance Analysis Error: " . $e->getMessage());
            $defaultAnalysis = $this->getDefaultAnalysis($current, $questions);
            // Add prompt to default analysis as well
            $defaultAnalysis['ai_prompt'] = $prompt;
            return $defaultAnalysis;
        }
    }
    
    /**
     * Build comprehensive prompt for performance analysis
     */
    private function buildPerformancePrompt($current, $questions, $history) {
        $total_questions = $current['total_questions'];
        $correct_answers = $current['correct_answers'];
        $incorrect_answers = $current['incorrect_answers'];
        $accuracy = round(($correct_answers / $total_questions) * 100, 2);
        
        // Handle avg_time_per_question safely - calculate if not provided
        if (isset($current['avg_time_per_question'])) {
            $avg_time = round($current['avg_time_per_question'], 2);
        } else {
            // Calculate from questions if available
            $total_time = 0;
            if (!empty($questions)) {
                foreach ($questions as $q) {
                    if (isset($q['time_taken']) && is_numeric($q['time_taken'])) {
                        $total_time += $q['time_taken'];
                    }
                }
            }
            $avg_time = $total_questions > 0 ? round($total_time / $total_questions, 2) : 0;
        }
        
        // Handle total_time safely
        $total_time = isset($current['total_time']) ? $current['total_time'] : ($avg_time * $total_questions);
        
        // Handle subject_name and quiz_name safely
        $subject_name = isset($current['subject_name']) ? $current['subject_name'] : 'Mixed';
        $quiz_name = isset($current['quiz_name']) ? $current['quiz_name'] : 'Quiz';
        
        // Analyze subjects and topics
        $subject_analysis = $this->analyzeSubjectsAndTopics($questions);
        
        // Historical comparison
        $historical_context = $this->buildHistoricalContext($history, $accuracy, $avg_time);
        
        $prompt = "
        You are an AI tutor analyzing a student's quiz performance. Provide a comprehensive analysis in JSON format.
        
        **CURRENT QUIZ PERFORMANCE:**
        - Quiz: {$quiz_name} ({$subject_name})
        - Total Questions: {$total_questions}
        - Correct Answers: {$correct_answers}
        - Incorrect Answers: {$incorrect_answers}
        - Accuracy: {$accuracy}%
        - Average Time per Question: {$avg_time} seconds
        - Total Time: {$total_time} seconds
        
        **QUESTION-WISE ANALYSIS:**
        " . $this->formatQuestionsAdaptive($questions) . "
        
        **SUBJECT/TOPIC BREAKDOWN:**
        " . $this->formatSubjectTopicAnalysis($subject_analysis) . "
        
        **HISTORICAL PERFORMANCE:**
        " . $historical_context . "
        
        **ANALYSIS REQUIREMENTS:**
        Provide a detailed JSON response with the following structure:
        {
            \"strongest_subject\": \"Subject name where student performed best\",
            \"weakest_subject\": \"Subject name needing improvement\",
            \"strongest_topic\": \"Specific topic with best performance\",
            \"weakest_topic\": \"Specific topic needing attention\",
            \"time_management_score\": 8.5,
            \"overall_progress_trend\": \"improving|declining|stable|first_attempt\",
            \"accuracy_improvement\": 5.2,
            \"time_improvement\": -10.5,
            \"performance_score\": 85.5,
            \"ai_recommendations\": [
                \"Specific recommendation 1\",
                \"Specific recommendation 2\",
                \"Specific recommendation 3\"
            ],
            \"ai_learning_suggestions\": [
                \"Study suggestion 1\",
                \"Study suggestion 2\",
                \"Study suggestion 3\"
            ],
            \"progress_insights\": [
                \"Progress insight 1\",
                \"Progress insight 2\"
            ],
            \"improvement_areas\": [
                \"Area needing improvement 1\",
                \"Area needing improvement 2\"
            ],
            \"strengths_identified\": [
                \"Strength 1\",
                \"Strength 2\"
            ],
            \"difficulty_performance\": \"Analysis of performance across different difficulty levels\",
            \"subject_scores\": {
                \"Physics\": 85.5,
                \"Mathematics\": 78.2
            },
            \"topic_scores\": {
                \"Mechanics\": 90.0,
                \"Thermodynamics\": 75.5
            }
        }
        
        **IMPORTANT GUIDELINES:**
        1. Be specific and actionable in recommendations
        2. Consider time management patterns
        3. Identify learning gaps and strengths
        4. Compare with historical performance if available
        5. Provide encouragement while being honest about areas needing work
        6. Focus on educational value and improvement strategies
        7. Return ONLY valid JSON, no additional text or formatting
        ";
        
        return $prompt;
    }
    
    /**
     * Build historical context for comparison
     */
    private function buildHistoricalContext($history, $current_accuracy, $current_avg_time) {
        if (empty($history)) {
            return "This is the student's first recorded quiz performance.";
        }
        
        $context = "Previous Quiz Performance:\n";
        foreach ($history as $index => $past) {
            $context .= "- Quiz " . ($index + 1) . ": {$past['accuracy_percentage']}% accuracy, ";
            $context .= "{$past['average_time_per_question']}s avg time ({$past['subject_name']})\n";
        }
        
        // Calculate trends
        $latest_accuracy = $history[0]['accuracy_percentage'];
        $accuracy_change = $current_accuracy - $latest_accuracy;
        
        $latest_time = $history[0]['average_time_per_question'];
        $time_change = $current_avg_time - $latest_time;
        
        $context .= "\nComparison with most recent quiz:\n";
        $context .= "- Accuracy change: " . ($accuracy_change >= 0 ? '+' : '') . "{$accuracy_change}%\n";
        $context .= "- Time change: " . ($time_change >= 0 ? '+' : '') . "{$time_change}s per question\n";
        
        return $context;
    }
    
    /**
     * Format questions for AI prompt with question text and intelligent truncation
     */
    private function formatQuestionsForPrompt($questions, $include_question_text = true, $max_question_length = 100) {
        $formatted = "";
        $total_questions = count($questions);
        
        foreach ($questions as $index => $q) {
            $status = $q['is_correct'] ? 'CORRECT' : 'INCORRECT';
            $formatted .= "Q" . ($index + 1) . ": ";
            
            // Include question text if available and requested
            if ($include_question_text && (!empty($q['question_text']) || !empty($q['ai_summary']))) {
                $question_text = '';
                
                // Prefer AI summary over full question text for token efficiency
                if (!empty($q['ai_summary'])) {
                    $question_text = $q['ai_summary'];
                } else if (!empty($q['question_text'])) {
                    $question_text = $q['question_text'];
                    
                    // Truncate long questions to save tokens while preserving key information
                    if (strlen($question_text) > $max_question_length) {
                        $question_text = substr($question_text, 0, $max_question_length) . "...";
                    }
                }
                
                // Clean and format the question text
                $question_text = $this->cleanQuestionText($question_text);
                $formatted .= "\"" . $question_text . "\" | ";
            }
            
            // Add metadata
            $formatted .= "{$q['difficulty']} level, {$q['subject_name']}, ";
            $formatted .= "Topic: {$q['topic_name']}, ";
            $formatted .= "Time: {$q['time_taken']}s, Status: {$status}";
            
            // Add selected answer if available
            if (!empty($q['selected_answer'])) {
                $formatted .= ", Selected: {$q['selected_answer']}";
            }
            
            // Add correct answer if available and question was incorrect
            if (!$q['is_correct'] && !empty($q['correct_answer'])) {
                $formatted .= ", Correct: {$q['correct_answer']}";
            }
            
            $formatted .= "\n";
        }
        
        return $formatted;
    }
    
    /**
     * Clean question text for prompt inclusion
     */
    private function cleanQuestionText($text) {
        // Remove excessive whitespace and line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters that might interfere with prompt parsing
        $text = str_replace(array('"', "\n", "\r", "\t"), array("'", " ", " ", " "), $text);
        
        // Trim and ensure proper formatting
        return trim($text);
    }
    
    /**
     * Format questions with adaptive detail based on quiz size
     */
    private function formatQuestionsAdaptive($questions) {
        $total_questions = count($questions);
        
        if ($total_questions <= 20) {
            // Small quiz: Include full question text and details
            return $this->formatQuestionsForPrompt($questions, true, 200);
        } elseif ($total_questions <= 50) {
            // Medium quiz: Include truncated question text
            return $this->formatQuestionsForPrompt($questions, true, 100);
        } else {
            // Large quiz: Focus on patterns, minimal question text
            return $this->formatQuestionsForLargeQuiz($questions);
        }
    }
    
    /**
     * Format questions for large quizzes with pattern focus
     */
    private function formatQuestionsForLargeQuiz($questions) {
        $formatted = "PATTERN ANALYSIS (" . count($questions) . " questions):\n\n";
        
        // Group questions by correctness and analyze patterns
        $correct_questions = array_filter($questions, function($q) { return $q['is_correct']; });
        $incorrect_questions = array_filter($questions, function($q) { return !$q['is_correct']; });
        
        // Analyze correct answers patterns
        if (!empty($correct_questions)) {
            $formatted .= "CORRECTLY ANSWERED QUESTIONS (" . count($correct_questions) . "):\n";
            $sample_correct = array_slice($correct_questions, 0, 5); // Sample of 5
            foreach ($sample_correct as $index => $q) {
                $question_preview = !empty($q['question_text']) ? 
                    substr($this->cleanQuestionText($q['question_text']), 0, 60) . "..." : 
                    "Question about {$q['topic_name']}";
                $formatted .= "✓ " . ($index + 1) . ". \"" . $question_preview . "\" | {$q['difficulty']}, {$q['subject_name']}, {$q['time_taken']}s\n";
            }
            if (count($correct_questions) > 5) {
                $formatted .= "... and " . (count($correct_questions) - 5) . " more correct answers\n";
            }
            $formatted .= "\n";
        }
        
        // Analyze incorrect answers patterns
        if (!empty($incorrect_questions)) {
            $formatted .= "INCORRECTLY ANSWERED QUESTIONS (" . count($incorrect_questions) . "):\n";
            $sample_incorrect = array_slice($incorrect_questions, 0, 8); // Larger sample for analysis
            foreach ($sample_incorrect as $index => $q) {
                $question_preview = !empty($q['question_text']) ? 
                    substr($this->cleanQuestionText($q['question_text']), 0, 60) . "..." : 
                    "Question about {$q['topic_name']}";
                $formatted .= "✗ " . ($index + 1) . ". \"" . $question_preview . "\" | {$q['difficulty']}, {$q['subject_name']}, {$q['time_taken']}s";
                
                if (!empty($q['selected_answer']) && !empty($q['correct_answer'])) {
                    $formatted .= " | Selected: {$q['selected_answer']}, Correct: {$q['correct_answer']}";
                }
                $formatted .= "\n";
            }
            if (count($incorrect_questions) > 8) {
                $formatted .= "... and " . (count($incorrect_questions) - 8) . " more incorrect answers\n";
            }
        }
        
        return $formatted;
    }
    
    /**
     * Call OpenAI API
     */
    private function callOpenAI($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => $this->openai_model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 2000,
            'temperature' => 0.3
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
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('CURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('OpenAI API Error: HTTP ' . $http_code . ' - ' . $response);
        }
        
        $decoded = json_decode($response, true);
        
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new Exception('Invalid OpenAI API response format');
        }
        
        return $decoded['choices'][0]['message']['content'];
    }
    
    /**
     * Parse AI response and extract structured data
     */
    private function parseAIResponse($response) {
        // Try to extract JSON from response
        $json_start = strpos($response, '{');
        $json_end = strrpos($response, '}');
        
        if ($json_start !== false && $json_end !== false) {
            $json_content = substr($response, $json_start, $json_end - $json_start + 1);
            $parsed = json_decode($json_content, true);
            
            if ($parsed) {
                // Convert arrays to strings for database storage
                $parsed['ai_recommendations'] = is_array($parsed['ai_recommendations']) 
                    ? implode("\n• ", $parsed['ai_recommendations']) : $parsed['ai_recommendations'];
                $parsed['ai_learning_suggestions'] = is_array($parsed['ai_learning_suggestions']) 
                    ? implode("\n• ", $parsed['ai_learning_suggestions']) : $parsed['ai_learning_suggestions'];
                $parsed['progress_insights'] = is_array($parsed['progress_insights']) 
                    ? implode("\n• ", $parsed['progress_insights']) : $parsed['progress_insights'];
                $parsed['improvement_areas'] = is_array($parsed['improvement_areas']) 
                    ? implode("\n• ", $parsed['improvement_areas']) : $parsed['improvement_areas'];
                $parsed['strengths_identified'] = is_array($parsed['strengths_identified']) 
                    ? implode("\n• ", $parsed['strengths_identified']) : $parsed['strengths_identified'];
                
                // Convert JSON objects to strings
                $parsed['subject_scores'] = isset($parsed['subject_scores']) 
                    ? json_encode($parsed['subject_scores']) : '{}';
                $parsed['topic_scores'] = isset($parsed['topic_scores']) 
                    ? json_encode($parsed['topic_scores']) : '{}';
                
                $parsed['raw_ai_response'] = $response;
                
                return $parsed;
            }
        }
        
        throw new Exception('Failed to parse AI response as JSON');
    }
    
    /**
     * Provide default analysis if AI fails
     */
    private function getDefaultAnalysis($current, $questions) {
        $accuracy = round(($current['correct_answers'] / $current['total_questions']) * 100, 2);
        
        return array(
            'strongest_subject' => isset($current['subject_name']) ? $current['subject_name'] : '',
            'weakest_subject' => isset($current['subject_name']) ? $current['subject_name'] : '',
            'strongest_topic' => 'General',
            'weakest_topic' => 'General',
            'time_management_score' => 7.0,
            'overall_progress_trend' => 'first_attempt',
            'accuracy_improvement' => 0,
            'time_improvement' => 0,
            'performance_score' => $accuracy,
            'ai_recommendations' => "• Continue practicing to improve accuracy\n• Review incorrect answers\n• Focus on time management",
            'ai_learning_suggestions' => "• Study the topics you got wrong\n• Practice similar questions\n• Time yourself during practice",
            'progress_insights' => "• This is your first attempt\n• Good foundation to build upon",
            'improvement_areas' => "• Overall accuracy\n• Speed and efficiency",
            'strengths_identified' => "• Completion of the quiz\n• Engagement with learning",
            'difficulty_performance' => 'Performance analysis not available - AI service unavailable',
            'subject_scores' => '{}',
            'topic_scores' => '{}',
            'raw_ai_response' => 'Default analysis - AI service was unavailable'
        );
    }
    
    /**
     * Get a representative sample of questions for large quizzes (optimization)
     */
    private function getQuestionSample($questions, $sample_size = 30) {
        $total_questions = count($questions);
        
        if ($total_questions <= $sample_size) {
            return $questions;
        }
        
        // Stratified sampling - ensure we get questions from different categories
        $correct_questions = array_filter($questions, function($q) { return $q['is_correct'] == 1; });
        $incorrect_questions = array_filter($questions, function($q) { return $q['is_correct'] == 0; });
        
        // Get proportional samples
        $correct_ratio = count($correct_questions) / $total_questions;
        $correct_sample_size = (int)($sample_size * $correct_ratio);
        $incorrect_sample_size = $sample_size - $correct_sample_size;
        
        // Randomly sample from each group
        $correct_sample = $this->randomSample($correct_questions, $correct_sample_size);
        $incorrect_sample = $this->randomSample($incorrect_questions, $incorrect_sample_size);
        
        // Combine and shuffle
        $sample = array_merge($correct_sample, $incorrect_sample);
        shuffle($sample);
        
        log_message('error', "Sampled {$sample_size} questions from {$total_questions} total questions");
        return $sample;
    }
    
    /**
     * Get random sample from array
     */
    private function randomSample($array, $size) {
        if (count($array) <= $size) {
            return $array;
        }
        
        $keys = array_rand($array, $size);
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        
        $sample = array();
        foreach ($keys as $key) {
            $sample[] = $array[$key];
        }
        
        return $sample;
    }
    
    /**
     * Build optimized AI prompt for large quizzes (200+ questions)
     */
    private function buildOptimizedAIPrompt($total_questions, $correct_answers, $accuracy, $avg_time, $time_taken_minutes, $difficulty_analysis, $subject_topic_analysis, $history, $question_sample) {
        $incorrect_answers = $total_questions - $correct_answers;
        
        $prompt = "
        You are an AI tutor analyzing a LARGE QUIZ performance with {$total_questions} questions. 
        Due to the large size, you're receiving aggregated data and a representative sample.
        Provide comprehensive analysis in JSON format.
        
        **QUIZ PERFORMANCE SUMMARY:**
        - Total Questions: {$total_questions}
        - Correct Answers: {$correct_answers}
        - Incorrect Answers: {$incorrect_answers}
        - Overall Accuracy: {$accuracy}%
        - Average Time per Question: {$avg_time} seconds
        - Total Time: {$time_taken_minutes} minutes
        
        **DIFFICULTY BREAKDOWN:**
        " . $this->formatDifficultyAnalysis($difficulty_analysis) . "
        
        **SUBJECT/TOPIC PERFORMANCE:**
        " . $this->formatSubjectTopicAnalysis($subject_topic_analysis) . "
        
        **REPRESENTATIVE QUESTION SAMPLE (" . count($question_sample) . " questions):**
        " . $this->formatQuestionsForPrompt($question_sample, true, 80) . "
        
        **HISTORICAL PERFORMANCE CONTEXT:**
        " . $this->buildHistoricalContext($history, $accuracy, $avg_time) . "
        
        **ANALYSIS REQUIREMENTS:**
        Based on this aggregated data and representative sample, provide detailed insights:
        
        {
            \"personalized_message\": \"Comprehensive feedback considering the large quiz scope\",
            \"motivational_message\": \"Encouraging message acknowledging the effort in completing {$total_questions} questions\",
            \"study_recommendations\": [
                \"Specific study recommendation 1\",
                \"Specific study recommendation 2\",
                \"Focus area based on weak performance patterns\"
            ],
            \"strengths_identified\": [
                \"Strong performance area 1\",
                \"Strong performance area 2\"
            ],
            \"improvement_areas\": [
                \"Area needing improvement 1\",
                \"Area needing improvement 2\"
            ],
            \"progress_insights\": [
                \"Insight about learning progress\",
                \"Pattern observation from the quiz\"
            ],
            \"time_management_analysis\": \"Analysis of time usage patterns across {$total_questions} questions\",
            \"difficulty_performance_insight\": \"How student performed across different difficulty levels\",
            \"subject_mastery_assessment\": \"Assessment of subject understanding\",
            \"next_learning_steps\": [
                \"Next step 1\",
                \"Next step 2\"
            ]
        }
        
        **IMPORTANT GUIDELINES FOR LARGE QUIZ ANALYSIS:**
        1. Focus on patterns rather than individual questions
        2. Consider the endurance aspect of completing {$total_questions} questions
        3. Analyze time management across the large quiz scope
        4. Identify systematic strengths and weaknesses
        5. Provide actionable feedback suitable for comprehensive review
        6. Acknowledge the significant effort in completing such a large quiz
        7. Return ONLY valid JSON, no additional text
        ";
        
        return $prompt;
    }
    
    /**
     * Format difficulty analysis for prompt
     */
    private function formatDifficultyAnalysis($difficulty_analysis) {
        $formatted = "";
        foreach ($difficulty_analysis as $level => $data) {
            $accuracy = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0;
            $formatted .= "- {$level}: {$data['correct']}/{$data['total']} ({$accuracy}%)\n";
        }
        return $formatted;
    }
    
    /**
     * Format subject/topic analysis for prompt
     */
    private function formatSubjectTopicAnalysis($analysis) {
        $formatted = "SUBJECTS:\n";
        foreach ($analysis['subjects'] as $subject => $data) {
            $accuracy = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0;
            $formatted .= "- {$subject}: {$data['correct']}/{$data['total']} ({$accuracy}%)\n";
        }
        
        $formatted .= "\nTOPICS (Top performing):\n";
        $top_topics = array_slice($analysis['topics'], 0, 10); // Limit to top 10 topics
        foreach ($top_topics as $topic => $data) {
            $accuracy = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100, 1) : 0;
            $formatted .= "- {$topic}: {$data['correct']}/{$data['total']} ({$accuracy}%)\n";
        }
        
        return $formatted;
    }
    
    /**
     * Generate performance analysis with intelligent optimization for large quizzes
     */
    public function generate_performance_analysis($user_id, $quiz_id) {
        try {
            $this->CI->load->model('user_performance/user_performance_model');
            $analysisData = $this->CI->user_performance_model->get_quiz_analysis_data($user_id, $quiz_id);
            
            if (empty($analysisData['questions_analysis'])) {
                return FALSE;
            }
            
            $questions = $analysisData['questions_analysis'];
            $history = $analysisData['historical_performance'];
            
            // Calculate basic metrics
            $total_questions = count($questions);
            $correct_answers = array_sum(array_column($questions, 'is_correct'));
            $incorrect_answers = $total_questions - $correct_answers;
            $accuracy = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100, 2) : 0;
            
            // Calculate timing
            $total_time = array_sum(array_column($questions, 'time_taken'));
            $avg_time = $total_questions > 0 ? round($total_time / $total_questions, 2) : 0;
            $time_taken_minutes = round($total_time / 60, 2);
            
            // Analyze by difficulty
            $difficulty_analysis = $this->analyzeDifficulty($questions);
            
            // Analyze subjects and topics
            $subject_topic_analysis = $this->analyzeSubjectsAndTopics($questions);
            
            // Determine if we need optimization for large quizzes
            $use_optimized_prompt = $total_questions > 50; // Threshold for optimization
            
            // Build AI prompt - use aggregated data for large quizzes
            if ($use_optimized_prompt) {
                $prompt = $this->buildOptimizedAIPrompt(
                    $total_questions, 
                    $correct_answers, 
                    $accuracy, 
                    $avg_time, 
                    $time_taken_minutes,
                    $difficulty_analysis,
                    $subject_topic_analysis,
                    $history,
                    $this->getQuestionSample($questions, 30) // Sample for AI analysis
                );
                
                log_message('error', "Using optimized prompt for {$total_questions} questions (sample size: 30)");
            } else {
                // Use existing buildPerformancePrompt for smaller quizzes
                $current = array(
                    'total_questions' => $total_questions,
                    'correct_answers' => $correct_answers,
                    'incorrect_answers' => $incorrect_answers,
                    'avg_time_per_question' => $avg_time,
                    'total_time' => $total_time,
                    'quiz_name' => 'Quiz',
                    'subject_name' => 'Mixed'
                );
                $prompt = $this->buildPerformancePrompt($current, $questions, $history);
            }
            
            // Call OpenAI
            $ai_response = $this->callOpenAI($prompt);
            
            if ($ai_response === FALSE) {
                
                log_message('error', "OpenAI API call failed for quiz {$quiz_id}, falling back to default analysis");
                $defaultAnalysis = $this->getDefaultAnalysis(array(
                    'total_questions' => $total_questions,
                    'correct_answers' => $correct_answers
                ), $questions);
                $defaultAnalysis['ai_prompt'] = $prompt; // Store prompt even for fallback
                return $defaultAnalysis;
            }
            
            // Parse AI response
            $ai_insights = json_decode($ai_response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                
                log_message('error', "JSON decode error in AI response: " . json_last_error_msg());
                $defaultAnalysis = $this->getDefaultAnalysis(array(
                    'total_questions' => $total_questions,
                    'correct_answers' => $correct_answers
                ), $questions);
                $defaultAnalysis['ai_prompt'] = $prompt; // Store prompt even for fallback
                return $defaultAnalysis;
            }
            
            // Historical comparison
            $historical_context = $this->buildHistoricalContext($history, $accuracy, $avg_time);
            
            // Return comprehensive analysis
            return array(
                'total_questions' => $total_questions,
                'correct_answers' => $correct_answers,
                'incorrect_answers' => $incorrect_answers,
                'score_percentage' => $accuracy,
                'time_taken_minutes' => $time_taken_minutes,
                'difficulty_performance' => $difficulty_analysis,
                'subject_performance' => $subject_topic_analysis['subjects'],
                'topic_performance' => $subject_topic_analysis['topics'],
                'chapter_performance' => $subject_topic_analysis['chapters'],
                'strengths' => $ai_insights['strengths_identified'] ?? array(),
                'weaknesses' => $ai_insights['improvement_areas'] ?? array(),
                'improvement_areas' => $ai_insights['improvement_areas'] ?? array(),
                'study_recommendations' => $ai_insights['study_recommendations'] ?? array(),
                'ai_generated_insights' => $ai_response,
                'ai_prompt' => $prompt, // Store the raw prompt sent to LLM
                'performance_trend' => $this->determinePerformanceTrend($history, $accuracy),
                'comparative_analysis' => array(
                    'historical_context' => $historical_context,
                    'trend_analysis' => $ai_insights['progress_insights'] ?? array()
                ),
                'personalized_feedback' => $ai_insights['personalized_message'] ?? '',
                'next_steps' => $ai_insights['study_recommendations'] ?? array(),
                'motivational_message' => $ai_insights['motivational_message'] ?? '',
                'detailed_analytics' => array(
                    'difficulty_breakdown' => $difficulty_analysis,
                    'subject_breakdown' => $subject_topic_analysis['subjects'],
                    'topic_breakdown' => $subject_topic_analysis['topics'],
                    'timing_analysis' => array(
                        'total_time_seconds' => $total_time,
                        'average_time_per_question' => $avg_time,
                        'fastest_question_time' => min(array_column($questions, 'time_taken')),
                        'slowest_question_time' => max(array_column($questions, 'time_taken'))
                    )
                ),
                'metadata' => array(
                    'analysis_timestamp' => date('Y-m-d H:i:s'),
                    'ai_model_used' => $this->openai_model,
                    'total_questions_analyzed' => $total_questions,
                    'optimized_prompt_used' => $use_optimized_prompt,
                    'question_sample_size' => $use_optimized_prompt ? 30 : $total_questions,
                    'data_sources' => array('user_questions', 'historical_performance')
                )
            );
            
        } catch (Exception $e) {
            log_message('error', "Error in generate_performance_analysis: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Analyze difficulty performance
     */
    private function analyzeDifficulty($questions) {
        $difficulty_stats = array();
        
        foreach ($questions as $q) {
            $difficulty = $q['difficulty'] ?: 'Unknown';
            
            if (!isset($difficulty_stats[$difficulty])) {
                $difficulty_stats[$difficulty] = array('correct' => 0, 'total' => 0);
            }
            
            $difficulty_stats[$difficulty]['total']++;
            
            if ($q['is_correct']) {
                $difficulty_stats[$difficulty]['correct']++;
            }
        }
        
        return $difficulty_stats;
    }
    
    /**
     * Enhanced subject and topic analysis with chapters
     */
    private function analyzeSubjectsAndTopics($questions) {
        $subjects = array();
        $topics = array();
        $chapters = array();
        
        foreach ($questions as $q) {
            $subject = $q['subject_name'] ?: 'Unknown';
            $topic = $q['topic_name'] ?: 'Unknown';
            $chapter = $q['chapter_name'] ?: 'Unknown';
            
            // Initialize arrays if not exist
            if (!isset($subjects[$subject])) {
                $subjects[$subject] = array('correct' => 0, 'total' => 0);
            }
            if (!isset($topics[$topic])) {
                $topics[$topic] = array('correct' => 0, 'total' => 0);
            }
            if (!isset($chapters[$chapter])) {
                $chapters[$chapter] = array('correct' => 0, 'total' => 0);
            }
            
            // Update totals
            $subjects[$subject]['total']++;
            $topics[$topic]['total']++;
            $chapters[$chapter]['total']++;
            
            // Update correct counts
            if ($q['is_correct']) {
                $subjects[$subject]['correct']++;
                $topics[$topic]['correct']++;
                $chapters[$chapter]['correct']++;
            }
        }
        
        return array(
            'subjects' => $subjects,
            'topics' => $topics,
            'chapters' => $chapters
        );
    }
    
    /**
     * Determine performance trend based on historical data
     */
    private function determinePerformanceTrend($history, $current_accuracy) {
        if (empty($history)) {
            return 'first_attempt';
        }
        
        if (count($history) == 1) {
            $prev_accuracy = $history[0]['accuracy_percentage'];
            if ($current_accuracy > $prev_accuracy + 5) {
                return 'improving';
            } elseif ($current_accuracy < $prev_accuracy - 5) {
                return 'declining';
            } else {
                return 'stable';
            }
        }
        
        // Multiple attempts - analyze trend
        $recent_scores = array_slice(array_column($history, 'accuracy_percentage'), 0, 3);
        $recent_scores[] = $current_accuracy;
        
        $trend_score = 0;
        for ($i = 1; $i < count($recent_scores); $i++) {
            if ($recent_scores[$i] > $recent_scores[$i-1]) {
                $trend_score++;
            } elseif ($recent_scores[$i] < $recent_scores[$i-1]) {
                $trend_score--;
            }
        }
        
        if ($trend_score >= 2) {
            return 'improving';
        } elseif ($trend_score <= -2) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
}

?>
