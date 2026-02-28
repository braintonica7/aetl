<?php

class User_performance_summary_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Add or update user performance summary
     */
    public function add_user_performance_summary($objSummary) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "INSERT INTO user_performance_summary (
                user_id, summary_period_start, summary_period_end, generation_date,
                total_quizzes_taken, total_questions_attempted, total_correct_answers, total_incorrect_answers,
                overall_accuracy_percentage, average_quiz_score, total_time_spent_minutes,
                average_time_per_quiz_minutes, average_time_per_question_seconds, time_efficiency_rating,
                strongest_overall_subject, weakest_overall_subject, subject_scores_json,
                strongest_overall_topic, weakest_overall_topic, topic_scores_json,
                performance_trend, accuracy_trend, speed_trend, consistency_score,
                easy_level_accuracy, medium_level_accuracy, hard_level_accuracy, preferred_difficulty,
                most_active_day, most_active_time, average_session_length_minutes, quiz_frequency_per_week,
                mastery_level, learning_velocity, improvement_rate,
                ai_overall_assessment, ai_learning_patterns, ai_strengths_summary, ai_improvement_areas,
                ai_study_recommendations, ai_predicted_performance, ai_personalized_goals,
                recommended_daily_study_minutes, recommended_focus_subjects, recommended_difficulty_progression, next_milestone,
                previous_period_accuracy, accuracy_change_percentage, previous_period_speed, speed_change_percentage,
                ai_prompt_used, raw_ai_response, analysis_version, data_quality_score
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
            
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $objSummary->user_id,
                $objSummary->summary_period_start,
                $objSummary->summary_period_end,
                $objSummary->generation_date,
                $objSummary->total_quizzes_taken,
                $objSummary->total_questions_attempted,
                $objSummary->total_correct_answers,
                $objSummary->total_incorrect_answers,
                $objSummary->overall_accuracy_percentage,
                $objSummary->average_quiz_score,
                $objSummary->total_time_spent_minutes,
                $objSummary->average_time_per_quiz_minutes,
                $objSummary->average_time_per_question_seconds,
                $objSummary->time_efficiency_rating,
                $objSummary->strongest_overall_subject,
                $objSummary->weakest_overall_subject,
                $objSummary->subject_scores_json,
                $objSummary->strongest_overall_topic,
                $objSummary->weakest_overall_topic,
                $objSummary->topic_scores_json,
                $objSummary->performance_trend,
                $objSummary->accuracy_trend,
                $objSummary->speed_trend,
                $objSummary->consistency_score,
                $objSummary->easy_level_accuracy,
                $objSummary->medium_level_accuracy,
                $objSummary->hard_level_accuracy,
                $objSummary->preferred_difficulty,
                $objSummary->most_active_day,
                $objSummary->most_active_time,
                $objSummary->average_session_length_minutes,
                $objSummary->quiz_frequency_per_week,
                $objSummary->mastery_level,
                $objSummary->learning_velocity,
                $objSummary->improvement_rate,
                $objSummary->ai_overall_assessment,
                $objSummary->ai_learning_patterns,
                $objSummary->ai_strengths_summary,
                $objSummary->ai_improvement_areas,
                $objSummary->ai_study_recommendations,
                $objSummary->ai_predicted_performance,
                $objSummary->ai_personalized_goals,
                $objSummary->recommended_daily_study_minutes,
                $objSummary->recommended_focus_subjects,
                $objSummary->recommended_difficulty_progression,
                $objSummary->next_milestone,
                $objSummary->previous_period_accuracy,
                $objSummary->accuracy_change_percentage,
                $objSummary->previous_period_speed,
                $objSummary->speed_change_percentage,
                $objSummary->ai_prompt_used,
                $objSummary->raw_ai_response,
                $objSummary->analysis_version,
                $objSummary->data_quality_score
            ));
            
            if ($result) {
                $objSummary->id = $pdo->lastInsertId();
                $statement = null;
                $pdo = null;
                return $objSummary;
            } else {
                $statement = null;
                $pdo = null;
                return false;
            }
        } catch (Exception $e) {
            log_message('error', "Error adding user performance summary: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get latest performance summary for a user
     */
    public function get_latest_user_summary($user_id) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT * FROM user_performance_summary 
                    WHERE user_id = ? 
                    ORDER BY generation_date DESC 
                    LIMIT 1";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $row = $statement->fetch();
            
            if ($row) {
                $summary = $this->map_to_object($row);
                $statement = null;
                $pdo = null;
                return $summary;
            } else {
                $statement = null;
                $pdo = null;
                return null;
            }
        } catch (Exception $e) {
            log_message('error', "Error getting latest user summary: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user performance summary history
     */
    public function get_user_summary_history($user_id, $limit = 10) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT * FROM user_performance_summary 
                    WHERE user_id = ? 
                    ORDER BY generation_date DESC 
                    LIMIT ?";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $limit));
            
            $summaries = array();
            while ($row = $statement->fetch()) {
                $summaries[] = $this->map_to_object($row);
            }
            
            $statement = null;
            $pdo = null;
            return $summaries;
        } catch (Exception $e) {
            log_message('error', "Error getting user summary history: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Get aggregated data for performance summary generation
     */
    public function get_user_performance_data($user_id, $months_back = 6) {
        try {
            $pdo = CDatabase::getPdo();
            
            $start_date = date('Y-m-d', strtotime("-{$months_back} months"));
            $end_date = date('Y-m-d');
            
            // Get all user quiz performance data for the period
            $sql = "SELECT 
                        up.*,
                        q.name as quiz_name,
                        q.start_date as quiz_date,
                        s.subject as subject_name,
                        e.exam_name
                    FROM user_performance up
                    LEFT JOIN quiz q ON up.quiz_id = q.id
                    LEFT JOIN subject s ON q.subject_id = s.id
                    LEFT JOIN exam e ON q.exam_id = e.id
                    WHERE up.user_id = ? 
                    AND DATE(up.created_at) >= ? 
                    AND DATE(up.created_at) <= ?
                    ORDER BY up.created_at ASC";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $start_date, $end_date));
            $performance_records = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            // Get quiz activity patterns
            $activity_sql = "SELECT 
                                DAYNAME(uq.created_at) as day_name,
                                HOUR(uq.created_at) as hour_of_day,
                                COUNT(*) as question_count,
                                AVG(uq.duration) as avg_duration,
                                DATE(uq.created_at) as quiz_date
                             FROM user_question uq
                             WHERE uq.user_id = ?
                             AND DATE(uq.created_at) >= ?
                             AND DATE(uq.created_at) <= ?
                             GROUP BY DATE(uq.created_at), DAYNAME(uq.created_at), HOUR(uq.created_at)
                             ORDER BY uq.created_at";
            
            $statement = $pdo->prepare($activity_sql);
            $statement->execute(array($user_id, $start_date, $end_date));
            $activity_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            // Get difficulty-wise performance
            $difficulty_sql = "SELECT 
                                   q.level,
                                   COUNT(*) as total_questions,
                                   SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                                   AVG(uq.duration) as avg_time
                               FROM user_question uq
                               JOIN quiz_question qq ON uq.quiz_question_id = qq.id
                               JOIN question q ON qq.question_id = q.id
                               WHERE uq.user_id = ?
                               AND DATE(uq.created_at) >= ?
                               AND DATE(uq.created_at) <= ?
                               GROUP BY q.level";
            
            $statement = $pdo->prepare($difficulty_sql);
            $statement->execute(array($user_id, $start_date, $end_date));
            $difficulty_data = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $statement = null;
            $pdo = null;
            
            return array(
                'period_start' => $start_date,
                'period_end' => $end_date,
                'performance_records' => $performance_records,
                'activity_data' => $activity_data,
                'difficulty_data' => $difficulty_data
            );
            
        } catch (Exception $e) {
            log_message('error', "Error getting user performance data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if summary needs to be generated for user
     */
    public function needs_summary_generation($user_id) {
        try {
            $pdo = CDatabase::getPdo();
            
            // Check if there's a summary generated in the last week
            $sql = "SELECT id FROM user_performance_summary 
                    WHERE user_id = ? 
                    AND generation_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                    ORDER BY generation_date DESC 
                    LIMIT 1";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $result = $statement->fetch();
            
            $statement = null;
            $pdo = null;
            
            return !$result; // Returns true if no recent summary found
            
        } catch (Exception $e) {
            log_message('error', "Error checking summary generation need: " . $e->getMessage());
            return true; // Default to generating if error
        }
    }
    
    /**
     * Get activity patterns for user within a time period
     */
    public function get_activity_patterns($user_id, $start_date, $end_date) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "
                SELECT 
                    DAYNAME(STR_TO_DATE(up.created_at, '%Y-%m-%d %H:%i:%s')) as day_name,
                    HOUR(STR_TO_DATE(up.created_at, '%Y-%m-%d %H:%i:%s')) as hour_of_day,
                    COUNT(*) as quiz_count,
                    SUM(up.total_questions) as question_count,
                    SUM(up.total_time_spent) as total_time
                FROM user_performance up
                WHERE up.user_id = ? 
                AND up.created_at >= ? 
                AND up.created_at <= ?
                GROUP BY day_name, hour_of_day
                ORDER BY quiz_count DESC
            ";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $start_date, $end_date));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $statement = null;
            $pdo = null;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Error getting activity patterns: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get difficulty level performance for user within a time period
     */
    public function get_difficulty_performance($user_id, $start_date, $end_date) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "
                SELECT 
                    COALESCE(up.difficulty_level, 'medium') as level,
                    COUNT(*) as quiz_count,
                    SUM(up.total_questions) as total_questions,
                    SUM(up.correct_answers) as correct_answers,
                    AVG(up.total_time_spent) as avg_time_spent
                FROM user_performance up
                WHERE up.user_id = ? 
                AND up.created_at >= ? 
                AND up.created_at <= ?
                GROUP BY level
            ";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $start_date, $end_date));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $statement = null;
            $pdo = null;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Error getting difficulty performance: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get user performance data for specific period (used by controller)
     */
    public function get_user_performance_period($user_id, $start_date, $end_date) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "
                SELECT 
                    up.*,
                    q.name as quiz_name,
                    s.subject as subject_name,
                    e.exam_name
                FROM user_performance up
                LEFT JOIN quiz q ON up.quiz_id = q.id
                LEFT JOIN subject s ON q.subject_id = s.id
                LEFT JOIN exam e ON q.exam_id = e.id
                WHERE up.user_id = ? 
                AND up.created_at >= ? 
                AND up.created_at <= ?
                ORDER BY up.created_at ASC
            ";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $start_date, $end_date));
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $statement = null;
            $pdo = null;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Error getting user performance period: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Map database row to object
     */
    private function map_to_object($row) {
        $obj = new User_performance_summary_object();
        
        foreach ($row as $key => $value) {
            if (property_exists($obj, $key)) {
                $obj->$key = $value;
            }
        }
        
        return $obj;
    }

    /**
     * Get latest performance summary for a user
     * 
     * @param int $user_id User ID
     * @return object|null Latest summary object or null
     */
    public function get_latest_summary_by_user($user_id) {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT * FROM user_performance_summary 
                    WHERE user_id = ? 
                    ORDER BY generation_date DESC 
                    LIMIT 1";
            
            $statement = $pdo->prepare($sql);
            $statement->execute([$user_id]);
            $result = $statement->fetch(PDO::FETCH_OBJ);
            
            $statement = null;
            $pdo = null;
            
            return $result ?: null;
            
        } catch (Exception $e) {
            log_message('error', "Error fetching latest summary for user {$user_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete old summaries (keep only last 12 months)
     */
    public function cleanup_old_summaries() {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "DELETE FROM user_performance_summary 
                    WHERE generation_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)";
            
            $statement = $pdo->prepare($sql);
            $result = $statement->execute();
            
            $statement = null;
            $pdo = null;
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Error cleaning up old summaries: " . $e->getMessage());
            return false;
        }
    }
}

?>
