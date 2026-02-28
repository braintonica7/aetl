<?php

/**
 * Cron Script: Daily Notification Sender
 * 
 * Sends automated notifications based on type and schedule.
 * Each notification type has recommended timing for optimal engagement.
 * 
 * Usage:
 *   php cron_send_daily_notifications.php --type=custom_quiz
 *   php cron_send_daily_notifications.php --type=inactivity --limit=200
 *   php cron_send_daily_notifications.php --type=all
 *   php cron_send_daily_notifications.php --help
 * 
 * Options:
 *   --type=TYPE      Notification type to send (required)
 *                    Options: custom_quiz, pyq, mock, inactivity, 
 *                            motivational, milestones, quota_warning, all
 *   --limit=N        Maximum number of notifications to send (optional)
 *   --help          Show this help message
 * 
 * Recommended Cron Schedules:
 *   Custom Quiz:      0 9 * * *        (Daily 9 AM)
 *   PYQ Suggestions:  0 10 * * 1,3,5   (Mon/Wed/Fri 10 AM)
 *   Mock Tests:       0 10 * * 6       (Saturday 10 AM)
 *   Inactivity:       0 18 * * *       (Daily 6 PM)
 *   Motivational:     0 11 */2 * *     (Every 2 days 11 AM)
 *   Milestones:       0 * * * *        (Hourly)
 *   Quota Warnings:   0 20 * * 0       (Sunday 8 PM)
 *   Send All:         0 9 * * *        (Daily 9 AM - handles all types)
 * 
 * @package WiziAI
 * @subpackage Cron
 * @category Notification
 */

// Parse command line arguments
$options = getopt('', ['type:', 'limit:', 'help']);

if (isset($options['help'])) {
    echo file_get_contents(__FILE__);
    exit(0);
}

// Validate required arguments
if (!isset($options['type'])) {
    echo "Error: --type parameter is required\n";
    echo "Usage: php cron_send_daily_notifications.php --type=TYPE [--limit=N]\n";
    echo "Run with --help for more information\n";
    exit(1);
}

$notification_type = $options['type'];
$limit = isset($options['limit']) ? intval($options['limit']) : null;

// Validate notification type
$valid_types = [
    'custom_quiz',
    'pyq',
    'mock',
    'inactivity',
    'motivational',
    'milestones',
    'quota_warning',
    'all'
];

if (!in_array($notification_type, $valid_types)) {
    echo "Error: Invalid notification type '{$notification_type}'\n";
    echo "Valid types: " . implode(', ', $valid_types) . "\n";
    exit(1);
}

// Bootstrap CodeIgniter
define('BASEPATH', dirname(__FILE__) . '/system/');
define('APPPATH', dirname(__FILE__) . '/application/');
define('ENVIRONMENT', 'production');

// Set execution limits
ini_set('max_execution_time', 1800); // 30 minutes
ini_set('memory_limit', '512M');

// Load CodeIgniter core
require_once BASEPATH . 'core/Common.php';
require_once APPPATH . 'config/constants.php';

// Initialize logging
$log_file = APPPATH . 'logs/cron_notifications.log';
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
write_log("Starting notification sender: type={$notification_type}" . ($limit ? ", limit={$limit}" : ""));

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
    
    // Get eligible users based on type
    $users = [];
    
    switch ($notification_type) {
        case 'custom_quiz':
            $limit = $limit ?: 100;
            $users = $context_model->get_users_for_custom_quiz_suggestion($limit);
            write_log("Found " . count($users) . " users for custom quiz suggestions");
            break;
            
        case 'pyq':
            $limit = $limit ?: 50;
            $users = $context_model->get_users_for_pyq_suggestion(10, $limit);
            write_log("Found " . count($users) . " users for PYQ suggestions");
            break;
            
        case 'mock':
            $limit = $limit ?: 50;
            // Get high performers who haven't tried mock tests
            $stmt = $pdo->prepare("
                SELECT * FROM user_notification_context
                WHERE performance_category = 'good'
                AND total_quizzes_all_time >= 20
                AND is_high_performer = 1
                AND never_tried_mock = 1
                AND eligible_for_notification = 1
                ORDER BY current_accuracy_percentage DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();
            write_log("Found " . count($users) . " users for mock test suggestions");
            break;
            
        case 'inactivity':
            $days_inactive = 3;
            $limit = $limit ?: 200;
            $users = $context_model->get_inactive_users($days_inactive, $limit);
            write_log("Found " . count($users) . " inactive users (>={$days_inactive} days)");
            break;
            
        case 'motivational':
            $limit = $limit ?: 100;
            $users = $context_model->get_users_needing_encouragement($limit);
            write_log("Found " . count($users) . " users needing encouragement");
            break;
            
        case 'milestones':
            $limit = $limit ?: 50;
            $users = $context_model->get_users_near_milestone(100.0, $limit);
            write_log("Found " . count($users) . " users with milestone achievements");
            break;
            
        case 'quota_warning':
            $threshold = 80.0;
            $limit = $limit ?: 100;
            $users = $context_model->get_users_approaching_quota($threshold, $limit);
            write_log("Found " . count($users) . " users approaching quota limit");
            break;
            
        case 'all':
            write_log("Processing all notification types...");
            
            // Custom quiz
            $custom_users = $context_model->get_users_for_custom_quiz_suggestion(50);
            if (!empty($custom_users)) {
                write_log("Processing " . count($custom_users) . " custom quiz notifications");
                send_batch($custom_users, 'custom_quiz_suggestion', $pdo, $user_model, $notification_model, $notification_service, $ai_service, $context_model);
            }
            
            // Inactivity
            $inactive_users = $context_model->get_inactive_users(3, 100);
            if (!empty($inactive_users)) {
                write_log("Processing " . count($inactive_users) . " inactivity reminders");
                send_batch($inactive_users, 'inactivity_reminder', $pdo, $user_model, $notification_model, $notification_service, $ai_service, $context_model);
            }
            
            // Motivational
            $motivational_users = $context_model->get_users_needing_encouragement(50);
            if (!empty($motivational_users)) {
                write_log("Processing " . count($motivational_users) . " motivational messages");
                send_batch($motivational_users, 'motivational_message', $pdo, $user_model, $notification_model, $notification_service, $ai_service, $context_model);
            }
            
            // Milestones
            $milestone_users = $context_model->get_users_near_milestone(100.0, 30);
            if (!empty($milestone_users)) {
                write_log("Processing " . count($milestone_users) . " milestone notifications");
                send_batch($milestone_users, 'milestone_achievement', $pdo, $user_model, $notification_model, $notification_service, $ai_service, $context_model);
            }
            
            $duration = round(microtime(true) - $start_time, 2);
            write_log("All notification types processed in {$duration}s");
            write_log("========================================");
            exit(0);
    }
    
    // Send notifications for single type
    if (empty($users)) {
        write_log("No eligible users found for {$notification_type}");
        write_log("========================================");
        exit(0);
    }
    
    // Map type to notification type string
    $type_map = [
        'custom_quiz' => 'custom_quiz_suggestion',
        'pyq' => 'pyq_suggestion',
        'mock' => 'mock_suggestion',
        'inactivity' => 'inactivity_reminder',
        'motivational' => 'motivational_message',
        'milestones' => 'milestone_achievement',
        'quota_warning' => 'quota_warning'
    ];
    
    $notification_type_full = $type_map[$notification_type];
    
    send_batch($users, $notification_type_full, $pdo, $user_model, $notification_model, $notification_service, $ai_service, $context_model);
    
    $duration = round(microtime(true) - $start_time, 2);
    write_log("Total execution time: {$duration}s");
    write_log("========================================");
    
} catch (Exception $e) {
    write_log("FATAL ERROR: " . $e->getMessage());
    write_log("Stack trace: " . $e->getTraceAsString());
    write_log("========================================");
    exit(1);
}

/**
 * Send notifications to batch of users
 */
function send_batch($users, $notification_type, $pdo, $user_model, $notification_model, $notification_service, $ai_service, $context_model) {
    $sent = 0;
    $failed = 0;
    $batch_start = microtime(true);
    
    foreach ($users as $context) {
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
            
            // Generate personalized notification
            $notification = $ai_service->generate_notification(
                $context,
                $notification_type,
                []
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
                'type' => $notification_type,
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
                        $notification_type
                    );
                    
                    write_log("User {$context->user_id}: Sent successfully");
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
    
    $duration = round(microtime(true) - $batch_start, 2);
    write_log("Batch complete: {$sent} sent, {$failed} failed in {$duration}s");
    
    return ['sent' => $sent, 'failed' => $failed];
}
