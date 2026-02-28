<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Text Generator
 * 
 * Generates personalized notification titles and text based on user context data.
 * Supports dynamic variable substitution and multiple notification types.
 * 
 * @package WiziAI
 * @subpackage Libraries
 * @category Notification
 * @author WiziAI Team
 * @created December 20, 2025
 */
class Notification_text_generator {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Generate notification title and text for a user based on context
     * 
     * @param string $notification_type Type of notification
     * @param object $user_context User notification context object
     * @param object $user User object with basic info
     * @return array ['title' => string, 'text' => string]
     */
    public function generate($notification_type, $user_context, $user) {
        $method_name = "generate_{$notification_type}";
        
        if (method_exists($this, $method_name)) {
            return $this->$method_name($user_context, $user);
        }
        
        // Default fallback
        return [
            'title' => '📚 WiziAI Update',
            'text' => "Hi {$user->display_name}, check out what's new on WiziAI!"
        ];
    }

    /**
     * Inactivity Reminder
     * Trigger: days_since_last_quiz >= 3
     */
    private function generate_inactivity($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $days = $user_context->days_since_last_quiz;
        
        // Check if user has never taken a quiz (days would be since registration)
        $never_taken_quiz = !isset($user_context->last_quiz_date) || $user_context->last_quiz_date === null;
        
        $titles = [
            "⏰ We Miss You, {$name}!",
            "📚 Come Back to Learning!",
            "🎯 Your Quiz Awaits!",
            "💡 Time to Resume Learning!"
        ];
        
        if ($never_taken_quiz) {
            // User has never taken a quiz
            $text = "Hey {$name}, you joined {$days} " . ($days == 1 ? 'day' : 'days') . " ago. ";
            $text .= "Start your learning journey today with your first quiz! 🚀";
        } else {
            // User has taken quizzes before
            $text = "Hey {$name}, it's been {$days} " . ($days == 1 ? 'day' : 'days') . " since your last quiz. ";
            
            if ($user_context->strongest_subject) {
                $text .= "Your {$user_context->strongest_subject} skills are waiting! ";
            }
            
            if ($user_context->quiz_streak_days > 0) {
                $text .= "Come back and maintain your {$user_context->quiz_streak_days}-day streak. 🚀";
            } else {
                $text .= "Let's start a new learning streak today! 🚀";
            }
        }
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Milestone Proximity
     * Trigger: next_milestone_percentage >= 75 AND next_milestone_percentage < 100
     */
    private function generate_milestone($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $percentage = round($user_context->next_milestone_percentage);
        $remaining = $user_context->next_milestone_target - $user_context->next_milestone_progress;
        
        $titles = [
            "🏆 You're Almost There!",
            "🎯 Milestone Within Reach!",
            "⭐ So Close to Success!",
            "💪 Almost Unlocked!"
        ];
        
        $text = "Amazing work, {$name}! You're {$percentage}% toward earning '{$user_context->next_milestone_name}'. ";
        $text .= "Just {$remaining} more {$user_context->next_milestone_type} to go! Keep pushing! 💪";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Custom Quiz Encouragement
     * Trigger: never_tried_custom = 1 AND performance_category IN ('excellent', 'good')
     */
    private function generate_custom_quiz($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $accuracy = round($user_context->average_accuracy_30days, 1);
        
        $titles = [
            "🎯 Ready for a Challenge?",
            "🎨 Create Your Own Quiz!",
            "✨ Try Custom Quiz Feature!",
            "🚀 Personalize Your Learning!"
        ];
        
        $text = "Hi {$name}, with {$accuracy}% accuracy, you're crushing it! ";
        
        if ($user_context->weakest_subject) {
            $text .= "Why not create a custom quiz focusing on {$user_context->weakest_subject} to boost your skills even further? ";
        } else {
            $text .= "Create a personalized quiz with your favorite topics and difficulty level. ";
        }
        
        $text .= "Take control of your learning journey! 🎯";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * PYQ Suggestion
     * Trigger: never_tried_pyq = 1 AND total_quizzes_all_time >= 10
     */
    private function generate_pyq($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $total_quizzes = $user_context->total_quizzes_all_time;
        
        $titles = [
            "📚 Try Real Exam Questions!",
            "🎓 Practice Previous Years!",
            "💯 Master PYQ Now!",
            "📖 Exam Pattern Practice!"
        ];
        
        $text = "Hey {$name}, you've completed {$total_quizzes} quizzes! ";
        $text .= "Time to test yourself with Previous Year Questions (PYQ). ";
        
        if ($user_context->strongest_subject) {
            $text .= "Master {$user_context->strongest_subject} with real exam patterns! ";
        }
        
        $text .= "Get exam-ready! 🎓";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Mock Test Recommendation
     * Trigger: never_tried_mock = 1 AND engagement_level IN ('highly_active', 'active')
     */
    private function generate_mock_test($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $weekly_count = $user_context->weekly_quiz_count;
        
        $titles = [
            "🎓 Ready for Full-Length Mock Test?",
            "📝 Test Your Exam Readiness!",
            "⏱️ Challenge Yourself!",
            "🏆 Try Mock Test Now!"
        ];
        
        $text = "Impressive consistency, {$name}! ";
        
        if ($weekly_count > 0) {
            $text .= "With {$weekly_count} quizzes this week, ";
        }
        
        $text .= "you're prepared for a full-length mock test. Test your exam readiness and time management skills! 🎯";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Performance Encouragement (Declining)
     * Trigger: accuracy_trend = 'declining' AND motivation_level = 'needs_encouragement'
     */
    private function generate_performance_declining($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $prev_accuracy = round($user_context->average_accuracy_30days, 1);
        $current_accuracy = round($user_context->current_accuracy_percentage, 1);
        
        $titles = [
            "💙 Don't Give Up!",
            "🌟 We Believe in You!",
            "💪 Keep Going Strong!",
            "🤝 We're Here to Help!"
        ];
        
        $text = "Hey {$name}, we notice your accuracy has dipped from {$prev_accuracy}% to {$current_accuracy}% recently. ";
        $text .= "Everyone has rough patches! ";
        
        if ($user_context->weakest_subject) {
            $text .= "Let's focus on {$user_context->weakest_subject} together. ";
        }
        
        $text .= "You've got this! 🌟";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Celebration (Improving Performance)
     * Trigger: accuracy_trend = 'improving' AND is_high_performer = 1
     */
    private function generate_performance_improving($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $prev_accuracy = round($user_context->average_accuracy_30days, 1);
        $current_accuracy = round($user_context->current_accuracy_percentage, 1);
        $improvement = round($current_accuracy - $prev_accuracy, 1);
        
        $titles = [
            "🎉 You're On Fire!",
            "⭐ Outstanding Progress!",
            "🚀 Amazing Improvement!",
            "👏 Phenomenal Work!"
        ];
        
        $text = "Wow {$name}! Your accuracy jumped from {$prev_accuracy}% to {$current_accuracy}% ";
        
        if ($improvement > 0) {
            $text .= "(+{$improvement}%) ";
        }
        
        $text .= "this month! Keep this momentum going! You're a star! ⭐";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Quota Warning
     * Trigger: approaching_quota_limit = 1 AND subscription_type = 'free'
     */
    private function generate_quota_warning($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $used = $user_context->custom_quiz_quota_used;
        $limit = $user_context->custom_quiz_quota_limit;
        $remaining = $user_context->custom_quiz_quota_remaining;
        
        $titles = [
            "⚠️ Quota Alert",
            "📊 Usage Limit Notice",
            "🔔 Quota Running Low",
            "⏰ Limited Quizzes Left"
        ];
        
        $text = "Hi {$name}, you've used {$used} out of {$limit} custom quizzes this month. ";
        
        if ($remaining > 0) {
            $text .= "Only {$remaining} left! ";
        }
        
        $text .= "Consider upgrading to Premium for unlimited access and more features! 🚀";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Subscription Expiry Reminder
     * Trigger: subscription_expires_in_days <= 7 AND subscription_type != 'free'
     */
    private function generate_subscription_expiry($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        $days = $user_context->subscription_expires_in_days;
        $plan = ucfirst($user_context->subscription_type);
        
        $titles = [
            "⏳ Subscription Expiring Soon",
            "🔔 Renewal Reminder",
            "⚠️ Plan Expiry Alert",
            "💎 Keep Your Benefits!"
        ];
        
        $text = "Hey {$name}, your {$plan} plan expires in {$days} " . ($days == 1 ? 'day' : 'days') . "! ";
        $text .= "Don't lose access to custom quizzes, PYQs, and performance insights. ";
        $text .= "Renew now to keep learning! 📚";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Streak Broken Recovery
     * Trigger: quiz_streak_days = 0 AND quiz_streak_broken_date is recent
     */
    private function generate_streak_broken($user_context, $user) {
        $name = $this->get_first_name($user->display_name);
        
        $titles = [
            "🔄 Rebuild Your Streak!",
            "💪 Start Fresh Today!",
            "🎯 New Streak Awaits!",
            "🚀 Comeback Time!"
        ];
        
        $text = "Hey {$name}, your streak ended recently. No worries! ";
        $text .= "Every great learner faces setbacks. Start fresh today and build an even better streak! ";
        $text .= "You've got what it takes! 💪";
        
        return [
            'title' => $titles[array_rand($titles)],
            'text' => $text
        ];
    }

    /**
     * Extract first name from display name
     */
    private function get_first_name($display_name) {
        $parts = explode(' ', trim($display_name));
        return $parts[0];
    }

    /**
     * Get all available notification types
     */
    public function get_notification_types() {
        return [
            'inactivity',
            'milestone',
            'custom_quiz',
            'pyq',
            'mock_test',
            'performance_declining',
            'performance_improving',
            'quota_warning',
            'subscription_expiry',
            'streak_broken'
        ];
    }
}
