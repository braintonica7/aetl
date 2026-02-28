<?php

class Notifications extends API_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load necessary models
        $this->load->model('notification/notification_model');
        $this->load->model('user/user_model');
        
        // Load libraries
        $this->load->library('notification_service');
    }

    /**
     * Register FCM token for authenticated user
     * POST /api/notifications/register-token
     */
    public function register_token_post() {
        try {
            // Get user from token
            $user = $this->require_jwt_auth();
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Get POST data
            $request = $this->get_request();
            $fcm_token = $request['fcm_token'] ?? null;
            $platform = $request['platform'] ?? null; // ios, android, web
            $device_info = $request['device_info'] ?? null; // optional device information

            // Validate required fields
            if (empty($fcm_token)) {
                $this->response([
                    'success' => false,
                    'message' => 'FCM token is required'
                ], 400);
                return;
            }

            // Update user's FCM token
            $fcm_token_updated_at = date('Y-m-d H:i:s');
            $notification_enabled = 1;
            $update_data = [
                'fcm_token' => $fcm_token,
                'platform' => $platform,
                'fcm_token_updated_at' => $fcm_token_updated_at,
                'notification_enabled' => $notification_enabled
            ];
           

            $result = $this->user_model->update_user_fcm_details($user->id, $fcm_token, $platform, $fcm_token_updated_at, $notification_enabled);

            if ($result) {
                $this->response([
                    'success' => true,
                    'message' => 'FCM token registered successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'platform' => $platform,
                        'registered_at' => date('Y-m-d H:i:s')
                    ]
                ], 200);
            } else {
                $this->response([
                    'success' => false,
                    'message' => 'Failed to register FCM token'
                ], 500);
            }

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification (Admin only)
     * POST /api/notifications/send
     */
    public function send_post() {
        try {
            // Get user from token and validate admin access
            $user = $this->require_jwt_auth(true); // true = require admin
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Get POST data
            $request = $this->get_request();
            $title = $request['title'] ?? null;
            $body = $request['body'] ?? null;
            $type = $request['type'] ?? null; // study_reminder, admin_announcement, etc.
            $target_type = $request['target_type'] ?? null; // all_users, specific_user
            $target_user_id = $request['target_user_id'] ?? null; // if specific_user
            $deep_link_screen = $request['deep_link_screen'] ?? null; // dashboard, custom-quiz
            $deep_link_data = $request['deep_link_data'] ?? null; // additional data

            // Validate required fields
            if (empty($title) || empty($body)) {
                $this->response([
                    'success' => false,
                    'message' => 'Title and body are required'
                ], 400);
                return;
            }

            if (empty($type)) {
                $type = 'general';
            }

            if (empty($target_type)) {
                $target_type = 'all_users';
            }

            // Validate target_user_id if target_type is specific_user
            if ($target_type === 'specific_user' && empty($target_user_id)) {
                $this->response([
                    'success' => false,
                    'message' => 'target_user_id is required when target_type is specific_user'
                ], 400);
                return;
            }

            // Create notification record
            $notification_data = [
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'target_type' => $target_type,
                'target_user_id' => $target_user_id,
                'deep_link_screen' => $deep_link_screen,
                'deep_link_data' => $deep_link_data ? json_encode($deep_link_data) : null,
                'created_by' => $user->id,
                'status' => 'pending'
            ];

            $notification_id = $this->notification_model->create_notification($notification_data);

            if (!$notification_id) {
                $this->response([
                    'success' => false,
                    'message' => 'Failed to create notification record'
                ], 500);
                return;
            }

            // Get target users and their FCM tokens
            $target_users = $this->_get_target_users($target_type, $target_user_id);

            if (empty($target_users)) {
                // Update notification status
                $this->notification_model->update_notification($notification_id, ['status' => 'failed']);
                
                $this->response([
                    'success' => false,
                    'message' => 'No valid recipients found'
                ], 400);
                return;
            }

            // Send notifications using FCM
            $sent_count = 0;
            $failed_count = 0;

            foreach ($target_users as $target_user) {
                if (!empty($target_user->fcm_token)) {
                    // Use unified method that auto-detects Expo vs FCM tokens
                    $fcm_result = $this->notification_service->send_notification(
                        $target_user->fcm_token,
                        $title,
                        $body,
                        [
                            'screen' => $deep_link_screen,
                            'notification_id' => $notification_id,
                            'type' => $type
                        ]
                    );

                    // Record delivery attempt
                    $recipient_data = [
                        'notification_id' => $notification_id,
                        'user_id' => $target_user->id,
                        'fcm_token' => $target_user->fcm_token,
                        'delivery_status' => $fcm_result['success'] ? 'sent' : 'failed',
                        'delivery_response' => json_encode($fcm_result)
                    ];

                    $this->notification_model->create_notification_recipient($recipient_data);

                    if ($fcm_result['success']) {
                        $sent_count++;
                    } else {
                        $failed_count++;
                    }
                }
            }

            // Update notification status and recipient count
            $status = ($sent_count > 0) ? 'sent' : 'failed';
            $this->notification_model->update_notification($notification_id, [
                'status' => $status,
                'recipients_count' => $sent_count,
                'sent_at' => date('Y-m-d H:i:s')
            ]);

            $this->response([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => [
                    'notification_id' => $notification_id,
                    'sent_count' => $sent_count,
                    'failed_count' => $failed_count,
                    'total_recipients' => count($target_users)
                ]
            ], 200);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification history (Admin only)
     * GET /api/notifications/history
     */
    public function history_get() {
        try {
            // Get user from token and validate admin access
            $user = $this->require_jwt_auth(true); // true = require admin
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Get query parameters
            $request = $this->get_request();
            $page = $request['page'] ?? 1;
            $limit = $request['limit'] ?? 20;
            $type = $request['type'] ?? null; // filter by notification type

            $notifications = $this->notification_model->get_notification_history($page, $limit, $type);

            $this->response([
                'success' => true,
                'data' => $notifications
            ], 200);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification sending (for debugging)
     * POST /api/notifications/test
     */
    public function test_post() {
        try {
            // Get user from token
            $user = $this->require_jwt_auth();
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Get the user's FCM token
            $user_with_token = $this->user_model->get_user_with_fcm_token($user->id);
            
            if (!$user_with_token || empty($user_with_token->fcm_token)) {
                $this->response([
                    'success' => false,
                    'message' => 'No FCM token found for your account. Please register a token first.'
                ], 400);
                return;
            }

            // Validate token
            $token_type = 'Unknown';
            if ($this->notification_service->is_expo_token($user_with_token->fcm_token)) {
                $token_type = 'Expo Push Token';
            } elseif ($this->notification_service->is_fcm_token($user_with_token->fcm_token)) {
                $token_type = 'FCM Token';
            }

            // Send test notification
            $result = $this->notification_service->send_notification(
                $user_with_token->fcm_token,
                'Test Notification',
                'This is a test notification from WiziAI API',
                [
                    'screen' => 'dashboard',
                    'test' => 'true',
                    'timestamp' => (string)time()
                ]
            );

            $this->response([
                'success' => true,
                'message' => 'Test notification processed',
                'data' => [
                    'user_id' => $user->id,
                    'token' => substr($user_with_token->fcm_token, 0, 20) . '...',
                    'token_type' => $token_type,
                    'platform' => $user_with_token->platform,
                    'notification_result' => $result
                ]
            ], 200);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get target users based on target type
     */
    private function _get_target_users($target_type, $target_user_id = null) {
        switch ($target_type) {
            case 'all_users':
                return $this->user_model->get_users_with_fcm_tokens();
                
            case 'specific_user':
                if ($target_user_id) {
                    $user = $this->user_model->get_user_with_fcm_token($target_user_id);
                    return $user ? [$user] : [];
                }
                return [];
                
            default:
                return [];
        }
    }
}