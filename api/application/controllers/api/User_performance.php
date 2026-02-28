<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_performance extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    /**
     * Generate performance analysis for a user's quiz
     * POST /api/user_performance/generate/{user_id}/{quiz_id}
     */
    function generate_post($userId = NULL, $quizId = NULL) {
        $this->require_jwt_auth(false); // User level auth - users can generate their performance analysis
        if ($userId == NULL || $quizId == NULL) {
            $response = $this->get_failed_response(NULL, "User ID and Quiz ID are required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('user_performance/user_performance_model');
            $this->load->library('AI_Performance_Service');
            
            // Check if quiz is completed
            if (!$this->user_performance_model->is_quiz_completed($userId, $quizId)) {
                $response = $this->get_failed_response(NULL, "Quiz not yet completed by user. Ensure all questions are answered or skipped, then submit the quiz.");
                $this->set_output($response);
                return;
            }
            log_message('info', "Quiz completed by user: $userId, Quiz ID: $quizId");

            // Check if performance analysis already exists
            $existing = $this->user_performance_model->get_user_quiz_performance($userId, $quizId);
            if ($existing) {
                log_message('info', "Existing performance analysis found for user: $userId, Quiz ID: $quizId");
                $response = $this->get_success_response($existing, "Performance analysis already exists");
                $this->set_output($response);
                return;
            }
            log_message('info', "No existing performance analysis found for user: $userId, Quiz ID: $quizId");
            // Get analysis data
            $analysisData = $this->user_performance_model->get_quiz_analysis_data($userId, $quizId);
            // Add user id and quiz id to current performance for logging
            $analysisData['current_performance']['user_id'] = $userId;
            $analysisData['current_performance']['quiz_id'] = $quizId;

            if (empty($analysisData['current_performance']) || empty($analysisData['questions_analysis'])) {
                $response = $this->get_failed_response(NULL, "Insufficient data for performance analysis");
                $this->set_output($response);
                return;
            }
            
            // Generate AI analysis
            $aiAnalysis = $this->ai_performance_service->generatePerformanceAnalysis($analysisData);
            log_message('info', "AI analysis generated for user: $userId, Quiz ID: $quizId");

            // Create performance object
            $objPerformance = new User_performance_object();
            $objPerformance = $this->populatePerformanceObject($objPerformance, $analysisData, $aiAnalysis, $userId, $quizId);

            log_message('info', "Performance object populated for user: $userId, Quiz ID: $quizId");
            // Save to database
            $result = $this->user_performance_model->add_user_performance($objPerformance);
            
            if ($result !== FALSE) {
                $response = $this->get_success_response($result, "Performance analysis generated successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to save performance analysis");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Performance generation error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error generating performance analysis: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get performance analysis for a user's quiz
     * GET /api/user_performance/{user_id}/{quiz_id}
     */
    function index_get($userId = NULL, $quizId = NULL) {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($userId == NULL || $quizId == NULL) {
            $response = $this->get_failed_response(NULL, "User ID and Quiz ID are required");
            $this->set_output($response);
            return;
        }

        $this->load->model('user_performance/user_performance_model');
        $performance = $this->user_performance_model->get_user_quiz_performance($userId, $quizId);
        
        if ($performance) {
            // Format response for frontend
            $formatted_performance = $this->formatPerformanceForResponse($performance);
            $response = $this->get_success_response($formatted_performance, "Performance analysis retrieved");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "Performance analysis not found");
            $this->set_output($response);
        }
    }

    /**
     * Get user's performance history
     * GET /api/user_performance/history/{user_id}
     */
    function history_get($userId = NULL) {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($userId == NULL) {
            $response = $this->get_failed_response(NULL, "User ID is required");
            $this->set_output($response);
            return;
        }

        $limit = $this->input->get('limit') ?: 10;
        
        $this->load->model('user_performance/user_performance_model');
        $history = $this->user_performance_model->get_user_performance_history($userId, $limit);
        
        if ($history) {
            $formatted_history = array();
            foreach ($history as $performance) {
                $formatted_history[] = $this->formatPerformanceForResponse($performance);
            }
            
            $response = $this->get_success_response($formatted_history, "Performance history retrieved");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response(array(), "No performance history found");
            $this->set_output($response);
        }
    }

    /**
     * Trigger performance analysis on quiz completion (internal method)
     * Called from Quiz controller when user answers last question
     */
    public function trigger_performance_generation($userId, $quizId) {
        try {
            $this->load->model('user_performance/user_performance_model');
            
            // Check if already generated
            $existing = $this->user_performance_model->get_user_quiz_performance($userId, $quizId);
            if ($existing) {
                return $existing;
            }
            
            // Check if quiz is completed
            if (!$this->user_performance_model->is_quiz_completed($userId, $quizId)) {
                return FALSE;
            }
            
            $this->load->library('AI_Performance_Service');
            
            // Get analysis data
            $analysisData = $this->user_performance_model->get_quiz_analysis_data($userId, $quizId);
            
            if (empty($analysisData['current_performance'])) {
                return FALSE;
            }
            
            // Generate AI analysis
            $aiAnalysis = $this->ai_performance_service->generatePerformanceAnalysis($analysisData);
            
            // Create and save performance object
            $objPerformance = new User_performance_object();
            $objPerformance = $this->populatePerformanceObject($objPerformance, $analysisData, $aiAnalysis, $userId, $quizId);
            
            return $this->user_performance_model->add_user_performance($objPerformance);
            
        } catch (Exception $e) {
            log_message('error', "Auto performance generation error: " . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Populate performance object with data
     */
    private function populatePerformanceObject($objPerformance, $analysisData, $aiAnalysis, $userId, $quizId) {
        $current = $analysisData['current_performance'];
        $questions = $analysisData['questions_analysis'];
        $history = $analysisData['historical_performance'];
        
        // Basic stats
        $objPerformance->user_id = $userId;
        $objPerformance->quiz_id = $quizId;
        $objPerformance->quiz_attempt_number = $this->user_performance_model->get_next_attempt_number($userId, $quizId);
        
        $objPerformance->total_questions = $current['total_questions'];
        $objPerformance->correct_answers = $current['correct_answers'];
        $objPerformance->incorrect_answers = $current['incorrect_answers'];
        $objPerformance->unanswered_questions = $current['total_questions'] - $current['total_answered'];
        $objPerformance->accuracy_percentage = round(($current['correct_answers'] / $current['total_questions']) * 100, 2);
        $objPerformance->total_time_spent = $current['total_time'];
        $objPerformance->average_time_per_question = isset($current['avg_time_per_question']) ? round($current['avg_time_per_question'], 2) : 0;
        
        // AI-generated insights
        $objPerformance->strongest_subject = $aiAnalysis['strongest_subject'] ?? '';
        $objPerformance->weakest_subject = $aiAnalysis['weakest_subject'] ?? '';
        $objPerformance->subject_scores = $aiAnalysis['subject_scores'] ?? '{}';
        $objPerformance->strongest_topic = $aiAnalysis['strongest_topic'] ?? '';
        $objPerformance->weakest_topic = $aiAnalysis['weakest_topic'] ?? '';
        $objPerformance->topic_scores = $aiAnalysis['topic_scores'] ?? '{}';
        
        // Progress tracking
        if (!empty($history)) {
            $objPerformance->previous_quiz_accuracy = $history[0]['accuracy_percentage'];
            $objPerformance->accuracy_improvement = $objPerformance->accuracy_percentage - $history[0]['accuracy_percentage'];
            $objPerformance->previous_avg_time = $history[0]['average_time_per_question'];
            $objPerformance->time_improvement = $objPerformance->average_time_per_question - $history[0]['average_time_per_question'];
        }
        
        $objPerformance->overall_progress_trend = $aiAnalysis['overall_progress_trend'] ?? 'first_attempt';
        $objPerformance->subject_progress = '{}'; // Could be populated with more detailed progress data
        
        // Performance scores
        $objPerformance->time_management_score = $aiAnalysis['time_management_score'] ?? 7.0;
        $objPerformance->difficulty_performance = $aiAnalysis['difficulty_performance'] ?? '';
        $objPerformance->performance_score = $aiAnalysis['performance_score'] ?? $objPerformance->accuracy_percentage;
        
        // AI insights
        $objPerformance->ai_recommendations = $aiAnalysis['ai_recommendations'] ?? '';
        $objPerformance->ai_learning_suggestions = $aiAnalysis['ai_learning_suggestions'] ?? '';
        $objPerformance->progress_insights = $aiAnalysis['progress_insights'] ?? '';
        $objPerformance->improvement_areas = $aiAnalysis['improvement_areas'] ?? '';
        $objPerformance->strengths_identified = $aiAnalysis['strengths_identified'] ?? '';
        
        // Raw data and prompt
        $objPerformance->ai_prompt = $aiAnalysis['ai_prompt'] ?? '';
        $objPerformance->raw_ai_response = $aiAnalysis['raw_ai_response'] ?? '';
        $objPerformance->analysis_version = '1.0';
        
        return $objPerformance;
    }

    /**
     * Format performance data for API response
     */
    private function formatPerformanceForResponse($performance) {
        // Parse JSON fields safely
        $subject_scores = json_decode($performance->subject_scores, true) ?: array();
        $topic_scores = json_decode($performance->topic_scores, true) ?: array();
        $subject_progress = json_decode($performance->subject_progress, true) ?: array();
        
        // Calculate additional metrics
        $completion_rate = $performance->total_questions > 0 ? 
            round((($performance->correct_answers + $performance->incorrect_answers) / $performance->total_questions) * 100, 2) : 0;
        
        $efficiency_score = $performance->average_time_per_question > 0 ? 
            round($performance->accuracy_percentage / $performance->average_time_per_question, 2) : 0;
        
        // Determine performance grade
        $accuracy = (float) $performance->accuracy_percentage;
        $performance_grade = $this->calculatePerformanceGrade($accuracy);
        
        // Calculate time efficiency rating
        $time_efficiency = $this->calculateTimeEfficiency($performance->average_time_per_question, $performance->accuracy_percentage);
        
        return array(
            'id' => $performance->id,
            'user_id' => $performance->user_id,
            'quiz_id' => $performance->quiz_id,
            'quiz_attempt_number' => $performance->quiz_attempt_number,
            
            'quiz_stats' => array(
                'total_questions' => $performance->total_questions,
                'correct_answers' => $performance->correct_answers,
                'incorrect_answers' => $performance->incorrect_answers,
                'unanswered_questions' => $performance->unanswered_questions,
                'accuracy_percentage' => $performance->accuracy_percentage,
                'total_time_spent' => $performance->total_time_spent,
                'average_time_per_question' => $performance->average_time_per_question,
                'completion_rate' => $completion_rate,
                'efficiency_score' => $efficiency_score,
                'performance_grade' => $performance_grade
            ),
            
            'performance_analysis' => array(
                'strongest_subject' => $performance->strongest_subject,
                'weakest_subject' => $performance->weakest_subject,
                'strongest_topic' => $performance->strongest_topic,
                'weakest_topic' => $performance->weakest_topic,
                'time_management_score' => $performance->time_management_score,
                'performance_score' => $performance->performance_score,
                'overall_progress_trend' => $performance->overall_progress_trend,
                'time_efficiency_rating' => $time_efficiency
            ),
            
            'progress_tracking' => array(
                'previous_quiz_accuracy' => $performance->previous_quiz_accuracy,
                'accuracy_improvement' => $performance->accuracy_improvement,
                'previous_avg_time' => $performance->previous_avg_time,
                'time_improvement' => $performance->time_improvement,
                'is_improvement' => (float) $performance->accuracy_improvement > 0,
                'is_faster' => (float) $performance->time_improvement < 0,
                'progress_direction' => $this->getProgressDirection($performance->overall_progress_trend)
            ),
            
            'ai_insights' => array(
                'recommendations' => $this->parseAIInsights($performance->ai_recommendations),
                'learning_suggestions' => $this->parseAIInsights($performance->ai_learning_suggestions),
                'progress_insights' => $this->parseAIInsights($performance->progress_insights),
                'improvement_areas' => $this->parseAIInsights($performance->improvement_areas),
                'strengths_identified' => $this->parseAIInsights($performance->strengths_identified),
                'recommendation_count' => count($this->parseAIInsights($performance->ai_recommendations)),
                'strength_count' => count($this->parseAIInsights($performance->strengths_identified)),
                'improvement_area_count' => count($this->parseAIInsights($performance->improvement_areas))
            ),
            
            'detailed_scores' => array(
                'subject_scores' => $subject_scores,
                'topic_scores' => $topic_scores,
                'subject_progress' => $subject_progress,
                'subject_count' => count($subject_scores),
                'topic_count' => count($topic_scores),
                'best_subject_score' => !empty($subject_scores) ? max($subject_scores) : 0,
                'worst_subject_score' => !empty($subject_scores) ? min($subject_scores) : 0,
                'average_subject_score' => !empty($subject_scores) ? round(array_sum($subject_scores) / count($subject_scores), 2) : 0
            ),
            
            'learning_insights' => array(
                'mastery_level' => $this->calculateMasteryLevel($performance->accuracy_percentage),
                'focus_areas' => $this->getFocusAreas($subject_scores, $topic_scores),
                'next_steps' => $this->getNextSteps($performance),
                'study_time_recommendation' => $this->getStudyTimeRecommendation($performance),
                'difficulty_readiness' => $this->getDifficultyReadiness($performance->accuracy_percentage)
            ),
            
            'metadata' => array(
                'difficulty_performance' => $performance->difficulty_performance,
                'analysis_version' => $performance->analysis_version,
                'ai_prompt' => $performance->ai_prompt,
                'raw_ai_response' => $performance->raw_ai_response,
                'created_at' => $performance->created_at,
                'updated_at' => $performance->updated_at,
                'analysis_completeness' => $this->calculateAnalysisCompleteness($performance),
                'data_quality_score' => $this->calculateDataQuality($performance)
            )
        );
    }

    /**
     * Helper method to parse AI insights safely
     */
    private function parseAIInsights($insights) {
        if (empty($insights)) return array();
        
        // Clean up the insights and split properly
        $insights = trim($insights);
        if (strpos($insights, "\n• ") !== false) {
            return array_filter(explode("\n• ", ltrim($insights, '• ')));
        } elseif (strpos($insights, "\n") !== false) {
            return array_filter(explode("\n", $insights));
        } else {
            return array($insights);
        }
    }

    /**
     * Calculate performance grade based on accuracy
     */
    private function calculatePerformanceGrade($accuracy) {
        if ($accuracy >= 90) return 'A+';
        if ($accuracy >= 80) return 'A';
        if ($accuracy >= 70) return 'B+';
        if ($accuracy >= 60) return 'B';
        if ($accuracy >= 50) return 'C+';
        if ($accuracy >= 40) return 'C';
        return 'D';
    }

    /**
     * Calculate time efficiency rating
     */
    private function calculateTimeEfficiency($avgTime, $accuracy) {
        if ($avgTime <= 0) return 'Unknown';
        
        $efficiency = $accuracy / $avgTime;
        if ($efficiency >= 30) return 'Excellent';
        if ($efficiency >= 20) return 'Good';
        if ($efficiency >= 15) return 'Average';
        if ($efficiency >= 10) return 'Below Average';
        return 'Needs Improvement';
    }

    /**
     * Get progress direction indicator
     */
    private function getProgressDirection($trend) {
        switch ($trend) {
            case 'improving': return 'upward';
            case 'declining': return 'downward';
            case 'stable': return 'steady';
            default: return 'baseline';
        }
    }

    /**
     * Calculate mastery level
     */
    private function calculateMasteryLevel($accuracy) {
        if ($accuracy >= 90) return 'Expert';
        if ($accuracy >= 75) return 'Proficient';
        if ($accuracy >= 60) return 'Developing';
        if ($accuracy >= 40) return 'Beginner';
        return 'Needs Foundation';
    }

    /**
     * Get focus areas based on scores
     */
    private function getFocusAreas($subjectScores, $topicScores) {
        $focusAreas = array();
        
        // Find subjects with low scores
        foreach ($subjectScores as $subject => $score) {
            if ($score < 60) {
                $focusAreas[] = array(
                    'area' => $subject,
                    'type' => 'subject',
                    'score' => $score,
                    'priority' => 'high'
                );
            }
        }
        
        // Find topics with low scores
        foreach ($topicScores as $topic => $score) {
            if ($score < 60) {
                $focusAreas[] = array(
                    'area' => $topic,
                    'type' => 'topic',
                    'score' => $score,
                    'priority' => $score < 40 ? 'critical' : 'medium'
                );
            }
        }
        
        return $focusAreas;
    }

    /**
     * Get personalized next steps
     */
    private function getNextSteps($performance) {
        $steps = array();
        $accuracy = (float) $performance->accuracy_percentage;
        
        if ($accuracy < 50) {
            $steps[] = array(
                'action' => 'Review fundamental concepts',
                'priority' => 'immediate',
                'estimated_time' => '2-3 hours'
            );
        }
        
        if ($performance->time_management_score < 7) {
            $steps[] = array(
                'action' => 'Practice time management techniques',
                'priority' => 'high',
                'estimated_time' => '30-60 minutes daily'
            );
        }
        
        if (!empty($performance->weakest_subject)) {
            $steps[] = array(
                'action' => 'Focus on ' . $performance->weakest_subject,
                'priority' => 'medium',
                'estimated_time' => '1-2 hours'
            );
        }
        
        return $steps;
    }

    /**
     * Get study time recommendation
     */
    private function getStudyTimeRecommendation($performance) {
        $accuracy = (float) $performance->accuracy_percentage;
        
        if ($accuracy >= 85) {
            return array(
                'daily_minutes' => 15,
                'focus' => 'maintenance',
                'type' => 'review'
            );
        } elseif ($accuracy >= 70) {
            return array(
                'daily_minutes' => 30,
                'focus' => 'improvement',
                'type' => 'targeted_practice'
            );
        } else {
            return array(
                'daily_minutes' => 60,
                'focus' => 'foundation_building',
                'type' => 'comprehensive_study'
            );
        }
    }

    /**
     * Get difficulty readiness
     */
    private function getDifficultyReadiness($accuracy) {
        if ($accuracy >= 85) return 'Ready for Advanced';
        if ($accuracy >= 70) return 'Ready for Intermediate+';
        if ($accuracy >= 60) return 'Ready for Intermediate';
        if ($accuracy >= 50) return 'Stick to Basic+';
        return 'Focus on Basics';
    }

    /**
     * Calculate analysis completeness score
     */
    private function calculateAnalysisCompleteness($performance) {
        $score = 0;
        $maxScore = 10;
        
        // Check if key fields are populated
        if (!empty($performance->strongest_subject)) $score++;
        if (!empty($performance->weakest_subject)) $score++;
        if (!empty($performance->ai_recommendations)) $score++;
        if (!empty($performance->ai_learning_suggestions)) $score++;
        if (!empty($performance->progress_insights)) $score++;
        if (!empty($performance->improvement_areas)) $score++;
        if (!empty($performance->strengths_identified)) $score++;
        if (!empty($performance->subject_scores)) $score++;
        if (!empty($performance->difficulty_performance)) $score++;
        if (!empty($performance->raw_ai_response)) $score++;
        
        return round(($score / $maxScore) * 100, 1);
    }

    /**
     * Calculate data quality score
     */
    private function calculateDataQuality($performance) {
        $score = 0;
        $checks = 0;
        
        // Check data consistency
        $checks++;
        if ($performance->correct_answers + $performance->incorrect_answers + $performance->unanswered_questions == $performance->total_questions) {
            $score++;
        }
        
        // Check if accuracy calculation is correct
        $checks++;
        $expectedAccuracy = $performance->total_questions > 0 ? 
            round(($performance->correct_answers / $performance->total_questions) * 100, 2) : 0;
        if (abs($expectedAccuracy - $performance->accuracy_percentage) < 0.01) {
            $score++;
        }
        
        // Check if time data is reasonable
        $checks++;
        if ($performance->total_time_spent > 0 && $performance->average_time_per_question > 0) {
            $score++;
        }
        
        return round(($score / $checks) * 100, 1);
    }

}

?>
