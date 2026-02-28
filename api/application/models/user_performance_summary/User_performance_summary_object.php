<?php

class User_performance_summary_object {
    public $id;
    public $user_id;
    public $summary_period_start;
    public $summary_period_end;
    public $generation_date;
    
    // Overall Quiz Statistics
    public $total_quizzes_taken;
    public $total_questions_attempted;
    public $total_correct_answers;
    public $total_incorrect_answers;
    public $overall_accuracy_percentage;
    public $average_quiz_score;
    
    // Time Management
    public $total_time_spent_minutes;
    public $average_time_per_quiz_minutes;
    public $average_time_per_question_seconds;
    public $time_efficiency_rating;
    
    // Subject & Topic Analysis
    public $strongest_overall_subject;
    public $weakest_overall_subject;
    public $subject_scores_json;
    public $strongest_overall_topic;
    public $weakest_overall_topic;
    public $topic_scores_json;
    
    // Performance Trends
    public $performance_trend;
    public $accuracy_trend;
    public $speed_trend;
    public $consistency_score;
    
    // Difficulty Analysis
    public $easy_level_accuracy;
    public $medium_level_accuracy;
    public $hard_level_accuracy;
    public $preferred_difficulty;
    
    // Learning Patterns
    public $most_active_day;
    public $most_active_time;
    public $average_session_length_minutes;
    public $quiz_frequency_per_week;
    
    // Progress Tracking
    public $mastery_level;
    public $learning_velocity;
    public $improvement_rate;
    
    // AI Generated Insights
    public $ai_overall_assessment;
    public $ai_learning_patterns;
    public $ai_strengths_summary;
    public $ai_improvement_areas;
    public $ai_study_recommendations;
    public $ai_predicted_performance;
    public $ai_personalized_goals;
    
    // Study Recommendations
    public $recommended_daily_study_minutes;
    public $recommended_focus_subjects;
    public $recommended_difficulty_progression;
    public $next_milestone;
    
    // Comparative Analysis
    public $previous_period_accuracy;
    public $accuracy_change_percentage;
    public $previous_period_speed;
    public $speed_change_percentage;
    
    // Raw Data
    public $ai_prompt_used;
    public $raw_ai_response;
    public $analysis_version;
    public $data_quality_score;
    
    // Metadata
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $this->id = 0;
        $this->user_id = 0;
        $this->summary_period_start = null;
        $this->summary_period_end = null;
        $this->generation_date = null;
        
        // Initialize with default values
        $this->total_quizzes_taken = 0;
        $this->total_questions_attempted = 0;
        $this->total_correct_answers = 0;
        $this->total_incorrect_answers = 0;
        $this->overall_accuracy_percentage = 0.00;
        $this->average_quiz_score = 0.00;
        
        $this->total_time_spent_minutes = 0;
        $this->average_time_per_quiz_minutes = 0.00;
        $this->average_time_per_question_seconds = 0.00;
        $this->time_efficiency_rating = null;
        
        $this->strongest_overall_subject = null;
        $this->weakest_overall_subject = null;
        $this->subject_scores_json = '{}';
        $this->strongest_overall_topic = null;
        $this->weakest_overall_topic = null;
        $this->topic_scores_json = '{}';
        
        $this->performance_trend = 'stable';
        $this->accuracy_trend = 'stable';
        $this->speed_trend = 'stable';
        $this->consistency_score = 0.00;
        
        $this->easy_level_accuracy = 0.00;
        $this->medium_level_accuracy = 0.00;
        $this->hard_level_accuracy = 0.00;
        $this->preferred_difficulty = null;
        
        $this->most_active_day = null;
        $this->most_active_time = null;
        $this->average_session_length_minutes = 0.00;
        $this->quiz_frequency_per_week = 0.00;
        
        $this->mastery_level = 'Beginner';
        $this->learning_velocity = 'moderate';
        $this->improvement_rate = 0.00;
        
        $this->ai_overall_assessment = null;
        $this->ai_learning_patterns = null;
        $this->ai_strengths_summary = null;
        $this->ai_improvement_areas = null;
        $this->ai_study_recommendations = null;
        $this->ai_predicted_performance = null;
        $this->ai_personalized_goals = null;
        
        $this->recommended_daily_study_minutes = 30;
        $this->recommended_focus_subjects = '[]';
        $this->recommended_difficulty_progression = null;
        $this->next_milestone = null;
        
        $this->previous_period_accuracy = null;
        $this->accuracy_change_percentage = null;
        $this->previous_period_speed = null;
        $this->speed_change_percentage = null;
        
        $this->ai_prompt_used = null;
        $this->raw_ai_response = null;
        $this->analysis_version = '1.0';
        $this->data_quality_score = 100.00;
        
        $this->created_at = null;
        $this->updated_at = null;
    }
    
    /**
     * Convert object to array for API responses
     */
    public function to_array() {
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'summary_period_start' => $this->summary_period_start,
            'summary_period_end' => $this->summary_period_end,
            'generation_date' => $this->generation_date,
            
            // Overall Quiz Statistics
            'total_quizzes_taken' => $this->total_quizzes_taken,
            'total_questions_attempted' => $this->total_questions_attempted,
            'total_correct_answers' => $this->total_correct_answers,
            'total_incorrect_answers' => $this->total_incorrect_answers,
            'overall_accuracy_percentage' => $this->overall_accuracy_percentage,
            'average_quiz_score' => $this->average_quiz_score,
            
            // Time Management
            'total_time_spent_minutes' => $this->total_time_spent_minutes,
            'average_time_per_quiz_minutes' => $this->average_time_per_quiz_minutes,
            'average_time_per_question_seconds' => $this->average_time_per_question_seconds,
            'time_efficiency_rating' => $this->time_efficiency_rating,
            
            // Subject & Topic Analysis
            'strongest_overall_subject' => $this->strongest_overall_subject,
            'weakest_overall_subject' => $this->weakest_overall_subject,
            'subject_scores_json' => $this->subject_scores_json,
            'strongest_overall_topic' => $this->strongest_overall_topic,
            'weakest_overall_topic' => $this->weakest_overall_topic,
            'topic_scores_json' => $this->topic_scores_json,
            
            // Performance Trends
            'performance_trend' => $this->performance_trend,
            'accuracy_trend' => $this->accuracy_trend,
            'speed_trend' => $this->speed_trend,
            'consistency_score' => $this->consistency_score,
            
            // Difficulty Analysis
            'easy_level_accuracy' => $this->easy_level_accuracy,
            'medium_level_accuracy' => $this->medium_level_accuracy,
            'hard_level_accuracy' => $this->hard_level_accuracy,
            'preferred_difficulty' => $this->preferred_difficulty,
            
            // Learning Patterns
            'most_active_day' => $this->most_active_day,
            'most_active_time' => $this->most_active_time,
            'average_session_length_minutes' => $this->average_session_length_minutes,
            'quiz_frequency_per_week' => $this->quiz_frequency_per_week,
            
            // Progress Tracking
            'mastery_level' => $this->mastery_level,
            'learning_velocity' => $this->learning_velocity,
            'improvement_rate' => $this->improvement_rate,
            
            // AI Generated Insights
            'ai_overall_assessment' => $this->ai_overall_assessment,
            'ai_learning_patterns' => $this->ai_learning_patterns,
            'ai_strengths_summary' => $this->ai_strengths_summary,
            'ai_improvement_areas' => $this->ai_improvement_areas,
            'ai_study_recommendations' => $this->ai_study_recommendations,
            'ai_predicted_performance' => $this->ai_predicted_performance,
            'ai_personalized_goals' => $this->ai_personalized_goals,
            
            // Study Recommendations
            'recommended_daily_study_minutes' => $this->recommended_daily_study_minutes,
            'recommended_focus_subjects' => $this->recommended_focus_subjects,
            'recommended_difficulty_progression' => $this->recommended_difficulty_progression,
            'next_milestone' => $this->next_milestone,
            
            // Comparative Analysis
            'previous_period_accuracy' => $this->previous_period_accuracy,
            'accuracy_change_percentage' => $this->accuracy_change_percentage,
            'previous_period_speed' => $this->previous_period_speed,
            'speed_change_percentage' => $this->speed_change_percentage,
            
            // Raw Data
            'ai_prompt_used' => $this->ai_prompt_used,
            'raw_ai_response' => $this->raw_ai_response,
            'analysis_version' => $this->analysis_version,
            'data_quality_score' => $this->data_quality_score,
            
            // Metadata
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        );
    }
}

?>
