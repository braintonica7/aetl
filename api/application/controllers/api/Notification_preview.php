<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Preview API Controller
 * 
 * Provides endpoints to preview notification suggestions for users
 * and send notifications individually or in bulk.
 * 
 * @package WiziAI
 * @subpackage Controllers
 * @category API
 * @author WiziAI Team
 * @created December 20, 2025
 */
class Notification_preview extends API_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load required models and libraries
        $this->load->model('notification/notification_context_model');
        $this->load->model('user/user_model');
        $this->load->model('notification/notification_model');
        $this->load->library('Notification_suggestion_service');
        $this->load->library('Notification_text_generator');
        $this->load->library('notification_service');
    }

    /**
     * GET /api/notification_preview
     * 
     * Get list of users with suggested notifications
     * 
     * Query Parameters:
     * - limit: Number of records (default: 50, max: 200)
     * - page: Page number (default: 1)
     * - notification_type: Filter by type (optional)
     * - priority: Filter by priority (high/medium/low) (optional)
     * - segment: Filter by user segment (optional)
     * - search: Search by name/email/mobile (optional)
     */
    public function index_get() {
        try {
            // Get query parameters
            $limit = min((int)$this->get('limit', TRUE) ?: 50, 200);
            $page = max((int)$this->get('page', TRUE) ?: 1, 1);
            $notification_type = $this->get('notification_type', TRUE);
            $priority_filter = $this->get('priority', TRUE);
            $segment = $this->get('segment', TRUE);
            $search = $this->get('search', TRUE);
            
            $offset = ($page - 1) * $limit;
            
            // Build filters
            $filters = [];
            if ($search) {
                $filters['search'] = $search;
            }
            if ($segment) {
                $filters['segment'] = $segment;
            }
            
            // Fetch more users than needed since some will be filtered out
            // Use a multiplier based on typical pass rate (roughly 10-20%)
            $fetch_limit = $limit * 10; // Fetch 10x more to ensure we have enough after filtering
            
            // Get users with context
            $users = $this->notification_context_model->get_users_for_notification_preview(
                $filters, 
                $fetch_limit, 
                $offset
            );
            
            // Get total count
            $total_count = $this->notification_context_model->get_eligible_users_count($filters);
            
            // Generate suggestions for each user
            $all_suggestions = [];
            $debug_stats = [
                'total_fetched' => count($users),
                'no_suggestion' => 0,
                'filtered_by_type' => 0,
                'filtered_by_priority' => 0,
                'included' => 0
            ];
            
            foreach ($users as $user_data) {
                // Create user object
                $user = (object)[
                    'id' => $user_data->user_id,
                    'display_name' => $user_data->display_name,
                    'email' => $user_data->email,
                    'mobile_number' => $user_data->mobile_number
                ];
                
                // Generate suggestion
                $suggestion = $this->notification_suggestion_service->generate_suggestion(
                    $user_data, 
                    $user
                );
                
                // Include user even if no suggestion
                if (!$suggestion) {
                    $debug_stats['no_suggestion']++;
                    
                    // Add user with no suggestion status
                    $all_suggestions[] = [
                        'id' => $user_data->user_id,
                        'user_id' => $user_data->user_id,
                        'display_name' => $user_data->display_name,
                        'email' => $user_data->email,
                        'mobile_number' => $user_data->mobile_number,
                        'notification_type' => null,
                        'notification_title' => 'No notification available',
                        'notification_text' => 'No notification criteria matched for this user',
                        'priority' => 'low',
                        'user_segment' => 'no_match',
                        'send_status' => 'not_eligible',
                        'days_since_last_notification' => null,
                        'last_notification_sent' => null,
                        'context_data' => [
                            'days_since_last_quiz' => $user_data->days_since_last_quiz ?? null,
                            'total_quizzes_all_time' => $user_data->total_quizzes_all_time ?? 0,
                            'engagement_level' => $user_data->engagement_level ?? 'unknown',
                            'performance_category' => $user_data->performance_category ?? 'unknown'
                        ]
                    ];
                    continue;
                }
                
                // Apply filters
                if ($notification_type && $suggestion['notification_type'] !== $notification_type) {
                    $debug_stats['filtered_by_type']++;
                    continue;
                }
                
                if ($priority_filter && $suggestion['priority'] !== $priority_filter) {
                    $debug_stats['filtered_by_priority']++;
                    continue;
                }
                
                $debug_stats['included']++;
                
                // Build response object
                $all_suggestions[] = [
                    'id' => $user_data->user_id, // For React Admin compatibility
                    'user_id' => $user_data->user_id,
                    'display_name' => $user_data->display_name,
                    'email' => $user_data->email,
                    'mobile_number' => $user_data->mobile_number,
                    'notification_type' => $suggestion['notification_type'],
                    'notification_title' => $suggestion['notification_title'],
                    'notification_text' => $suggestion['notification_text'],
                    'priority' => $suggestion['priority'],
                    'user_segment' => $suggestion['user_segment'],
                    'send_status' => $suggestion['send_status'],
                    'days_since_last_notification' => $suggestion['days_since_last_notification'],
                    'last_notification_sent' => $user_data->last_reminder_sent ?? null,
                    'context_data' => $suggestion['context_snapshot']
                ];
            }
            
            // Apply limit to final suggestions (client-side pagination)
            $suggestions = array_slice($all_suggestions, 0, $limit);
            
            // Return response
            $this->response([
                'success' => true,
                'data' => $suggestions,
                'debug' => $debug_stats,
                'pagination' => [
                    'total' => count($suggestions),
                    'total_suggestions' => count($all_suggestions), // Total suggestions generated
                    'total_eligible_users' => $total_count, // Total users in database
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil(count($all_suggestions) / $limit) // Pages based on actual suggestions
                ]
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Error in notification preview: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Failed to fetch notification suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/notification_preview/:user_id
     * 
     * Get notification suggestion for a specific user
     */
    public function show_get($user_id) {
        try {
            // Get user with context
            $user_data = $this->notification_context_model->get_user_with_context_by_id($user_id);
            
            if (!$user_data) {
                $this->response([
                    'success' => false,
                    'message' => 'User not found'
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }
            
            // Create user object
            $user = (object)[
                'id' => $user_data->user_id,
                'display_name' => $user_data->display_name,
                'email' => $user_data->email,
                'mobile_number' => $user_data->mobile_number
            ];
            
            // Generate suggestion
            $suggestion = $this->notification_suggestion_service->generate_suggestion(
                $user_data, 
                $user
            );
            
            if (!$suggestion) {
                $this->response([
                    'success' => true,
                    'message' => 'No notification suggestion available for this user',
                    'data' => null
                ], REST_Controller::HTTP_OK);
                return;
            }
            
            // Build response
            $response = [
                'user_id' => $user_data->user_id,
                'display_name' => $user_data->display_name,
                'email' => $user_data->email,
                'mobile_number' => $user_data->mobile_number,
                'notification_type' => $suggestion['notification_type'],
                'notification_title' => $suggestion['notification_title'],
                'notification_text' => $suggestion['notification_text'],
                'priority' => $suggestion['priority'],
                'user_segment' => $suggestion['user_segment'],
                'send_status' => $suggestion['send_status'],
                'context_data' => $suggestion['context_snapshot']
            ];
            
            $this->response([
                'success' => true,
                'data' => $response
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Error fetching user notification preview: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Failed to fetch notification suggestion',
                'error' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * POST /api/notification_preview/send
     * 
     * Send notification to a single user
     * 
     * Body Parameters:
     * - user_id: User ID (required)
     * - notification_type: Type of notification (required)
     * - notification_title: Custom title (optional, will generate if not provided)
     * - notification_text: Custom text (optional, will generate if not provided)
     */
    public function send_post() {
        try {
            $user_id = $this->post('user_id');
            $notification_type = $this->post('notification_type');
            $custom_title = $this->post('notification_title');
            $custom_text = $this->post('notification_text');
            
            if (!$user_id || !$notification_type) {
                $this->response([
                    'success' => false,
                    'message' => 'user_id and notification_type are required'
                ], 400);
                return;
            }
            
            // Get user with context
            $user_data = $this->notification_context_model->get_user_with_context_by_id($user_id);
            
            if (!$user_data || !$user_data->fcm_token) {
                $this->response([
                    'success' => false,
                    'message' => 'User not found or FCM token not available'
                ], 404);
                return;
            }
            
            // Generate notification text if not provided
            if (!$custom_title || !$custom_text) {
                $user = (object)[
                    'id' => $user_data->user_id,
                    'display_name' => $user_data->display_name,
                    'email' => $user_data->email,
                    'mobile_number' => $user_data->mobile_number
                ];
                
                $generated = $this->notification_text_generator->generate(
                    $notification_type, 
                    $user_data, 
                    $user
                );
                
                $notification_title = $custom_title ?: $generated['title'];
                $notification_text = $custom_text ?: $generated['text'];
            } else {
                $notification_title = $custom_title;
                $notification_text = $custom_text;
            }
            
            // Send notification via FCM
            $fcm_result = $this->send_fcm_notification(
                $user_data->fcm_token,
                $notification_title,
                $notification_text,
                ['notification_type' => $notification_type]
            );
            
            if ($fcm_result['success']) {
                // Update notification sent timestamp
                $this->notification_context_model->update_notification_sent($user_id, $notification_type);
                
                // Log to notifications table
                $this->notification_model->add_notification([
                    'user_id' => $user_id,
                    'title' => $notification_title,
                    'message' => $notification_text,
                    'notification_type' => $notification_type,
                    'is_sent' => 1,
                    'sent_at' => date('Y-m-d H:i:s')
                ]);
                
                $this->response([
                    'success' => true,
                    'message' => 'Notification sent successfully',
                    'data' => [
                        'user_id' => $user_id,
                        'notification_type' => $notification_type,
                        'sent_at' => date('Y-m-d H:i:s')
                    ]
                ], 200);
            } else {
                $this->response([
                    'success' => false,
                    'message' => 'Failed to send notification',
                    'error' => $fcm_result['error']
                ], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error sending notification: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/notification_preview/send_bulk
     * 
     * Send notifications to multiple users
     * 
     * Body Parameters:
     * - users: Array of {user_id, notification_type} objects (required)
     */
    public function send_bulk_post() {
        try {
            $users = $this->post('users');
            
            if (!$users || !is_array($users)) {
                $this->response([
                    'success' => false,
                    'message' => 'users array is required'
                ], 400);
                return;
            }
            
            $results = [
                'total' => count($users),
                'sent' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            foreach ($users as $user_item) {
                $user_id = $user_item['user_id'] ?? null;
                $notification_type = $user_item['notification_type'] ?? null;
                
                if (!$user_id || !$notification_type) {
                    $results['failed']++;
                    $results['details'][] = [
                        'user_id' => $user_id,
                        'success' => false,
                        'error' => 'Missing user_id or notification_type'
                    ];
                    continue;
                }
                
                // Get user with context
                $user_data = $this->notification_context_model->get_user_with_context_by_id($user_id);
                
                if (!$user_data || !$user_data->fcm_token) {
                    $results['failed']++;
                    $results['details'][] = [
                        'user_id' => $user_id,
                        'success' => false,
                        'error' => 'User not found or FCM token not available'
                    ];
                    continue;
                }
                
                // Generate notification
                $user = (object)[
                    'id' => $user_data->user_id,
                    'display_name' => $user_data->display_name,
                    'email' => $user_data->email,
                    'mobile_number' => $user_data->mobile_number
                ];
                
                $notification = $this->notification_text_generator->generate(
                    $notification_type, 
                    $user_data, 
                    $user
                );
                
                // Send FCM notification
                $fcm_result = $this->send_fcm_notification(
                    $user_data->fcm_token,
                    $notification['title'],
                    $notification['text'],
                    ['notification_type' => $notification_type]
                );
                
                if ($fcm_result['success']) {
                    $results['sent']++;
                    $this->notification_context_model->update_notification_sent($user_id, $notification_type);
                    
                    $results['details'][] = [
                        'user_id' => $user_id,
                        'success' => true
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'user_id' => $user_id,
                        'success' => false,
                        'error' => $fcm_result['error']
                    ];
                }
            }
            
            $this->response([
                'success' => true,
                'message' => "Sent {$results['sent']} of {$results['total']} notifications",
                'data' => $results
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Error sending bulk notifications: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Failed to send bulk notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send FCM notification
     * 
     * @param string $fcm_token FCM device token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data
     * @return array Result array with success status
     */
    private function send_fcm_notification($fcm_token, $title, $body, $data = []) {
        try {
            // Load FCM service if available
            if (!file_exists(APPPATH . 'libraries/Fcm_service.php')) {
                return ['success' => false, 'error' => 'FCM service not available'];
            }
            
            $this->load->library('fcm_service');
            
            $notification_data = [
                'title' => $title,
                'body' => $body,
                'data' => $data
            ];
            
            $result = $this->fcm_service->send_notification($fcm_token, $notification_data);
            
            return [
                'success' => $result === true || (is_array($result) && ($result['success'] ?? false)),
                'error' => is_array($result) ? ($result['error'] ?? null) : null
            ];
            
        } catch (Exception $e) {
            log_message('error', 'FCM send error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get last notification timestamp for a notification type
     */
    private function get_last_notification_timestamp($user_context, $notification_type) {
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
        
        if ($field && isset($user_context->$field)) {
            return $user_context->$field;
        }
        
        return null;
    }

    /**
     * POST /api/notification_preview/send_batch
     * 
     * Send intelligent notifications to multiple users based on their context
     * Uses Notification_suggestion_service for smart, varied notifications
     * 
     * Body Parameters:
     * - user_ids: array (optional) - specific user IDs to target
     * - max_users: int (optional) - max users to process (default: 50, max: 200)
     * - priority_filter: string (optional) - only 'high', 'medium', or 'low'
     * - segment_filter: string (optional) - specific user segment
     * - dry_run: bool (optional) - preview without sending (default: false)
     */
    public function send_batch_post() {
        $start_time = microtime(true);
        
        try {
            // Require admin authentication
            $admin = $this->require_jwt_auth(true);
            if (!$admin) {
                return; // Error already sent by require_jwt_auth
            }

            // Get and validate parameters
            $request = $this->get_request();
            $user_ids = $request['user_ids'] ?? null;
            $max_users = min((int)($request['max_users'] ?? 50), 20000); // Cap at 20000
            $priority_filter = $request['priority_filter'] ?? null;
            $segment_filter = $request['segment_filter'] ?? null;
            $dry_run = (bool)($request['dry_run'] ?? false);

            // Validate filters
            if ($priority_filter && !in_array($priority_filter, ['high', 'medium', 'low'])) {
                $this->response([
                    'success' => false,
                    'message' => 'Invalid priority_filter. Must be high, medium, or low'
                ], 400);
                return;
            }

            // Get eligible users
            $users = $this->get_eligible_users_for_batch($user_ids, $max_users);

            if (empty($users)) {
                $this->response([
                    'success' => false,
                    'message' => 'No eligible users found'
                ], 404);
                return;
            }

            // Initialize results tracking
            $results = [
                'dry_run' => $dry_run,
                'total_processed' => 0,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
                'breakdown' => [
                    'by_priority' => ['high' => 0, 'medium' => 0, 'low' => 0],
                    'by_type' => []
                ],
                'skipped_reasons' => [],
                'details' => []
            ];

            $time_limit = time() + 300; // 300 second safety limit

            // Process each user
            foreach ($users as $user_data) {
                // Check time limit
                if (time() >= $time_limit) {
                    log_message('debug', 'Batch notification sender hit time limit');
                    break;
                }

                $user_result = $this->process_user_notification(
                    $user_data,
                    $admin->id,
                    $priority_filter,
                    $segment_filter,
                    $dry_run
                );

                // Update results
                $results['total_processed']++;
                
                if ($user_result['status'] === 'sent' || $user_result['status'] === 'would_send') {
                    $results[$dry_run ? 'sent' : 'sent']++;
                    
                    // Track breakdown
                    $priority = $user_result['priority'] ?? 'low';
                    $type = $user_result['notification_type'] ?? 'unknown';
                    
                    $results['breakdown']['by_priority'][$priority]++;
                    $results['breakdown']['by_type'][$type] = 
                        ($results['breakdown']['by_type'][$type] ?? 0) + 1;
                        
                } elseif ($user_result['status'] === 'skipped') {
                    $results['skipped']++;
                    $reason = $user_result['reason'] ?? 'unknown';
                    $results['skipped_reasons'][$reason] = 
                        ($results['skipped_reasons'][$reason] ?? 0) + 1;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = $user_result;
            }

            $processing_time = round(microtime(true) - $start_time, 2);

            // Build response message
            if ($dry_run) {
                $message = "DRY RUN: Would send {$results['sent']} of {$results['total_processed']} notifications";
            } else {
                $message = "Sent {$results['sent']} of {$results['total_processed']} notifications";
            }

            if ($results['failed'] > 0) {
                $message .= " ({$results['failed']} failed)";
            }

            $this->response([
                'success' => true,
                'message' => $message,
                'data' => array_merge($results, [
                    'processing_time' => $processing_time . 's'
                ])
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Batch notification error: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Failed to send batch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get eligible users for batch processing
     */
    private function get_eligible_users_for_batch($user_ids, $limit) {
        if ($user_ids && is_array($user_ids) && !empty($user_ids)) {
            // Get specific users
            $users = [];
            foreach ($user_ids as $user_id) {
                $user = $this->notification_context_model->get_user_with_context_by_id($user_id);
                if ($user && !empty($user->fcm_token)) {
                    $users[] = $user;
                }
            }
            return array_slice($users, 0, $limit);
        } else {
            // Get users from preview list (already filtered and eligible)
            return $this->notification_context_model->get_users_for_notification_preview(0, $limit);
        }
    }

    /**
     * Process notification for a single user
     */
    private function process_user_notification($user_data, $admin_id, $priority_filter, $segment_filter, $dry_run) {
        try {
            // Generate suggestion
            $user = (object)[
                'id' => $user_data->user_id,
                'display_name' => $user_data->display_name,
                'email' => $user_data->email,
                'mobile_number' => $user_data->mobile_number
            ];

            $suggestion = $this->notification_suggestion_service->generate_suggestion($user_data, $user);

            if (!$suggestion) {
				log_message('info', "No suggestion for user {$user_data->user_id}");
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'skipped',
                    'reason' => 'no_suggestion'
                ];
            }

            // Apply filters
            if ($priority_filter && $suggestion['priority'] !== $priority_filter) {
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'skipped',
                    'reason' => 'priority_filtered'
                ];
            }

            if ($segment_filter && $suggestion['user_segment'] !== $segment_filter) {
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'skipped',
                    'reason' => 'segment_filtered'
                ];
            }

            // Check if ready to send
            if ($suggestion['send_status'] !== 'ready') {
				log_message('info', "User {$user_data->user_id} not ready to send: {$suggestion['send_status']}");
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'skipped',
                    'reason' => $suggestion['send_status']
                ];
            }

            // DRY RUN MODE - just preview
            if ($dry_run) {
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'display_name' => $user_data->display_name,
                    'status' => 'would_send',
                    'notification_type' => $suggestion['notification_type'],
                    'priority' => $suggestion['priority'],
                    'segment' => $suggestion['user_segment'],
                    'title' => $suggestion['notification_title'],
                    'text' => $suggestion['notification_text']
                ];
            }

            // Validate FCM token exists
            if (empty($user_data->fcm_token)) {
				log_message('info', "User {$user_data->user_id} has no FCM token");
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'skipped',
                    'reason' => 'no_fcm_token'
                ];
            }

            // REAL MODE - actually send
            $fcm_result = $this->notification_service->send_notification(
                $user_data->fcm_token,
                $suggestion['notification_title'],
                $suggestion['notification_text'],
                [
                    'notification_type' => $suggestion['notification_type'],
                    'screen' => $this->get_deep_link_screen($suggestion['notification_type'])
                ]
            );

            if ($fcm_result['success']) {
                // Create notification record with sent_via if column exists
                $notification_data = [
                    'title' => $suggestion['notification_title'],
                    'body' => $suggestion['notification_text'],
                    'type' => $suggestion['notification_type'],
                    'target_type' => 'specific_user',
                    'target_user_id' => $user_data->user_id,
                    'deep_link_screen' => $this->get_deep_link_screen($suggestion['notification_type']),
                    'deep_link_data' => null,
                    'created_by' => $admin_id,
                    'status' => 'sent'
                ];

                // Add sent_via if column exists
                $pdo = CDatabase::getPdo();
                $column_check = $pdo->query("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'notifications' 
                    AND COLUMN_NAME = 'sent_via'
                ");
                if ($column_check->rowCount() > 0) {
                    $notification_data['sent_via'] = 'batch_sender';
                }

                $notification_id = $this->notification_model->create_notification($notification_data);

                // Create recipient record
                if ($notification_id) {
                    $this->notification_model->create_notification_recipient([
                        'notification_id' => $notification_id,
                        'user_id' => $user_data->user_id,
                        'fcm_token' => $user_data->fcm_token,
                        'delivery_status' => 'sent',
                        'delivery_response' => json_encode($fcm_result)
                    ]);
                }

                // Update last sent timestamp
                $this->notification_context_model->update_notification_sent(
                    $user_data->user_id,
                    $suggestion['notification_type']
                );

                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'sent',
                    'notification_type' => $suggestion['notification_type'],
                    'priority' => $suggestion['priority'],
                    'notification_id' => $notification_id
                ];
            } else {
				log_message('error', "FCM send failed for user {$user_data->user_id}: " . ($fcm_result['message'] ?? 'Unknown error'));
                return [
                    'user_id' => $user_data->user_id,
                    'email' => $user_data->email,
                    'status' => 'failed',
                    'error' => $fcm_result['message'] ?? 'Unknown error'
                ];
            }

        } catch (Exception $e) {
            log_message('error', "Error processing user {$user_data->user_id}: " . $e->getMessage());
            return [
                'user_id' => $user_data->user_id,
                'email' => $user_data->email ?? 'unknown',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get batch send statistics
     * GET /api/notification_preview/batch_stats
     */
    public function batch_stats_get() {
        // Require admin authentication
        $this->require_jwt_auth(true);

        try {
            $pdo = CDatabase::getPdo();
            
            // Get total eligible users count (users with FCM token, not deleted, login allowed, notifications enabled)
            $stmt = $pdo->query("
                SELECT COUNT(*) 
                FROM user
                WHERE is_deleted = 0
                AND allow_login = 1
                AND fcm_token IS NOT NULL
                AND fcm_token != ''
                AND notification_enabled = 1
            ");
            $eligible_users = (int)$stmt->fetchColumn();

            // Get notification types from the Notification_suggestion_service constants
            // These are the available notification types in the system
            $notification_types = [
                'subscription_expiry',
                'quota_warning',
                'milestone',
                'performance_improving',
                'inactivity',
                'streak_broken',
                'performance_declining',
                'custom_quiz',
                'pyq',
                'mock_test'
            ];

            // Check if sent_via column exists
            $column_check = $pdo->query("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'notifications' 
                AND COLUMN_NAME = 'sent_via'
            ");
            $has_sent_via = $column_check->rowCount() > 0;

            $sent_today = 0;
            $sent_this_week = 0;
            $last_sent_at = null;

            if ($has_sent_via) {
                // Get sent today count
                $today_start = date('Y-m-d 00:00:00');
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM notifications 
                    WHERE created_at >= ? AND sent_via = 'batch_sender'
                ");
                $stmt->execute([$today_start]);
                $sent_today = (int)$stmt->fetchColumn();

                // Get sent this week count
                $week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM notifications 
                    WHERE created_at >= ? AND sent_via = 'batch_sender'
                ");
                $stmt->execute([$week_start]);
                $sent_this_week = (int)$stmt->fetchColumn();

                // Get last sent timestamp
                $stmt = $pdo->query("
                    SELECT created_at 
                    FROM notifications 
                    WHERE sent_via = 'batch_sender' 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $last_sent_at = $result['created_at'];
                }
            }

            // Get total users count
            $stmt = $pdo->query("SELECT COUNT(*) FROM user");
            $total_users = (int)$stmt->fetchColumn();

            $stats = [
                'total_users' => $total_users,
                'eligible_users' => $eligible_users,
                'notification_types' => $notification_types,
                'total_sent_today' => $sent_today,
                'total_sent_this_week' => $sent_this_week,
                'last_sent_at' => $last_sent_at
            ];

            $this->response([
                'status' => 'success',
                'data' => $stats
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Error fetching batch stats: ' . $e->getMessage());
            $this->response([
                'status' => 'error',
                'message' => 'Failed to fetch batch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch send history
     * GET /api/notification_preview/batch_history?limit=20
     */
    public function batch_history_get() {
        // Require admin authentication
        $this->require_jwt_auth(true);

        try {
            $limit = $this->get('limit') ?: 20;
            $limit = min((int)$limit, 100); // Max 100 records

            $pdo = CDatabase::getPdo();

            // Check if sent_via column exists
            $column_check = $pdo->query("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'notifications' 
                AND COLUMN_NAME = 'sent_via'
            ");
            $has_sent_via = $column_check->rowCount() > 0;

            $history = [];

            if ($has_sent_via) {
                // Get batch send history from notifications table
                $stmt = $pdo->prepare("
                    SELECT 
                        DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') as batch_time,
                        COUNT(*) as total_processed,
                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                        0 as skipped,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                        MIN(created_at) as created_at,
                        0 as dry_run
                    FROM notifications
                    WHERE sent_via = 'batch_sender'
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00')
                    ORDER BY created_at DESC
                    LIMIT :limit
                ");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Convert string numbers to integers
            foreach ($history as &$record) {
                $record['total_processed'] = (int)$record['total_processed'];
                $record['sent'] = (int)$record['sent'];
                $record['skipped'] = (int)$record['skipped'];
                $record['failed'] = (int)$record['failed'];
                $record['dry_run'] = (bool)$record['dry_run'];
            }

            $this->response([
                'status' => 'success',
                'data' => $history
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Error fetching batch history: ' . $e->getMessage());
            $this->response([
                'status' => 'error',
                'message' => 'Failed to fetch batch history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deep link screen for notification type
     */
    private function get_deep_link_screen($notification_type) {
        $screen_map = [
            'custom_quiz' => 'custom-quiz',
            'pyq' => 'pyq-quiz',
            'mock_test' => 'mock-test',
            'inactivity' => 'dashboard',
            'milestone' => 'profile',
            'performance_declining' => 'performance',
            'performance_improving' => 'performance',
            'quota_warning' => 'subscription',
            'subscription_expiry' => 'subscription',
            'streak_broken' => 'dashboard'
        ];
        
        return $screen_map[$notification_type] ?? 'dashboard';
    }
}
