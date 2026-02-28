<?php
/**
 * Point Service
 * 
 * Handles all point calculation, awarding, and management logic for WiziAI platform
 * 
 * @author WiziAI Development Team
 * @version 1.0
 * @date September 20, 2025
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_point_service
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        // Points config is auto-loaded via autoload.php
        $this->CI->load->model('user_point/user_point_model');
        $this->CI->load->model('quiz/quiz_model');
        $this->CI->load->model('user_question/user_question_model');
    }

    /**
     * Get point configuration value
     */
    private function _get_config($key, $default = null)
    {
        return $this->CI->config->item($key) !== null ? $this->CI->config->item($key) : $default;
    }

    /**
     * Calculate and award points for quiz completion
     * 
     * @param int $user_id User ID
     * @param int $quiz_id Quiz ID
     * @param array $quiz_results Quiz results data
     * @return array Point calculation result
     */
    public function award_quiz_completion_points($user_id, $quiz_id, $quiz_results = null)
    {
        try {
            // Get quiz details
            $quiz = $this->CI->quiz_model->get_quiz($quiz_id);
            if (!$quiz) {
                return $this->_get_error_result('Quiz not found');
            }

            // Get quiz results if not provided
            if ($quiz_results === null) {
                $quiz_results = $this->_get_quiz_results($user_id, $quiz_id);
            }

            // Validate quiz eligibility for points
            $eligibility_check = $this->_validate_quiz_eligibility($user_id, $quiz_id, $quiz_results, $quiz);
            if (!$eligibility_check['eligible']) {
                return $this->_get_error_result($eligibility_check['reason']);
            }

            // Check weekly limit
            $weekly_check = $this->_check_weekly_limit($user_id);
            if (!$weekly_check['can_earn']) {
                return $this->_get_error_result('Weekly point limit reached: ' . $weekly_check['current_points'] . '/' . $weekly_check['limit']);
            }

            // Calculate points
            $point_calculation = $this->_calculate_points($quiz_results, $quiz);
            
            // Check if award would exceed weekly limit
            if (($weekly_check['current_points'] + $point_calculation['total_points']) > $weekly_check['limit']) {
                // Check if admin override is enabled
                if (!$this->_is_admin_override_enabled($user_id)) {
                    return $this->_get_error_result('Awarding points would exceed weekly limit. Admin override required.');
                }
            }

            // Award points
            $award_result = $this->_award_points($user_id, $quiz_id, $point_calculation, $quiz_results, $quiz);
            
            if ($award_result['success']) {
                // Update weekly summary - DISABLED: Method not implemented
                // $this->_update_weekly_summary($user_id, $point_calculation, $quiz_results);
                
                // Calculate AI tutor minutes
                $ai_minutes = $this->_calculate_ai_tutor_minutes($award_result['user_points']);
                
                return $this->_get_success_result($point_calculation, $award_result, $ai_minutes);
            } else {
                return $this->_get_error_result('Failed to award points: ' . $award_result['error']);
            }

        } catch (Exception $e) {
            log_message('error', 'Point Service Error: ' . $e->getMessage());
            return $this->_get_error_result('Internal error calculating points');
        }
    }

    /**
     * Get quiz results for point calculation
     */
    private function _get_quiz_results($user_id, $quiz_id)
    {
        // Get user answers for this quiz
        $user_answers = $this->CI->user_question_model->get_user_quiz_results($user_id, $quiz_id);
        
        // Calculate statistics
        $total_questions = count($user_answers);
        $correct_answers = 0;
        $skipped_questions = 0;
        $total_score = 0;
        $max_possible_score = 0;

        foreach ($user_answers as $answer) {
            if ($answer->status === 'skipped') {
                $skipped_questions++;
            } elseif ($answer->is_correct == 1) {
                $correct_answers++;
            }
            
            if (isset($answer->score)) {
                $total_score += $answer->score;
                $max_possible_score += $answer->score; // Assuming each question has same max score
            }
        }

        // Calculate percentage
        $score_percentage = 0;
        if ($max_possible_score > 0) {
            $score_percentage = ($total_score / $max_possible_score) * 100;
        } elseif ($total_questions > 0) {
            // Fallback: use correct answers percentage
            $score_percentage = ($correct_answers / $total_questions) * 100;
        }

        return [
            'total_questions' => $total_questions,
            'correct_answers' => $correct_answers,
            'skipped_questions' => $skipped_questions,
            'total_score' => $total_score,
            'max_possible_score' => $max_possible_score,
            'score_percentage' => round($score_percentage, 2),
            'answers' => $user_answers
        ];
    }

    /**
     * Validate if quiz is eligible for point award
     */
    private function _validate_quiz_eligibility($user_id, $quiz_id, $quiz_results, $quiz)
    {
        // Check if point system is enabled
        if (!$this->_get_config('points_system_enabled', true)) {
            return [
                'eligible' => false,
                'reason' => 'Point system is currently disabled'
            ];
        }

        // Check minimum questions requirement
        $min_questions = $this->_get_config('points_minimum_quiz_questions', 30);
        if ($quiz_results['total_questions'] < $min_questions) {
            return [
                'eligible' => false, 
                'reason' => "Quiz must have at least {$min_questions} questions"
            ];
        }

        // Check if user has already been awarded points for this quiz
        if ($this->_get_config('points_duplicate_check_enabled', true)) {
            $existing_transactions = $this->CI->user_point_model->get_user_point_transactions($user_id, 50, 0, 'earned');
            $existing_award = null;
            foreach ($existing_transactions as $transaction) {
                if ($transaction->quiz_id == $quiz_id) {
                    $existing_award = $transaction;
                    break;
                }
            }
            if ($existing_award) {
                return [
                    'eligible' => false, 
                    'reason' => 'Points already awarded for this quiz completion'
                ];
            }
        }

        // Check if user answered at least some questions
        if ($quiz_results['total_questions'] === $quiz_results['skipped_questions']) {
            return [
                'eligible' => false, 
                'reason' => 'Cannot award points for quiz with all questions skipped'
            ];
        }

        // Check minimum questions answered percentage (e.g., 90% must be answered, not skipped)
        $min_answered_percentage = $this->_get_config('points_minimum_questions_answered_percentage', 90);
        $answered_questions = $quiz_results['total_questions'] - $quiz_results['skipped_questions'];
        $answered_percentage = ($answered_questions / $quiz_results['total_questions']) * 100;
        
        if ($answered_percentage < $min_answered_percentage) {
            return [
                'eligible' => false,
                'reason' => "Must answer at least {$min_answered_percentage}% of questions to earn points. Currently answered: " . round($answered_percentage, 1) . "%"
            ];
        }

        // Check minimum score requirement (if enabled)
        if ($this->_get_config('points_require_minimum_score', false)) {
            $min_score = $this->_get_config('points_minimum_score_threshold', 0);
            if ($quiz_results['score_percentage'] < $min_score) {
                return [
                    'eligible' => false,
                    'reason' => "Score must be at least {$min_score}% to earn points"
                ];
            }
        }

        return ['eligible' => true, 'reason' => 'Quiz eligible for points'];
    }

    /**
     * Calculate points based on quiz performance
     */
    private function _calculate_points($quiz_results, $quiz)
    {
        $base_points = 0;
        $bonus_points = 0;
        $bonuses_earned = [];

        // Base points calculation based on score percentage
        $score_percentage = $quiz_results['score_percentage'];
        $low_threshold = $this->_get_config('points_score_threshold_low', 50.0);
        $high_threshold = $this->_get_config('points_score_threshold_high', 90.0);
        
        if ($score_percentage < $low_threshold) {
            $base_points = $this->_get_config('points_low_score', 100);
            $earning_rule = 'low_score';
        } elseif ($score_percentage >= $high_threshold) {
            $base_points = $this->_get_config('points_high_score', 500);
            $earning_rule = 'high_score';
        } else {
            $base_points = $this->_get_config('points_medium_score', 300);
            $earning_rule = 'medium_score';
        }

        // Bonus: No skipped questions
        if ($quiz_results['skipped_questions'] === 0) {
            $bonus_points += $this->_get_config('points_bonus_no_skip', 100);
            $bonuses_earned[] = 'no_skip';
        }

        // Bonus: Hard level quiz
        if (strtolower($quiz->level) === 'hard') {
            $bonus_points += $this->_get_config('points_bonus_hard_level', 50);
            $bonuses_earned[] = 'hard_level';
        }

        // Future: Perfect score + high speed bonus
        // if ($score_percentage >= 100 && $this->_is_high_speed($quiz_results)) {
        //     $bonus_points += $this->_get_config('points_bonus_perfect_speed', 50);
        //     $bonuses_earned[] = 'perfect_speed';
        // }

        $total_points = $base_points + $bonus_points;

        return [
            'base_points' => $base_points,
            'bonus_points' => $bonus_points,
            'total_points' => $total_points,
            'earning_rule' => $earning_rule,
            'bonuses_earned' => $bonuses_earned,
            'score_percentage' => $score_percentage
        ];
    }

    /**
     * Check weekly point earning limit
     */
    private function _check_weekly_limit($user_id)
    {
        $user_points = $this->CI->user_point_model->get_user_points($user_id);
        $default_limit = $this->_get_config('points_weekly_limit_default', 1500);
        
        if (!$user_points) {
            // Create user points record if doesn't exist
            $this->CI->user_point_model->initialize_user_points($user_id);
            return [
                'can_earn' => true,
                'current_points' => 0,
                'limit' => $default_limit
            ];
        }

        // Check if week needs reset
        $current_monday = $this->_get_current_monday();
        if ($user_points->week_start_date !== $current_monday) {
            // Week has changed - the award_points method will handle the reset
            $user_points->current_week_points = 0; // Update local copy for calculation
        }

        return [
            'can_earn' => $user_points->current_week_points < $user_points->weekly_limit,
            'current_points' => $user_points->current_week_points,
            'limit' => $user_points->weekly_limit
        ];
    }

    /**
     * Award points to user
     */
    private function _award_points($user_id, $quiz_id, $point_calculation, $quiz_results, $quiz)
    {
        try {
            // Prepare transaction data
            $transaction_data = [
                'transaction_type' => 'earned',  // Changed from 'quiz_completion' to 'earned'
                'quiz_id' => $quiz_id,
                'quiz_score_percentage' => $quiz_results['score_percentage'],
                'quiz_total_questions' => $quiz_results['total_questions'],
                'quiz_skipped_questions' => $quiz_results['skipped_questions'],
                'quiz_level' => $quiz->level,
                'earning_rule_applied' => $point_calculation['earning_rule'],
                'bonus_type' => !empty($point_calculation['bonuses_earned']) ? implode(',', $point_calculation['bonuses_earned']) : null
            ];

            // Award points using the model
            $result = $this->CI->user_point_model->award_points(
                $user_id, 
                $point_calculation['total_points'], 
                $transaction_data
            );

            if ($result['success']) {
                return [
                    'success' => true,
                    'transaction_id' => $result['transaction_id'],
                    'points_awarded' => $result['points_awarded'],
                    'new_week_points' => $result['new_week_points'],
                    'user_points' => $result['user_points']  // Include user_points data
                ];
            } else {
                return $result; // Return the error from the model
            }

        } catch (Exception $e) {
            log_message('error', 'Point Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to award points: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update weekly summary statistics
     * DISABLED: Method not implemented in User_point_model
     */
    /*
    private function _update_weekly_summary($user_id, $point_calculation, $quiz_results)
    {
        $week_start = $this->_get_current_monday();
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

        $summary_data = [
            'user_id' => $user_id,
            'week_start_date' => $week_start,
            'week_end_date' => $week_end,
            'total_points_earned' => $point_calculation['total_points'],
            'total_quizzes_completed' => 1,
            'avg_quiz_score' => $quiz_results['score_percentage'],
            'bonuses_earned' => $point_calculation['bonus_points'],
            'no_skip_bonuses' => in_array('no_skip', $point_calculation['bonuses_earned']) ? 1 : 0,
            'hard_level_bonuses' => in_array('hard_level', $point_calculation['bonuses_earned']) ? 1 : 0,
            'perfect_score_bonuses' => in_array('perfect_speed', $point_calculation['bonuses_earned']) ? 1 : 0
        ];

        $this->CI->user_point_model->update_weekly_summary($summary_data);
    }
    */

    /**
     * Calculate AI tutor minutes from available points
     */
    private function _calculate_ai_tutor_minutes($user_points)
    {
        $ai_tutor_rate = $this->_get_config('points_ai_tutor_rate', 50);
        return [
            'available_minutes' => round($user_points['available_points'] / $ai_tutor_rate, 2),
            'points_per_minute' => $ai_tutor_rate
        ];
    }

    /**
     * Check if admin override is enabled for user
     */
    private function _is_admin_override_enabled($user_id)
    {
        // Check global config setting
        $global_override = $this->_get_config('points_admin_override_enabled', false);
        
        // Check user-specific override
        $user_points = $this->CI->user_point_model->get_user_points($user_id);
        $user_override = $user_points ? $user_points->admin_override_enabled : false;

        return $global_override || $user_override;
    }

    /**
     * Get current Monday date
     */
    private function _get_current_monday()
    {
        return date('Y-m-d', strtotime('monday this week'));
    }

    /**
     * Get success result structure
     */
    private function _get_success_result($point_calculation, $award_result, $ai_minutes)
    {
        return [
            'success' => true,
            'points_awarded' => $point_calculation['total_points'],
            'base_points' => $point_calculation['base_points'],
            'bonus_points' => $point_calculation['bonus_points'],
            'bonuses_earned' => $point_calculation['bonuses_earned'],
            'earning_rule' => $point_calculation['earning_rule'],
            'score_percentage' => $point_calculation['score_percentage'],
            'transaction_id' => $award_result['transaction_id'],
            'user_points' => $award_result['user_points'],
            'ai_tutor_minutes' => $ai_minutes,
            'awarded_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get error result structure
     */
    private function _get_error_result($reason)
    {
        return [
            'success' => false,
            'error' => $reason,
            'points_awarded' => 0
        ];
    }
}