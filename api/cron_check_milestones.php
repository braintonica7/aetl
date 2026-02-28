<?php

/**
 * Cron Script: Milestone Achievement Checker
 * 
 * Checks for users who have achieved milestones and sends immediate notifications.
 * Can be run hourly or triggered after quiz completion.
 * 
 * Usage:
 *   php cron_check_milestones.php
 *   php cron_check_milestones.php --limit=50
 *   php cron_check_milestones.php --help
 * 
 * Options:
 *   --limit=N    Maximum number of milestone notifications to send (default: 50)
 *   --help      Show this help message
 * 
 * Recommended Cron Schedule:
 *   0 * * * *    (Hourly check)
 * 
 * Alternative: Can be triggered directly from Quiz completion API endpoint
 * for real-time milestone notifications.
 * 
 * @package WiziAI
 * @subpackage Cron
 * @category Notification
 */

// Parse command line arguments
$options = getopt('', ['limit:', 'help']);

if (isset($options['help'])) {
    echo file_get_contents(__FILE__);
    exit(0);
}

$limit = isset($options['limit']) ? intval($options['limit']) : 50;

// Bootstrap CodeIgniter
define('BASEPATH', dirname(__FILE__) . '/system/');
define('APPPATH', dirname(__FILE__) . '/application/');
define('ENVIRONMENT', 'production');

// Set execution limits
ini_set('max_execution_time', 600); // 10 minutes
ini_set('memory_limit', '256M');

// Load CodeIgniter core
require_once BASEPATH . 'core/Common.php';
require_once APPPATH . 'config/constants.php';

// Initialize logging
$log_file = APPPATH . 'logs/cron_milestones.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
    echo "[{$timestamp}] {$message}\n";
}

// Start execution
$start_time = microtime(true);
write_log("========================================");
write_log("Starting milestone checker (limit={$limit})");

try {
    // Load database configuration
    require_once APPPATH . 'config/database.php';
    
    if (!isset($db['default'])) {
        throw new Exception("Database configuration not found");
    }
    
    $db_config = $db['default'];
    
    // Connect to database
    $dsn = "mysql:host={$db_config['hostname']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    write_log("Database connected successfully");
    
    // Load models manually
    require_once APPPATH . 'models/notification/Notification_context_model.php';
    require_once APPPATH . 'models/notification/notification_model.php';
    require_once APPPATH . 'models/user/user_model.php';
    
    // Load libraries
    require_once APPPATH . 'libraries/Notification_service.php';
    require_once APPPATH . 'libraries/AI_Notification_Service.php';
    require_once APPPATH . 'libraries/AI_Performance_Service.php';
    
    // Initialize services
    $context_model = new Notification_context_model($pdo);
    $notification_model = new notification_model($pdo);
    $user_model = new user_model($pdo);
    $notification_service = new Notification_service();
    $ai_service = new AI_Notification_Service();
    
    // Get users who achieved milestones (next_milestone_percentage >= 100)
    $users = $context_model->get_users_near_milestone(100.0, $limit);
    
    if (empty($users)) {
        write_log("No milestone achievements found");
        write_log("========================================");
        exit(0);
    }
    
    write_log("Found " . count($users) . " users with milestone achievements");
    
    // Check if milestone notification already sent recently (within last 1 hour)
    $eligible_users = [];
    
    foreach ($users as $user_context) {
        // Check last milestone notification time
        if ($user_context->last_milestone_achievement_sent) {
            $last_sent = strtotime($user_context->last_milestone_achievement_sent);
            $hours_since = (time() - $last_sent) / 3600;
            
            if ($hours_since < 1) {
                write_log("User {$user_context->user_id}: Milestone notification sent {$hours_since}h ago, skipping");
                continue;
            }
        }
        
        $eligible_users[] = $user_context;
    }
    
    if (empty($eligible_users)) {
        write_log("No eligible users after filtering recent notifications");
        write_log("========================================");
        exit(0);
    }
    
    write_log("Sending milestone notifications to " . count($eligible_users) . " users");
    
    $sent = 0;
    $failed = 0;
    
    foreach ($eligible_users as $context) {
        try {
            // Get user details
            $user = $user_model->get_user($context->user_id);
            
            if (!$user) {
                write_log("User {$context->user_id}: Not found in database");
                $failed++;
                continue;
            }
            
            if (!$user->fcm_token) {
                write_log("User {$context->user_id}: No FCM token");
                $failed++;
                continue;
            }
            
            // Prepare milestone data
            $additional_data = [
                'milestone_name' => $context->next_milestone_name,
                'milestone_icon' => $context->next_milestone_icon,
                'total_quizzes' => $context->total_quizzes_all_time,
                'accuracy' => $context->current_accuracy_percentage
            ];
            
            // Generate personalized notification
            $notification = $ai_service->generate_notification(
                $context,
                'milestone_achievement',
                $additional_data
            );
            
            if (!$notification || !isset($notification['title'])) {
                write_log("User {$context->user_id}: AI generation failed");
                $failed++;
                continue;
            }
            
            // Send notification
            $fcm_result = $notification_service->send_notification(
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
                'type' => 'milestone_achievement',
                'target_type' => 'specific_user',
                'target_user_id' => $context->user_id,
                'deep_link_screen' => $notification['deep_link_screen'],
                'deep_link_data' => json_encode($notification['deep_link_data']),
                'created_by' => 1, // System
                'status' => $fcm_result['success'] ? 'sent' : 'failed'
            ];
            
            $notification_id = $notification_model->create_notification($notification_data);
            
            if ($notification_id) {
                // Log recipient
                $notification_model->create_notification_recipient([
                    'notification_id' => $notification_id,
                    'user_id' => $context->user_id,
                    'fcm_token' => $user->fcm_token,
                    'delivery_status' => $fcm_result['success'] ? 'sent' : 'failed',
                    'delivery_response' => json_encode($fcm_result)
                ]);
                
                if ($fcm_result['success']) {
                    $sent++;
                    
                    // Update notification counter
                    $context_model->increment_notification_counter(
                        $context->user_id,
                        'milestone_achievement'
                    );
                    
                    write_log("User {$context->user_id}: Milestone notification sent - '{$context->next_milestone_name}'");
                } else {
                    $failed++;
                    write_log("User {$context->user_id}: FCM delivery failed - " . ($fcm_result['message'] ?? 'Unknown error'));
                }
            } else {
                $failed++;
                write_log("User {$context->user_id}: Failed to create notification record");
            }
            
            // Small delay to avoid rate limits
            usleep(100000); // 100ms
            
        } catch (Exception $e) {
            $failed++;
            write_log("User {$context->user_id}: Error - " . $e->getMessage());
        }
    }
    
    $duration = round(microtime(true) - $start_time, 2);
    write_log("Milestone check complete: {$sent} sent, {$failed} failed in {$duration}s");
    write_log("========================================");
    
} catch (Exception $e) {
    write_log("FATAL ERROR: " . $e->getMessage());
    write_log("Stack trace: " . $e->getTraceAsString());
    write_log("========================================");
    exit(1);
}
