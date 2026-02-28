#!/usr/bin/env php
<?php
/**
 * Notification Context Builder - Cron Job Script
 * 
 * This script builds and maintains user notification contexts.
 * Designed to run via DirectAdmin cron jobs.
 * 
 * Usage Examples:
 * 
 * Full rebuild (all users):
 *   php /path/to/cron_build_contexts.php
 * 
 * Stale only (faster):
 *   php /path/to/cron_build_contexts.php --stale-only
 * 
 * Specific users:
 *   php /path/to/cron_build_contexts.php --users=123,456,789
 * 
 * Custom batch size:
 *   php /path/to/cron_build_contexts.php --batch-size=1000
 * 
 * @package WiziAI
 * @category Cron
 */

// Ensure script is run from CLI only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Set execution time limit (30 minutes max)
set_time_limit(1800);
ini_set('memory_limit', '512M');

// Get the script's directory
$script_dir = dirname(__FILE__);

// Load CodeIgniter (adjust path based on your structure)
define('BASEPATH', $script_dir . '/system/');
define('APPPATH', $script_dir . '/application/');
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');

// Bootstrap CodeIgniter
require_once(BASEPATH . 'core/Common.php');
require_once(APPPATH . 'config/constants.php');

// Minimal CI bootstrap for CLI
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Load core files
require_once(BASEPATH . 'core/CodeIgniter.php');

// ============================================================================
// Script Configuration
// ============================================================================

$start_time = microtime(true);
$log_file = $script_dir . '/logs/cron_context_builder.log';

// Parse command line arguments
$options = getopt('', [
    'stale-only',
    'users:',
    'batch-size:',
    'help'
]);

if (isset($options['help'])) {
    echo <<<HELP

WiziAI Notification Context Builder - Cron Job

Usage:
  php cron_build_contexts.php [OPTIONS]

Options:
  --stale-only          Only rebuild stale/outdated contexts (faster)
  --users=ID1,ID2       Rebuild specific user IDs (comma-separated)
  --batch-size=N        Number of users to process (default: 500)
  --help                Show this help message

Examples:
  # Full rebuild (all active users)
  php cron_build_contexts.php

  # Update only stale contexts (recommended for hourly runs)
  php cron_build_contexts.php --stale-only

  # Process specific users
  php cron_build_contexts.php --users=1,2,3,4,5

  # Large batch processing
  php cron_build_contexts.php --batch-size=1000

Recommended Cron Schedule:
  # Full rebuild every 6 hours
  0 */6 * * * php /path/to/cron_build_contexts.php >> /path/to/logs/cron.log 2>&1

  # Stale refresh every hour
  0 * * * * php /path/to/cron_build_contexts.php --stale-only >> /path/to/logs/cron.log 2>&1

HELP;
    exit(0);
}

// ============================================================================
// Initialize CodeIgniter Components
// ============================================================================

// Create CI instance
$CI =& get_instance();
$CI->load->database();
$CI->load->model('notification/notification_context_model');
$CI->load->model('user/user_model');
$CI->load->model('quiz/quiz_model');
$CI->load->model('user_performance/user_performance_model');
$CI->load->model('user_performance_summary/user_performance_summary_model');
$CI->load->model('wizi_quiz/wizi_quiz_user_model');

// ============================================================================
// Main Execution Logic
// ============================================================================

log_message("Starting context builder...");

try {
    $stale_only = isset($options['stale-only']);
    $batch_size = isset($options['batch-size']) ? (int)$options['batch-size'] : 500;
    $user_ids_param = isset($options['users']) ? $options['users'] : null;
    
    // Determine which users to process
    if ($user_ids_param) {
        $user_ids = array_map('intval', explode(',', $user_ids_param));
        log_message("Processing specific users: " . implode(', ', $user_ids));
    } elseif ($stale_only) {
        $user_ids = $CI->notification_context_model->get_stale_contexts($batch_size);
        log_message("Processing {count} stale contexts", ['count' => count($user_ids)]);
    } else {
        $user_ids = get_active_user_ids($batch_size);
        log_message("Processing {count} active users", ['count' => count($user_ids)]);
    }
    
    if (empty($user_ids)) {
        log_message("No users to process. Exiting.");
        exit(0);
    }
    
    $processed = 0;
    $failed = 0;
    $errors = [];
    
    foreach ($user_ids as $user_id) {
        try {
            $context = build_user_context($user_id);
            
            if ($context) {
                $result = $CI->notification_context_model->upsert_context($user_id, $context);
                if ($result) {
                    $processed++;
                    if ($processed % 50 == 0) {
                        log_message("Progress: {$processed} users processed...");
                    }
                } else {
                    $failed++;
                    $errors[] = "Failed to save context for user {$user_id}";
                }
            } else {
                $failed++;
                $errors[] = "Failed to build context for user {$user_id}";
            }
        } catch (Exception $e) {
            $failed++;
            $errors[] = "User {$user_id}: " . $e->getMessage();
        }
    }
    
    $duration = round(microtime(true) - $start_time, 2);
    
    log_message("Context building completed:");
    log_message("  Processed: {$processed}");
    log_message("  Failed: {$failed}");
    log_message("  Total: " . count($user_ids));
    log_message("  Duration: {$duration}s");
    
    if (!empty($errors) && count($errors) <= 10) {
        log_message("Errors:");
        foreach ($errors as $error) {
            log_message("  - {$error}");
        }
    } elseif (count($errors) > 10) {
        log_message("Errors: " . count($errors) . " errors occurred (showing first 10)");
        foreach (array_slice($errors, 0, 10) as $error) {
            log_message("  - {$error}");
        }
    }
    
    exit(0);
    
} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Log message to file and stdout
 */
function log_message($message, $context = []) {
    global $log_file;
    
    $timestamp = date('Y-m-d H:i:s');
    
    // Replace placeholders
    foreach ($context as $key => $value) {
        $message = str_replace("{{$key}}", $value, $message);
    }
    
    $log_line = "[{$timestamp}] {$message}\n";
    
    // Output to console
    echo $log_line;
    
    // Write to log file
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    file_put_contents($log_file, $log_line, FILE_APPEND);
}

/**
 * Get active user IDs
 */
function get_active_user_ids($limit) {
    global $CI;
    
    $query = $CI->db->query("
        SELECT id 
        FROM user 
        WHERE notification_enabled = 1 
          AND fcm_token IS NOT NULL 
          AND is_deleted = 0
        ORDER BY last_activity DESC
        LIMIT ?
    ", [$limit]);
    
    $result = $query->result_array();
    return array_column($result, 'id');
}

/**
 * Build user context (simplified version - delegates to actual implementation)
 */
function build_user_context($user_id) {
    global $CI;
    
    // Load the actual context building logic from your controller
    // For now, this is a placeholder that returns mock data
    // In production, you'd call your actual context building methods
    
    try {
        // Get user
        $user = $CI->user_model->get_user($user_id);
        if (!$user) {
            return null;
        }
        
        // Build context components
        $context = [
            // Activity data
            'last_quiz_date' => null,
            'days_since_last_quiz' => 0,
            'quiz_streak_days' => 0,
            'weekly_quiz_count' => 0,
            'monthly_quiz_count' => 0,
            
            // Performance
            'current_accuracy_percentage' => 0,
            'performance_category' => 'beginner',
            
            // Flags
            'is_active_user' => 1,
            'eligible_for_notification' => 1,
            
            // Metadata
            'computation_duration_ms' => 0,
            'computation_version' => '1.0',
            'is_stale' => 0
        ];
        
        // TODO: Implement actual context building logic here
        // For now, return basic structure
        
        return $context;
        
    } catch (Exception $e) {
        log_message("Error building context for user {$user_id}: " . $e->getMessage());
        return null;
    }
}
