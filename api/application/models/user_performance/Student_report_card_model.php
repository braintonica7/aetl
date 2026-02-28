<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Student Report Card Model
 * Handles database operations for student report card generation and retrieval
 */
class Student_report_card_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Save or update student report card
     * @param int $user_id
     * @param array $report_data
     * @return int|false Report card ID or false on failure
     */
    public function save_student_report_card($user_id, $report_data)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Delete existing report card for this user (keep only latest)
            $sql_delete = "DELETE FROM student_report_card WHERE user_id = ?";
            $statement = $pdo->prepare($sql_delete);
            $statement->execute(array($user_id));
            
            // Insert new report card
            $sql_insert = "INSERT INTO student_report_card (
                user_id, generated_at, data_period_start, data_period_end,
                total_quizzes_taken, total_questions_attempted, total_correct_answers,
                total_incorrect_answers, total_skipped_questions,
                total_time_spent_seconds, average_time_per_quiz_seconds, average_time_per_question_seconds,
                overall_accuracy_percentage, average_quiz_score, highest_quiz_score,
                highest_score_quiz_id, highest_score_quiz_name,
                subject_wise_stats, strongest_subject, weakest_subject,
                chapter_wise_stats, topic_wise_stats, topics_covered_count,
                difficulty_wise_stats, quiz_progress_data, accuracy_trend_data,
                speed_improvement_data, learning_streak_days, questions_per_day_average,
                peak_performance_hour, favorite_subject, total_badges_earned, achievement_score
            ) VALUES (
                ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
            
            $params = array(
                $user_id,
                $report_data['data_period_start'],
                $report_data['data_period_end'],
                $report_data['total_quizzes_taken'],
                $report_data['total_questions_attempted'],
                $report_data['total_correct_answers'],
                $report_data['total_incorrect_answers'],
                $report_data['total_skipped_questions'],
                $report_data['total_time_spent_seconds'],
                $report_data['average_time_per_quiz_seconds'],
                $report_data['average_time_per_question_seconds'],
                $report_data['overall_accuracy_percentage'],
                $report_data['average_quiz_score'],
                $report_data['highest_quiz_score'],
                $report_data['highest_score_quiz_id'],
                $report_data['highest_score_quiz_name'],
                $report_data['subject_wise_stats'],
                $report_data['strongest_subject'],
                $report_data['weakest_subject'],
                $report_data['chapter_wise_stats'],
                $report_data['topic_wise_stats'],
                $report_data['topics_covered_count'],
                $report_data['difficulty_wise_stats'],
                $report_data['quiz_progress_data'],
                $report_data['accuracy_trend_data'],
                $report_data['speed_improvement_data'],
                $report_data['learning_streak_days'],
                $report_data['questions_per_day_average'],
                $report_data['peak_performance_hour'],
                $report_data['favorite_subject'],
                $report_data['total_badges_earned'],
                $report_data['achievement_score']
            );
            
            $statement = $pdo->prepare($sql_insert);
            $result = $statement->execute($params);
            
            if ($result) {
                $report_id = $pdo->lastInsertId();
                $statement = NULL;
                $pdo = NULL;
                return $report_id;
            }
            
            $statement = NULL;
            $pdo = NULL;
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Error saving student report card: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Get student report card by user ID
     * @param int $user_id
     * @return object|false Report card data or false if not found
     */
    public function get_student_report_card($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT * FROM student_report_card WHERE user_id = ? ORDER BY generated_at DESC LIMIT 1";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            $statement = NULL;
            $pdo = NULL;
            
            if ($result) {
                // Parse JSON fields
                $result['subject_wise_stats'] = json_decode($result['subject_wise_stats'], true);
                $result['chapter_wise_stats'] = json_decode($result['chapter_wise_stats'], true);
                $result['topic_wise_stats'] = json_decode($result['topic_wise_stats'], true);
                $result['difficulty_wise_stats'] = json_decode($result['difficulty_wise_stats'], true);
                $result['quiz_progress_data'] = json_decode($result['quiz_progress_data'], true);
                $result['accuracy_trend_data'] = json_decode($result['accuracy_trend_data'], true);
                $result['speed_improvement_data'] = json_decode($result['speed_improvement_data'], true);
                
                return (object)$result;
            }
            
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Error retrieving student report card: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Check if report card exists for user
     * @param int $user_id
     * @return bool
     */
    public function report_card_exists($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT COUNT(*) as count FROM student_report_card WHERE user_id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $result = $statement->fetch();
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result && $result['count'] > 0;
            
        } catch (Exception $e) {
            log_message('error', 'Error checking report card existence: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Get report card generation date
     * @param int $user_id
     * @return string|false Generation date or false if not found
     */
    public function get_report_generation_date($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT generated_at FROM student_report_card WHERE user_id = ? ORDER BY generated_at DESC LIMIT 1";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $result = $statement->fetch();
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result ? $result['generated_at'] : FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Error getting report generation date: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Update badge count in report card
     * @param int $user_id
     * @param int $badge_count
     * @return bool
     */
    public function update_badge_count($user_id, $badge_count)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "UPDATE student_report_card SET total_badges_earned = ? WHERE user_id = ?";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($badge_count, $user_id));
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Error updating badge count: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Get summary statistics for all users (admin function)
     * @return array|false Summary data or false on failure
     */
    public function get_summary_statistics()
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT 
                      COUNT(*) as total_reports,
                      AVG(total_quizzes_taken) as avg_quizzes_per_user,
                      AVG(overall_accuracy_percentage) as avg_accuracy,
                      AVG(total_time_spent_seconds) as avg_study_time,
                      MAX(highest_quiz_score) as highest_score_overall,
                      AVG(total_badges_earned) as avg_badges_per_user
                    FROM student_report_card";
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result ? $result : FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Error getting summary statistics: ' . $e->getMessage());
            return FALSE;
        }
    }
}
