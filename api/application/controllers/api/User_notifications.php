<?php

class User_notifications extends API_Controller {

    public function __construct() {
        parent::__construct();
        
        // Load necessary models
        $this->load->model('notification/notification_model');
    }

    /**
     * Get user notifications for dashboard
     * GET /api/notifications/user-notifications
     */
    public function get_notifications_get() {
        try {
            // Get user from JWT token
            $user = $this->require_jwt_auth();
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Get query parameters
            $request = $this->get_request();
            $page = isset($request['page']) ? (int)$request['page'] : 1;
            $limit = isset($request['limit']) ? (int)$request['limit'] : 20;
            $type = $request['type'] ?? null;
            $unread_only = isset($request['unread_only']) && $request['unread_only'] === 'true';

            // Validate pagination parameters
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 20;

            // Get notifications from database
            $result = $this->notification_model->get_user_notifications(
                $user->id,
                $page,
                $limit,
                $type,
                $unread_only
            );

            // Process each notification
            $processed_notifications = [];
            foreach ($result['notifications'] as $notification) {
                $processed = $this->_process_notification($notification);
                $processed_notifications[] = $processed;
            }

            // Get statistics
            $stats = $this->notification_model->get_user_notification_stats($user->id);

            // Add has_more flag for pagination
            $result['pagination']['has_more'] = $page < $result['pagination']['total_pages'];

            $this->response([
                'success' => true,
                'data' => [
                    'notifications' => $processed_notifications,
                    'stats' => $stats,
                    'pagination' => $result['pagination']
                ]
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Error in get_notifications_get: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     * POST /api/notifications/mark-read
     */
    public function mark_read_post() {
        try {
            // Get user from JWT token
            $user = $this->require_jwt_auth();
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Get POST data
            $request = $this->get_request();
            $notification_id = $request['notification_id'] ?? null;

            // Validate required fields
            if (empty($notification_id)) {
                $this->response([
                    'success' => false,
                    'message' => 'notification_id is required'
                ], 400);
                return;
            }

            // Mark notification as opened
            $result = $this->notification_model->mark_notification_opened($notification_id, $user->id);

            if ($result) {
                $this->response([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ], 200);
            } else {
                $this->response([
                    'success' => false,
                    'message' => 'Notification not found or already marked as read'
                ], 404);
            }

        } catch (Exception $e) {
            log_message('error', 'Error in mark_read_post: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     * POST /api/notifications/mark-all-read
     */
    public function mark_all_read_post() {
        try {
            // Get user from JWT token
            $user = $this->require_jwt_auth();
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Mark all notifications as read
            $count = $this->notification_model->mark_all_notifications_read($user->id);

            $this->response([
                'success' => true,
                'message' => 'All notifications marked as read',
                'count' => $count
            ], 200);

        } catch (Exception $e) {
            log_message('error', 'Error in mark_all_read_post: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification for user
     * DELETE /api/notifications/delete/:notification_id
     */
    public function delete_delete($notification_id = null) {
        try {
            // Get user from JWT token
            $user = $this->require_jwt_auth();
            if (!$user) {
                return; // Error response already sent by require_jwt_auth()
            }

            // Validate notification_id
            if (empty($notification_id)) {
                $this->response([
                    'success' => false,
                    'message' => 'notification_id is required'
                ], 400);
                return;
            }

            // Delete notification for this user
            $result = $this->notification_model->delete_user_notification($notification_id, $user->id);

            if ($result) {
                $this->response([
                    'success' => true,
                    'message' => 'Notification deleted successfully'
                ], 200);
            } else {
                $this->response([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

        } catch (Exception $e) {
            log_message('error', 'Error in delete_delete: ' . $e->getMessage());
            $this->response([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process notification data for frontend
     * Map types, add icons, colors, relative time, and action links
     * Returns data in exact format expected by NotificationsPanel component
     */
    private function _process_notification($notification) {
        // Map database type to frontend display type
        $display_type = $this->_map_notification_type($notification['type']);
        
        // Get appearance (icon and color)
        $appearance = $this->_get_notification_appearance($display_type);
        
        // Calculate relative time
        $relative_time = $this->_get_relative_time($notification['sent_at']);
        
        // Generate action link
        $action = $this->_generate_action_link($notification);
        
        // Return in exact format expected by NotificationsPanel
        $result = [
            'id' => (int)$notification['id'],
            'type' => $display_type,
            'title' => $notification['title'],
            'message' => $notification['message'],
            'time' => $relative_time,
            'read' => (bool)$notification['read'],
            'icon' => $appearance['icon'],
            'color' => $appearance['color']
        ];
        
        // Only include action if it exists
        if ($action) {
            $result['action'] = $action;
        }
        
        return $result;
    }

    /**
     * Map database notification type to frontend display type
     */
    private function _map_notification_type($db_type) {
        $type_mapping = [
            'study_reminder' => 'reminder',
            'admin_announcement' => 'update',
            'quiz_updates' => 'reminder',
            'general' => 'update',
            'milestone' => 'achievement',
            'inactivity' => 'reminder',
            'custom_quiz' => 'reminder',
            'pyq' => 'reminder',
            'mock' => 'reminder',
            'motivational' => 'challenge',
            'achievement' => 'achievement',
            'streak' => 'streak',
            'social' => 'social'
        ];
        
        return $type_mapping[$db_type] ?? 'update';
    }

    /**
     * Get icon and color for notification type
     */
    private function _get_notification_appearance($type) {
        $appearance = [
            'reminder' => ['icon' => 'fas fa-bell', 'color' => '#667eea'],
            'achievement' => ['icon' => 'fas fa-trophy', 'color' => '#f39c12'],
            'streak' => ['icon' => 'fas fa-fire', 'color' => '#e74c3c'],
            'challenge' => ['icon' => 'fas fa-bolt', 'color' => '#27ae60'],
            'social' => ['icon' => 'fas fa-user-plus', 'color' => '#2d9cdb'],
            'update' => ['icon' => 'fas fa-sparkles', 'color' => '#9b59b6']
        ];
        
        return $appearance[$type] ?? ['icon' => 'fas fa-bell', 'color' => '#667eea'];
    }

    /**
     * Generate action link based on notification type and deep link
     */
    private function _generate_action_link($notification) {
        $action = null;
        
        // If deep_link_screen is specified, use it
        if ($notification['deep_link_screen']) {
            $screen_map = [
                'dashboard' => '/dashboard',
                'custom-quiz' => '/unattempted-quizzes',
                'quiz' => '/custom-quiz-history',
                'profile' => '/report-card',
                'achievements' => '/report-card',
                'progress' => '/performance',
                'leaderboard' => '/report-card',
                'history' => '/pyq-history'
            ];
            
            $link = $screen_map[$notification['deep_link_screen']] ?? '/dashboard';
            $action = [
                'label' => $this->_get_action_label($notification['type']),
                'link' => $link
            ];
        } 
        // Otherwise, determine based on type
        else {
            $type_actions = [
                'milestone' => ['label' => 'View Progress', 'link' => '/report-card'],
                'inactivity' => ['label' => 'Take Quiz', 'link' => '/unattempted-quizzes'],
                'study_reminder' => ['label' => 'Start Quiz', 'link' => '/custom-quiz-history'],
                'custom_quiz' => ['label' => 'Create Quiz', 'link' => '/custom-quiz-history'],
                'pyq' => ['label' => 'Try PYQs', 'link' => '/pyq-history'],
                'mock' => ['label' => 'Start Mock', 'link' => '/pyq-quiz-generator'],
                'achievement' => ['label' => 'View Badges', 'link' => '/report-card'],
                'motivational' => ['label' => 'Keep Going', 'link' => '/dashboard'],
                'admin_announcement' => ['label' => 'Learn More', 'link' => '/dashboard']
            ];
            
            $action = $type_actions[$notification['type']] ?? null;
        }
        
        return $action;
    }

    /**
     * Get action label based on notification type
     */
    private function _get_action_label($type) {
        $labels = [
            'milestone' => 'View Progress',
            'inactivity' => 'Take Quiz',
            'study_reminder' => 'Start Quiz',
            'custom_quiz' => 'Create Quiz',
            'achievement' => 'View Badges',
            'general' => 'View Details',
            'admin_announcement' => 'Learn More'
        ];
        
        return $labels[$type] ?? 'View Details';
    }

    /**
     * Calculate relative time (e.g., "10 minutes ago")
     */
    private function _get_relative_time($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } else {
            return date('M d, Y', $time);
        }
    }
}
