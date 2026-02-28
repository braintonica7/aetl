<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_performance_library {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('user_performance/user_performance_model');
        $this->CI->load->library('AI_Performance_Service');
    }
    
    /**
     * Generate performance analysis for a completed quiz
     */
    public function generate_performance_analysis($user_id, $quiz_id) {
        try {
            // Check if analysis already exists for this quiz attempt
            $existing_analysis = $this->CI->user_performance_model->get_user_performance_by_quiz($user_id, $quiz_id);
            
            if ($existing_analysis) {
                log_message('error', "Performance analysis already exists for user {$user_id}, quiz {$quiz_id}");
                return $existing_analysis->id;
            }
            
            // Generate AI analysis
            $ai_analysis = $this->CI->ai_performance_service->generate_performance_analysis($user_id, $quiz_id);
            
            if ($ai_analysis === FALSE) {
                log_message('error', "Failed to generate AI analysis for user {$user_id}, quiz {$quiz_id}");
                return FALSE;
            }
            
            // Load the User_performance_object model
            $this->CI->load->model('user_performance/user_performance_object');
            
            // Create performance object
            $performance_obj = new User_performance_object();
            
            // Map AI analysis to performance object according to database schema
            $performance_obj->user_id = $user_id;
            $performance_obj->quiz_id = $quiz_id;
            $performance_obj->quiz_attempt_number = 1; // Default to 1, should be calculated properly
            
            // Core metrics
            $performance_obj->total_questions = isset($ai_analysis['total_questions']) ? $ai_analysis['total_questions'] : 0;
            $performance_obj->correct_answers = isset($ai_analysis['correct_answers']) ? $ai_analysis['correct_answers'] : 0;
            $performance_obj->incorrect_answers = isset($ai_analysis['incorrect_answers']) ? $ai_analysis['incorrect_answers'] : 0;
            $performance_obj->unanswered_questions = $performance_obj->total_questions - $performance_obj->correct_answers - $performance_obj->incorrect_answers;
            $performance_obj->accuracy_percentage = isset($ai_analysis['score_percentage']) ? $ai_analysis['score_percentage'] : 0;
            
            // Time metrics (convert minutes to seconds for database storage)
            $time_minutes = isset($ai_analysis['time_taken_minutes']) ? $ai_analysis['time_taken_minutes'] : 0;
            $performance_obj->total_time_spent = $time_minutes * 60;
            $performance_obj->average_time_per_question = $performance_obj->total_questions > 0 ? 
                ($performance_obj->total_time_spent / $performance_obj->total_questions) : 0;
            
            // Subject and topic analysis - extract from detailed analytics if available
            $detailed = isset($ai_analysis['detailed_analytics']) ? $ai_analysis['detailed_analytics'] : array();
            $subject_breakdown = isset($detailed['subject_breakdown']) ? $detailed['subject_breakdown'] : array();
            $topic_breakdown = isset($detailed['topic_breakdown']) ? $detailed['topic_breakdown'] : array();
            
            // Find strongest and weakest subjects/topics
            $performance_obj->strongest_subject = $this->findStrongestSubject($subject_breakdown);
            $performance_obj->weakest_subject = $this->findWeakestSubject($subject_breakdown);
            $performance_obj->subject_scores = !empty($subject_breakdown) ? json_encode($subject_breakdown) : '{}';
            
            $performance_obj->strongest_topic = $this->findStrongestTopic($topic_breakdown);
            $performance_obj->weakest_topic = $this->findWeakestTopic($topic_breakdown);
            $performance_obj->topic_scores = !empty($topic_breakdown) ? json_encode($topic_breakdown) : '{}';
            
            // Progress metrics (placeholder for now - should be calculated from history)
            $performance_obj->previous_quiz_accuracy = null;
            $performance_obj->accuracy_improvement = null;
            $performance_obj->previous_avg_time = null;
            $performance_obj->time_improvement = null;
            $performance_obj->overall_progress_trend = isset($ai_analysis['performance_trend']) ? $ai_analysis['performance_trend'] : 'first_attempt';
            $performance_obj->subject_progress = '{}';
            
            // Performance scores
            $performance_obj->time_management_score = 7.0; // Default score
            $performance_obj->difficulty_performance = isset($ai_analysis['difficulty_performance']) && is_array($ai_analysis['difficulty_performance']) ? 
                json_encode($ai_analysis['difficulty_performance']) : (isset($ai_analysis['difficulty_performance']) ? $ai_analysis['difficulty_performance'] : 'N/A');
            $performance_obj->performance_score = $performance_obj->accuracy_percentage;
            
            // AI generated insights
            $performance_obj->ai_recommendations = isset($ai_analysis['study_recommendations']) && is_array($ai_analysis['study_recommendations']) ? 
                implode("\n• ", $ai_analysis['study_recommendations']) : (isset($ai_analysis['study_recommendations']) ? $ai_analysis['study_recommendations'] : '');
            $performance_obj->ai_learning_suggestions = isset($ai_analysis['next_steps']) && is_array($ai_analysis['next_steps']) ? 
                implode("\n• ", $ai_analysis['next_steps']) : (isset($ai_analysis['next_steps']) ? $ai_analysis['next_steps'] : '');
            $performance_obj->progress_insights = isset($ai_analysis['personalized_feedback']) ? $ai_analysis['personalized_feedback'] : '';
            $performance_obj->improvement_areas = isset($ai_analysis['weaknesses']) && is_array($ai_analysis['weaknesses']) ? 
                implode("\n• ", $ai_analysis['weaknesses']) : (isset($ai_analysis['weaknesses']) ? $ai_analysis['weaknesses'] : '');
            $performance_obj->strengths_identified = isset($ai_analysis['strengths']) && is_array($ai_analysis['strengths']) ? 
                implode("\n• ", $ai_analysis['strengths']) : (isset($ai_analysis['strengths']) ? $ai_analysis['strengths'] : '');
            
            // Raw data
            $performance_obj->ai_prompt = isset($ai_analysis['ai_prompt']) ? $ai_analysis['ai_prompt'] : '';
            $performance_obj->raw_ai_response = isset($ai_analysis['ai_generated_insights']) ? $ai_analysis['ai_generated_insights'] : '';
            $performance_obj->analysis_version = '2.0'; // Updated version
            
            $result = $this->CI->user_performance_model->add_user_performance($performance_obj);
            
            if ($result !== FALSE) {
                log_message('error', "Performance analysis successfully generated with ID {$result->id} for user {$user_id}, quiz {$quiz_id}");
                return $result->id;
            } else {
                log_message('error', "Failed to save performance analysis for user {$user_id}, quiz {$quiz_id}");
                return FALSE;
            }
            
        } catch (Exception $e) {
            log_message('error', "Exception in generate_performance_analysis: " . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Helper function to find strongest subject
     */
    private function findStrongestSubject($subject_breakdown) {
        if (empty($subject_breakdown) || !is_array($subject_breakdown)) {
            return 'N/A';
        }
        
        $strongest = '';
        $highest_score = -1;
        foreach ($subject_breakdown as $subject => $data) {
            $score = isset($data['accuracy']) ? $data['accuracy'] : (isset($data['score']) ? $data['score'] : 0);
            if ($score > $highest_score) {
                $highest_score = $score;
                $strongest = $subject;
            }
        }
        return $strongest ?: 'N/A';
    }
    
    /**
     * Helper function to find weakest subject
     */
    private function findWeakestSubject($subject_breakdown) {
        if (empty($subject_breakdown) || !is_array($subject_breakdown)) {
            return 'N/A';
        }
        
        $weakest = '';
        $lowest_score = 101; // Start higher than possible percentage
        foreach ($subject_breakdown as $subject => $data) {
            $score = isset($data['accuracy']) ? $data['accuracy'] : (isset($data['score']) ? $data['score'] : 100);
            if ($score < $lowest_score) {
                $lowest_score = $score;
                $weakest = $subject;
            }
        }
        return $weakest ?: 'N/A';
    }
    
    /**
     * Helper function to find strongest topic
     */
    private function findStrongestTopic($topic_breakdown) {
        if (empty($topic_breakdown) || !is_array($topic_breakdown)) {
            return 'N/A';
        }
        
        $strongest = '';
        $highest_score = -1;
        foreach ($topic_breakdown as $topic => $data) {
            $score = isset($data['accuracy']) ? $data['accuracy'] : (isset($data['score']) ? $data['score'] : 0);
            if ($score > $highest_score) {
                $highest_score = $score;
                $strongest = $topic;
            }
        }
        return $strongest ?: 'N/A';
    }
    
    /**
     * Helper function to find weakest topic
     */
    private function findWeakestTopic($topic_breakdown) {
        if (empty($topic_breakdown) || !is_array($topic_breakdown)) {
            return 'N/A';
        }
        
        $weakest = '';
        $lowest_score = 101; // Start higher than possible percentage
        foreach ($topic_breakdown as $topic => $data) {
            $score = isset($data['accuracy']) ? $data['accuracy'] : (isset($data['score']) ? $data['score'] : 100);
            if ($score < $lowest_score) {
                $lowest_score = $score;
                $weakest = $topic;
            }
        }
        return $weakest ?: 'N/A';
    }
    
    /**
     * Check if performance analysis should be generated
     */
    public function should_generate_analysis($user_id, $quiz_id) {
        // Check if quiz is completed
        $this->CI->load->model('user_question/user_question_model');
        if (!$this->CI->user_question_model->is_quiz_completed($user_id, $quiz_id)) {
            return FALSE;
        }
        
        // Check if analysis already exists
        $existing_analysis = $this->CI->user_performance_model->get_user_performance_by_quiz($user_id, $quiz_id);
        
        return ($existing_analysis === FALSE || $existing_analysis === NULL);
    }
    
    /**
     * Get performance summary for user
     */
    public function get_performance_summary($user_id, $limit = 5) {
        return $this->CI->user_performance_model->get_user_performance_list($user_id, $limit);
    }
    
    /**
     * Get detailed performance analysis by ID
     */
    public function get_performance_details($performance_id) {
        return $this->CI->user_performance_model->get_user_performance($performance_id);
    }
}

?>
