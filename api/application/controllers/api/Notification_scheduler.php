<?php

/**
 * Notification Scheduler Controller
 * 
 * Orchestrates automated notification sending based on user context data.
 * Each method targets a specific notification type with intelligent user selection.
 * 
 * Endpoints:
 * - POST /api/notification-scheduler/send-custom-quiz - Daily custom quiz suggestions
 * - POST /api/notification-scheduler/send-pyq - PYQ suggestions
 * - POST /api/notification-scheduler/send-mock - Mock test suggestions
 * - POST /api/notification-scheduler/send-inactivity - Inactivity reminders
 * - POST /api/notification-scheduler/send-motivational - Motivational messages
 * - POST /api/notification-scheduler/send-milestones - Milestone achievements
 * - POST /api/notification-scheduler/send-quota-warnings - Quota limit warnings
 * - POST /api/notification-scheduler/send-all - Send all eligible notifications
 * 
 * @package WiziAI
 * @subpackage Controllers/API
 * @category Notification
 */
class Notification_scheduler extends API_Controller {

    private $batch_size = 100;
    private $execution_start_time;

    public function __construct() {
        parent::__construct();
        
        // Load models
        $this->load->model('notification/notification_context_model');
        $this->load->model('notification/notification_model');
        $this->load->model('user/user_model');
        
        // Load services
        $this->load->library('notification_service');
        $this->load->library('ai_notification_service');
    }

    /**
     * Send custom quiz suggestions
     * POST /api/notification-scheduler/send-custom-quiz
     * 
     * Targets: Active users with identified weak subjects
     * Timing: Daily at 9 AM
     */
    public function send_custom_quiz_post() {
        try {
            // Admin only for manual triggers
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $limit = $request['limit'] ?? $this->batch_size;
            
            // Get eligible users
            $users = $this->notification_context_model->get_users_for_custom_quiz_suggestion($limit);
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No eligible users for custom quiz suggestions',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'custom_quiz_suggestion',
                'Custom Quiz Suggestions'
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send custom quiz error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send PYQ suggestions
     * POST /api/notification-scheduler/send-pyq
     * 
     * Targets: High performers who haven't tried PYQ
     * Timing: Monday/Wednesday/Friday at 10 AM
     */
    public function send_pyq_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $limit = $request['limit'] ?? 50;
            
            // Get eligible users
            $users = $this->notification_context_model->get_users_for_pyq_suggestion(10, $limit);
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No eligible users for PYQ suggestions',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'pyq_suggestion',
                'PYQ Suggestions'
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send PYQ error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send mock test suggestions
     * POST /api/notification-scheduler/send-mock
     * 
     * Targets: Experienced users ready for full mock tests
     * Timing: Weekly (Saturday at 10 AM)
     */
    public function send_mock_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $limit = $request['limit'] ?? 50;
            
            // Get users ready for mock tests
            $users = $this->notification_context_model->get_users_with_filters([
                'performance_category' => 'good',
                'min_quizzes' => 20,
                'is_high_performer' => 1
            ], $limit);
            
            // Filter users who haven't tried mock tests
            $users = array_filter($users, function($user) {
                return $user->never_tried_mock == 1;
            });
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No eligible users for mock test suggestions',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'mock_suggestion',
                'Mock Test Suggestions'
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send mock test error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send inactivity reminders
     * POST /api/notification-scheduler/send-inactivity
     * 
     * Targets: Users inactive for 3+ days
     * Timing: Daily at 6 PM
     */
    public function send_inactivity_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $days_inactive = $request['days_inactive'] ?? 3;
            $limit = $request['limit'] ?? 200;
            
            // Get inactive users
            $users = $this->notification_context_model->get_inactive_users($days_inactive, $limit);
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No inactive users to remind',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'inactivity_reminder',
                'Inactivity Reminders'
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send inactivity reminders error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send motivational messages
     * POST /api/notification-scheduler/send-motivational
     * 
     * Targets: Users needing encouragement
     * Timing: Daily at 11 AM (alternate days)
     */
    public function send_motivational_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $limit = $request['limit'] ?? 100;
            
            // Get users needing encouragement
            $users = $this->notification_context_model->get_users_needing_encouragement($limit);
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No users needing motivational messages',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'motivational_message',
                'Motivational Messages',
                ['trigger' => 'daily']
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send motivational messages error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send milestone achievement notifications
     * POST /api/notification-scheduler/send-milestones
     * 
     * Targets: Users who achieved milestones
     * Timing: Hourly check or real-time after quiz completion
     */
    public function send_milestones_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $limit = $request['limit'] ?? 50;
            
            // Get users near milestone (100% = just achieved)
            $users = $this->notification_context_model->get_users_near_milestone(100.0, $limit);
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No milestone achievements to notify',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'milestone_achievement',
                'Milestone Achievements'
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send milestone notifications error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send quota warning notifications
     * POST /api/notification-scheduler/send-quota-warnings
     * 
     * Targets: Free users approaching quota limit
     * Timing: Weekly (Sunday at 8 PM)
     */
    public function send_quota_warnings_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $request = $this->get_request();
            $threshold = $request['threshold'] ?? 80.0;
            $limit = $request['limit'] ?? 100;
            
            // Get users approaching quota
            $users = $this->notification_context_model->get_users_approaching_quota($threshold, $limit);
            
            if (empty($users)) {
                $this->response([
                    'success' => true,
                    'message' => 'No users approaching quota limit',
                    'sent' => 0
                ], 200);
                return;
            }
            
            $result = $this->send_notifications_batch(
                $users,
                'quota_warning',
                'Quota Warnings'
            );
            
            $this->response($result, 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send quota warnings error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send all eligible notifications (master scheduler)
     * POST /api/notification-scheduler/send-all
     * 
     * Runs multiple notification types in sequence
     * Timing: Can be run once daily to handle all types
     */
    public function send_all_post() {
        try {
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return;
            }

            $this->execution_start_time = microtime(true);
            
            $results = [];
            
            // 1. Custom quiz suggestions
            $custom_quiz_users = $this->notification_context_model->get_users_for_custom_quiz_suggestion(50);
            if (!empty($custom_quiz_users)) {
                $results['custom_quiz'] = $this->send_notifications_batch(
                    $custom_quiz_users,
                    'custom_quiz_suggestion',
                    'Custom Quiz'
                );
            }
            
            // 2. Inactivity reminders
            $inactive_users = $this->notification_context_model->get_inactive_users(3, 100);
            if (!empty($inactive_users)) {
                $results['inactivity'] = $this->send_notifications_batch(
                    $inactive_users,
                    'inactivity_reminder',
                    'Inactivity'
                );
            }
            
            // 3. Motivational messages
            $motivational_users = $this->notification_context_model->get_users_needing_encouragement(50);
            if (!empty($motivational_users)) {
                $results['motivational'] = $this->send_notifications_batch(
                    $motivational_users,
                    'motivational_message',
                    'Motivational',
                    ['trigger' => 'daily']
                );
            }
            
            // 4. Milestone achievements
            $milestone_users = $this->notification_context_model->get_users_near_milestone(100.0, 30);
            if (!empty($milestone_users)) {
                $results['milestones'] = $this->send_notifications_batch(
                    $milestone_users,
                    'milestone_achievement',
                    'Milestones'
                );
            }
            
            // Calculate totals
            $total_sent = 0;
            $total_failed = 0;
            
            foreach ($results as $type => $result) {
                $total_sent += $result['sent'];
                $total_failed += $result['failed'];
            }
            
            $duration = round(microtime(true) - $this->execution_start_time, 2);
            
            $this->response([
                'success' => true,
                'message' => 'All notifications processed',
                'summary' => [
                    'total_sent' => $total_sent,
                    'total_failed' => $total_failed,
                    'duration_seconds' => $duration
                ],
                'details' => $results
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send all notifications error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification for single user (for testing or real-time triggers)
     * POST /api/notification-scheduler/send-single
     * 
     * Body: { "user_id": 123, "notification_type": "custom_quiz_suggestion" }
     */
    public function send_single_post() {
        try {
            $user = $this->require_jwt_auth();
            if (!$user) {
                return;
            }

            $request = $this->get_request();
            $target_user_id = $request['user_id'] ?? $user->id;
            $notification_type = $request['notification_type'] ?? 'motivational_message';
            $additional_data = $request['additional_data'] ?? [];
            
            // Non-admin users can only send to themselves
            if ($user->role_id != 1 && $target_user_id != $user->id) {
                $this->response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
                return;
            }
            
            // Get user context
            $context = $this->notification_context_model->get_user_context($target_user_id);
            
            if (!$context) {
                $this->response([
                    'success' => false,
                    'message' => 'User context not found. Please build context first.'
                ], 404);
                return;
            }
            
            // Check eligibility
            if ($context->eligible_for_notification != 1) {
                $this->response([
                    'success' => false,
                    'message' => 'User not eligible for notifications (daily limit reached or notifications disabled)'
                ], 400);
                return;
            }
            
            // Get user for FCM token
            $target_user = $this->user_model->get_user($target_user_id);
            
            if (!$target_user || !$target_user->fcm_token) {
                $this->response([
                    'success' => false,
                    'message' => 'User FCM token not found'
                ], 404);
                return;
            }
            
            // Generate notification
            $notification = $this->ai_notification_service->generate_notification(
                $context,
                $notification_type,
                $additional_data
            );
            
            // Send notification
            $fcm_result = $this->notification_service->send_notification(
                $target_user->fcm_token,
                $notification['title'],
                $notification['body'],
                array_merge(
                    $notification['deep_link_data'],
                    ['screen' => $notification['deep_link_screen']]
                )
            );
            
            // Create notification record
            $notification_data = [
                'title' => $notification['title'],
                'body' => $notification['body'],
                'type' => $notification_type,
                'target_type' => 'specific_user',
                'target_user_id' => $target_user_id,
                'deep_link_screen' => $notification['deep_link_screen'],
                'deep_link_data' => json_encode($notification['deep_link_data']),
                'created_by' => $user->id,
                'status' => $fcm_result['success'] ? 'sent' : 'failed'
            ];
            
            $notification_id = $this->notification_model->create_notification($notification_data);
            
            // Log recipient
            if ($notification_id) {
                $this->notification_model->create_notification_recipient([
                    'notification_id' => $notification_id,
                    'user_id' => $target_user_id,
                    'fcm_token' => $target_user->fcm_token,
                    'delivery_status' => $fcm_result['success'] ? 'sent' : 'failed',
                    'delivery_response' => json_encode($fcm_result)
                ]);
                
                // Update notification counter
                if ($fcm_result['success']) {
                    $this->notification_context_model->increment_notification_counter(
                        $target_user_id,
                        $notification_type
                    );
                }
            }
            
            $this->response([
                'success' => true,
                'message' => 'Notification sent',
                'data' => [
                    'notification_id' => $notification_id,
                    'delivery_status' => $fcm_result['success'] ? 'sent' : 'failed',
                    'notification' => $notification,
                    'fcm_result' => $fcm_result
                ]
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Send single notification error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Send notifications to batch of users
     * 
     * @param array $users Array of user context objects
     * @param string $notification_type Type of notification
     * @param string $log_label Label for logging
     * @param array $additional_data Additional data for AI generation
     * @return array Statistics of sent/failed notifications
     */
    private function send_notifications_batch($users, $notification_type, $log_label, $additional_data = []) {
        $sent = 0;
        $failed = 0;
        $errors = [];
        $notification_ids = [];
        
        log_message('info', "Starting {$log_label} notifications for " . count($users) . " users");
        
        foreach ($users as $context) {
            try {
                // Get user details
                $user = $this->user_model->get_user($context->user_id);
                
                if (!$user || !$user->fcm_token) {
                    $failed++;
                    continue;
                }
                
                // Generate personalized notification
                $notification = $this->ai_notification_service->generate_notification(
                    $context,
                    $notification_type,
                    $additional_data
                );
                
                // Send notification
                $fcm_result = $this->notification_service->send_notification(
                    $user->fcm_token,
                    $notification['title'],
                    $notification['body'],
                    array_merge(
                        $notification['deep_link_data'],
                        ['screen' => $notification['deep_link_screen']]
                    )
                );
                
                // Create notification record
                $notification_data = [
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'type' => $notification_type,
                    'target_type' => 'specific_user',
                    'target_user_id' => $context->user_id,
                    'deep_link_screen' => $notification['deep_link_screen'],
                    'deep_link_data' => json_encode($notification['deep_link_data']),
                    'created_by' => 1, // System user
                    'status' => $fcm_result['success'] ? 'sent' : 'failed'
                ];
                
                $notification_id = $this->notification_model->create_notification($notification_data);
                
                if ($notification_id) {
                    $notification_ids[] = $notification_id;
                    
                    // Log recipient
                    $this->notification_model->create_notification_recipient([
                        'notification_id' => $notification_id,
                        'user_id' => $context->user_id,
                        'fcm_token' => $user->fcm_token,
                        'delivery_status' => $fcm_result['success'] ? 'sent' : 'failed',
                        'delivery_response' => json_encode($fcm_result)
                    ]);
                    
                    if ($fcm_result['success']) {
                        $sent++;
                        
                        // Update notification counter
                        $this->notification_context_model->increment_notification_counter(
                            $context->user_id,
                            $notification_type
                        );
                    } else {
                        $failed++;
                        $errors[] = "User {$context->user_id}: FCM delivery failed";
                    }
                } else {
                    $failed++;
                    $errors[] = "User {$context->user_id}: Failed to create notification record";
                }
                
            } catch (Exception $e) {
                $failed++;
                $errors[] = "User {$context->user_id}: " . $e->getMessage();
                log_message('error', "Failed to send {$log_label} notification to user {$context->user_id}: " . $e->getMessage());
            }
        }
        
        $duration = round(microtime(true) - $this->execution_start_time, 2);
        
        log_message('info', "{$log_label} notifications complete: {$sent} sent, {$failed} failed in {$duration}s");
        
        return [
            'success' => true,
            'notification_type' => $notification_type,
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($users),
            'duration_seconds' => $duration,
            'notification_ids' => $notification_ids,
            'errors' => array_slice($errors, 0, 5) // Return first 5 errors
        ];
    }
}
