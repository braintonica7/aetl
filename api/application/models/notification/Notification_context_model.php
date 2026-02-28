<?php

/**
 * Notification Context Model
 * 
 * Manages user notification context data for intelligent, personalized notifications.
 * This model handles CRUD operations for the user_notification_context table.
 * 
 * @package WiziAI
 * @subpackage Models
 * @category Notification
 */
class Notification_context_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get user notification context by user ID
     * 
     * @param int $user_id User ID
     * @return object|null User context object or null if not found
     */
    public function get_user_context($user_id) {
        try {
            $sql = "SELECT * FROM user_notification_context WHERE user_id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$user_id]);
            
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;
            
            return $result ?: null;
        } catch (Exception $e) {
            log_message('error', 'Error fetching user context: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get multiple user contexts by user IDs
     * 
     * @param array $user_ids Array of user IDs
     * @return array Array of user context objects
     */
    public function get_user_contexts($user_ids) {
        try {
            if (empty($user_ids)) {
                return [];
            }
            
            $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
            $sql = "SELECT * FROM user_notification_context WHERE user_id IN ($placeholders)";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute($user_ids);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching multiple user contexts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Insert or update user notification context (UPSERT)
     * 
     * @param int $user_id User ID
     * @param array $context_data Associative array of context fields
     * @return bool Success status
     */
    public function upsert_context($user_id, $context_data) {
        try {
            // Add user_id to data
            $context_data['user_id'] = $user_id;
            $context_data['last_computed_at'] = date('Y-m-d H:i:s');
            $context_data['updated_at'] = date('Y-m-d H:i:s');
            
            // Build field list and placeholders
            $fields = array_keys($context_data);
            $placeholders = array_fill(0, count($fields), '?');
            
            // Build UPDATE clause for ON DUPLICATE KEY
            $update_clauses = [];
            foreach ($fields as $field) {
                if ($field !== 'user_id' && $field !== 'created_at') {
                    $update_clauses[] = "$field = VALUES($field)";
                }
            }
            
            $sql = "INSERT INTO user_notification_context (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE " . implode(', ', $update_clauses);
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array_values($context_data));
            $statement = null;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', "Error upserting context for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update specific fields of user context
     * 
     * @param int $user_id User ID
     * @param array $update_data Fields to update
     * @return bool Success status
     */
    public function update_context($user_id, $update_data) {
        try {
            if (empty($update_data)) {
                return false;
            }
            
            $fields = [];
            $params = [];
            
            foreach ($update_data as $key => $value) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
            
            $fields[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            $params[] = $user_id;
            
            $sql = "UPDATE user_notification_context SET " . implode(', ', $fields) . " WHERE user_id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $statement = null;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', "Error updating context for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark user context as stale (needs recomputation)
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function mark_as_stale($user_id) {
        return $this->update_context($user_id, ['is_stale' => 1]);
    }

    /**
     * Mark multiple users as stale
     * 
     * @param array $user_ids Array of user IDs
     * @return bool Success status
     */
    public function mark_users_as_stale($user_ids) {
        try {
            if (empty($user_ids)) {
                return false;
            }
            
            $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
            $sql = "UPDATE user_notification_context 
                    SET is_stale = 1, updated_at = ? 
                    WHERE user_id IN ($placeholders)";
            
            $params = array_merge([date('Y-m-d H:i:s')], $user_ids);
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $statement = null;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Error marking users as stale: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get users eligible for inactivity reminders
     * 
     * @param int $days_inactive Minimum days of inactivity (default: 3)
     * @param int $limit Maximum number of users to return
     * @return array Array of user context objects
     */
    public function get_inactive_users($days_inactive = 3, $limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE days_since_last_quiz >= ?
                      AND is_inactive_user = 1
                      AND eligible_for_notification = 1
                      AND (last_reminder_sent IS NULL OR last_reminder_sent < DATE_SUB(NOW(), INTERVAL 24 HOUR))
                    ORDER BY days_since_last_quiz DESC
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$days_inactive]);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching inactive users: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users close to achieving milestones
     * 
     * @param float $min_percentage Minimum progress percentage (default: 75)
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_near_milestone($min_percentage = 40, $limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE next_milestone_percentage >= ?
                      AND next_milestone_percentage < 100
                      AND eligible_for_notification = 1
                      AND (last_milestone_notification_sent IS NULL 
                           OR last_milestone_notification_sent < DATE_SUB(NOW(), INTERVAL 48 HOUR))
                    ORDER BY next_milestone_percentage DESC
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$min_percentage]);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users near milestone: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get high performers who have never tried PYQ
     * 
     * @param int $min_quizzes Minimum total quizzes (default: 3)
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_for_pyq_suggestion($min_quizzes = 3, $limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE total_quizzes_all_time >= ?
                      AND eligible_for_notification = 1
                      AND (last_pyq_suggestion_sent IS NULL 
                           OR last_pyq_suggestion_sent < DATE_SUB(NOW(), INTERVAL 7 DAY))
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$min_quizzes]);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users for PYQ suggestion: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users needing encouragement (declining performance but still active)
     * 
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_needing_encouragement($limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE is_active_user = 1
                      AND motivation_level = 'needs_encouragement'
                      AND eligible_for_notification = 1
                      AND (last_motivational_sent IS NULL 
                           OR last_motivational_sent < DATE_SUB(NOW(), INTERVAL 24 HOUR))
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users needing encouragement: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users for custom quiz suggestions
     * 
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_for_custom_quiz_suggestion($limit = 100) {
        try {
            // Relaxed criteria: Include all non-dormant users eligible for notifications
            // Note: FCM token and notification preferences will be checked during actual sending
            
            // Cast limit to integer for LIMIT clause (PDO can't bind LIMIT parameters as strings)
            $limit = (int)$limit;
            
            $sql = "SELECT unc.* 
                    FROM user_notification_context unc
                    WHERE unc.eligible_for_notification = 1
                      AND unc.is_dormant_user = 0
                      AND (unc.last_custom_quiz_suggestion_sent IS NULL 
                           OR unc.last_custom_quiz_suggestion_sent < DATE_SUB(NOW(), INTERVAL 24 HOUR))
                    ORDER BY 
                        CASE 
                            WHEN unc.is_active_user = 1 THEN 1
                            WHEN unc.is_inactive_user = 1 THEN 2
                            ELSE 3
                        END,
                        RAND()
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users for custom quiz suggestion: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users for mock test suggestion
     * 
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_for_mock_suggestion($limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE performance_category = 'good'
                      AND total_quizzes_all_time >= 10
                      AND is_high_performer = 1
                      AND never_tried_mock = 1
                      AND eligible_for_notification = 1
                    ORDER BY current_accuracy_percentage DESC
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users for mock test suggestion: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users approaching quota limit
     * 
     * @param float $threshold Quota usage percentage threshold (default: 80)
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_approaching_quota($threshold = 80.0, $limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE custom_quiz_quota_percentage >= ?
                      AND is_quota_exhausted = 0
                      AND subscription_type = 'free'
                      AND eligible_for_notification = 1
                      AND (last_quota_warning_sent IS NULL 
                           OR last_quota_warning_sent < DATE_SUB(NOW(), INTERVAL 7 DAY))
                    ORDER BY custom_quiz_quota_percentage DESC
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$threshold]);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users approaching quota: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get stale user contexts that need recomputation
     * 
     * @param int $limit Maximum number of users
     * @return array Array of user IDs with stale contexts
     */
    public function get_stale_contexts($limit = 500) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT user_id FROM user_notification_context
                    WHERE is_stale = 1 OR last_computed_at < DATE_SUB(NOW(), INTERVAL 6 HOUR)
                    ORDER BY last_computed_at ASC
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $results = $statement->fetchAll(PDO::FETCH_COLUMN);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching stale contexts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by suggested action
     * 
     * @param string $action Action type (custom_quiz, pyq, mock_test, review_weak_topics)
     * @param int $limit Maximum number of users
     * @return array Array of user context objects
     */
    public function get_users_by_suggested_action($action, $limit = 100) {
        try {
            $limit = (int)$limit;
            
            $sql = "SELECT * FROM user_notification_context
                    WHERE suggested_action = ?
                      AND eligible_for_notification = 1
                      AND total_notifications_sent_today < 3
                    ORDER BY last_quiz_date DESC
                    LIMIT {$limit}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$action]);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', "Error fetching users by action {$action}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Reset daily notification counters (run at midnight)
     * 
     * @return bool Success status
     */
    public function reset_daily_counters() {
        try {
            $sql = "UPDATE user_notification_context 
                    SET total_notifications_sent_today = 0,
                        updated_at = ?
                    WHERE total_notifications_sent_today > 0";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute([date('Y-m-d H:i:s')]);
            $affected = $statement->rowCount();
            $statement = null;
            
            log_message('info', "Reset daily notification counters for {$affected} users");
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Error resetting daily counters: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment notification counter for user
     * 
     * @param int $user_id User ID
     * @param string $notification_type Type of notification sent
     * @return bool Success status
     */
    public function increment_notification_counter($user_id, $notification_type) {
        try {
            $field_map = [
                'custom_quiz_suggestion' => 'last_custom_quiz_suggestion_sent',
                'pyq_suggestion' => 'last_pyq_suggestion_sent',
                'mock_suggestion' => 'last_mock_suggestion_sent',
                'motivational' => 'last_motivational_sent',
                'reminder' => 'last_reminder_sent',
                'milestone' => 'last_milestone_notification_sent',
                'quota_warning' => 'last_quota_warning_sent'
            ];
            
            $timestamp_field = $field_map[$notification_type] ?? null;
            
            $sql = "UPDATE user_notification_context 
                    SET total_notifications_sent_today = total_notifications_sent_today + 1,
                        total_notifications_sent_7days = total_notifications_sent_7days + 1,
                        total_notifications_sent_30days = total_notifications_sent_30days + 1";
            
            if ($timestamp_field) {
                $sql .= ", $timestamp_field = ?";
            }
            
            $sql .= ", updated_at = ? WHERE user_id = ?";
            
            $params = $timestamp_field 
                ? [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $user_id]
                : [date('Y-m-d H:i:s'), $user_id];
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $statement = null;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', "Error incrementing notification counter for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user contexts with filters
     * 
     * @param array $filters Associative array of filters
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of user context objects
     */
    public function get_users_with_filters($filters = [], $limit = 100, $offset = 0) {
        try {
            $where_clauses = ['eligible_for_notification = 1'];
            $params = [];
            
            // Build WHERE clause from filters
            if (isset($filters['performance_category'])) {
                $where_clauses[] = "performance_category = ?";
                $params[] = $filters['performance_category'];
            }
            
            if (isset($filters['engagement_level'])) {
                $where_clauses[] = "engagement_level = ?";
                $params[] = $filters['engagement_level'];
            }
            
            if (isset($filters['min_quizzes'])) {
                $where_clauses[] = "total_quizzes_all_time >= ?";
                $params[] = $filters['min_quizzes'];
            }
            
            if (isset($filters['is_new_user'])) {
                $where_clauses[] = "is_new_user = ?";
                $params[] = $filters['is_new_user'];
            }
            
            if (isset($filters['is_high_performer'])) {
                $where_clauses[] = "is_high_performer = ?";
                $params[] = $filters['is_high_performer'];
            }
            
            if (isset($filters['needs_support'])) {
                $where_clauses[] = "needs_support = ?";
                $params[] = $filters['needs_support'];
            }
            
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            $sql = "SELECT * FROM user_notification_context 
                    WHERE " . implode(' AND ', $where_clauses) . "
                    ORDER BY last_quiz_date DESC
                    LIMIT {$limit} OFFSET {$offset}";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users with filters: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete user context (typically on user account deletion)
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function delete_context($user_id) {
        try {
            $sql = "DELETE FROM user_notification_context WHERE user_id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute([$user_id]);
            $statement = null;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', "Error deleting context for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get context statistics for reporting
     * 
     * @return object Statistics object
     */
    public function get_context_statistics() {
        try {
            $pdo = CDatabase::getPdo();
            
            // Get context counts - check if table has data first
            $sql = "SELECT 
                        COUNT(*) as total_contexts,
                        COALESCE(SUM(eligible_for_notification), 0) as eligible_users,
                        COALESCE(SUM(is_stale), 0) as stale_contexts,
                        COALESCE(AVG(computation_duration_ms), 0) as avg_computation_ms
                    FROM user_notification_context";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            $statement = null;
            
            // Calculate percentages
            $total = (int)($result['total_contexts'] ?? 0);
            $eligible = (int)($result['eligible_users'] ?? 0);
            $stale = (int)($result['stale_contexts'] ?? 0);
            
            $eligible_percentage = $total > 0 ? round(($eligible / $total) * 100, 1) : 0;
            $stale_percentage = $total > 0 ? round(($stale / $total) * 100, 1) : 0;
            
            // Get last build info (most recent last_computed_at)
            $sql = "SELECT 
                        MAX(last_computed_at) as last_build,
                        COUNT(*) as last_build_total,
                        SUM(CASE WHEN error_count > 0 THEN 1 ELSE 0 END) as last_build_failed
                    FROM user_notification_context
                    WHERE last_computed_at IS NOT NULL";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $buildInfo = $statement->fetch(PDO::FETCH_ASSOC);
            $statement = null;
            
            $last_build = $buildInfo['last_build'] ?? null;
            $last_build_relative = 'Never';
            $last_build_duration = null;
            
            if ($last_build) {
                $timestamp = strtotime($last_build);
                $diff = time() - $timestamp;
                
                if ($diff < 60) {
                    $last_build_relative = 'Just now';
                } elseif ($diff < 3600) {
                    $minutes = floor($diff / 60);
                    $last_build_relative = $minutes . ' min ago';
                } elseif ($diff < 86400) {
                    $hours = floor($diff / 3600);
                    $last_build_relative = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                } else {
                    $days = floor($diff / 86400);
                    $last_build_relative = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                }
            }
            
            // Format average build time
            $avg_ms = $result['avg_computation_ms'] ?? 0;
            $avg_build_time = 'N/A';
            if ($avg_ms > 0) {
                if ($avg_ms < 1000) {
                    $avg_build_time = round($avg_ms) . ' ms';
                } else {
                    $avg_build_time = round($avg_ms / 1000, 2) . ' sec';
                }
            }
            
            return [
                'total_contexts' => $total,
                'eligible_users' => $eligible,
                'eligible_percentage' => $eligible_percentage,
                'stale_contexts' => $stale,
                'stale_percentage' => $stale_percentage,
                'last_build' => $last_build,
                'last_build_relative' => $last_build_relative,
                'last_build_duration' => $last_build_duration,
                'last_build_total' => (int)($buildInfo['last_build_total'] ?? 0),
                'last_build_failed' => (int)($buildInfo['last_build_failed'] ?? 0),
                'avg_build_time' => $avg_build_time
            ];
        } catch (Exception $e) {
            log_message('error', 'Error fetching context statistics: ' . $e->getMessage());
            return null;
        }
    }
    /**
     * Get users with their basic info and context for notification preview
     * 
     * @param array $filters Filter parameters (notification_type, priority, segment, search)
     * @param int $limit Number of records to return
     * @param int $offset Pagination offset
     * @return array Array of user data with context
     */
    public function get_users_for_notification_preview($filters = [], $limit = 50, $offset = 0) {
        try {
            $where_clauses = [];
            $params = [];
            
            // Base query joining user and context tables
            $sql = "SELECT 
                        u.id as user_id,
                        u.display_name,
                        u.username as email,
                        u.mobile_number,
                        u.fcm_token,
                        unc.*
                    FROM user u
                    INNER JOIN user_notification_context unc ON u.id = unc.user_id
                    WHERE unc.eligible_for_notification = 1
                    AND unc.total_notifications_sent_today < 3
                    AND u.mobile_number IS NOT NULL
                    AND u.mobile_number != ''
                    AND u.fcm_token IS NOT NULL
                    AND u.fcm_token != ''";
            
            // Apply search filter
            if (!empty($filters['search'])) {
                $search_term = '%' . $filters['search'] . '%';
                $where_clauses[] = "(u.display_name LIKE ? OR u.username LIKE ? OR u.mobile_number LIKE ?)";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            // Apply user segment filter
            if (!empty($filters['segment'])) {
                switch ($filters['segment']) {
                    case 'inactive_user':
                        $where_clauses[] = "unc.is_inactive_user = 1";
                        break;
                    case 'dormant_user':
                        $where_clauses[] = "unc.is_dormant_user = 1";
                        break;
                    case 'high_performer':
                        $where_clauses[] = "unc.is_high_performer = 1";
                        break;
                    case 'needs_support':
                        $where_clauses[] = "unc.needs_support = 1";
                        break;
                    case 'new_user':
                        $where_clauses[] = "unc.is_new_user = 1";
                        break;
                    case 'at_risk':
                        $where_clauses[] = "unc.is_at_risk = 1";
                        break;
                }
            }
            
            // Add where clauses to query
            if (!empty($where_clauses)) {
                $sql .= " AND " . implode(" AND ", $where_clauses);
            }
            
            // Sanitize limit and offset as integers
            $limit = max(1, (int)$limit);
            $offset = max(0, (int)$offset);
            
            // Order by priority factors - use direct interpolation for LIMIT/OFFSET
            $sql .= " ORDER BY 
                        unc.is_dormant_user DESC,
                        unc.days_since_last_quiz DESC,
                        unc.next_milestone_percentage DESC,
                        unc.last_computed_at DESC
                      LIMIT $offset, $limit";
            
			log_message('debug', 'Notification Preview SQL: ' . $sql . ' | Params: ' . implode(', ', $params));
			
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching users for notification preview: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user with context by user ID (for individual preview)
     * 
     * @param int $user_id User ID
     * @return object|null User data with context
     */
    public function get_user_with_context_by_id($user_id) {
        try {
            $sql = "SELECT 
                        u.id as user_id,
                        u.display_name,
                        u.username as email,
                        u.mobile_number,
                        u.fcm_token,
                        unc.*
                    FROM user u
                    LEFT JOIN user_notification_context unc ON u.id = unc.user_id
                    WHERE u.id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$user_id]);
            
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;
            
            return $result ?: null;
        } catch (Exception $e) {
            log_message('error', "Error fetching user with context for user {$user_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get count of eligible users for notification preview
     * 
     * @param array $filters Filter parameters
     * @return int Count of eligible users
     */
    public function get_eligible_users_count($filters = []) {
        try {
            $where_clauses = [];
            $params = [];
            
            $sql = "SELECT COUNT(*) as total
                    FROM user u
                    INNER JOIN user_notification_context unc ON u.id = unc.user_id
                    WHERE unc.eligible_for_notification = 1
                    AND unc.total_notifications_sent_today < 3
                    AND u.mobile_number IS NOT NULL
                    AND u.mobile_number != ''
                    AND u.fcm_token IS NOT NULL
                    AND u.fcm_token != ''";
            
            // Apply search filter
            if (!empty($filters['search'])) {
                $search_term = '%' . $filters['search'] . '%';
                $where_clauses[] = "(u.display_name LIKE ? OR u.username LIKE ? OR u.mobile_number LIKE ?)";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            // Apply user segment filter
            if (!empty($filters['segment'])) {
                switch ($filters['segment']) {
                    case 'inactive_user':
                        $where_clauses[] = "unc.is_inactive_user = 1";
                        break;
                    case 'dormant_user':
                        $where_clauses[] = "unc.is_dormant_user = 1";
                        break;
                    case 'high_performer':
                        $where_clauses[] = "unc.is_high_performer = 1";
                        break;
                    case 'needs_support':
                        $where_clauses[] = "unc.needs_support = 1";
                        break;
                    case 'new_user':
                        $where_clauses[] = "unc.is_new_user = 1";
                        break;
                    case 'at_risk':
                        $where_clauses[] = "unc.is_at_risk = 1";
                        break;
                }
            }
            
            // Add where clauses to query
            if (!empty($where_clauses)) {
                $sql .= " AND " . implode(" AND ", $where_clauses);
            }
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;
            
            return $result ? (int)$result->total : 0;
        } catch (Exception $e) {
            log_message('error', 'Error counting eligible users: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update notification sent timestamp
     * 
     * @param int $user_id User ID
     * @param string $notification_type Type of notification sent
     * @return bool Success status
     */
    public function update_notification_sent($user_id, $notification_type) {
        try {
            $field_map = [
                'inactivity' => 'last_reminder_sent',
                'milestone' => 'last_milestone_notification_sent',
                'custom_quiz' => 'last_custom_quiz_suggestion_sent',
                'pyq' => 'last_pyq_suggestion_sent',
                'mock_test' => 'last_mock_suggestion_sent',
                'performance_declining' => 'last_motivational_sent',
                'performance_improving' => 'last_motivational_sent',
                'quota_warning' => 'last_quota_warning_sent',
                'subscription_expiry' => 'last_quota_warning_sent',
                'streak_broken' => 'last_reminder_sent'
            ];

            $field = $field_map[$notification_type] ?? null;
            if (!$field) {
                return false;
            }

            $now = date('Y-m-d H:i:s');
            
            $sql = "UPDATE user_notification_context 
                    SET {$field} = ?,
                        total_notifications_sent_today = total_notifications_sent_today + 1,
                        total_notifications_sent_7days = total_notifications_sent_7days + 1,
                        total_notifications_sent_30days = total_notifications_sent_30days + 1,
                        updated_at = ?
                    WHERE user_id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute([$now, $now, $user_id]);
            $statement = null;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', "Error updating notification sent for user {$user_id}: " . $e->getMessage());
            return false;
        }
    }}
