<?php
/**
 * User Points API Controller
 * 
 * Provides REST API endpoints for managing user points, transactions,
 * and AI tutor minutes in the WiziAI platform
 * 
 * @author WiziAI Development Team
 * @version 1.0
 * @date September 20, 2025
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_points extends API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_point/user_point_model');
        $this->load->library('User_point_service');
    }

    // ==================== USER ENDPOINTS ====================

    /**
     * Get user point summary
     * GET /api/user_points/summary/{user_id}
     * 
     * Returns current points, weekly status, AI tutor minutes, etc.
     */
    public function summary_get($user_id = null)
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // Get user_id from URL parameter or request
            if (empty($user_id)) {
                $user_id = $this->input->get('user_id');
            }

            if (empty($user_id)) {
                $response = $this->get_failed_response(null, "Missing required parameter: user_id");
                $this->set_output($response);
                return;
            }

            // Get comprehensive point statistics
            $point_stats = $this->user_point_model->get_user_point_stats($user_id);
            
            if (!$point_stats) {
                // Initialize user points if not exists
                $this->user_point_model->initialize_user_points($user_id);
                $point_stats = $this->user_point_model->get_user_point_stats($user_id);
            }

            $response_data = [
                'user_id' => (int)$user_id,
                'points' => [
                    'total_points' => (int)$point_stats['user_points']->total_points,
                    'available_points' => (int)$point_stats['user_points']->available_points,
                    'spent_points' => (int)$point_stats['user_points']->spent_points,
                    'current_week_points' => (int)$point_stats['user_points']->current_week_points,
                    'weekly_limit' => (int)$point_stats['user_points']->weekly_limit,
                    'weekly_limit_percentage' => $point_stats['weekly_limit_percentage']
                ],
                'ai_tutor' => [
                    'minutes_available' => $point_stats['ai_tutor_minutes_available'],
                    'minutes_used' => (float)$point_stats['user_points']->ai_tutor_minutes_used,
                    'points_per_minute' => (int)$this->config->item('points_ai_tutor_rate')
                ],
                'weekly_activity' => [
                    'week_start_date' => $point_stats['user_points']->week_start_date,
                    'quizzes_completed' => (int)$point_stats['weekly_stats']->quizzes_completed_this_week,
                    'points_earned' => (int)$point_stats['weekly_stats']->points_earned_this_week,
                    'points_spent' => (int)$point_stats['weekly_stats']->points_spent_this_week
                ],
                'achievements' => [
                    'total_quizzes_with_points' => (int)$point_stats['all_time_stats']->total_quizzes_with_points,
                    'average_quiz_score' => round((float)$point_stats['all_time_stats']->avg_quiz_score, 2),
                    'no_skip_bonuses' => (int)$point_stats['all_time_stats']->total_no_skip_bonuses,
                    'hard_level_bonuses' => (int)$point_stats['all_time_stats']->total_hard_level_bonuses
                ],
                'status' => [
                    'is_active' => (bool)$point_stats['user_points']->is_active,
                    'admin_override_enabled' => (bool)$point_stats['user_points']->admin_override_enabled,
                    'last_quiz_completion' => $point_stats['user_points']->last_quiz_completion
                ]
            ];

            $response = $this->get_success_response($response_data, "User point summary retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'User Points API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error retrieving user points: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get user point transaction history
     * GET /api/user_points/transactions/{user_id}
     * 
     * Parameters: limit, offset, transaction_type
     */
    public function transactions_get($user_id = null)
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // Get user_id from URL parameter or request
            if (empty($user_id)) {
                $user_id = $this->input->get('user_id');
            }

            if (empty($user_id)) {
                $response = $this->get_failed_response(null, "Missing required parameter: user_id");
                $this->set_output($response);
                return;
            }

            // Get optional parameters
            $limit = $this->input->get('limit') ?: 50;
            $offset = $this->input->get('offset') ?: 0;
            $transaction_type = $this->input->get('transaction_type');

            // Validate limit
            if ($limit > 100) {
                $limit = 100; // Maximum 100 records per request
            }

            // Get transactions
            $transactions = $this->user_point_model->get_user_point_transactions(
                $user_id, 
                $limit, 
                $offset, 
                $transaction_type
            );

            // Format transactions for response
            $formatted_transactions = [];
            foreach ($transactions as $transaction) {
                $formatted_transactions[] = [
                    'id' => (int)$transaction->id,
                    'transaction_type' => $transaction->transaction_type,
                    'points_amount' => (int)$transaction->points_amount,
                    'quiz_id' => $transaction->quiz_id ? (int)$transaction->quiz_id : null,
                    'quiz_score_percentage' => $transaction->quiz_score_percentage ? (float)$transaction->quiz_score_percentage : null,
                    'quiz_total_questions' => $transaction->quiz_total_questions ? (int)$transaction->quiz_total_questions : null,
                    'quiz_skipped_questions' => $transaction->quiz_skipped_questions ? (int)$transaction->quiz_skipped_questions : null,
                    'quiz_level' => $transaction->quiz_level,
                    'earning_rule_applied' => $transaction->earning_rule_applied,
                    'bonus_type' => $transaction->bonus_type,
                    'ai_tutor_minutes' => $transaction->ai_tutor_minutes ? (float)$transaction->ai_tutor_minutes : null,
                    'week_start_date' => $transaction->week_start_date,
                    'total_points_after' => (int)$transaction->total_points_after,
                    'created_at' => $transaction->created_at
                ];
            }

            $response_data = [
                'user_id' => (int)$user_id,
                'transactions' => $formatted_transactions,
                'pagination' => [
                    'limit' => (int)$limit,
                    'offset' => (int)$offset,
                    'total_returned' => count($formatted_transactions),
                    'has_more' => count($formatted_transactions) === (int)$limit
                ],
                'filters' => [
                    'transaction_type' => $transaction_type
                ]
            ];

            $response = $this->get_success_response($response_data, "Point transactions retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'User Points API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error retrieving transactions: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get points leaderboard
     * GET /api/user_points/leaderboard
     * 
     * Parameters: period (weekly/monthly/all_time), limit
     */
    public function leaderboard_get()
    {
        try {
            $period = $this->input->get('period') ?: 'all_time';
            $limit = $this->input->get('limit') ?: 50;

            // Validate parameters
            $valid_periods = ['weekly', 'monthly', 'all_time'];
            if (!in_array($period, $valid_periods)) {
                $response = $this->get_failed_response(null, "Invalid period. Must be: weekly, monthly, or all_time");
                $this->set_output($response);
                return;
            }

            if ($limit > 100) {
                $limit = 100;
            }

            // Get leaderboard data
            $leaderboard = $this->user_point_model->get_points_leaderboard($limit, $period);

            $response_data = [
                'leaderboard' => $leaderboard,
                'period' => $period,
                'limit' => (int)$limit,
                'total_users' => count($leaderboard),
                'generated_at' => date('Y-m-d H:i:s')
            ];

            $response = $this->get_success_response($response_data, "Points leaderboard retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'User Points API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error retrieving leaderboard: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    // ==================== AI TUTOR ENDPOINTS ====================

    /**
     * Start AI tutor session
     * POST /api/user_points/ai_tutor/start
     * 
     * Deducts points and starts AI tutor session
     */
    public function ai_tutor_start_post()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $request = $this->get_request();

            // Validate required fields
            if (empty($request['user_id']) || empty($request['minutes_requested'])) {
                $response = $this->get_failed_response(null, "Missing required fields: user_id, minutes_requested");
                $this->set_output($response);
                return;
            }

            $user_id = $request['user_id'];
            $minutes_requested = (float)$request['minutes_requested'];
            $ai_tutor_rate = $this->config->item('points_ai_tutor_rate');
            $points_needed = $minutes_requested * $ai_tutor_rate;

            // Validate minutes requested
            if ($minutes_requested <= 0 || $minutes_requested > 60) {
                $response = $this->get_failed_response(null, "Minutes requested must be between 0.1 and 60");
                $this->set_output($response);
                return;
            }

            // Start AI tutor session
            $session_data = [
                'ai_tutor_minutes' => $minutes_requested,
                'points_needed' => $points_needed
            ];

            $spend_result = $this->user_point_model->spend_points(
                $user_id, 
                $points_needed, 
                'ai_tutor', 
                $session_data
            );

            if ($spend_result['success']) {
                // Create AI tutor session record using CDatabase
                $pdo = CDatabase::getPdo();
                $session_record = [
                    'user_id' => $user_id,
                    'minutes_allocated' => $minutes_requested,
                    'points_deducted' => $points_needed,
                    'session_status' => 'active',
                    'ip_address' => $this->input->ip_address(),
                    'user_agent' => $this->input->user_agent()
                ];

                $stmt = $pdo->prepare("
                    INSERT INTO ai_tutor_sessions 
                    (user_id, minutes_allocated, points_deducted, session_status, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $session_record['user_id'],
                    $session_record['minutes_allocated'],
                    $session_record['points_deducted'],
                    $session_record['session_status'],
                    $session_record['ip_address'],
                    $session_record['user_agent']
                ]);
                $session_id = $pdo->lastInsertId();

                $response_data = [
                    'session_id' => $session_id,
                    'user_id' => (int)$user_id,
                    'minutes_allocated' => $minutes_requested,
                    'points_deducted' => $points_needed,
                    'remaining_points' => $spend_result['remaining_points'],
                    'ai_tutor_minutes_remaining' => $spend_result['ai_tutor_minutes_available'],
                    'session_started_at' => date('Y-m-d H:i:s'),
                    'transaction_id' => $spend_result['transaction_id']
                ];

                $response = $this->get_success_response($response_data, "AI tutor session started successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(null, "Failed to start AI tutor session: " . $spend_result['error']);
                $this->set_output($response);
            }

        } catch (Exception $e) {
            log_message('error', 'AI Tutor API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error starting AI tutor session: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * End AI tutor session
     * POST /api/user_points/ai_tutor/end
     * 
     * Records actual minutes used and updates session
     */
    public function ai_tutor_end_post()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $request = $this->get_request();

            // Validate required fields
            if (empty($request['session_id']) || !isset($request['minutes_used'])) {
                $response = $this->get_failed_response(null, "Missing required fields: session_id, minutes_used");
                $this->set_output($response);
                return;
            }

            $session_id = $request['session_id'];
            $minutes_used = (float)$request['minutes_used'];
            $conversation_messages = isset($request['conversation_messages']) ? (int)$request['conversation_messages'] : 0;

            // Get session record using CDatabase
            $pdo = CDatabase::getPdo();
            $stmt = $pdo->prepare("
                SELECT * FROM ai_tutor_sessions 
                WHERE id = ? AND session_status = 'active'
            ");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$session) {
                $response = $this->get_failed_response(null, "Active AI tutor session not found");
                $this->set_output($response);
                return;
            }

            // Validate minutes used
            if ($minutes_used < 0 || $minutes_used > $session->minutes_allocated) {
                $minutes_used = $session->minutes_allocated; // Use allocated minutes if invalid
            }

            // Update session record
            $update_data = [
                'session_end' => date('Y-m-d H:i:s'),
                'minutes_used' => $minutes_used,
                'conversation_messages' => $conversation_messages,
                'session_status' => 'completed'
            ];

            $stmt = $pdo->prepare("
                UPDATE ai_tutor_sessions 
                SET session_end = ?, minutes_used = ?, conversation_messages = ?, session_status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $update_data['session_end'],
                $update_data['minutes_used'],
                $update_data['conversation_messages'],
                $update_data['session_status'],
                $session_id
            ]);

            $response_data = [
                'session_id' => (int)$session_id,
                'user_id' => (int)$session->user_id,
                'minutes_allocated' => (float)$session->minutes_allocated,
                'minutes_used' => $minutes_used,
                'points_deducted' => (int)$session->points_deducted,
                'conversation_messages' => $conversation_messages,
                'session_ended_at' => date('Y-m-d H:i:s'),
                'session_duration_actual' => $minutes_used . ' minutes'
            ];

            $response = $this->get_success_response($response_data, "AI tutor session ended successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'AI Tutor API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error ending AI tutor session: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    // ==================== ADMIN ENDPOINTS ====================

    /**
     * Admin: Get comprehensive user point report
     * GET /api/user_points/admin/user_report/{user_id}
     * 
     * Requires admin authentication
     */
    public function admin_user_report_get($user_id = null)
    {
        try {
            // TODO: Add admin authentication check
            // if (!$this->_is_admin()) {
            //     $response = $this->get_failed_response(null, "Admin access required");
            //     $this->set_output($response);
            //     return;
            // }

            if (empty($user_id)) {
                $response = $this->get_failed_response(null, "Missing required parameter: user_id");
                $this->set_output($response);
                return;
            }

            // Get comprehensive point data
            $point_stats = $this->user_point_model->get_user_point_stats($user_id);
            
            if (!$point_stats) {
                $response = $this->get_failed_response(null, "User not found or no point data available");
                $this->set_output($response);
                return;
            }

            // Get recent transactions
            $recent_transactions = $this->user_point_model->get_user_point_transactions($user_id, 20, 0);

            // Get AI tutor sessions using CDatabase
            $pdo = CDatabase::getPdo();
            $stmt = $pdo->prepare("
                SELECT * FROM ai_tutor_sessions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $ai_sessions = $stmt->fetchAll(PDO::FETCH_OBJ);

            $response_data = [
                'user_id' => (int)$user_id,
                'summary' => $point_stats,
                'recent_transactions' => $recent_transactions,
                'ai_tutor_sessions' => $ai_sessions,
                'system_config' => [
                    'weekly_limit_default' => $this->config->item('points_weekly_limit_default'),
                    'ai_tutor_rate' => $this->config->item('points_ai_tutor_rate'),
                    'admin_override_enabled' => $this->config->item('points_admin_override_enabled')
                ],
                'generated_at' => date('Y-m-d H:i:s')
            ];

            $response = $this->get_success_response($response_data, "Admin user report generated successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'Admin Points API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error generating admin report: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Admin: Manually adjust user points
     * POST /api/user_points/admin/adjust
     * 
     * Allows admin to add/subtract points with reason
     */
    public function admin_adjust_post()
    {
        try {
            // TODO: Add admin authentication check
            // if (!$this->_is_admin()) {
            //     $response = $this->get_failed_response(null, "Admin access required");
            //     $this->set_output($response);
            //     return;
            // }

            $request = $this->get_request();

            // Validate required fields
            if (empty($request['user_id']) || !isset($request['points_adjustment']) || empty($request['reason'])) {
                $response = $this->get_failed_response(null, "Missing required fields: user_id, points_adjustment, reason");
                $this->set_output($response);
                return;
            }

            $user_id = $request['user_id'];
            $points_adjustment = (int)$request['points_adjustment'];
            $reason = $request['reason'];
            $admin_user_id = $request['admin_user_id'] ?? 1; // TODO: Get from authentication

            // Validate adjustment amount
            if ($points_adjustment == 0) {
                $response = $this->get_failed_response(null, "Points adjustment cannot be zero");
                $this->set_output($response);
                return;
            }

            if (abs($points_adjustment) > 10000) {
                $response = $this->get_failed_response(null, "Points adjustment cannot exceed 10,000 points");
                $this->set_output($response);
                return;
            }

            // Apply adjustment
            $adjust_result = $this->user_point_model->admin_adjust_points(
                $user_id, 
                $points_adjustment, 
                $admin_user_id, 
                $reason
            );

            if ($adjust_result['success']) {
                $response_data = [
                    'user_id' => (int)$user_id,
                    'points_adjusted' => $adjust_result['points_adjusted'],
                    'new_total_points' => $adjust_result['new_total_points'],
                    'new_available_points' => $adjust_result['new_available_points'],
                    'reason' => $reason,
                    'admin_user_id' => (int)$admin_user_id,
                    'transaction_id' => $adjust_result['transaction_id'],
                    'adjusted_at' => date('Y-m-d H:i:s')
                ];

                $response = $this->get_success_response($response_data, "Points adjusted successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(null, "Failed to adjust points: " . $adjust_result['error']);
                $this->set_output($response);
            }

        } catch (Exception $e) {
            log_message('error', 'Admin Points API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error adjusting points: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Admin: Get system-wide point statistics
     * GET /api/user_points/admin/system_stats
     */
    public function admin_system_stats_get()
    {
        try {
            // TODO: Add admin authentication check

            // Get system-wide statistics using CDatabase
            $pdo = CDatabase::getPdo();
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_users_with_points,
                    SUM(total_points) as total_points_awarded,
                    SUM(available_points) as total_points_available,
                    SUM(spent_points) as total_points_spent,
                    AVG(total_points) as avg_points_per_user,
                    SUM(current_week_points) as total_weekly_points,
                    SUM(ai_tutor_minutes_used) as total_ai_minutes_used
                FROM user_points
            ");
            $stmt->execute();
            $overall_stats = $stmt->fetch(PDO::FETCH_OBJ);

            // Get weekly statistics
            $current_monday = date('Y-m-d', strtotime('monday this week'));
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT user_id) as active_users_this_week,
                    SUM(CASE WHEN points_amount > 0 THEN points_amount ELSE 0 END) as points_earned_this_week,
                    SUM(CASE WHEN points_amount < 0 THEN ABS(points_amount) ELSE 0 END) as points_spent_this_week,
                    COUNT(CASE WHEN quiz_id IS NOT NULL THEN 1 END) as quizzes_with_points_this_week
                FROM user_point_transactions 
                WHERE week_start_date = ?
            ");
            $stmt->execute([$current_monday]);
            $weekly_stats = $stmt->fetch(PDO::FETCH_OBJ);

            // Get top earners this week
            $stmt = $pdo->prepare("
                SELECT up.user_id, u.name as user_name, up.current_week_points
                FROM user_points up
                JOIN user u ON u.id = up.user_id
                WHERE up.current_week_points > 0
                ORDER BY up.current_week_points DESC
                LIMIT 10
            ");
            $stmt->execute();
            $top_earners = $stmt->fetchAll(PDO::FETCH_OBJ);

            $response_data = [
                'overall_statistics' => $overall_stats,
                'weekly_statistics' => $weekly_stats,
                'top_earners_this_week' => $top_earners,
                'system_configuration' => [
                    'points_weekly_limit_default' => $this->config->item('points_weekly_limit_default'),
                    'points_ai_tutor_rate' => $this->config->item('points_ai_tutor_rate'),
                    'points_system_enabled' => $this->config->item('points_system_enabled'),
                    'points_admin_override_enabled' => $this->config->item('points_admin_override_enabled')
                ],
                'generated_at' => date('Y-m-d H:i:s'),
                'week_start_date' => $current_monday
            ];

            $response = $this->get_success_response($response_data, "System statistics retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'Admin Points API Error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, "Error retrieving system statistics: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Check if current user is admin
     * TODO: Implement proper admin authentication
     */
    private function _is_admin()
    {
        // TODO: Implement admin authentication logic
        // This could check JWT token, session, or database role
        return true; // Placeholder
    }
}