<?php

class AI_Overall_Performance_Service {
    
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
    }
    
    /**
     * Generate comprehensive overall performance analysis using AI
     */
    public function generateOverallPerformanceAnalysis($performanceData, $previousSummary = null) {
        $prompt = $this->buildOverallPerformancePrompt($performanceData, $previousSummary);
        
        log_message('debug', "AI Overall Performance Analysis Starting for User");
        
        try {
            // Call OpenAI API
            $response = $this->callOpenAI($prompt);
            log_message('debug', "AI Overall Performance Analysis Response Received");
            
            // Parse the AI response
            $parsedAnalysis = $this->parseAIResponse($response);
            
            // Add the raw prompt to the parsed analysis
            $parsedAnalysis['ai_prompt'] = $prompt;
            
            log_message('info', "AI Overall Performance Analysis Completed Successfully");
            return $parsedAnalysis;
            
        } catch (Exception $e) {
            log_message('error', "AI Overall Performance Analysis Error: " . $e->getMessage());
            $defaultAnalysis = $this->getDefaultOverallAnalysis($performanceData);
            $defaultAnalysis['ai_prompt'] = $prompt;
            return $defaultAnalysis;
        }
    }
    
    /**
     * Build comprehensive prompt for overall performance analysis
     */
    private function buildOverallPerformancePrompt($data, $previousSummary) {
        $performance_records = $data['performance_records'];
        $activity_data = $data['activity_data'];
        $difficulty_data = $data['difficulty_data'];
        
        // Calculate summary statistics
        $total_quizzes = count($performance_records);
        $total_questions = array_sum(array_column($performance_records, 'total_questions'));
        $total_correct = array_sum(array_column($performance_records, 'correct_answers'));
        $overall_accuracy = $total_questions > 0 ? round(($total_correct / $total_questions) * 100, 2) : 0;
        
        // Subject analysis
        $subject_stats = $this->analyzeSubjects($performance_records);
        
        // Time trend analysis
        $time_trends = $this->analyzeTimeTrends($performance_records);
        
        // Activity patterns
        $activity_patterns = $this->analyzeActivityPatterns($activity_data);
        
        // Previous comparison
        $comparison_context = $this->buildComparisonContext($previousSummary, $overall_accuracy);
        
        $prompt = "
        You are an AI educational analytics expert analyzing a student's overall performance across {$data['period_start']} to {$data['period_end']} (6 months). 
        Provide a comprehensive analysis in JSON format with the following structure:
        
        **OVERALL PERFORMANCE SUMMARY:**
        - Total Quizzes: {$total_quizzes}
        - Total Questions: {$total_questions}
        - Overall Accuracy: {$overall_accuracy}%
        - Analysis Period: {$data['period_start']} to {$data['period_end']}
        
        **PERFORMANCE TRENDS:**
        " . $this->formatTimeTrends($time_trends) . "
        
        **SUBJECT-WISE ANALYSIS:**
        " . $this->formatSubjectAnalysis($subject_stats) . "
        
        **DIFFICULTY LEVEL PERFORMANCE:**
        " . $this->formatDifficultyAnalysis($difficulty_data) . "
        
        **LEARNING ACTIVITY PATTERNS:**
        " . $this->formatActivityPatterns($activity_patterns) . "
        
        **HISTORICAL COMPARISON:**
        " . $comparison_context . "
        
        **REQUIRED JSON RESPONSE STRUCTURE:**
        {
            \"overall_assessment\": \"Comprehensive 2-3 paragraph assessment of overall performance, learning trajectory, and current standing\",
            \"learning_patterns\": \"Detailed analysis of learning habits, study patterns, consistency, and behavioral insights\",
            \"strengths_summary\": \"Comprehensive summary of identified strengths across subjects, skills, and learning approaches\",
            \"improvement_areas\": \"Specific areas needing attention with concrete recommendations\",
            \"study_recommendations\": \"Detailed study schedule, methods, and strategies tailored to learning style and performance level\",
            \"predicted_performance\": \"Evidence-based predictions for future performance with specific targets and timelines\",
            \"personalized_goals\": \"SMART goals tailored to current performance level and learning trajectory\",
            
            \"performance_trend\": \"improving|declining|stable|inconsistent\",
            \"accuracy_trend\": \"improving|declining|stable\",
            \"speed_trend\": \"getting_faster|getting_slower|stable\",
            \"consistency_score\": 85.5,
            \"mastery_level\": \"Beginner|Developing|Proficient|Expert|Master\",
            \"learning_velocity\": \"slow|moderate|fast|accelerating\",
            \"improvement_rate\": 15.2,
            
            \"strongest_subject\": \"Subject Name\",
            \"weakest_subject\": \"Subject Name\",
            \"strongest_topic\": \"Topic Name\",
            \"weakest_topic\": \"Topic Name\",
            \"preferred_difficulty\": \"easy|medium|hard\",
            \"time_efficiency_rating\": \"Excellent|Good|Average|Needs Improvement\",
            
            \"recommended_daily_study_minutes\": 45,
            \"recommended_focus_subjects\": [\"Subject1\", \"Subject2\"],
            \"recommended_difficulty_progression\": \"Current → Next level strategy\",
            \"next_milestone\": \"Specific achievable goal within 2-4 weeks\",
            
            \"most_active_day\": \"Monday|Tuesday|...\",
            \"most_active_time\": \"morning|afternoon|evening\",
            \"quiz_frequency_per_week\": 3.5,
            \"average_session_length_minutes\": 25.5
        }
        
        **ANALYSIS GUIDELINES:**
        - Consider performance level (beginner vs advanced) in recommendations
        - Identify learning patterns and habits
        - Provide actionable insights and specific recommendations
        - Focus on trends, not just current state
        - Consider learning efficiency and time management
        - Suggest realistic improvement strategies
        - Be encouraging but honest about areas needing work
        - Tailor advice to detected learning style and preferences
        
        **RESPONSE REQUIREMENTS:**
        - Must be valid JSON format
        - All string fields must be properly escaped
        - Numerical values must be realistic and evidence-based
        - Recommendations must be specific and actionable
        - Assessment must be balanced (strengths AND areas for improvement)
        - Consider student motivation and learning psychology
        ";
        
        return $prompt;
    }
    
    /**
     * Analyze subject performance trends
     */
    private function analyzeSubjects($performance_records) {
        $subjects = array();
        
        foreach ($performance_records as $record) {
            $subject = $record['subject_name'] ?: 'Unknown';
            if (!isset($subjects[$subject])) {
                $subjects[$subject] = array(
                    'total_questions' => 0,
                    'correct_answers' => 0,
                    'total_time' => 0,
                    'quiz_count' => 0
                );
            }
            
            $subjects[$subject]['total_questions'] += $record['total_questions'];
            $subjects[$subject]['correct_answers'] += $record['correct_answers'];
            $subjects[$subject]['total_time'] += $record['total_time_spent'];
            $subjects[$subject]['quiz_count']++;
        }
        
        // Calculate accuracies
        foreach ($subjects as $subject => $stats) {
            $subjects[$subject]['accuracy'] = $stats['total_questions'] > 0 ? 
                round(($stats['correct_answers'] / $stats['total_questions']) * 100, 2) : 0;
        }
        
        return $subjects;
    }
    
    /**
     * Analyze time trends in performance
     */
    private function analyzeTimeTrends($performance_records) {
        if (count($performance_records) < 2) {
            return array('trend' => 'insufficient_data');
        }
        
        // Sort by date
        usort($performance_records, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        
        // Calculate moving averages
        $accuracies = array();
        $speeds = array();
        
        foreach ($performance_records as $record) {
            $accuracy = $record['total_questions'] > 0 ? 
                ($record['correct_answers'] / $record['total_questions']) * 100 : 0;
            $speed = $record['average_time_per_question'];
            
            $accuracies[] = $accuracy;
            $speeds[] = $speed;
        }
        
        return array(
            'accuracy_trend' => $this->calculateTrend($accuracies),
            'speed_trend' => $this->calculateTrend($speeds, true), // true for inverse (lower is better)
            'accuracy_data' => $accuracies,
            'speed_data' => $speeds
        );
    }
    
    /**
     * Calculate trend direction
     */
    private function calculateTrend($data, $inverse = false) {
        if (count($data) < 3) return 'stable';
        
        $recent = array_slice($data, -3); // Last 3 data points
        $early = array_slice($data, 0, 3);  // First 3 data points
        
        $recent_avg = array_sum($recent) / count($recent);
        $early_avg = array_sum($early) / count($early);
        
        $change_percentage = ($recent_avg - $early_avg) / $early_avg * 100;
        
        if ($inverse) {
            $change_percentage = -$change_percentage; // Invert for metrics where lower is better
        }
        
        if ($change_percentage > 10) return 'improving';
        if ($change_percentage < -10) return 'declining';
        return 'stable';
    }
    
    /**
     * Analyze activity patterns
     */
    private function analyzeActivityPatterns($activity_data) {
        if (empty($activity_data)) {
            return array('most_active_day' => null, 'most_active_time' => null);
        }
        
        $day_counts = array();
        $hour_counts = array();
        
        foreach ($activity_data as $activity) {
            $day = $activity['day_name'];
            $hour = (int)$activity['hour_of_day'];
            
            $day_counts[$day] = ($day_counts[$day] ?? 0) + $activity['question_count'];
            $hour_counts[$hour] = ($hour_counts[$hour] ?? 0) + $activity['question_count'];
        }
        
        $most_active_day = !empty($day_counts) ? array_keys($day_counts, max($day_counts))[0] : null;
        
        $most_active_hour = !empty($hour_counts) ? array_keys($hour_counts, max($hour_counts))[0] : null;
        $most_active_time = $this->categorizeTimeOfDay($most_active_hour);
        
        return array(
            'most_active_day' => $most_active_day,
            'most_active_time' => $most_active_time,
            'day_distribution' => $day_counts,
            'hour_distribution' => $hour_counts
        );
    }
    
    /**
     * Categorize hour into time of day
     */
    private function categorizeTimeOfDay($hour) {
        if ($hour === null) return null;
        
        if ($hour >= 6 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        if ($hour >= 18 && $hour < 22) return 'evening';
        return 'night';
    }
    
    /**
     * Format subject analysis for prompt
     */
    private function formatSubjectAnalysis($subjects) {
        $formatted = "";
        foreach ($subjects as $subject => $stats) {
            $formatted .= "- {$subject}: {$stats['accuracy']}% accuracy ({$stats['quiz_count']} quizzes)\n";
        }
        return $formatted;
    }
    
    /**
     * Format difficulty analysis for prompt
     */
    private function formatDifficultyAnalysis($difficulty_data) {
        $formatted = "";
        foreach ($difficulty_data as $diff) {
            $accuracy = $diff['total_questions'] > 0 ? 
                round(($diff['correct_answers'] / $diff['total_questions']) * 100, 2) : 0;
            $formatted .= "- {$diff['level']}: {$accuracy}% accuracy ({$diff['total_questions']} questions)\n";
        }
        return $formatted;
    }
    
    /**
     * Format time trends for prompt
     */
    private function formatTimeTrends($trends) {
        if (isset($trends['trend']) && $trends['trend'] === 'insufficient_data') {
            return "- Insufficient data for trend analysis\n";
        }
        
        return "- Accuracy Trend: {$trends['accuracy_trend']}\n- Speed Trend: {$trends['speed_trend']}\n";
    }
    
    /**
     * Format activity patterns for prompt
     */
    private function formatActivityPatterns($patterns) {
        $day = $patterns['most_active_day'] ?: 'Not determined';
        $time = $patterns['most_active_time'] ?: 'Not determined';
        return "- Most Active Day: {$day}\n- Most Active Time: {$time}\n";
    }
    
    /**
     * Build comparison context with previous summary
     */
    private function buildComparisonContext($previousSummary, $currentAccuracy) {
        if (!$previousSummary) {
            return "- No previous summary available for comparison\n";
        }
        
        $prev_accuracy = $previousSummary->overall_accuracy_percentage;
        $change = $currentAccuracy - $prev_accuracy;
        $change_text = $change > 0 ? "improved by {$change}%" : "declined by " . abs($change) . "%";
        
        return "- Previous Period Accuracy: {$prev_accuracy}%\n- Current Period Accuracy: {$currentAccuracy}%\n- Change: {$change_text}\n";
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
                    'role' => 'system',
                    'content' => 'You are an expert educational AI analyst. Respond only with valid JSON formatted analysis.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 4000,
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("OpenAI API request failed with status: {$httpCode}");
        }
        
        $decoded = json_decode($response, true);
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new Exception("Invalid OpenAI API response format");
        }
        
        return $decoded['choices'][0]['message']['content'];
    }
    
    /**
     * Parse AI response
     */
    private function parseAIResponse($response) {
        // Clean the response to extract JSON
        $response = trim($response);
        
        // Remove markdown code blocks if present
        $response = preg_replace('/^```json\s*/', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse AI response as JSON: " . json_last_error_msg());
        }
        
        // Add raw response for debugging
        $decoded['raw_ai_response'] = $response;
        
        return $decoded;
    }
    
    /**
     * Get default analysis when AI fails
     */
    private function getDefaultOverallAnalysis($data) {
        $total_quizzes = count($data['performance_records']);
        $total_questions = array_sum(array_column($data['performance_records'], 'total_questions'));
        $total_correct = array_sum(array_column($data['performance_records'], 'correct_answers'));
        $overall_accuracy = $total_questions > 0 ? round(($total_correct / $total_questions) * 100, 2) : 0;
        
        return array(
            'overall_assessment' => "Overall performance analysis shows {$overall_accuracy}% accuracy across {$total_quizzes} quizzes and {$total_questions} questions during the analysis period.",
            'learning_patterns' => "Basic learning pattern analysis indicates regular quiz participation with consistent effort.",
            'strengths_summary' => "Demonstrated commitment to regular practice and learning through consistent quiz participation.",
            'improvement_areas' => "Continue practicing to improve accuracy and time management skills.",
            'study_recommendations' => "Focus on regular practice sessions and review of incorrect answers.",
            'predicted_performance' => "With continued practice, expect gradual improvement in accuracy and speed.",
            'personalized_goals' => "Aim to improve accuracy by 5-10% over the next month through focused practice.",
            
            'performance_trend' => 'stable',
            'accuracy_trend' => 'stable',
            'speed_trend' => 'stable',
            'consistency_score' => 70.0,
            'mastery_level' => $overall_accuracy >= 80 ? 'Proficient' : ($overall_accuracy >= 60 ? 'Developing' : 'Beginner'),
            'learning_velocity' => 'moderate',
            'improvement_rate' => 0.0,
            
            'strongest_subject' => '',
            'weakest_subject' => '',
            'strongest_topic' => '',
            'weakest_topic' => '',
            'preferred_difficulty' => 'medium',
            'time_efficiency_rating' => 'Average',
            
            'recommended_daily_study_minutes' => 30,
            'recommended_focus_subjects' => array(),
            'recommended_difficulty_progression' => 'Continue with current level',
            'next_milestone' => 'Complete 5 more quizzes with 70%+ accuracy',
            
            'most_active_day' => null,
            'most_active_time' => null,
            'quiz_frequency_per_week' => round($total_quizzes / 24, 1), // Assuming 6 months = 24 weeks
            'average_session_length_minutes' => 20.0,
            
            'raw_ai_response' => 'Default analysis generated due to AI service unavailability'
        );
    }
}

?>
