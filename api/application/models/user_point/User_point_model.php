<?php
/**
 * User Point Model
 * 
 * Handles all database operations for the user points system
 * using the CDatabase::getPdo() pattern consistent with the codebase
 * 
 * @author WiziAI Development Team
 * @version 1.0
 * @date September 20, 2025
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_point_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        // Points config is auto-loaded via autoload.php
    }

    /**
     * Get comprehensive user point statistics
     * Returns user_points record, weekly stats, all-time stats, and calculated values
     */
    public function get_user_point_stats($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Get or create user_points record
            $stmt = $pdo->prepare("
                SELECT * FROM user_points 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $user_points = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$user_points) {
                $this->initialize_user_points($user_id);
                $stmt->execute([$user_id]);
                $user_points = $stmt->fetch(PDO::FETCH_OBJ);
            }
            
            // Get weekly statistics
            $current_monday = date('Y-m-d', strtotime('monday this week'));
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN (transaction_type = 'earned' OR transaction_type = 'quiz_completion') AND quiz_id IS NOT NULL THEN 1 END) as quizzes_completed_this_week,
                    SUM(CASE WHEN points_amount > 0 THEN points_amount ELSE 0 END) as points_earned_this_week,
                    SUM(CASE WHEN points_amount < 0 THEN ABS(points_amount) ELSE 0 END) as points_spent_this_week
                FROM user_point_transactions 
                WHERE user_id = ? AND week_start_date = ?
            ");
            $stmt->execute([$user_id, $current_monday]);
            $weekly_stats = $stmt->fetch(PDO::FETCH_OBJ);
            
            // Get all-time statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN (transaction_type = 'earned' OR transaction_type = 'quiz_completion') AND quiz_id IS NOT NULL THEN 1 END) as total_quizzes_with_points,
                    AVG(CASE WHEN quiz_score_percentage IS NOT NULL THEN quiz_score_percentage END) as avg_quiz_score,
                    COUNT(CASE WHEN bonus_type IS NOT NULL AND (bonus_type = 'no_skip' OR bonus_type LIKE '%no_skip%') THEN 1 END) as total_no_skip_bonuses,
                    COUNT(CASE WHEN bonus_type IS NOT NULL AND (bonus_type = 'hard_level' OR bonus_type LIKE '%hard_level%') THEN 1 END) as total_hard_level_bonuses
                FROM user_point_transactions 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $all_time_stats = $stmt->fetch(PDO::FETCH_OBJ);
            
            // Calculate AI tutor minutes available
            $ai_tutor_rate = $this->config->item('points_ai_tutor_rate') ?: 50;
            $ai_tutor_minutes_available = floor($user_points->available_points / $ai_tutor_rate);
            
            // Calculate weekly limit percentage
            $weekly_limit_percentage = $user_points->weekly_limit > 0 
                ? round(($user_points->current_week_points / $user_points->weekly_limit) * 100, 1) 
                : 0;
            
            return [
                'user_points' => $user_points,
                'weekly_stats' => $weekly_stats,
                'all_time_stats' => $all_time_stats,
                'ai_tutor_minutes_available' => $ai_tutor_minutes_available,
                'weekly_limit_percentage' => $weekly_limit_percentage
            ];
            
        } catch (Exception $e) {
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize user points record if it doesn't exist
     */
    public function initialize_user_points($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $current_monday = date('Y-m-d', strtotime('monday this week'));
            $weekly_limit = $this->config->item('points_weekly_limit_default') ?: 1500;
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO user_points 
                (user_id, total_points, available_points, spent_points, current_week_points, 
                 weekly_limit, week_start_date, is_active, admin_override_enabled) 
                VALUES (?, 0, 0, 0, 0, ?, ?, 1, 0)
            ");
            $stmt->execute([$user_id, $weekly_limit, $current_monday]);
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user point transactions with pagination and filtering
     */
    public function get_user_point_transactions($user_id, $limit = 50, $offset = 0, $transaction_type = null)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Sanitize limit and offset as integers
            $limit = max(1, min(1000, (int)$limit)); // Between 1 and 1000
            $offset = max(0, (int)$offset);
            
            $sql = "
                SELECT * FROM user_point_transactions 
                WHERE user_id = ?
            ";
            $params = [$user_id];
            
            if ($transaction_type) {
                $sql .= " AND transaction_type = ?";
                $params[] = $transaction_type;
            }
            
            // Use MySQL LIMIT syntax consistent with other models in the codebase
            $sql .= " ORDER BY created_at DESC LIMIT $offset, $limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a point transaction record
     */
    public function create_point_transaction($transaction_data)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $current_monday = date('Y-m-d', strtotime('monday this week'));
            
            // Get current points for before/after tracking
            $stmt = $pdo->prepare("SELECT total_points, current_week_points FROM user_points WHERE user_id = ?");
            $stmt->execute([$transaction_data['user_id']]);
            $current_points = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_points_before = $current_points ? $current_points['total_points'] : 0;
            $weekly_points_before = $current_points ? $current_points['current_week_points'] : 0;
            
            // Calculate after values
            $points_amount = $transaction_data['points_amount'];
            $total_points_after = $total_points_before + $points_amount;
            $weekly_points_after = $weekly_points_before + ($points_amount > 0 ? $points_amount : 0); // Only add positive points to weekly total
            
            // Basic transaction data that should always exist
            $base_data = [
                'user_id' => $transaction_data['user_id'],
                'transaction_type' => $transaction_data['transaction_type'],
                'points_amount' => $points_amount,
                'week_start_date' => $current_monday,
                'weekly_points_before' => $weekly_points_before,
                'weekly_points_after' => $weekly_points_after,
                'total_points_before' => $total_points_before,
                'total_points_after' => $total_points_after,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Optional fields - only add if provided
            $optional_fields = [
                'quiz_id', 'quiz_score_percentage', 'quiz_total_questions', 
                'quiz_skipped_questions', 'quiz_level', 'earning_rule_applied',
                'bonus_type', 'ai_tutor_minutes', 'admin_user_id', 'admin_reason'
            ];
            
            foreach ($optional_fields as $field) {
                if (isset($transaction_data[$field]) && $transaction_data[$field] !== null) {
                    $base_data[$field] = $transaction_data[$field];
                }
            }
            
            // Build dynamic INSERT query
            $columns = implode(', ', array_keys($base_data));
            $placeholders = ':' . implode(', :', array_keys($base_data));
            
            $sql = "INSERT INTO user_point_transactions ({$columns}) VALUES ({$placeholders})";
            log_message('debug', 'Transaction SQL: ' . $sql);
            log_message('debug', 'Transaction data: ' . json_encode($base_data));
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($base_data);
            
            if (!$result) {
                $error_info = $stmt->errorInfo();
                log_message('error', 'Transaction insert failed: ' . json_encode($error_info));
                return false;
            }
            
            $last_id = $pdo->lastInsertId();
            log_message('debug', 'Transaction inserted with ID: ' . $last_id);
            
            return $last_id;
            
        } catch (Exception $e) {
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Award points to user
     */
    public function award_points($user_id, $points_amount, $transaction_data = [])
    {
        try {
            $pdo = CDatabase::getPdo();
            $pdo->beginTransaction();
            
            // Initialize user points if needed
            $this->initialize_user_points($user_id);
            
            // Check weekly limit
            $current_monday = date('Y-m-d', strtotime('monday this week'));
            $stmt = $pdo->prepare("
                SELECT current_week_points, weekly_limit, admin_override_enabled 
                FROM user_points 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $user_point_info = $stmt->fetch(PDO::FETCH_OBJ);
            
            $new_week_points = $user_point_info->current_week_points + $points_amount;
            
            // Check weekly limit (unless admin override)
            if (!$user_point_info->admin_override_enabled && $new_week_points > $user_point_info->weekly_limit) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Weekly points limit exceeded',
                    'weekly_limit' => $user_point_info->weekly_limit,
                    'current_week_points' => $user_point_info->current_week_points
                ];
            }
            
            // Reset weekly points if new week
            $stmt = $pdo->prepare("SELECT week_start_date FROM user_points WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stored_week_start = $stmt->fetchColumn();
            
            if ($stored_week_start !== $current_monday) {
                // New week - reset weekly points
                $stmt = $pdo->prepare("
                    UPDATE user_points 
                    SET current_week_points = ?, week_start_date = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$points_amount, $current_monday, $user_id]);
                $new_week_points = $points_amount;
            } else {
                // Same week - add to existing
                $stmt = $pdo->prepare("
                    UPDATE user_points 
                    SET current_week_points = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$new_week_points, $user_id]);
            }
            
            // Update total and available points
            $stmt = $pdo->prepare("
                UPDATE user_points 
                SET total_points = total_points + ?, 
                    available_points = available_points + ?,
                    last_quiz_completion = CASE WHEN ? = 'earned' THEN NOW() ELSE last_quiz_completion END,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $points_amount, 
                $points_amount, 
                $transaction_data['transaction_type'] ?? 'earned',
                $user_id
            ]);
            
            // Create transaction record
            $transaction_data['user_id'] = $user_id;
            $transaction_data['points_amount'] = $points_amount;
            
            log_message('debug', 'About to create point transaction with data: ' . json_encode($transaction_data));
            $transaction_id = $this->create_point_transaction($transaction_data);
            
            if ($transaction_id === false) {
                log_message('error', 'Failed to create point transaction for user ' . $user_id);
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to create transaction record'
                ];
            }
            
            log_message('debug', 'Point transaction created successfully with ID: ' . $transaction_id);
            
            // Get updated user points data
            $stmt = $pdo->prepare("
                SELECT total_points, available_points, current_week_points, weekly_limit
                FROM user_points 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $user_points = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'points_awarded' => $points_amount,
                'new_week_points' => $new_week_points,
                'transaction_id' => $transaction_id,
                'user_points' => $user_points
            ];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }

    /**
     * Spend points (for AI tutor sessions)
     */
    public function spend_points($user_id, $points_amount, $transaction_type = 'spent', $transaction_data = [])
    {
        try {
            $pdo = CDatabase::getPdo();
            $pdo->beginTransaction();
            
            // Check available points
            $stmt = $pdo->prepare("SELECT available_points FROM user_points WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $available_points = $stmt->fetchColumn();
            
            if ($available_points < $points_amount) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Insufficient points',
                    'available_points' => $available_points,
                    'required_points' => $points_amount
                ];
            }
            
            // Update user points
            $stmt = $pdo->prepare("
                UPDATE user_points 
                SET available_points = available_points - ?,
                    spent_points = spent_points + ?,
                    ai_tutor_minutes_used = ai_tutor_minutes_used + ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            
            $ai_minutes = $transaction_data['ai_tutor_minutes'] ?? 0;
            $stmt->execute([$points_amount, $points_amount, $ai_minutes, $user_id]);
            
            // Create transaction record
            $transaction_data['user_id'] = $user_id;
            $transaction_data['transaction_type'] = $transaction_type;
            $transaction_data['points_amount'] = -$points_amount; // Negative for spending
            $transaction_id = $this->create_point_transaction($transaction_data);
            
            // Calculate remaining values
            $ai_tutor_rate = $this->config->item('points_ai_tutor_rate') ?: 50;
            $remaining_points = $available_points - $points_amount;
            $ai_tutor_minutes_available = floor($remaining_points / $ai_tutor_rate);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'points_spent' => $points_amount,
                'remaining_points' => $remaining_points,
                'ai_tutor_minutes_available' => $ai_tutor_minutes_available,
                'transaction_id' => $transaction_id
            ];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }

    /**
     * Admin point adjustment
     */
    public function admin_adjust_points($user_id, $points_adjustment, $admin_user_id, $reason)
    {
        try {
            $pdo = CDatabase::getPdo();
            $pdo->beginTransaction();
            
            // Initialize user points if needed
            $this->initialize_user_points($user_id);
            
            // Get current points
            $stmt = $pdo->prepare("SELECT total_points, available_points FROM user_points WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_points = $stmt->fetch(PDO::FETCH_OBJ);
            
            // Calculate new values
            $new_total = $current_points->total_points + $points_adjustment;
            $new_available = $current_points->available_points + $points_adjustment;
            
            // Ensure points don't go negative
            if ($new_total < 0) $new_total = 0;
            if ($new_available < 0) $new_available = 0;
            
            // Update user points
            $stmt = $pdo->prepare("
                UPDATE user_points 
                SET total_points = ?, 
                    available_points = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$new_total, $new_available, $user_id]);
            
            // Create transaction record
            $transaction_data = [
                'user_id' => $user_id,
                'transaction_type' => 'admin_adjustment',
                'points_amount' => $points_adjustment,
                'admin_user_id' => $admin_user_id,
                'admin_reason' => $reason
            ];
            $transaction_id = $this->create_point_transaction($transaction_data);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'points_adjusted' => $points_adjustment,
                'new_total_points' => $new_total,
                'new_available_points' => $new_available,
                'transaction_id' => $transaction_id
            ];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }

    /**
     * Get points leaderboard
     */
    public function get_points_leaderboard($limit = 50, $period = 'all_time')
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Sanitize limit as integer
            $limit = max(1, min(1000, (int)$limit));
            
            if ($period === 'weekly') {
                $current_monday = date('Y-m-d', strtotime('monday this week'));
                $sql = "
                    SELECT 
                        up.user_id,
                        u.display_name as user_name,
                        up.total_points,
                        up.current_week_points
                    FROM user_points up
                    JOIN user u ON u.id = up.user_id
                    WHERE up.week_start_date = ? AND up.current_week_points > 0
                    ORDER BY up.current_week_points DESC
                    LIMIT $limit
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$current_monday]);
            } elseif ($period === 'monthly') {
                $current_month_start = date('Y-m-01');
                $sql = "
                    SELECT 
                        up.user_id,
                        u.display_name as user_name,
                        up.total_points,
                        COALESCE(SUM(CASE WHEN upt.created_at >= ? AND upt.points_amount > 0 THEN upt.points_amount END), 0) as monthly_points
                    FROM user_points up
                    JOIN user u ON u.id = up.user_id
                    LEFT JOIN user_point_transactions upt ON upt.user_id = up.user_id
                    GROUP BY up.user_id
                    HAVING monthly_points > 0
                    ORDER BY monthly_points DESC
                    LIMIT $limit
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$current_month_start]);
            } else {
                $sql = "
                    SELECT 
                        up.user_id,
                        u.display_name as user_name,
                        up.total_points
                    FROM user_points up
                    JOIN user u ON u.id = up.user_id
                    WHERE up.total_points > 0
                    ORDER BY up.total_points DESC
                    LIMIT $limit
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            // Add rank to results
            $ranked_results = [];
            foreach ($results as $index => $user) {
                $user->rank = $index + 1;
                $ranked_results[] = $user;
            }
            
            return $ranked_results;
            
        } catch (Exception $e) {
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user points record
     */
    public function get_user_points($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $stmt = $pdo->prepare("SELECT * FROM user_points WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            return $stmt->fetch(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            log_message('error', 'User Point Model Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Start an AI tutor session
     */
    public function start_ai_tutor_session($user_id, $minutes_allocated, $points_to_deduct)
    {
        try {
            $pdo = CDatabase::getPdo();
            $pdo->beginTransaction();
            
            // Check if user has enough points
            $stmt = $pdo->prepare("SELECT available_points FROM user_points WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $available_points = $stmt->fetchColumn();
            
            if ($available_points < $points_to_deduct) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Insufficient points for AI tutor session',
                    'available_points' => $available_points,
                    'required_points' => $points_to_deduct
                ];
            }
            
            // Create AI tutor session record
            $stmt = $pdo->prepare("
                INSERT INTO ai_tutor_sessions 
                (user_id, minutes_allocated, points_deducted, session_status, ip_address, user_agent) 
                VALUES (?, ?, ?, 'active', ?, ?)
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->execute([
                $user_id, 
                $minutes_allocated, 
                $points_to_deduct,
                $ip_address,
                $user_agent
            ]);
            
            $session_id = $pdo->lastInsertId();
            
            // Deduct points using spend_points method
            $spend_result = $this->spend_points($user_id, $points_to_deduct, 'spent', [
                'ai_tutor_minutes' => $minutes_allocated,
                'admin_reason' => "AI Tutor Session #$session_id"
            ]);
            
            if (!$spend_result['success']) {
                $pdo->rollBack();
                return $spend_result;
            }
            
            $pdo->commit();
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'minutes_allocated' => $minutes_allocated,
                'points_deducted' => $points_to_deduct,
                'remaining_points' => $spend_result['remaining_points']
            ];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_message('error', 'AI Tutor Session Start Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to start AI tutor session'
            ];
        }
    }

    /**
     * End an AI tutor session
     */
    public function end_ai_tutor_session($session_id, $minutes_used = null)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Get session details
            $stmt = $pdo->prepare("
                SELECT user_id, minutes_allocated, points_deducted, session_status 
                FROM ai_tutor_sessions 
                WHERE id = ?
            ");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session not found'
                ];
            }
            
            if ($session->session_status !== 'active') {
                return [
                    'success' => false,
                    'error' => 'Session is not active'
                ];
            }
            
            // If minutes_used not provided, use full allocated time
            if ($minutes_used === null) {
                $minutes_used = $session->minutes_allocated;
            }
            
            // Update session end time and minutes used
            $stmt = $pdo->prepare("
                UPDATE ai_tutor_sessions 
                SET session_end = NOW(), 
                    minutes_used = ?, 
                    session_status = 'completed',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$minutes_used, $session_id]);
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'minutes_used' => $minutes_used,
                'minutes_allocated' => $session->minutes_allocated,
                'points_deducted' => $session->points_deducted
            ];
            
        } catch (Exception $e) {
            log_message('error', 'AI Tutor Session End Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to end AI tutor session'
            ];
        }
    }

    /**
     * Get user's AI tutor session history
     */
    public function get_ai_tutor_sessions($user_id, $limit = 10)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            // Sanitize limit as integer
            $limit = max(1, min(100, (int)$limit));
            
            $stmt = $pdo->prepare("
                SELECT id, session_start, session_end, minutes_allocated, minutes_used,
                       points_deducted, conversation_messages, session_status,
                       created_at, updated_at
                FROM ai_tutor_sessions 
                WHERE user_id = ? 
                ORDER BY session_start DESC 
                LIMIT $limit
            ");
            $stmt->execute([$user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            log_message('error', 'AI Tutor Sessions Fetch Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active AI tutor session for user
     */
    public function get_active_ai_tutor_session($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();
            
            $stmt = $pdo->prepare("
                SELECT id, session_start, minutes_allocated, points_deducted,
                       conversation_messages, created_at
                FROM ai_tutor_sessions 
                WHERE user_id = ? AND session_status = 'active' 
                ORDER BY session_start DESC 
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            
            return $stmt->fetch(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            log_message('error', 'Active AI Tutor Session Fetch Error: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Get point configuration value
     */
    private function _get_config($key, $default = null)
    {
        return $this->config->item($key) !== null ? $this->config->item($key) : $default;
    }

}