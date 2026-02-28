<?php

/**
 * Notification Context Builder Controller
 * 
 * This controller builds and maintains pre-computed user context data
 * for intelligent notification targeting and personalization.
 * 
 * Endpoints:
 * - POST /api/notification-context/build - Build/update user contexts (batch)
 * - POST /api/notification-context/build-user - Build single user context
 * - GET /api/notification-context/stats - Get context statistics
 * - POST /api/notification-context/reset-counters - Reset daily counters
 * 
 * @package WiziAI
 * @subpackage Controllers/API
 * @category Notification
 */
class Notification_context extends API_Controller {

    private $batch_size = 500;
    private $computation_start_time;

    public function __construct() {
        parent::__construct();
        
        // Load libraries
        $this->load->driver('cache');
        
        // Load models
        $this->load->model('notification/notification_context_model');
        $this->load->model('user/user_model');
        $this->load->model('quiz/quiz_model');
        $this->load->model('user_performance/user_performance_model');
        $this->load->model('user_performance_summary/user_performance_summary_model');
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
    }

    /**
     * Build user notification contexts (batch processing)
     * POST /api/notification-context/build
     * 
     * Query Parameters:
     * - user_ids: Array of specific user IDs (optional)
     * - batch_size: Number of users to process (default: 500)
     * - stale_only: Only process stale contexts (default: false)
     */
    public function build_post() {
        try {
            // Admin only for manual triggers
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            // Check if a build is already in progress
            $existing_build = $this->cache->get('notification_context_build_status');
            if ($existing_build) {
                $this->response([
                    'success' => false,
                    'message' => 'A build is already in progress. Please wait for it to complete.',
                    'data' => [
                        'current_build' => $existing_build
                    ]
                ], 409); // Conflict
                return;
            }

            $this->computation_start_time = microtime(true);
            $start_timestamp = time();
            
            $request = $this->get_request();
            $user_ids = $request['user_ids'] ?? null;
            $batch_size = $request['batch_size'] ?? $this->batch_size;
            $stale_only = $request['stale_only'] ?? false;
            
            // Determine build type
            $build_type = 'Full Build';
            if ($user_ids) {
                $build_type = 'Specific Users';
            } elseif ($stale_only) {
                $build_type = 'Stale Only';
            }
            
            // Get users to process
            if ($user_ids) {
                $users_to_process = $user_ids;
            } elseif ($stale_only) {
                $users_to_process = $this->notification_context_model->get_stale_contexts($batch_size);
            } else {
                $users_to_process = $this->_get_active_user_ids($batch_size);
            }
            
            if (empty($users_to_process)) {
                $this->response([
                    'success' => true,
                    'message' => 'No users to process',
                    'processed' => 0
                ], 200);
                return;
            }
            
            // Initialize build status in cache
            $total_users = count($users_to_process);
            $this->cache->save('notification_context_build_status', [
                'type' => $build_type,
                'start_time' => $start_timestamp,
                'total' => $total_users,
                'processed' => 0,
                'failed' => 0,
                'user' => $admin->display_name ?? 'Admin'
            ], 3600); // 1 hour TTL
            
            $processed = 0;
            $failed = 0;
            $errors = [];
            
            foreach ($users_to_process as $user_id) {
                try {
                    $context = $this->build_user_context($user_id);
                    
                    if ($context) {
                        $result = $this->notification_context_model->upsert_context($user_id, $context);
                        if ($result) {
                            $processed++;
                        } else {
                            $failed++;
                            $errors[] = "Failed to save context for user {$user_id}";
                        }
                    } else {
                        $failed++;
                        $errors[] = "Failed to build context for user {$user_id}";
                    }
                    
                    // Update progress in cache every 10 users
                    if (($processed + $failed) % 10 == 0) {
                        $this->cache->save('notification_context_build_status', [
                            'type' => $build_type,
                            'start_time' => $start_timestamp,
                            'total' => $total_users,
                            'processed' => $processed + $failed,
                            'failed' => $failed,
                            'user' => $admin->display_name ?? 'Admin'
                        ], 3600);
                    }
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "User {$user_id}: " . $e->getMessage();
                    log_message('error', "Failed to build context for user {$user_id}: " . $e->getMessage());
                }
            }
            
            $duration_seconds = microtime(true) - $this->computation_start_time;
            $duration_ms = round($duration_seconds * 1000);
            
            // Clear build status from cache
            $this->cache->delete('notification_context_build_status');
            
            // Add to build history
            $this->_add_to_build_history([
                'timestamp' => $start_timestamp,
                'type' => $build_type,
                'processed' => $processed,
                'failed' => $failed,
                'total' => $total_users,
                'duration_seconds' => $duration_seconds,
                'triggered_by' => $admin->display_name ?? 'Admin'
            ]);
            
            $this->response([
                'success' => true,
                'message' => 'Context building completed',
                'data' => [
                    'processed' => $processed,
                    'failed' => $failed,
                    'total' => $total_users,
                    'duration_ms' => $duration_ms,
                    'errors' => array_slice($errors, 0, 10) // Return first 10 errors
                ]
            ], 200);
            
        } catch (Exception $e) {
            // Clear build status on error
            $this->cache->delete('notification_context_build_status');
            
            log_message('error', 'Context build error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build single user context
     * POST /api/notification-context/build-user
     * 
     * Body: { "user_id": 123 }
     */
    public function build_user_post() {
        try {
            $user = $this->require_jwt_auth();
            if (!$user) {
                return;
            }

            $request = $this->get_request();
            $target_user_id = $request['user_id'] ?? $user->id;
            
            // Non-admin users can only build their own context
            if ($user->role_id != 1 && $target_user_id != $user->id) {
                $this->response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
                return;
            }
            
            $this->computation_start_time = microtime(true);
            $context = $this->build_user_context($target_user_id);
            
            if (!$context) {
                $this->response([
                    'success' => false,
                    'message' => 'Failed to build user context'
                ], 500);
                return;
            }
            
            $result = $this->notification_context_model->upsert_context($target_user_id, $context);
            $duration = round((microtime(true) - $this->computation_start_time) * 1000);
            
            if ($result) {
                $this->response([
                    'success' => true,
                    'message' => 'User context built successfully',
                    'data' => [
                        'user_id' => $target_user_id,
                        'duration_ms' => $duration,
                        'context' => $context
                    ]
                ], 200);
            } else {
                $this->response([
                    'success' => false,
                    'message' => 'Failed to save user context'
                ], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Build user context error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get context statistics
     * GET /api/notification-context/stats
     */
    public function stats_get() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $stats = $this->notification_context_model->get_context_statistics();
            
            // Add detailed debug info about table state and why eligible users might be 0
            $pdo = CDatabase::getPdo();
            $debug_sql = "SELECT 
                            COUNT(*) as total_rows,
                            SUM(CASE WHEN eligible_for_notification = 1 THEN 1 ELSE 0 END) as eligible_count,
                            SUM(CASE WHEN is_active_user = 1 THEN 1 ELSE 0 END) as active_count,
                            SUM(CASE WHEN weakest_subject IS NOT NULL THEN 1 ELSE 0 END) as has_weak_subject,
                            SUM(CASE WHEN last_computed_at IS NOT NULL THEN 1 ELSE 0 END) as has_computed,
                            SUM(CASE WHEN last_custom_quiz_suggestion_sent IS NULL THEN 1 ELSE 0 END) as no_custom_notification_sent,
                            SUM(CASE WHEN never_tried_custom = 1 THEN 1 ELSE 0 END) as never_tried_custom,
                            SUM(CASE WHEN days_since_last_quiz >= 1 THEN 1 ELSE 0 END) as inactive_1day_plus
                        FROM user_notification_context";
            $statement = $pdo->prepare($debug_sql);
            $statement->execute();
            $debug = $statement->fetch(PDO::FETCH_ASSOC);
            $statement = null;
            
            // Check custom quiz suggestion criteria match (updated to match relaxed criteria)
            $match_sql = "SELECT COUNT(*) as matches FROM user_notification_context
                          WHERE eligible_for_notification = 1
                            AND is_dormant_user = 0
                            AND (last_custom_quiz_suggestion_sent IS NULL 
                                 OR last_custom_quiz_suggestion_sent < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
            $statement = $pdo->prepare($match_sql);
            $statement->execute();
            $debug['custom_quiz_eligible'] = $statement->fetch(PDO::FETCH_ASSOC)['matches'];
            $statement = null;
            
            // Check dormant users count
            $dormant_sql = "SELECT COUNT(*) as dormant FROM user_notification_context WHERE is_dormant_user = 1";
            $statement = $pdo->prepare($dormant_sql);
            $statement->execute();
            $debug['dormant_users'] = $statement->fetch(PDO::FETCH_ASSOC)['dormant'];
            $statement = null;
            
            // Sample first 3 rows to see actual data
            $sample_sql = "SELECT user_id, eligible_for_notification, is_active_user, weakest_subject, 
                                  never_tried_custom, days_since_last_quiz, last_custom_quiz_suggestion_sent
                           FROM user_notification_context LIMIT 3";
            $statement = $pdo->prepare($sample_sql);
            $statement->execute();
            $debug['sample_rows'] = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement = null;
            
            // Add debug info to stats
            if ($stats) {
                $stats['debug'] = $debug;
            }
            
            $this->response([
                'success' => true,
                'data' => $stats
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Get context stats error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset daily notification counters (run at midnight)
     * POST /api/notification-context/reset-counters
     */
    public function reset_counters_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $result = $this->notification_context_model->reset_daily_counters();
            
            $this->response([
                'success' => $result,
                'message' => $result ? 'Daily counters reset successfully' : 'Failed to reset counters'
            ], $result ? 200 : 500);
            
        } catch (Exception $e) {
            log_message('error', 'Reset counters error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current build status
     * GET /api/notification-context/build-status
     * 
     * Returns whether a build is currently in progress and its status
     */
    public function build_status_get() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            // Check cache for active build status
            $build_status = $this->cache->get('notification_context_build_status');
            
            if ($build_status) {
                $elapsed = time() - $build_status['start_time'];
                $progress_percentage = 0;
                
                if ($build_status['total'] > 0) {
                    $progress_percentage = round(($build_status['processed'] / $build_status['total']) * 100, 1);
                }
                
                $this->response([
                    'success' => true,
                    'data' => [
                        'is_building' => true,
                        'progress_percentage' => $progress_percentage,
                        'processed' => $build_status['processed'],
                        'total' => $build_status['total'],
                        'failed' => $build_status['failed'],
                        'elapsed_seconds' => $elapsed,
                        'started_at' => date('Y-m-d H:i:s', $build_status['start_time']),
                        'build_type' => $build_status['type'] ?? 'unknown',
                        'triggered_by' => $build_status['user'] ?? 'system'
                    ]
                ], 200);
            } else {
                // No active build
                $this->response([
                    'success' => true,
                    'data' => [
                        'is_building' => false,
                        'message' => 'No build currently in progress'
                    ]
                ], 200);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Get build status error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent build history
     * GET /api/notification-context/recent-builds
     * 
     * Query Parameters:
     * - limit: Number of recent builds to return (default: 20, max: 100)
     */
    public function recent_builds_get() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $limit = (int)($this->input->get('limit') ?? 20);
            $limit = min(max($limit, 1), 100); // Between 1 and 100
            
            // Get build history from cache or database
            $history = $this->cache->get('notification_context_build_history');
            
            if (!$history) {
                $history = [];
            }
            
            // Sort by timestamp descending
            usort($history, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            // Limit results
            $history = array_slice($history, 0, $limit);
            
            // Format timestamps for display
            foreach ($history as &$build) {
                $build['completed_at'] = date('Y-m-d H:i:s', $build['timestamp']);
                $build['relative_time'] = $this->_get_relative_time($build['timestamp']);
                $build['duration_formatted'] = $this->_format_duration($build['duration_seconds']);
            }
            
            $this->response([
                'success' => true,
                'data' => [
                    'builds' => $history,
                    'total' => count($history)
                ]
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Get recent builds error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get eligible users preview by notification type
     * GET /api/notification-context/eligible-users
     * 
     * Query Parameters:
     * - type: Notification type (custom_quiz, pyq, mock, inactivity, motivational, milestones, quota_warning)
     * - limit: Preview limit (default: 100)
     */
    public function eligible_users_get() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $notification_type = $this->input->get('type') ?? 'custom_quiz';
            $limit = (int)($this->input->get('limit') ?? 100);
            
            $data = [];
            
            switch ($notification_type) {
                case 'custom_quiz':
                    $users = $this->notification_context_model->get_users_for_custom_quiz_suggestion($limit);
                    $data = [
                        'notification_type' => 'Custom Quiz Suggestions',
                        'eligible_count' => count($users),
                        'description' => 'All non-dormant users eligible for notifications',
                        'criteria' => [
                            'Is not dormant (active/inactive/moderate)',
                            'Has not received notification in last 24 hours',
                            'Notifications enabled',
                            'Has FCM token'
                        ],
                        'breakdown' => $this->_analyze_custom_quiz_users($users)
                    ];
                    break;
                    
                case 'pyq':
                    $users = $this->notification_context_model->get_users_for_pyq_suggestion(3, $limit);
                    $data = [
                        'notification_type' => 'PYQ Suggestions',
                        'eligible_count' => count($users),
                        'description' => 'Users ready for previous year questions',
                        'criteria' => [
                            'Total quizzes >= 3',
                            'Has not received notification in last 7 days',
                            'Notifications enabled'
                        ],
                        'breakdown' => $this->_analyze_pyq_users($users)
                    ];
                    break;
                    
                case 'mock':
                    $users = $this->notification_context_model->get_users_for_mock_suggestion($limit);
                    
                    $data = [
                        'notification_type' => 'Mock Test Suggestions',
                        'eligible_count' => count($users),
                        'description' => 'Experienced users ready for full mock tests',
                        'criteria' => [
                            'Performance category: Good',
                            'Total quizzes >= 20',
                            'Is high performer',
                            'Never tried mock test',
                            'Eligible for notifications'
                        ],
                        'breakdown' => $this->_analyze_mock_users($users)
                    ];
                    break;
                    
                case 'inactivity':
                    $days = 3;
                    $users = $this->notification_context_model->get_inactive_users($days, $limit);
                    $data = [
                        'notification_type' => 'Inactivity Reminders',
                        'eligible_count' => count($users),
                        'description' => "Users inactive for {$days}+ days",
                        'criteria' => [
                            "Days since last quiz >= {$days}",
                            'Is active user (not deleted)',
                            'Has not received notification in last 24 hours',
                            'Notifications enabled'
                        ],
                        'breakdown' => $this->_analyze_inactive_users($users)
                    ];
                    break;
                    
                case 'motivational':
                    $users = $this->notification_context_model->get_users_needing_encouragement($limit);
                    $data = [
                        'notification_type' => 'Motivational Messages',
                        'eligible_count' => count($users),
                        'description' => 'Users who need encouragement',
                        'criteria' => [
                            'Motivation level: needs_encouragement',
                            'Is active user',
                            'Has not received notification in last 24 hours',
                            'Notifications enabled'
                        ],
                        'breakdown' => $this->_analyze_motivational_users($users)
                    ];
                    break;
                    
                case 'milestones':
                    $users = $this->notification_context_model->get_users_near_milestone(100.0, $limit);
                    $data = [
                        'notification_type' => 'Milestone Achievements',
                        'eligible_count' => count($users),
                        'description' => 'Users who achieved milestones',
                        'criteria' => [
                            'Next milestone progress >= 100%',
                            'Has not received milestone notification recently',
                            'Notifications enabled'
                        ],
                        'breakdown' => $this->_analyze_milestone_users($users)
                    ];
                    break;
                    
                case 'quota_warning':
                    $threshold = 80.0;
                    $users = $this->notification_context_model->get_users_approaching_quota($threshold, $limit);
                    $data = [
                        'notification_type' => 'Quota Warnings',
                        'eligible_count' => count($users),
                        'description' => 'Free users approaching quota limit',
                        'criteria' => [
                            'Subscription type: free',
                            "Quota usage >= {$threshold}%",
                            'Has not received notification in last 7 days',
                            'Notifications enabled'
                        ],
                        'breakdown' => $this->_analyze_quota_users($users)
                    ];
                    break;
                    
                default:
                    $this->response([
                        'success' => false,
                        'message' => 'Invalid notification type'
                    ], 400);
                    return;
            }
            
            $this->response([
                'success' => true,
                'data' => $data
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Get eligible users error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================================================
    // PRIVATE HELPER METHODS - Context Building Logic
    // ========================================================================

    /**
     * Build complete user context
     * 
     * @param int $user_id User ID
     * @return array|null Context data array or null on failure
     */
    private function build_user_context($user_id) {
        try {
            $start_time = microtime(true);
            
            // 1. Get base user data
            $user = $this->user_model->get_user($user_id);
            if (!$user) {
                return null;
            }
            
            // 2. Compute all context components
            $activity = $this->compute_activity_data($user_id);
            $quiz_distribution = $this->compute_quiz_distribution($user_id);
            $performance = $this->compute_performance_metrics($user_id);
            $subject_analysis = $this->compute_subject_analysis($user_id);
            $time_patterns = $this->compute_time_patterns($user_id);
            $engagement = $this->compute_engagement_metrics($user_id, $activity);
            $milestone = $this->compute_next_milestone($user_id, $activity, $performance);
            $subscription = $this->get_subscription_context($user);
            $notification_history = $this->get_notification_history($user_id);
            $ai_context = $this->compute_ai_context($activity, $quiz_distribution, $performance, $subject_analysis, $engagement);
            $behavioral_flags = $this->compute_behavioral_flags($user, $activity, $quiz_distribution, $performance, $engagement, $notification_history);
            
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            
            // 3. Merge all components
            return array_merge(
                $activity,
                $quiz_distribution,
                $performance,
                $subject_analysis,
                $time_patterns,
                $engagement,
                $milestone,
                $subscription,
                $notification_history,
                $ai_context,
                $behavioral_flags,
                [
                    'computation_duration_ms' => $duration_ms,
                    'computation_version' => '1.0',
                    'is_stale' => 0,
                    'error_count' => 0,
                    'last_error_message' => null
                ]
            );
            
        } catch (Exception $e) {
            log_message('error', "Build context error for user {$user_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute activity tracking data
     */
    private function compute_activity_data($user_id) {
        $pdo = CDatabase::getPdo();
        
        // Get user registration date for fallback
        $user_sql = "SELECT created_at FROM user WHERE id = ?";
        $statement = $pdo->prepare($user_sql);
        $statement->execute([$user_id]);
        $user_data = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        $user_created_at = $user_data['created_at'] ? strtotime($user_data['created_at']) : time();
        
        // Query 1: Mock tests from wizi_quiz_user
        $wizi_sql = "SELECT 
                MAX(wqu.completed_at) as last_quiz_date,
                COUNT(CASE WHEN wqu.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as weekly_count,
                COUNT(CASE WHEN wqu.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_count,
                MIN(wqu.completed_at) as first_quiz_date
            FROM wizi_quiz_user wqu
            WHERE wqu.user_id = ? AND wqu.attempt_status = 'completed'";
        
        $statement = $pdo->prepare($wizi_sql);
        $statement->execute([$user_id]);
        $wizi_result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        // Query 2: Custom/PYQ quizzes from quiz table
        $quiz_sql = "SELECT 
                MAX(q.completed_at) as last_quiz_date,
                MAX(CASE WHEN q.quiz_type = 'custom' THEN q.completed_at END) as last_custom_quiz,
                MAX(CASE WHEN q.quiz_question_type = 'pyq' THEN q.completed_at END) as last_pyq,
                MAX(CASE WHEN q.quiz_question_type = 'regular' THEN q.completed_at END) as last_regular,
                COUNT(CASE WHEN q.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as weekly_count,
                COUNT(CASE WHEN q.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_count,
                MIN(q.completed_at) as first_quiz_date
            FROM quiz q
            WHERE q.user_id = ? AND q.quiz_status = 'completed'";
        
        $statement = $pdo->prepare($quiz_sql);
        $statement->execute([$user_id]);
        $quiz_result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        // Combine results - take MAX dates and SUM counts
        $last_quiz_date = max(
            $wizi_result['last_quiz_date'] ? strtotime($wizi_result['last_quiz_date']) : 0,
            $quiz_result['last_quiz_date'] ? strtotime($quiz_result['last_quiz_date']) : 0
        );
        $last_quiz_date_str = $last_quiz_date > 0 ? date('Y-m-d H:i:s', $last_quiz_date) : null;
        
        $first_quiz_date = min(
            $wizi_result['first_quiz_date'] ? strtotime($wizi_result['first_quiz_date']) : PHP_INT_MAX,
            $quiz_result['first_quiz_date'] ? strtotime($quiz_result['first_quiz_date']) : PHP_INT_MAX
        );
        $first_quiz_date_str = ($first_quiz_date < PHP_INT_MAX) ? date('Y-m-d H:i:s', $first_quiz_date) : null;
        
        $weekly_count = (int)$wizi_result['weekly_count'] + (int)$quiz_result['weekly_count'];
        $monthly_count = (int)$wizi_result['monthly_count'] + (int)$quiz_result['monthly_count'];
        
        // Calculate days since last quiz - use registration date as fallback if no quiz taken
        if ($last_quiz_date > 0) {
            $days_since = floor((time() - $last_quiz_date) / 86400);
        } else {
            // No quiz taken yet - calculate days since registration
            $days_since = floor((time() - $user_created_at) / 86400);
        }
        
        $days_since_first = ($first_quiz_date < PHP_INT_MAX) ? floor((time() - $first_quiz_date) / 86400) : 0;
        
        // Calculate streak
        $streak = $this->calculate_quiz_streak($user_id);
        
        // Last high score date (from wizi_quiz_user only, quiz table doesn't have score field yet)
        $high_score_sql = "SELECT MAX(wqu.completed_at) as last_high_score
            FROM wizi_quiz_user wqu
            WHERE wqu.user_id = ? AND wqu.attempt_status = 'completed' AND wqu.total_score >= 85";
        
        $statement = $pdo->prepare($high_score_sql);
        $statement->execute([$user_id]);
        $high_score_result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        $last_high_score = $high_score_result['last_high_score'];
        $days_since_high_score = $last_high_score ? floor((time() - strtotime($last_high_score)) / 86400) : 9999;
        
        return [
            'last_quiz_date' => $last_quiz_date_str,
            'last_custom_quiz_date' => $quiz_result['last_custom_quiz'],
            'last_pyq_date' => $quiz_result['last_pyq'],
            'last_mock_test_date' => $wizi_result['last_quiz_date'], // Mock tests are in wizi_quiz_user
            'last_regular_quiz_date' => $quiz_result['last_regular'],
            'days_since_last_quiz' => $days_since,
            'quiz_streak_days' => $streak['days'],
            'quiz_streak_broken_date' => $streak['broken_date'],
            'weekly_quiz_count' => $weekly_count,
            'monthly_quiz_count' => $monthly_count,
            'first_quiz_date' => $first_quiz_date_str,
            'days_since_first_quiz' => $days_since_first,
            'last_high_score_date' => $last_high_score,
            'days_since_high_score' => $days_since_high_score
        ];
    }

    /**
     * Compute quiz type distribution
     */
    private function compute_quiz_distribution($user_id) {
        $pdo = CDatabase::getPdo();
        
        // Query 1: Mock tests from wizi_quiz_user
        $wizi_sql = "SELECT COUNT(*) as total_count
            FROM wizi_quiz_user wqu
            WHERE wqu.user_id = ? AND wqu.attempt_status = 'completed'";
        
        $statement = $pdo->prepare($wizi_sql);
        $statement->execute([$user_id]);
        $wizi_result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        $mock_count = (int)($wizi_result['total_count'] ?? 0);
        
        // Query 2: Custom/PYQ quizzes from quiz table with type breakdown
        $quiz_sql = "SELECT 
                COUNT(*) as total_count,
                COUNT(CASE WHEN quiz_type = 'custom' THEN 1 END) as custom_count,
                COUNT(CASE WHEN quiz_question_type = 'pyq' THEN 1 END) as pyq_count,
                COUNT(CASE WHEN quiz_question_type = 'regular' THEN 1 END) as regular_count
            FROM quiz
            WHERE user_id = ? AND quiz_status = 'completed'";
        
        $statement = $pdo->prepare($quiz_sql);
        $statement->execute([$user_id]);
        $quiz_result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        $custom_count = (int)($quiz_result['custom_count'] ?? 0);
        $pyq_count = (int)($quiz_result['pyq_count'] ?? 0);
        $regular_count = (int)($quiz_result['regular_count'] ?? 0);
        $total_quiz_table = (int)($quiz_result['total_count'] ?? 0);
        
        // Calculate totals
        $total_quizzes = $mock_count + $total_quiz_table;
        
        // Determine preferred quiz type
        $type_counts = [
            'mock' => $mock_count,
            'custom' => $custom_count,
            'pyq' => $pyq_count,
            'regular' => $regular_count
        ];
        arsort($type_counts);
        $preferred_type = ($total_quizzes > 0) ? array_key_first($type_counts) : 'none';
        
        return [
            'total_custom_quizzes' => $custom_count,
            'total_pyq_attempts' => $pyq_count,
            'total_mock_tests' => $mock_count,
            'total_regular_quizzes' => $regular_count,
            'preferred_quiz_type' => $preferred_type,
            'never_tried_custom' => ($custom_count === 0 ? 1 : 0),
            'never_tried_pyq' => ($pyq_count === 0 ? 1 : 0),
            'never_tried_mock' => ($mock_count === 0 ? 1 : 0)
        ];
    }

    /**
     * Compute performance metrics
     */
    private function compute_performance_metrics($user_id) {
        $pdo = CDatabase::getPdo();
        
        // Get from user_performance_summary if available
        $summary = $this->user_performance_summary_model->get_latest_summary_by_user($user_id);
        
        if ($summary) {
            $avg_accuracy = $summary->overall_accuracy_percentage;
            $accuracy_trend = $summary->accuracy_trend ?? 'stable';
            $avg_time = $summary->average_time_per_question_seconds ?? 0;
        } else {
            // Fallback: Calculate from both quiz systems
            
            // 1. Mock test performance from wizi_quiz_user
            $wizi_sql = "SELECT 
                    AVG(total_score) as avg_accuracy,
                    AVG(time_spent / NULLIF(total_questions, 0)) as avg_time,
                    COUNT(*) as quiz_count
                FROM wizi_quiz_user
                WHERE user_id = ? AND attempt_status = 'completed' AND total_questions > 0";
            
            $statement = $pdo->prepare($wizi_sql);
            $statement->execute([$user_id]);
            $wizi_result = $statement->fetch(PDO::FETCH_ASSOC);
            $statement = null;
            
            // 2. Custom quiz performance calculated from user_question table
            $custom_sql = "SELECT 
                    AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END) as avg_accuracy,
                    AVG(uq.duration) as avg_time,
                    COUNT(DISTINCT uq.quiz_id) as quiz_count
                FROM user_question uq
                JOIN quiz q ON uq.quiz_id = q.id
                WHERE q.user_id = ? AND q.quiz_status = 'completed'";
            
            $statement = $pdo->prepare($custom_sql);
            $statement->execute([$user_id]);
            $custom_result = $statement->fetch(PDO::FETCH_ASSOC);
            $statement = null;
            
            // 3. Calculate weighted average based on quiz counts
            $wizi_count = (int)($wizi_result['quiz_count'] ?? 0);
            $custom_count = (int)($custom_result['quiz_count'] ?? 0);
            $total_count = $wizi_count + $custom_count;
            
            if ($total_count > 0) {
                $wizi_weight = $wizi_count / $total_count;
                $custom_weight = $custom_count / $total_count;
                
                $wizi_accuracy = (float)($wizi_result['avg_accuracy'] ?? 0);
                $custom_accuracy = (float)($custom_result['avg_accuracy'] ?? 0);
                $avg_accuracy = ($wizi_accuracy * $wizi_weight) + ($custom_accuracy * $custom_weight);
                
                $wizi_time = (float)($wizi_result['avg_time'] ?? 0);
                $custom_time = (float)($custom_result['avg_time'] ?? 0);
                $avg_time = ($wizi_time * $wizi_weight) + ($custom_time * $custom_weight);
            } else {
                $avg_accuracy = 0;
                $avg_time = 0;
            }
            
            $accuracy_trend = 'insufficient_data';
        }
        
        // Get recent performance for trend analysis (combine both systems)
        $recent_wizi_sql = "SELECT total_score as score, completed_at
            FROM wizi_quiz_user 
            WHERE user_id = ? AND attempt_status = 'completed' 
            ORDER BY completed_at DESC 
            LIMIT 3";
        
        $statement = $pdo->prepare($recent_wizi_sql);
        $statement->execute([$user_id]);
        $recent_wizi = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement = null;
        
        $recent_custom_sql = "SELECT 
            AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END) as score,
            MAX(q.completed_at) as completed_at
            FROM user_question uq
            JOIN quiz q ON uq.quiz_id = q.id
            WHERE q.user_id = ? AND q.quiz_status = 'completed'
            GROUP BY q.id
            ORDER BY q.completed_at DESC
            LIMIT 3";
        
        $statement = $pdo->prepare($recent_custom_sql);
        $statement->execute([$user_id]);
        $recent_custom = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement = null;
        
        // Merge and sort by date
        $recent = array_merge($recent_wizi, $recent_custom);
        usort($recent, function($a, $b) {
            return strtotime($b['completed_at']) - strtotime($a['completed_at']);
        });
        $recent = array_slice($recent, 0, 3);
        
        // Count consecutive low scores
        $consecutive_low = 0;
        foreach ($recent as $quiz) {
            if ($quiz['score'] < 50) {
                $consecutive_low++;
            } else {
                break;
            }
        }
        
        return [
            'current_accuracy_percentage' => round($avg_accuracy, 2),
            'average_accuracy_7days' => round($avg_accuracy, 2),
            'average_accuracy_30days' => round($avg_accuracy, 2),
            'accuracy_trend' => $accuracy_trend,
            'performance_category' => $this->categorize_performance($avg_accuracy),
            'average_time_per_question_seconds' => round($avg_time, 2),
            'speed_category' => $this->categorize_speed($avg_time),
            'consecutive_low_scores' => $consecutive_low
        ];
    }

    /**
     * Compute subject/topic analysis
     */
    private function compute_subject_analysis($user_id) {
        $summary = $this->user_performance_summary_model->get_latest_summary_by_user($user_id);
        
        if ($summary) {
            $subject_scores = json_decode($summary->subject_scores_json ?? '{}', true);
            $topic_scores = json_decode($summary->topic_scores_json ?? '{}', true);
            
            return [
                'strongest_subject' => $summary->strongest_overall_subject,
                'strongest_subject_accuracy' => round($summary->strongest_overall_subject ? 
                    ($subject_scores[$summary->strongest_overall_subject]['accuracy'] ?? 0) : 0, 2),
                'weakest_subject' => $summary->weakest_overall_subject,
                'weakest_subject_accuracy' => round($summary->weakest_overall_subject ?
                    ($subject_scores[$summary->weakest_overall_subject]['accuracy'] ?? 0) : 0, 2),
                'weakest_topics_json' => $this->get_weak_topics_json($topic_scores),
                'untouched_subjects_json' => $this->get_untouched_subjects_json($user_id),
                'subject_count_attempted' => count($subject_scores)
            ];
        }
        
        return [
            'strongest_subject' => null,
            'strongest_subject_accuracy' => 0,
            'weakest_subject' => null,
            'weakest_subject_accuracy' => 0,
            'weakest_topics_json' => null,
            'untouched_subjects_json' => $this->get_untouched_subjects_json($user_id),
            'subject_count_attempted' => 0
        ];
    }

    /**
     * Compute time patterns
     */
    private function compute_time_patterns($user_id) {
        // Combine time patterns from both quiz systems using UNION
        $sql = "
            SELECT hour, day_name, SUM(count) as count
            FROM (
                SELECT 
                    HOUR(completed_at) as hour,
                    DAYNAME(completed_at) as day_name,
                    COUNT(*) as count
                FROM wizi_quiz_user
                WHERE user_id = ? AND attempt_status = 'completed'
                GROUP BY hour, day_name
                
                UNION ALL
                
                SELECT 
                    HOUR(completed_at) as hour,
                    DAYNAME(completed_at) as day_name,
                    COUNT(*) as count
                FROM quiz
                WHERE user_id = ? AND quiz_status = 'completed' AND completed_at IS NOT NULL
                GROUP BY hour, day_name
            ) combined
            GROUP BY hour, day_name
            ORDER BY count DESC
            LIMIT 1";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute([$user_id, $user_id]); // Note: user_id twice for UNION
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        $most_active_hour = $result ? (int)$result['hour'] : null;
        $most_active_day = $result ? $result['day_name'] : null;
        
        // Determine study time preference
        $preferred_time = 'optimal';
        if ($most_active_hour !== null) {
            if ($most_active_hour >= 5 && $most_active_hour < 12) {
                $preferred_time = 'morning';
            } elseif ($most_active_hour >= 12 && $most_active_hour < 17) {
                $preferred_time = 'afternoon';
            } elseif ($most_active_hour >= 17 && $most_active_hour < 22) {
                $preferred_time = 'evening';
            } else {
                $preferred_time = 'night';
            }
        }
        
        return [
            'most_active_hour' => $most_active_hour,
            'most_active_day_of_week' => $most_active_day,
            'preferred_study_time' => $preferred_time
        ];
    }

    /**
     * Compute engagement metrics
     */
    private function compute_engagement_metrics($user_id, $activity_data) {
        $pdo = CDatabase::getPdo();
        
        // Count mock tests from wizi_quiz_user
        $statement = $pdo->prepare("SELECT COUNT(*) as count FROM wizi_quiz_user WHERE user_id = ? AND attempt_status = 'completed'");
        $statement->execute([$user_id]);
        $mock_count = $statement->fetch(PDO::FETCH_ASSOC)['count'];
        $statement = null;
        
        // Count custom/PYQ quizzes from quiz table
        $statement = $pdo->prepare("SELECT COUNT(*) as count FROM quiz WHERE user_id = ? AND quiz_status = 'completed'");
        $statement->execute([$user_id]);
        $custom_count = $statement->fetch(PDO::FETCH_ASSOC)['count'];
        $statement = null;
        
        // Combined total from both quiz systems
        $total_quizzes = (int)$mock_count + (int)$custom_count;
        
        $weeks_active = max(1, floor($activity_data['days_since_first_quiz'] / 7));
        $avg_per_week = round($total_quizzes / $weeks_active, 2);
        
        // Determine engagement level
        $engagement_level = 'inactive';
        if ($activity_data['days_since_last_quiz'] <= 1) {
            $engagement_level = 'highly_active';
        } elseif ($activity_data['days_since_last_quiz'] <= 3) {
            $engagement_level = 'active';
        } elseif ($activity_data['days_since_last_quiz'] <= 7) {
            $engagement_level = 'moderate';
        } elseif ($activity_data['days_since_last_quiz'] <= 14) {
            $engagement_level = 'low';
        }
        
        return [
            'total_quizzes_all_time' => $total_quizzes,
            'avg_quizzes_per_week' => $avg_per_week,
            'engagement_level' => $engagement_level
        ];
    }

    /**
     * Compute next milestone
     */
    private function compute_next_milestone($user_id, $activity_data, $performance_data) {
        $pdo = CDatabase::getPdo();
        
        // Count mock tests from wizi_quiz_user
        $statement = $pdo->prepare("SELECT COUNT(*) as count FROM wizi_quiz_user WHERE user_id = ? AND attempt_status = 'completed'");
        $statement->execute([$user_id]);
        $mock_count = $statement->fetch(PDO::FETCH_ASSOC)['count'];
        $statement = null;
        
        // Count custom/PYQ quizzes from quiz table
        $statement = $pdo->prepare("SELECT COUNT(*) as count FROM quiz WHERE user_id = ? AND quiz_status = 'completed'");
        $statement->execute([$user_id]);
        $custom_count = $statement->fetch(PDO::FETCH_ASSOC)['count'];
        $statement = null;
        
        // Combined total from both quiz systems
        $total_quizzes = (int)$mock_count + (int)$custom_count;
        
        // Quiz count milestones
        $quiz_milestones = [5, 10, 25, 50, 100, 250, 500, 1000];
        $next_quiz_milestone = null;
        
        foreach ($quiz_milestones as $threshold) {
            if ($total_quizzes < $threshold) {
                $next_quiz_milestone = [
                    'type' => 'quiz_count',
                    'name' => $this->get_milestone_name('quiz', $threshold),
                    'progress' => $total_quizzes,
                    'target' => $threshold,
                    'percentage' => round(($total_quizzes / $threshold) * 100, 2)
                ];
                break;
            }
        }
        
        // Get total milestones achieved
        $statement = $pdo->prepare("SELECT COUNT(*) as count FROM user_milestones WHERE user_id = ?");
        $statement->execute([$user_id]);
        $milestones_achieved = $statement->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        $statement = null;
        
        $statement = $pdo->prepare("SELECT achieved_at FROM user_milestones WHERE user_id = ? ORDER BY achieved_at DESC LIMIT 1");
        $statement->execute([$user_id]);
        $last_milestone = $statement->fetch(PDO::FETCH_OBJ);
        $statement = null;
        
        if ($next_quiz_milestone) {
            return [
                'next_milestone_type' => $next_quiz_milestone['type'],
                'next_milestone_name' => $next_quiz_milestone['name'],
                'next_milestone_progress' => $next_quiz_milestone['progress'],
                'next_milestone_target' => $next_quiz_milestone['target'],
                'next_milestone_percentage' => $next_quiz_milestone['percentage'],
                'milestones_achieved_total' => $milestones_achieved,
                'last_milestone_achieved_date' => $last_milestone ? $last_milestone->achieved_at : null
            ];
        }
        
        return [
            'next_milestone_type' => null,
            'next_milestone_name' => null,
            'next_milestone_progress' => 0,
            'next_milestone_target' => 0,
            'next_milestone_percentage' => 0,
            'milestones_achieved_total' => $milestones_achieved,
            'last_milestone_achieved_date' => $last_milestone ? $last_milestone->achieved_at : null
        ];
    }

    /**
     * Get subscription context
     */
    private function get_subscription_context($user) {
        $subscription_type = $user->subscription_type ?? 'free';
        
        // Get custom quiz quota
        $custom_quiz_count = $this->quiz_model->get_custom_quiz_count_by_user($user->id);
        
        // Get quota limit based on subscription
        $quota_limits = [
            'free' => 20,
            'basic' => 999999,
            'premium' => 999999,
            'pro' => 999999,
            'admin' => 999999
        ];
        
        $quota_limit = $quota_limits[$subscription_type] ?? 20;
        $quota_remaining = max(0, $quota_limit - $custom_quiz_count);
        $quota_percentage = $quota_limit > 0 ? round(($custom_quiz_count / $quota_limit) * 100, 2) : 0;
        
        $expires_in_days = null;
        if ($user->subscription_expires_at) {
            $expires_in_days = floor((strtotime($user->subscription_expires_at) - time()) / 86400);
        }
        
        return [
            'subscription_type' => $subscription_type,
            'custom_quiz_quota_used' => $custom_quiz_count,
            'custom_quiz_quota_limit' => $quota_limit,
            'custom_quiz_quota_remaining' => $quota_remaining,
            'custom_quiz_quota_percentage' => $quota_percentage,
            'subscription_expires_in_days' => $expires_in_days,
            'is_quota_exhausted' => $quota_remaining <= 0 ? 1 : 0,
            'approaching_quota_limit' => $quota_percentage >= 80 ? 1 : 0
        ];
    }

    /**
     * Get notification history
     */
    private function get_notification_history($user_id) {
        $sql = "
            SELECT 
                MAX(CASE WHEN n.type = 'custom_quiz_suggestion' THEN nr.created_at END) as last_custom_quiz_suggestion,
                MAX(CASE WHEN n.type = 'pyq_suggestion' THEN nr.created_at END) as last_pyq_suggestion,
                MAX(CASE WHEN n.type = 'mock_suggestion' THEN nr.created_at END) as last_mock_suggestion,
                MAX(CASE WHEN n.type = 'motivational_message' THEN nr.created_at END) as last_motivational,
                MAX(CASE WHEN n.type = 'study_reminder' THEN nr.created_at END) as last_reminder,
                MAX(CASE WHEN n.type = 'milestone_achievement' THEN nr.created_at END) as last_milestone,
                COUNT(CASE WHEN DATE(nr.created_at) = CURDATE() THEN 1 END) as today_count,
                COUNT(CASE WHEN nr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_count,
                COUNT(CASE WHEN nr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_count,
                MAX(CASE WHEN nr.opened_at IS NOT NULL THEN nr.opened_at END) as last_opened,
                COUNT(CASE WHEN nr.opened_at IS NOT NULL THEN 1 END) as opened_count,
                COUNT(*) as total_count
            FROM notification_recipients nr
            JOIN notifications n ON nr.notification_id = n.id
            WHERE nr.user_id = ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute([$user_id]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        $open_rate = $result && $result['total_count'] > 0 
            ? round(($result['opened_count'] / $result['total_count']) * 100, 2) 
            : 0;
        
        return [
            'last_custom_quiz_suggestion_sent' => $result['last_custom_quiz_suggestion'] ?? null,
            'last_pyq_suggestion_sent' => $result['last_pyq_suggestion'] ?? null,
            'last_mock_suggestion_sent' => $result['last_mock_suggestion'] ?? null,
            'last_motivational_sent' => $result['last_motivational'] ?? null,
            'last_reminder_sent' => $result['last_reminder'] ?? null,
            'last_milestone_notification_sent' => $result['last_milestone'] ?? null,
            'last_quota_warning_sent' => null,
            'total_notifications_sent_today' => (int)($result['today_count'] ?? 0),
            'total_notifications_sent_7days' => (int)($result['week_count'] ?? 0),
            'total_notifications_sent_30days' => (int)($result['month_count'] ?? 0),
            'last_notification_opened_date' => $result['last_opened'] ?? null,
            'notification_open_rate' => $open_rate
        ];
    }

    /**
     * Compute AI context and suggested actions
     */
    private function compute_ai_context($activity, $quiz_distribution, $performance, $subject_analysis, $engagement) {
        // Determine AI persona
        $accuracy = $performance['current_accuracy_percentage'];
        $total_quizzes = $engagement['total_quizzes_all_time'];
        
        $persona = 'beginner';
        if ($total_quizzes >= 50 && $accuracy >= 85) {
            $persona = 'expert';
        } elseif ($total_quizzes >= 20 && $accuracy >= 70) {
            $persona = 'advanced';
        } elseif ($total_quizzes >= 5 && $accuracy >= 50) {
            $persona = 'intermediate';
        }
        
        // Determine motivation level
        $motivation = 'steady';
        if ($performance['accuracy_trend'] == 'declining' || $performance['consecutive_low_scores'] >= 2) {
            $motivation = 'needs_encouragement';
        } elseif ($performance['accuracy_trend'] == 'improving' && $activity['quiz_streak_days'] >= 3) {
            $motivation = 'highly_motivated';
        }
        
        // Suggest action
        $suggested_action = 'custom_quiz';
        $reason = '';
        
        if ($activity['days_since_last_quiz'] >= 3) {
            $suggested_action = 'reminder';
            $reason = 'User inactive for ' . $activity['days_since_last_quiz'] . ' days';
        } elseif ($subject_analysis['weakest_subject'] && $accuracy < 70) {
            $suggested_action = 'custom_quiz';
            $reason = 'Practice weak subject: ' . $subject_analysis['weakest_subject'];
        } elseif ($accuracy >= 75 && $quiz_distribution['never_tried_pyq']) {
            $suggested_action = 'pyq';
            $reason = 'Ready for previous year questions';
        } elseif ($total_quizzes >= 20 && $quiz_distribution['never_tried_mock']) {
            $suggested_action = 'mock_test';
            $reason = 'Time for full mock test';
        }
        
        // Build context summary
        $context_summary = sprintf(
            "User: %s level, %s motivation. %d quizzes, %.1f%% accuracy. %s",
            $persona,
            $motivation,
            $total_quizzes,
            $accuracy,
            $reason
        );
        
        // Personalization tags
        $tags = [];
        if ($performance['accuracy_trend'] == 'improving') $tags[] = 'improving';
        if ($performance['accuracy_trend'] == 'declining') $tags[] = 'struggling';
        if ($activity['quiz_streak_days'] >= 7) $tags[] = 'consistent';
        if ($engagement['engagement_level'] == 'highly_active') $tags[] = 'active';
        if ($total_quizzes < 5) $tags[] = 'new_user';
        
        return [
            'ai_persona_category' => $persona,
            'motivation_level' => $motivation,
            'suggested_action' => $suggested_action,
            'suggested_action_reason' => $reason,
            'ai_context_summary' => $context_summary,
            'personalization_tags_json' => json_encode($tags)
        ];
    }

    /**
     * Compute behavioral flags
     */
    private function compute_behavioral_flags($user, $activity, $quiz_distribution, $performance, $engagement, $notification_history) {
        $days_inactive = $activity['days_since_last_quiz'];
        $total_quizzes = $engagement['total_quizzes_all_time'];
        $accuracy = $performance['current_accuracy_percentage'];
        
        // Calculate days since registration
        $registration_date = strtotime($user->created_at ?? 'now');
        $days_since_registration = floor((time() - $registration_date) / 86400);
        
        // Active: Registered within 6 months (180 days) OR performed quiz within 90 days
        $registered_recently = $days_since_registration <= 180;
        $quiz_recently = $days_inactive <= 90;
        $is_active = ($registered_recently || $quiz_recently) ? 1 : 0;
        
        // Dormant: Does not meet active criteria
        $is_dormant = $is_active ? 0 : 1;
        
        // Inactive for backward compatibility (between 4-90 days since last quiz)
        $is_inactive = ($days_inactive > 3 && $days_inactive <= 90) ? 1 : 0;
        
        $is_new = $total_quizzes < 5 ? 1 : 0;
        $is_at_risk = ($total_quizzes >= 10 && $days_inactive >= 30) ? 1 : 0;
        $is_high_performer = ($accuracy >= 80 && $total_quizzes >= 5) ? 1 : 0;
        $needs_support = ($accuracy < 50 || $performance['consecutive_low_scores'] >= 2) ? 1 : 0;
        $ready_for_challenge = ($is_high_performer && $quiz_distribution['never_tried_mock']) ? 1 : 0;
        
        // Check notification eligibility
        $eligible = ($notification_history['total_notifications_sent_today'] < 3) ? 1 : 0;
        
        return [
            'is_new_user' => $is_new,
            'is_active_user' => $is_active,
            'is_inactive_user' => $is_inactive,
            'is_dormant_user' => $is_dormant,
            'is_at_risk' => $is_at_risk,
            'is_high_performer' => $is_high_performer,
            'needs_support' => $needs_support,
            'ready_for_challenge' => $ready_for_challenge,
            'eligible_for_notification' => $eligible
        ];
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Calculate quiz streak
     */
    private function calculate_quiz_streak($user_id) {
        $pdo = CDatabase::getPdo();
        
        // Get dates from mock tests (wizi_quiz_user)
        $wizi_sql = "SELECT DATE(completed_at) as quiz_date
            FROM wizi_quiz_user
            WHERE user_id = ? AND attempt_status = 'completed'";
        
        $statement = $pdo->prepare($wizi_sql);
        $statement->execute([$user_id]);
        $wizi_dates = $statement->fetchAll(PDO::FETCH_COLUMN);
        $statement = null;
        
        // Get dates from custom/PYQ quizzes (quiz)
        $quiz_sql = "SELECT DATE(completed_at) as quiz_date
            FROM quiz
            WHERE user_id = ? AND quiz_status = 'completed' AND completed_at IS NOT NULL";
        
        $statement = $pdo->prepare($quiz_sql);
        $statement->execute([$user_id]);
        $quiz_dates = $statement->fetchAll(PDO::FETCH_COLUMN);
        $statement = null;
        
        // Combine, deduplicate by date, and sort descending
        $all_dates = array_unique(array_merge($wizi_dates, $quiz_dates));
        rsort($all_dates); // Sort descending (most recent first)
        $all_dates = array_slice($all_dates, 0, 90); // Limit to 90 days
        
        // Convert to expected format for streak calculation
        $dates = array_map(function($date) {
            return ['quiz_date' => $date];
        }, $all_dates);
        
        if (empty($dates)) {
            return ['days' => 0, 'broken_date' => null];
        }
        
        $streak = 0;
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        $broken_date = null;
        
        foreach ($dates as $row) {
            $date = $row['quiz_date'];
            
            if ($streak == 0) {
                if ($date == $today || $date == $yesterday) {
                    $streak = 1;
                    $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
                } else {
                    break;
                }
            } else {
                if ($date == $yesterday) {
                    $streak++;
                    $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));
                } else {
                    $broken_date = $yesterday;
                    break;
                }
            }
        }
        
        return ['days' => $streak, 'broken_date' => $broken_date];
    }

    /**
     * Categorize performance
     */
    private function categorize_performance($accuracy) {
        if ($accuracy >= 85) return 'excellent';
        if ($accuracy >= 70) return 'good';
        if ($accuracy >= 50) return 'average';
        if ($accuracy > 0) return 'needs_improvement';
        return 'beginner';
    }

    /**
     * Categorize speed
     */
    private function categorize_speed($avg_time_seconds) {
        if ($avg_time_seconds < 20) return 'very_fast';
        if ($avg_time_seconds < 40) return 'fast';
        if ($avg_time_seconds < 60) return 'optimal';
        if ($avg_time_seconds < 90) return 'slow';
        return 'very_slow';
    }

    /**
     * Get milestone name
     */
    private function get_milestone_name($type, $threshold) {
        $names = [
            'quiz' => [
                5 => 'Quiz Starter',
                10 => 'Quiz Enthusiast',
                25 => 'Quiz Warrior',
                50 => 'Quiz Champion',
                100 => 'Quiz Legend',
                250 => 'Quiz Master',
                500 => 'Quiz Grandmaster',
                1000 => 'Quiz Immortal'
            ]
        ];
        
        return $names[$type][$threshold] ?? "Milestone $threshold";
    }

    /**
     * Get weak topics as JSON
     */
    private function get_weak_topics_json($topic_scores) {
        if (empty($topic_scores)) return null;
        
        $weak_topics = [];
        foreach ($topic_scores as $topic => $data) {
            if (isset($data['accuracy']) && $data['accuracy'] < 60) {
                $weak_topics[] = [
                    'topic' => $topic,
                    'accuracy' => $data['accuracy']
                ];
            }
        }
        
        usort($weak_topics, function($a, $b) {
            return $a['accuracy'] <=> $b['accuracy'];
        });
        
        return json_encode(array_slice($weak_topics, 0, 5));
    }

    /**
     * Get untouched subjects
     */
    private function get_untouched_subjects_json($user_id) {
        $all_subjects_sql = "SELECT DISTINCT subject FROM subject WHERE subject IS NOT NULL";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($all_subjects_sql);
        $statement->execute();
        $all_subjects = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement = null;
        
        $attempted_sql = "SELECT DISTINCT s.subject
            FROM user_question uq
            JOIN question q ON uq.question_id = q.id
            JOIN subject s ON q.subject_id = s.id
            WHERE uq.user_id = ?";
        
        $statement = $pdo->prepare($attempted_sql);
        $statement->execute([$user_id]);
        $attempted = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement = null;
        $attempted_names = array_column($attempted, 'subject');
        
        $untouched = [];
        foreach ($all_subjects as $subject) {
            if (!in_array($subject['subject'], $attempted_names)) {
                $untouched[] = $subject['subject'];
            }
        }
        
        return empty($untouched) ? null : json_encode(array_slice($untouched, 0, 10));
    }

    /**
     * Get active user IDs
     */
    private function _get_active_user_ids($limit) {
        try {
            $limit = (int)$limit; // Ensure limit is an integer
            
            // Note: LIMIT must be an integer, not a bound parameter in some PDO drivers
            $sql = "SELECT id 
                    FROM user 
                    WHERE notification_enabled = 1 
                      AND fcm_token IS NOT NULL 
                      AND is_deleted = 0
                    ORDER BY last_activity DESC
                    LIMIT " . $limit;
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $results = $statement->fetchAll(PDO::FETCH_COLUMN);
            $statement = null;
            
            return $results;
        } catch (Exception $e) {
            log_message('error', 'Error fetching active user IDs: ' . $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // HELPER METHODS - User Analysis for Eligible Users Preview
    // ========================================================================

    private function _analyze_custom_quiz_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        $weak_subjects = [];
        $personas = [];
        
        foreach ($users as $user) {
            if ($user->weakest_subject) {
                $weak_subjects[] = $user->weakest_subject;
            }
            if ($user->ai_persona_category) {
                $personas[] = $user->ai_persona_category;
            }
        }
        
        $subject_counts = array_count_values($weak_subjects);
        arsort($subject_counts);
        
        $persona_counts = array_count_values($personas);
        arsort($persona_counts);
        
        return [
            'top_weak_subjects' => array_slice($subject_counts, 0, 5, true),
            'persona_distribution' => $persona_counts,
            'avg_accuracy' => round(array_sum(array_column($users, 'current_accuracy_percentage')) / count($users), 1) . '%'
        ];
    }

    private function _analyze_pyq_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        $performance_categories = [];
        
        foreach ($users as $user) {
            if ($user->performance_category) {
                $performance_categories[] = $user->performance_category;
            }
        }
        
        $category_counts = array_count_values($performance_categories);
        
        return [
            'performance_distribution' => $category_counts,
            'avg_total_quizzes' => round(array_sum(array_column($users, 'total_quizzes_all_time')) / count($users)),
            'avg_accuracy' => round(array_sum(array_column($users, 'current_accuracy_percentage')) / count($users), 1) . '%'
        ];
    }

    private function _analyze_mock_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        return [
            'avg_total_quizzes' => round(array_sum(array_column($users, 'total_quizzes_all_time')) / count($users)),
            'avg_accuracy' => round(array_sum(array_column($users, 'current_accuracy_percentage')) / count($users), 1) . '%',
            'high_performers' => count(array_filter($users, function($u) { return $u->is_high_performer == 1; }))
        ];
    }

    private function _analyze_inactive_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        $inactive_ranges = [
            '3-7 days' => 0,
            '8-14 days' => 0,
            '15-30 days' => 0,
            '30+ days' => 0
        ];
        
        foreach ($users as $user) {
            $days = $user->days_since_last_quiz;
            if ($days <= 7) {
                $inactive_ranges['3-7 days']++;
            } elseif ($days <= 14) {
                $inactive_ranges['8-14 days']++;
            } elseif ($days <= 30) {
                $inactive_ranges['15-30 days']++;
            } else {
                $inactive_ranges['30+ days']++;
            }
        }
        
        return [
            'inactive_duration_breakdown' => $inactive_ranges,
            'avg_previous_streak' => round(array_sum(array_column($users, 'quiz_streak_days')) / count($users)),
            'avg_total_quizzes' => round(array_sum(array_column($users, 'total_quizzes_all_time')) / count($users))
        ];
    }

    private function _analyze_motivational_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        $performance_categories = [];
        
        foreach ($users as $user) {
            if ($user->performance_category) {
                $performance_categories[] = $user->performance_category;
            }
        }
        
        $category_counts = array_count_values($performance_categories);
        
        return [
            'performance_distribution' => $category_counts,
            'avg_accuracy' => round(array_sum(array_column($users, 'current_accuracy_percentage')) / count($users), 1) . '%',
            'users_with_declining_trend' => count(array_filter($users, function($u) { 
                return isset($u->recent_performance_trend) && $u->recent_performance_trend == 'declining'; 
            }))
        ];
    }

    private function _analyze_milestone_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        $milestones = [];
        
        foreach ($users as $user) {
            if ($user->next_milestone_name) {
                $milestones[] = $user->next_milestone_name;
            }
        }
        
        $milestone_counts = array_count_values($milestones);
        arsort($milestone_counts);
        
        return [
            'milestone_distribution' => $milestone_counts,
            'avg_total_quizzes' => round(array_sum(array_column($users, 'total_quizzes_all_time')) / count($users)),
            'avg_accuracy' => round(array_sum(array_column($users, 'current_accuracy_percentage')) / count($users), 1) . '%'
        ];
    }

    private function _analyze_quota_users($users) {
        if (empty($users)) {
            return ['message' => 'No users available'];
        }

        $quota_ranges = [
            '80-85%' => 0,
            '86-90%' => 0,
            '91-95%' => 0,
            '96-100%' => 0
        ];
        
        foreach ($users as $user) {
            $usage = $user->custom_quiz_quota_used_percentage;
            if ($usage >= 80 && $usage <= 85) {
                $quota_ranges['80-85%']++;
            } elseif ($usage <= 90) {
                $quota_ranges['86-90%']++;
            } elseif ($usage <= 95) {
                $quota_ranges['91-95%']++;
            } else {
                $quota_ranges['96-100%']++;
            }
        }
        
        return [
            'quota_usage_breakdown' => $quota_ranges,
            'avg_remaining' => round(array_sum(array_column($users, 'custom_quiz_quota_remaining')) / count($users)),
            'total_custom_quizzes_taken' => array_sum(array_column($users, 'total_custom_quizzes'))
        ];
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Get relative time string (e.g., "2 hours ago", "5 minutes ago")
     */
    private function _get_relative_time($timestamp) {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return $diff . ' second' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 60);
        if ($diff < 60) {
            return $diff . ' minute' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 60);
        if ($diff < 24) {
            return $diff . ' hour' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 24);
        if ($diff < 30) {
            return $diff . ' day' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 30);
        return $diff . ' month' . ($diff != 1 ? 's' : '') . ' ago';
    }

    /**
     * Format duration in seconds to human-readable string
     */
    private function _format_duration($seconds) {
        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        }
        
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . 'm ' . round($secs) . 's';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        return $hours . 'h ' . $mins . 'm';
    }

    /**
     * Add build record to history (stored in cache)
     */
    private function _add_to_build_history($build_data) {
        $history = $this->cache->get('notification_context_build_history');
        
        if (!$history) {
            $history = [];
        }
        
        // Add new build to beginning of array
        array_unshift($history, $build_data);
        
        // Keep only last 50 builds
        $history = array_slice($history, 0, 50);
        
        // Save back to cache (30 days TTL)
        $this->cache->save('notification_context_build_history', $history, 2592000);
    }
}
