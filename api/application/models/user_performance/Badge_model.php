<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Badge Service Model
 * Handles badge criteria evaluation and user badge management
 */
class Badge_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check and award badges based on user performance data
     * @param int $user_id
     * @param array $report_data Performance data from report card
     * @return array List of newly earned badges
     */
    public function check_and_award_badges($user_id, $report_data)
    {
        try {
            $newly_earned = array();
            
            // Get all active badges
            $all_badges = $this->get_all_active_badges();
            
            foreach ($all_badges as $badge) {
                // Check if user already has this badge
                if ($this->user_has_badge($user_id, $badge['id'])) {
                    continue;
                }
                
                // Check if user meets criteria for this badge
                if ($this->check_badge_criteria($user_id, $badge, $report_data)) {
                    // Award the badge
                    $awarded = $this->award_badge_to_user($user_id, $badge['id'], $report_data);
                    if ($awarded) {
                        $newly_earned[] = $badge;
                    }
                }
            }
            
            // Update total badge count in report card
            $total_badges = $this->get_user_badge_count($user_id);
            $this->load->model('Student_report_card_model');
            $this->Student_report_card_model->update_badge_count($user_id, $total_badges);
            
            return $newly_earned;
            
        } catch (Exception $e) {
            log_message('error', 'Error checking and awarding badges: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Get all active badges from database
     * @return array List of active badges
     */
    private function get_all_active_badges()
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT * FROM badges WHERE is_active = 1 ORDER BY sort_order, badge_tier, badge_name";
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $badges = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse JSON criteria for each badge
            foreach ($badges as &$badge) {
                $badge['criteria_config'] = json_decode($badge['criteria_config'], true);
            }
            
            $statement = NULL;
            $pdo = NULL;
            
            return $badges;
            
        } catch (Exception $e) {
            log_message('error', 'Error getting active badges: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Check if user already has a specific badge
     * @param int $user_id
     * @param int $badge_id
     * @return bool
     */
    private function user_has_badge($user_id, $badge_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT COUNT(*) as count FROM user_badges WHERE user_id = ? AND badge_id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $badge_id));
            $result = $statement->fetch();
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result && $result['count'] > 0;
            
        } catch (Exception $e) {
            log_message('error', 'Error checking user badge: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Check if user meets criteria for a specific badge
     * @param int $user_id
     * @param array $badge Badge configuration
     * @param array $report_data Performance data
     * @return bool
     */
    private function check_badge_criteria($user_id, $badge, $report_data)
    {
        $criteria_type = $badge['criteria_type'];
        $criteria_config = $badge['criteria_config'];
        
        switch ($criteria_type) {
            case 'quiz_count':
                return $report_data['total_quizzes_taken'] >= $criteria_config['minimum_quizzes'];
                
            case 'accuracy_threshold':
                return $report_data['overall_accuracy_percentage'] >= $criteria_config['minimum_accuracy'] &&
                       $report_data['total_quizzes_taken'] >= $criteria_config['minimum_quizzes'];
                       
            case 'speed_threshold':
                return $report_data['average_time_per_question_seconds'] <= $criteria_config['maximum_avg_time'] &&
                       $report_data['total_quizzes_taken'] >= $criteria_config['minimum_quizzes'];
                       
            case 'streak':
                return $report_data['learning_streak_days'] >= $criteria_config['consecutive_days'];
                
            case 'subject_mastery':
                return $this->check_subject_mastery($user_id, $criteria_config, $report_data);
                
            case 'perfect_score':
                return $report_data['highest_quiz_score'] >= $criteria_config['required_score'];
                
            case 'improvement_rate':
                return $this->check_improvement_rate($user_id, $criteria_config);
                
            case 'time_pattern':
                return $this->check_time_pattern($user_id, $criteria_config);
                
            default:
                return FALSE;
        }
    }

    /**
     * Check subject mastery criteria
     * @param int $user_id
     * @param array $criteria_config
     * @param array $report_data
     * @return bool
     */
    private function check_subject_mastery($user_id, $criteria_config, $report_data)
    {
        $subject_stats = json_decode($report_data['subject_wise_stats'], true);
        $required_subject = $criteria_config['subject'];
        
        if (!isset($subject_stats[$required_subject])) {
            return FALSE;
        }
        
        $subject_data = $subject_stats[$required_subject];
        
        return $subject_data['accuracy'] >= $criteria_config['minimum_accuracy'] &&
               $subject_data['total_questions'] >= $criteria_config['minimum_questions'];
    }

    /**
     * Check improvement rate criteria
     * @param int $user_id
     * @param array $criteria_config
     * @return bool
     */
    private function check_improvement_rate($user_id, $criteria_config)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Get recent quiz scores for improvement calculation  
            $sql = "SELECT 
                      (SUM(COALESCE(score, 0)) / (COUNT(*) * 4)) * 100 as quiz_score,
                      MIN(created_at) as quiz_date
                    FROM user_question 
                    WHERE user_id = ?
                    GROUP BY quiz_id
                    ORDER BY quiz_date DESC
                    LIMIT ?";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $criteria_config['quiz_span']));
            $scores = $statement->fetchAll();
            
            $statement = NULL;
            $pdo = NULL;
            
            if (count($scores) < $criteria_config['quiz_span']) {
                return FALSE;
            }
            
            // Calculate improvement from first to last quiz in the span
            $first_score = end($scores)['quiz_score']; // Oldest
            $last_score = reset($scores)['quiz_score']; // Newest
            
            $improvement = $last_score - $first_score;
            
            return $improvement >= $criteria_config['minimum_improvement'];
            
        } catch (Exception $e) {
            log_message('error', 'Error checking improvement rate: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Check time pattern criteria (e.g., night owl, early bird)
     * @param int $user_id
     * @param array $criteria_config
     * @return bool
     */
    private function check_time_pattern($user_id, $criteria_config)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $time_range = explode('-', $criteria_config['time_range']);
            $start_hour = (int)explode(':', $time_range[0])[0];
            $end_hour = (int)explode(':', $time_range[1])[0];
            
            // Handle overnight ranges (e.g., 22:00-06:00)
            if ($start_hour > $end_hour) {
                $sql = "SELECT COUNT(DISTINCT quiz_id) as quiz_count
                        FROM user_question 
                        WHERE user_id = ? 
                        AND (HOUR(created_at) >= ? OR HOUR(created_at) <= ?)";
            } else {
                $sql = "SELECT COUNT(DISTINCT quiz_id) as quiz_count
                        FROM user_question 
                        WHERE user_id = ? 
                        AND HOUR(created_at) >= ? AND HOUR(created_at) <= ?";
            }
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $start_hour, $end_hour));
            $result = $statement->fetch();
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result && $result['quiz_count'] >= $criteria_config['minimum_quizzes'];
            
        } catch (Exception $e) {
            log_message('error', 'Error checking time pattern: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Award a badge to a user
     * @param int $user_id
     * @param int $badge_id
     * @param array $criteria_met_data
     * @return bool
     */
    private function award_badge_to_user($user_id, $badge_id, $criteria_met_data)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "INSERT INTO user_badges (user_id, badge_id, earned_at, criteria_met_data) 
                    VALUES (?, ?, NOW(), ?)";
            
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $user_id, 
                $badge_id, 
                json_encode($criteria_met_data)
            ));
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Error awarding badge: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Get user badges with progress toward unearned badges
     * @param int $user_id
     * @return array Badge data with progress information
     */
    public function get_user_badges_with_progress($user_id)
    {
        try {
            $earned_badges = $this->get_user_earned_badges($user_id);
            $available_badges = $this->get_badges_with_progress($user_id);
            
            return array(
                'earned_badges' => $earned_badges,
                'available_badges' => $available_badges,
                'total_earned' => count($earned_badges),
                'total_available' => count($available_badges)
            );
            
        } catch (Exception $e) {
            log_message('error', 'Error getting user badges with progress: ' . $e->getMessage());
            return array(
                'earned_badges' => array(),
                'available_badges' => array(),
                'total_earned' => 0,
                'total_available' => 0
            );
        }
    }

    /**
     * Get badges earned by user
     * @param int $user_id
     * @return array Earned badges
     */
    private function get_user_earned_badges($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT b.*, ub.earned_at, ub.is_displayed
                    FROM user_badges ub
                    JOIN badges b ON ub.badge_id = b.id
                    WHERE ub.user_id = ?
                    ORDER BY ub.earned_at DESC, b.badge_tier, b.badge_name";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $badges = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $statement = NULL;
            $pdo = NULL;
            
            return $badges;
            
        } catch (Exception $e) {
            log_message('error', 'Error getting user earned badges: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Get available badges with progress toward earning them
     * @param int $user_id
     * @return array Available badges with progress
     */
    private function get_badges_with_progress($user_id)
    {
        try {
            // Get current user stats for progress calculation
            $this->load->model('Student_report_card_model');
            $report_card = $this->Student_report_card_model->get_student_report_card($user_id);
            
            if (!$report_card) {
                return array();
            }
            
            $all_badges = $this->get_all_active_badges();
            $available_badges = array();
            
            foreach ($all_badges as $badge) {
                if (!$this->user_has_badge($user_id, $badge['id'])) {
                    $progress = $this->calculate_badge_progress($user_id, $badge, $report_card);
                    $badge['progress'] = $progress;
                    $available_badges[] = $badge;
                }
            }
            
            return $available_badges;
            
        } catch (Exception $e) {
            log_message('error', 'Error getting available badges: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Calculate progress toward earning a badge
     * @param int $user_id
     * @param array $badge
     * @param object $report_card
     * @return array Progress information
     */
    private function calculate_badge_progress($user_id, $badge, $report_card)
    {
        $criteria_type = $badge['criteria_type'];
        $criteria_config = $badge['criteria_config'];
        
        switch ($criteria_type) {
            case 'quiz_count':
                $current = $report_card->total_quizzes_taken;
                $required = $criteria_config['minimum_quizzes'];
                return array(
                    'current' => $current,
                    'required' => $required,
                    'percentage' => min(100, round(($current / $required) * 100, 1)),
                    'description' => "Complete {$required} quizzes (currently {$current})"
                );
                
            case 'accuracy_threshold':
                $current_accuracy = $report_card->overall_accuracy_percentage;
                $current_quizzes = $report_card->total_quizzes_taken;
                $required_accuracy = $criteria_config['minimum_accuracy'];
                $required_quizzes = $criteria_config['minimum_quizzes'];
                
                $accuracy_progress = min(100, ($current_accuracy / $required_accuracy) * 100);
                $quiz_progress = min(100, ($current_quizzes / $required_quizzes) * 100);
                
                return array(
                    'current_accuracy' => $current_accuracy,
                    'required_accuracy' => $required_accuracy,
                    'current_quizzes' => $current_quizzes,
                    'required_quizzes' => $required_quizzes,
                    'percentage' => min($accuracy_progress, $quiz_progress),
                    'description' => "Achieve {$required_accuracy}% accuracy in {$required_quizzes} quizzes"
                );
                
            case 'streak':
                $current = $report_card->learning_streak_days;
                $required = $criteria_config['consecutive_days'];
                return array(
                    'current' => $current,
                    'required' => $required,
                    'percentage' => min(100, round(($current / $required) * 100, 1)),
                    'description' => "Take quizzes for {$required} consecutive days"
                );
                
            default:
                return array(
                    'percentage' => 0,
                    'description' => 'Progress calculation not available'
                );
        }
    }

    /**
     * Get total badge count for user
     * @param int $user_id
     * @return int
     */
    public function get_user_badge_count($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "SELECT COUNT(*) as count FROM user_badges WHERE user_id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $result = $statement->fetch();
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result ? (int)$result['count'] : 0;
            
        } catch (Exception $e) {
            log_message('error', 'Error getting user badge count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Toggle badge display for user
     * @param int $user_id
     * @param int $badge_id
     * @param bool $is_displayed
     * @return bool
     */
    public function toggle_badge_display($user_id, $badge_id, $is_displayed)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $sql = "UPDATE user_badges SET is_displayed = ? WHERE user_id = ? AND badge_id = ?";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($is_displayed ? 1 : 0, $user_id, $badge_id));
            
            $statement = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Error toggling badge display: ' . $e->getMessage());
            return FALSE;
        }
    }
}
