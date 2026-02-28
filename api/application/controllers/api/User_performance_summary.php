<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_performance_summary extends API_Controller {
    
    public function __constructor() {
        parent::__construct();
    }
    
    /**
     * Get latest performance summary for a user
     * GET /user_performance_summary/latest/{user_id}
     */
    public function latest_get($user_id = null) {
        // Validate user_id
        if (!$user_id || !is_numeric($user_id)) {
            $response = $this->get_failed_response(null, "Valid user_id is required");
            $this->set_output($response);
            return;
        }
        
        try {
            $this->load->model('user_performance_summary/user_performance_summary_model');
            
            // Get latest summary
            $summary = $this->user_performance_summary_model->get_latest_user_summary($user_id);
            
            if (!$summary) {
                $response = $this->get_success_response(null, "No performance summary found");
                $this->set_output($response);
                return;
            }
            
            // Convert object to array for response
            $summary_data = $summary->to_array();
            
            // Parse JSON fields
            $json_fields = ['recommended_focus_subjects', 'raw_ai_response'];
            foreach ($json_fields as $field) {
                if (isset($summary_data[$field]) && is_string($summary_data[$field])) {
                    $summary_data[$field] = json_decode($summary_data[$field], true);
                }
            }
            
            $response = $this->get_success_response($summary_data, "Performance summary retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Performance Summary Get Latest Error: " . $e->getMessage());
            $response = $this->get_failed_response(null, "Failed to retrieve performance summary");
            $this->set_output($response);
        }
    }
    
    /**
     * Get performance summary history for a user
     * GET /user_performance_summary/history/{user_id}
     */
    public function history_get($user_id = null) {
        // Validate user_id
        if (!$user_id || !is_numeric($user_id)) {
            $response = $this->get_failed_response(null, "Valid user_id is required");
            $this->set_output($response);
            return;
        }
        
        try {
            $this->load->model('user_performance_summary/user_performance_summary_model');
            
            // Get optional parameters
            $limit = $this->get('limit') ?: 10;
            
            // Validate limit
            if (!is_numeric($limit)) {
                $response = $this->get_failed_response(null, "Limit must be numeric");
                $this->set_output($response);
                return;
            }
            
            // Get history
            $history = $this->user_performance_summary_model->get_user_summary_history($user_id, $limit);
            
            // Convert objects to arrays and parse JSON fields
            $history_data = array();
            foreach ($history as $summary) {
                $summary_data = $summary->to_array();
                
                // Parse JSON fields
                $json_fields = ['recommended_focus_subjects', 'raw_ai_response'];
                foreach ($json_fields as $field) {
                    if (isset($summary_data[$field]) && is_string($summary_data[$field])) {
                        $summary_data[$field] = json_decode($summary_data[$field], true);
                    }
                }
                
                $history_data[] = $summary_data;
            }
            
            $response_data = array(
                'data' => $history_data,
                'pagination' => array(
                    'limit' => (int)$limit,
                    'count' => count($history_data)
                )
            );
            
            $response = $this->get_success_response($response_data, "Performance summary history retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Performance Summary History Error: " . $e->getMessage());
            $response = $this->get_failed_response(null, "Failed to retrieve performance summary history");
            $this->set_output($response);
        }
    }
    
    /**
     * Generate new performance summary for a user
     * POST /user_performance_summary/generate
     */
    public function generate_post() {
        // Get POST data
        $json_input = $this->input->raw_input_stream;
        $input_data = json_decode($json_input, true);
        
        // Validate input
        if (!$input_data || !isset($input_data['user_id'])) {
            $response = $this->get_failed_response(null, "user_id is required");
            $this->set_output($response);
            return;
        }
        
        $user_id = $input_data['user_id'];
        
        if (!is_numeric($user_id)) {
            $response = $this->get_failed_response(null, "user_id must be numeric");
            $this->set_output($response);
            return;
        }
        
        try {
            $this->load->model('user_performance_summary/user_performance_summary_model');
            $this->load->library('AI_Overall_Performance_Service');
            
            log_message('info', "Starting performance summary generation for user_id: $user_id");
            
            // Optional parameters
            $force_regenerate = isset($input_data['force_regenerate']) ? (bool)$input_data['force_regenerate'] : false;
            $period_months = isset($input_data['period_months']) ? (int)$input_data['period_months'] : 6;
            
            log_message('info', "Parameters - force_regenerate: " . ($force_regenerate ? 'true' : 'false') . ", period_months: $period_months");
            
            // Check if recent summary exists (unless force regenerate)
            if (!$force_regenerate) {
                log_message('info', "Checking for existing recent summary for user_id: $user_id");
                $latest_summary = $this->user_performance_summary_model->get_latest_user_summary($user_id);
                if ($latest_summary) {
                    $created_time = strtotime($latest_summary->created_at);
                    $one_week_ago = strtotime('-1 week');
                    
                    log_message('info', "Found existing summary created at: " . $latest_summary->created_at);
                    
                    if ($created_time > $one_week_ago) {
                        log_message('info', "Recent summary exists, returning existing data for user_id: $user_id");
                        $response_data = array(
                            'data' => $latest_summary->to_array(),
                            'generated' => false
                        );
                        $response = $this->get_success_response($response_data, "Recent performance summary already exists");
                        $this->set_output($response);
                        return;
                    }
                } else {
                    log_message('info', "No existing summary found for user_id: $user_id");
                }
            } else {
                log_message('info', "Force regenerate is enabled, skipping existing summary check for user_id: $user_id");
            }
            
            // Get performance data for analysis
            $period_end = date('Y-m-d H:i:s');
            $period_start = date('Y-m-d H:i:s', strtotime("-{$period_months} months"));
            
            log_message('info', "Analysis period: $period_start to $period_end for user_id: $user_id");
            
            // Get performance records
            $performance_records = $this->user_performance_summary_model->get_user_performance_period($user_id, $period_start, $period_end);
            
            log_message('info', "Retrieved " . count($performance_records) . " performance records for user_id: $user_id");
            
            if (empty($performance_records)) {
                log_message('error', "No performance data found for analysis period for user_id: $user_id");
                $response = $this->get_failed_response(null, "No performance data found for analysis period");
                $this->set_output($response);
                return;
            }
            
            // Get additional analysis data
            $activity_data = $this->user_performance_summary_model->get_activity_patterns($user_id, $period_start, $period_end);
            $difficulty_data = $this->user_performance_summary_model->get_difficulty_performance($user_id, $period_start, $period_end);
            
            log_message('info', "Retrieved activity patterns: " . count($activity_data) . " records, difficulty data: " . count($difficulty_data) . " records for user_id: $user_id");
            
            // Prepare data for AI analysis
            $analysis_data = array(
                'performance_records' => $performance_records,
                'activity_data' => $activity_data,
                'difficulty_data' => $difficulty_data,
                'period_start' => $period_start,
                'period_end' => $period_end,
                'period_months' => $period_months
            );
            
            // Get previous summary for comparison
            $previous_summary = $this->user_performance_summary_model->get_latest_user_summary($user_id);
            
            log_message('info', "Previous summary " . ($previous_summary ? "found" : "not found") . " for comparison for user_id: $user_id");
            
            // Generate AI analysis
            log_message('info', "Starting AI analysis generation for user_id: $user_id");
            $ai_analysis = $this->ai_overall_performance_service->generateOverallPerformanceAnalysis($analysis_data, $previous_summary);
            
            log_message('info', "AI analysis completed for user_id: $user_id");
            
            // Calculate aggregated performance data
            log_message('info', "Calculating performance metrics for user_id: $user_id");
            $performance_data = $this->calculatePerformanceMetrics($performance_records, $activity_data, $difficulty_data);
            
            // Create performance summary object
            log_message('info', "Creating performance summary object for user_id: $user_id");
            $summary_object = new User_performance_summary_object();
            
            // Set basic data
            $summary_object->user_id = $user_id;
            $summary_object->summary_period_start = $period_start;
            $summary_object->summary_period_end = $period_end;
            $summary_object->generation_date = date('Y-m-d H:i:s');
            
            // Set aggregated metrics
            $summary_object->total_quizzes_taken = $performance_data['total_quizzes'];
            $summary_object->total_questions_attempted = $performance_data['total_questions'];
            $summary_object->total_correct_answers = $performance_data['total_correct'];
            $summary_object->total_incorrect_answers = $performance_data['total_questions'] - $performance_data['total_correct'];
            $summary_object->overall_accuracy_percentage = $performance_data['overall_accuracy'];
            $summary_object->average_quiz_score = $performance_data['avg_quiz_score'];
            $summary_object->total_time_spent_minutes = $performance_data['total_time'];
            $summary_object->average_time_per_quiz_minutes = $performance_data['avg_time_per_quiz'] ?? 0;
            $summary_object->average_time_per_question_seconds = $performance_data['avg_time_per_question'];
            $summary_object->time_efficiency_rating = $ai_analysis['time_efficiency_rating'] ?? 'Average';
            
            // Set subject performance
            $summary_object->strongest_overall_subject = $ai_analysis['strongest_subject'] ?? '';
            $summary_object->weakest_overall_subject = $ai_analysis['weakest_subject'] ?? '';
            $summary_object->subject_scores_json = json_encode($ai_analysis['subject_scores'] ?? array());
            $summary_object->strongest_overall_topic = $ai_analysis['strongest_topic'] ?? '';
            $summary_object->weakest_overall_topic = $ai_analysis['weakest_topic'] ?? '';
            $summary_object->topic_scores_json = json_encode($ai_analysis['topic_scores'] ?? array());
            
            // Set difficulty analysis
            $summary_object->preferred_difficulty = $ai_analysis['preferred_difficulty'] ?? 'medium';
            $summary_object->easy_level_accuracy = $performance_data['difficulty_accuracies']['easy'] ?? 0;
            $summary_object->medium_level_accuracy = $performance_data['difficulty_accuracies']['medium'] ?? 0;
            $summary_object->hard_level_accuracy = $performance_data['difficulty_accuracies']['hard'] ?? 0;
            
            // Set trends and analysis
            $summary_object->performance_trend = $ai_analysis['performance_trend'] ?? 'stable';
            $summary_object->accuracy_trend = $ai_analysis['accuracy_trend'] ?? 'stable';
            $summary_object->speed_trend = $ai_analysis['speed_trend'] ?? 'stable';
            $summary_object->consistency_score = $ai_analysis['consistency_score'] ?? 70;
            $summary_object->improvement_rate = $ai_analysis['improvement_rate'] ?? 0;
            
            // Set learning insights
            $summary_object->mastery_level = $ai_analysis['mastery_level'] ?? 'Developing';
            $summary_object->learning_velocity = $ai_analysis['learning_velocity'] ?? 'moderate';
            
            // Set activity patterns
            $summary_object->most_active_day = $ai_analysis['most_active_day'] ?? '';
            $summary_object->most_active_time = $ai_analysis['most_active_time'] ?? '';
            $summary_object->average_session_length_minutes = $ai_analysis['average_session_length_minutes'] ?? 0;
            $summary_object->quiz_frequency_per_week = $ai_analysis['quiz_frequency_per_week'] ?? 0;
            
            // Set AI-generated insights
            $summary_object->ai_overall_assessment = $ai_analysis['overall_assessment'] ?? '';
            $summary_object->ai_learning_patterns = $ai_analysis['learning_patterns'] ?? '';
            $summary_object->ai_strengths_summary = $ai_analysis['strengths_summary'] ?? '';
            $summary_object->ai_improvement_areas = $ai_analysis['improvement_areas'] ?? '';
            $summary_object->ai_study_recommendations = $ai_analysis['study_recommendations'] ?? '';
            $summary_object->ai_predicted_performance = $ai_analysis['predicted_performance'] ?? '';
            $summary_object->ai_personalized_goals = $ai_analysis['personalized_goals'] ?? '';
            
            // Set recommendations
            $summary_object->recommended_daily_study_minutes = $ai_analysis['recommended_daily_study_minutes'] ?? 30;
            $summary_object->recommended_focus_subjects = json_encode($ai_analysis['recommended_focus_subjects'] ?? array());
            $summary_object->recommended_difficulty_progression = $ai_analysis['recommended_difficulty_progression'] ?? '';
            $summary_object->next_milestone = $ai_analysis['next_milestone'] ?? '';
            
            // Set comparison data (if previous summary exists)
            if ($previous_summary) {
                $summary_object->previous_period_accuracy = $previous_summary->overall_accuracy_percentage;
                $summary_object->accuracy_change_percentage = $summary_object->overall_accuracy_percentage - $previous_summary->overall_accuracy_percentage;
                $summary_object->previous_period_speed = $previous_summary->average_time_per_question_seconds;
                $summary_object->speed_change_percentage = (($summary_object->average_time_per_question_seconds - $previous_summary->average_time_per_question_seconds) / $previous_summary->average_time_per_question_seconds) * 100;
            }
            
            // Set metadata
            $summary_object->ai_prompt_used = $ai_analysis['ai_prompt'] ?? '';
            $summary_object->raw_ai_response = json_encode($ai_analysis['raw_ai_response'] ?? array());
            $summary_object->analysis_version = '1.0';
            $summary_object->data_quality_score = 100.00;
            
            log_message('info', "Performance summary object populated for user_id: $user_id, attempting to save to database");
            
            // Save to database
            $summary_result = $this->user_performance_summary_model->add_user_performance_summary($summary_object);
            
            if ($summary_result && is_object($summary_result) && isset($summary_result->id)) {
                $summary_id = $summary_result->id;
                log_message('info', "Database save successful for user_id: $user_id - summary_id: $summary_id");
                
                $response = $this->get_success_response($summary_result, "Performance summary generated and saved successfully");
                $this->set_output($response);
                
            } else {
                log_message('error', "Failed to save performance summary to database for user_id: $user_id");
                $response = $this->get_failed_response(null, "Failed to save performance summary");
                $this->set_output($response);
                return;
            }
            
            log_message('info', "Performance summary generation completed successfully for user_id: $user_id");
            
        } catch (Exception $e) {
            log_message('error', "Performance Summary Generation Error: " . $e->getMessage());
            $response = $this->get_failed_response(null, "Failed to generate performance summary: " . $e->getMessage());
            $this->set_output($response);
        }
    }
    
    /**
     * Calculate aggregated performance metrics
     */
    private function calculatePerformanceMetrics($performance_records, $activity_data, $difficulty_data) {
        $total_quizzes = count($performance_records);
        $total_questions = array_sum(array_column($performance_records, 'total_questions'));
        $total_correct = array_sum(array_column($performance_records, 'correct_answers'));
        $total_time = array_sum(array_column($performance_records, 'total_time_spent'));
        
        $overall_accuracy = $total_questions > 0 ? round(($total_correct / $total_questions) * 100, 2) : 0;
        $avg_time_per_question = $total_questions > 0 ? round($total_time / $total_questions, 2) : 0;
        
        // Calculate average quiz score
        $quiz_scores = array();
        foreach ($performance_records as $record) {
            if ($record['total_questions'] > 0) {
                $quiz_scores[] = ($record['correct_answers'] / $record['total_questions']) * 100;
            }
        }
        $avg_quiz_score = !empty($quiz_scores) ? round(array_sum($quiz_scores) / count($quiz_scores), 2) : 0;
        
        // Calculate time management score (based on average time efficiency)
        $time_scores = array_column($performance_records, 'time_management_score');
        $time_management_score = !empty($time_scores) ? round(array_sum($time_scores) / count($time_scores), 2) : 0;
        
        // Calculate difficulty accuracies
        $difficulty_accuracies = array();
        foreach ($difficulty_data as $diff) {
            $accuracy = $diff['total_questions'] > 0 ? 
                round(($diff['correct_answers'] / $diff['total_questions']) * 100, 2) : 0;
            $difficulty_accuracies[strtolower($diff['level'])] = $accuracy;
        }
        
        return array(
            'total_quizzes' => $total_quizzes,
            'total_questions' => $total_questions,
            'total_correct' => $total_correct,
            'overall_accuracy' => $overall_accuracy,
            'total_time' => $total_time,
            'avg_time_per_question' => $avg_time_per_question,
            'avg_quiz_score' => $avg_quiz_score,
            'time_management_score' => $time_management_score,
            'difficulty_accuracies' => $difficulty_accuracies
        );
    }
    
    /**
     * Get performance data for dashboard/analytics
     * GET /user_performance_summary/performance_data/{user_id}
     */
    public function performance_data_get($user_id = null) {
        // Validate user_id
        if (!$user_id || !is_numeric($user_id)) {
            $response = $this->get_failed_response(null, "Valid user_id is required");
            $this->set_output($response);
            return;
        }
        
        try {
            $this->load->model('user_performance_summary/user_performance_summary_model');
            
            // Get performance data from model
            $performance_data = $this->user_performance_summary_model->get_user_performance_data($user_id);
            
            $response = $this->get_success_response($performance_data, "Performance data retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Performance Data Get Error: " . $e->getMessage());
            $response = $this->get_failed_response(null, "Failed to retrieve performance data");
            $this->set_output($response);
        }
    }
    
    /**
     * Clean up old performance summaries
     * DELETE /user_performance_summary/cleanup
     */
    public function cleanup_delete() {
        try {
            $this->load->model('user_performance_summary/user_performance_summary_model');
            
            $deleted_result = $this->user_performance_summary_model->cleanup_old_summaries();
            
            $response_data = array('success' => $deleted_result);
            $response = $this->get_success_response($response_data, "Cleanup completed successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Performance Summary Cleanup Error: " . $e->getMessage());
            $response = $this->get_failed_response(null, "Failed to cleanup old summaries");
            $this->set_output($response);
        }
    }
}

?>
