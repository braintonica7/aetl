<?php

class User_performance_object extends CI_Model {

    public $id;
    public $user_id;
    public $quiz_id;
    public $quiz_attempt_number;
    
    // Current Quiz Statistics
    public $total_questions;
    public $correct_answers;
    public $incorrect_answers;
    public $unanswered_questions;
    public $accuracy_percentage;
    public $total_time_spent;
    public $average_time_per_question;
    
    // Subject/Topic Analysis
    public $strongest_subject;
    public $weakest_subject;
    public $subject_scores;
    public $strongest_topic;
    public $weakest_topic;
    public $topic_scores;
    
    // Progress Tracking
    public $previous_quiz_accuracy;
    public $accuracy_improvement;
    public $previous_avg_time;
    public $time_improvement;
    public $overall_progress_trend;
    public $subject_progress;
    
    // Performance Scoring
    public $time_management_score;
    public $difficulty_performance;
    public $performance_score;
    
    // AI Generated Insights
    public $ai_recommendations;
    public $ai_learning_suggestions;
    public $progress_insights;
    public $improvement_areas;
    public $strengths_identified;
    
    // Raw Data Storage
    public $raw_ai_response;
    public $ai_prompt;
    public $analysis_version;
    
    // Metadata
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->quiz_id = 0;
        $this->quiz_attempt_number = 1;
        
        $this->total_questions = 0;
        $this->correct_answers = 0;
        $this->incorrect_answers = 0;
        $this->unanswered_questions = 0;
        $this->accuracy_percentage = 0.0;
        $this->total_time_spent = 0;
        $this->average_time_per_question = 0.0;
        
        $this->strongest_subject = '';
        $this->weakest_subject = '';
        $this->subject_scores = '';
        $this->strongest_topic = '';
        $this->weakest_topic = '';
        $this->topic_scores = '';
        
        $this->previous_quiz_accuracy = 0.0;
        $this->accuracy_improvement = 0.0;
        $this->previous_avg_time = 0.0;
        $this->time_improvement = 0.0;
        $this->overall_progress_trend = 'first_attempt';
        $this->subject_progress = '';
        
        $this->time_management_score = 0.0;
        $this->difficulty_performance = '';
        $this->performance_score = 0.0;
        
        $this->ai_recommendations = '';
        $this->ai_learning_suggestions = '';
        $this->progress_insights = '';
        $this->improvement_areas = '';
        $this->strengths_identified = '';
        
        $this->raw_ai_response = '';
        $this->ai_prompt = '';
        $this->analysis_version = '1.0';
        
        $this->created_at = '';
        $this->updated_at = '';
    }

}

?>
