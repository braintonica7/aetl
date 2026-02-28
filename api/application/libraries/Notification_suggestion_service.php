<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Suggestion Service
 * 
 * Analyzes user context data to determine:
 * - Which notification type to suggest
 * - Priority level (high/medium/low)
 * - User segment classification
 * - Whether user is eligible for notification
 * 
 * @package WiziAI
 * @subpackage Libraries
 * @category Notification
 * @author WiziAI Team
 * @created December 20, 2025
 */
class Notification_suggestion_service {

    protected $CI;
    protected $text_generator;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->library('Notification_text_generator');
        $this->text_generator = $this->CI->notification_text_generator;
    }

    /**
     * Generate notification suggestion for a user
     * 
     * @param object $user_context User notification context
     * @param object $user User object with basic info
     * @return array|null Suggestion data or null if no notification needed
     */
    public function generate_suggestion($user_context, $user) {
        // Check if user is eligible for notification
        if (!$this->is_eligible($user_context)) {
            return null;
        }

        // Determine notification type based on context analysis
        $notification_type = $this->determine_notification_type($user_context);
        
        if (!$notification_type) {
            return null;
        }

        // Generate notification text
        $notification = $this->text_generator->generate($notification_type, $user_context, $user);

        // Determine priority and segment
        $priority = $this->calculate_priority($notification_type, $user_context);
        $segment = $this->classify_segment($notification_type, $user_context);

        // Get send status
        $send_status = $this->get_send_status($notification_type, $user_context);

        // Calculate days since last notification
        $days_since_last = $this->get_days_since_last_notification($notification_type, $user_context);

        return [
            'notification_type' => $notification_type,
            'notification_title' => $notification['title'],
            'notification_text' => $notification['text'],
            'priority' => $priority,
            'user_segment' => $segment,
            'send_status' => $send_status,
            'days_since_last_notification' => $days_since_last,
            'context_snapshot' => $this->get_context_snapshot($notification_type, $user_context)
        ];
    }

    /**
     * Determine which notification type to suggest based on priority rules
     * Now with dynamic selection and variety
     */
    private function determine_notification_type($context) {
        // Collect all eligible notification types with their priority weights
        $eligible_notifications = [];

        // Priority 1: Critical notifications (weight: 100)
        if ($this->should_suggest_subscription_expiry($context)) {
            $eligible_notifications[] = ['type' => 'subscription_expiry', 'weight' => 100, 'priority' => 'high'];
        }

        // Priority 2: Important engagement (weight: 90)
        if ($this->should_suggest_quota_warning($context)) {
            $eligible_notifications[] = ['type' => 'quota_warning', 'weight' => 90, 'priority' => 'high'];
        }

        // Priority 3: High engagement opportunity (weight: 80)
        if ($this->should_suggest_milestone($context)) {
            $eligible_notifications[] = ['type' => 'milestone', 'weight' => 80, 'priority' => 'high'];
        }

        // Priority 4: Positive reinforcement (weight: 70)
        if ($this->should_suggest_performance_improving($context)) {
            $eligible_notifications[] = ['type' => 'performance_improving', 'weight' => 70, 'priority' => 'high'];
        }

        // Priority 5: Dormant user reactivation (weight: 65)
        if ($context->days_since_last_quiz >= 14 && $context->is_dormant_user) {
            $eligible_notifications[] = ['type' => 'inactivity', 'weight' => 65, 'priority' => 'high'];
        }

        // Priority 6: Streak broken recovery (weight: 60)
        if ($this->should_suggest_streak_broken($context)) {
            $eligible_notifications[] = ['type' => 'streak_broken', 'weight' => 60, 'priority' => 'medium'];
        }

        // Priority 7: Performance declining (weight: 55)
        if ($this->should_suggest_performance_declining($context)) {
            $eligible_notifications[] = ['type' => 'performance_declining', 'weight' => 55, 'priority' => 'medium'];
        }

        // Priority 8: Inactive users (weight: 50)
        if ($this->should_suggest_inactivity($context)) {
            $eligible_notifications[] = ['type' => 'inactivity', 'weight' => 50, 'priority' => 'medium'];
        }

        // Priority 9: Feature suggestions (weight: 30-40)
        if ($this->should_suggest_custom_quiz($context)) {
            $eligible_notifications[] = ['type' => 'custom_quiz', 'weight' => 40, 'priority' => 'low'];
        }

        if ($this->should_suggest_pyq($context)) {
            $eligible_notifications[] = ['type' => 'pyq', 'weight' => 35, 'priority' => 'low'];
        }

        if ($this->should_suggest_mock_test($context)) {
            $eligible_notifications[] = ['type' => 'mock_test', 'weight' => 30, 'priority' => 'low'];
        }

        // If no eligible notifications, return custom_quiz as fallback
        if (empty($eligible_notifications)) {
            return 'custom_quiz';
        }

        // Filter out notifications sent in the last 2 days for variety
        $filtered_notifications = $this->filter_recently_sent($eligible_notifications, $context, 2);

        // If all filtered out, use 1 day filter instead
        if (empty($filtered_notifications)) {
            $filtered_notifications = $this->filter_recently_sent($eligible_notifications, $context, 1);
        }

        // If still empty, use original list (prevent no notification)
        if (empty($filtered_notifications)) {
            $filtered_notifications = $eligible_notifications;
        }

        // Select notification using weighted random selection
        return $this->weighted_random_selection($filtered_notifications);
    }

    /**
     * Filter out notifications that were sent recently
     * 
     * @param array $notifications List of eligible notifications
     * @param object $context User context
     * @param int $min_days Minimum days since last sent
     * @return array Filtered notifications
     */
    private function filter_recently_sent($notifications, $context, $min_days) {
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

        $filtered = [];
        
        foreach ($notifications as $notification) {
            $type = $notification['type'];
            $field = $field_map[$type] ?? null;
            
            // If no field mapping or field not set, include it
            if (!$field || !isset($context->$field) || !$context->$field) {
                $filtered[] = $notification;
                continue;
            }

            // Check days since last sent
            $days_since = $this->days_between($context->$field, date('Y-m-d H:i:s'));
            if ($days_since >= $min_days) {
                $filtered[] = $notification;
            }
        }

        return $filtered;
    }

    /**
     * Select notification using weighted random selection
     * Higher weight = higher chance of being selected
     * 
     * @param array $notifications Array of notifications with weights
     * @return string Selected notification type
     */
    private function weighted_random_selection($notifications) {
        // Calculate total weight
        $total_weight = array_sum(array_column($notifications, 'weight'));
        
        // Generate random number between 0 and total weight
        $random = mt_rand(0, $total_weight);
        
        // Select notification based on weight
        $current_weight = 0;
        foreach ($notifications as $notification) {
            $current_weight += $notification['weight'];
            if ($random <= $current_weight) {
                return $notification['type'];
            }
        }
        
        // Fallback to last notification (should never reach here)
        return end($notifications)['type'];
    }

    /**
     * Check if subscription expiry notification should be sent
     */
    private function should_suggest_subscription_expiry($context) {
        return $context->subscription_expires_in_days !== null 
            && $context->subscription_expires_in_days <= 7
            && $context->subscription_expires_in_days > 0
            && $context->subscription_type !== 'free';
    }

    /**
     * Check if quota warning should be sent
     */
    private function should_suggest_quota_warning($context) {
        return $context->approaching_quota_limit == 1
            && $context->subscription_type === 'free';
    }

    /**
     * Check if milestone notification should be sent
     */
    private function should_suggest_milestone($context) {
        return $context->next_milestone_percentage >= 75
            && $context->next_milestone_percentage < 100;
    }

    /**
     * Check if performance improving celebration should be sent
     */
    private function should_suggest_performance_improving($context) {
        return $context->accuracy_trend === 'improving'
            && $context->is_high_performer == 1;
    }

    /**
     * Check if streak broken recovery should be sent
     */
    private function should_suggest_streak_broken($context) {
        if ($context->quiz_streak_days > 0 || !$context->quiz_streak_broken_date) {
            return false;
        }

        $days_since_broken = $this->days_between($context->quiz_streak_broken_date, date('Y-m-d H:i:s'));
        return $days_since_broken <= 2;
    }

    /**
     * Check if performance declining support should be sent
     */
    private function should_suggest_performance_declining($context) {
        return $context->accuracy_trend === 'declining'
            && $context->is_active_user == 1
            && $context->motivation_level === 'needs_encouragement';
    }

    /**
     * Check if custom quiz suggestion should be sent
     */
    private function should_suggest_custom_quiz($context) {
        // Encourage ALL users to try custom quiz if they haven't
        return $context->never_tried_custom == 1;
    }

    /**
     * Check if PYQ suggestion should be sent
     */
    private function should_suggest_pyq($context) {
        // Suggest PYQ to users with some experience
        return $context->never_tried_pyq == 1
            && $context->total_quizzes_all_time >= 5;
    }

    /**
     * Check if mock test suggestion should be sent
     */
    private function should_suggest_mock_test($context) {
        // Suggest mock test to users with moderate experience
        return $context->never_tried_mock == 1
            && $context->total_quizzes_all_time >= 10;
    }

    /**
     * Check if inactivity reminder should be sent
     */
    private function should_suggest_inactivity($context) {
        // Send reminder to anyone inactive for 3+ days
        return $context->days_since_last_quiz >= 3;
    }

    /**
     * Check if notification can be sent based on last sent timestamp
     */
    private function can_send_notification($type, $context, $min_days_between) {
        $field_map = [
            'inactivity' => 'last_reminder_sent',
            'milestone' => 'last_milestone_notification_sent',
            'custom_quiz' => 'last_custom_quiz_suggestion_sent',
            'pyq' => 'last_pyq_suggestion_sent',
            'mock_test' => 'last_mock_suggestion_sent',
            'performance_declining' => 'last_motivational_sent',
            'performance_improving' => 'last_motivational_sent',
            'quota_warning' => 'last_quota_warning_sent',
            'subscription_expiry' => 'last_quota_warning_sent', // Reuse field
            'streak_broken' => 'last_reminder_sent'
        ];

        $field = $field_map[$type] ?? null;
        if (!$field || !isset($context->$field)) {
            return true;
        }

        $last_sent = $context->$field;
        if (!$last_sent) {
            return true;
        }

        $days_since = $this->days_between($last_sent, date('Y-m-d H:i:s'));
        return $days_since >= $min_days_between;
    }

    /**
     * Calculate priority level
     */
    private function calculate_priority($type, $context) {
        // High priority notifications
        $high_priority = [
            'subscription_expiry',
            'quota_warning',
            'milestone',
            'performance_improving'
        ];

        // Medium priority notifications
        $medium_priority = [
            'inactivity',
            'streak_broken',
            'performance_declining'
        ];

        if (in_array($type, $high_priority)) {
            return 'high';
        } elseif (in_array($type, $medium_priority)) {
            // Escalate dormant users to high
            if ($context->is_dormant_user == 1 && $type === 'inactivity') {
                return 'high';
            }
            return 'medium';
        }

        return 'low'; // Feature suggestions
    }

    /**
     * Classify user segment
     */
    private function classify_segment($type, $context) {
        $segments = [
            'inactivity' => $context->is_dormant_user ? 'dormant_user' : 'inactive_user',
            'milestone' => 'milestone_achiever',
            'custom_quiz' => 'high_performer_never_tried_custom',
            'pyq' => 'experienced_user_needs_pyq',
            'mock_test' => 'active_user_ready_for_mock',
            'performance_declining' => 'needs_support',
            'performance_improving' => 'high_performer',
            'quota_warning' => 'free_user_high_usage',
            'subscription_expiry' => 'premium_user_expiring',
            'streak_broken' => 'streak_rebuilder'
        ];

        return $segments[$type] ?? 'general_user';
    }

    /**
     * Get send status
     */
    private function get_send_status($type, $context) {
        // Check daily throttle limit
        if ($context->total_notifications_sent_today >= 3) {
            return 'throttled';
        }

        // Check if not eligible
        if ($context->eligible_for_notification != 1) {
            return 'not_eligible';
        }

        return 'ready';
    }

    /**
     * Get days since last notification of this type
     */
    private function get_days_since_last_notification($type, $context) {
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

        $field = $field_map[$type] ?? null;
        if (!$field || !isset($context->$field) || !$context->$field) {
            return null;
        }

        return $this->days_between($context->$field, date('Y-m-d H:i:s'));
    }

    /**
     * Get context snapshot for reference
     */
    private function get_context_snapshot($type, $context) {
        $base = [
            'days_since_last_quiz' => $context->days_since_last_quiz,
            'total_quizzes_all_time' => $context->total_quizzes_all_time,
            'engagement_level' => $context->engagement_level,
            'performance_category' => $context->performance_category
        ];

        // Add type-specific context
        $type_specific = [];
        
        switch ($type) {
            case 'milestone':
                $type_specific = [
                    'next_milestone_name' => $context->next_milestone_name,
                    'next_milestone_percentage' => $context->next_milestone_percentage
                ];
                break;
            case 'custom_quiz':
            case 'pyq':
            case 'mock_test':
                $type_specific = [
                    'never_tried_custom' => $context->never_tried_custom,
                    'never_tried_pyq' => $context->never_tried_pyq,
                    'never_tried_mock' => $context->never_tried_mock
                ];
                break;
            case 'performance_declining':
            case 'performance_improving':
                $type_specific = [
                    'accuracy_trend' => $context->accuracy_trend,
                    'current_accuracy' => $context->current_accuracy_percentage,
                    'average_accuracy_30days' => $context->average_accuracy_30days
                ];
                break;
            case 'quota_warning':
                $type_specific = [
                    'custom_quiz_quota_used' => $context->custom_quiz_quota_used,
                    'custom_quiz_quota_limit' => $context->custom_quiz_quota_limit,
                    'custom_quiz_quota_remaining' => $context->custom_quiz_quota_remaining
                ];
                break;
            case 'subscription_expiry':
                $type_specific = [
                    'subscription_type' => $context->subscription_type,
                    'subscription_expires_in_days' => $context->subscription_expires_in_days
                ];
                break;
        }

        return array_merge($base, $type_specific);
    }

    /**
     * Check if user is eligible for notifications
     */
    private function is_eligible($context) {
        return $context->eligible_for_notification == 1
            && $context->total_notifications_sent_today < 3; // Max 3 per day
    }

    /**
     * Calculate days between two dates
     */
    private function days_between($date1, $date2) {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return abs($interval->days);
    }
}
