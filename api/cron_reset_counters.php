#!/usr/bin/env php
<?php
/**
 * Reset Daily Notification Counters - Cron Job Script
 * 
 * Resets the daily notification counters for all users.
 * Should be run at midnight (00:00) every day.
 * 
 * Usage:
 *   php /path/to/cron_reset_counters.php
 * 
 * DirectAdmin Cron Setup:
 *   Minute: 0
 *   Hour: 0
 *   Day: *
 *   Month: *
 *   Weekday: *
 *   Command: php /home/username/domains/yourdomain.com/public_html/api/cron_reset_counters.php
 * 
 * @package WiziAI
 * @category Cron
 */

// Ensure script is run from CLI only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

set_time_limit(300);

$script_dir = dirname(__FILE__);
$log_file = $script_dir . '/logs/cron_reset_counters.log';

// ============================================================================
// Bootstrap CodeIgniter
// ============================================================================

define('BASEPATH', $script_dir . '/system/');
define('APPPATH', $script_dir . '/application/');
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');

require_once(BASEPATH . 'core/Common.php');
require_once(APPPATH . 'config/constants.php');

$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once(BASEPATH . 'core/CodeIgniter.php');

// ============================================================================
// Main Execution
// ============================================================================

$start_time = microtime(true);
log_message("Starting daily counter reset...");

try {
    $CI =& get_instance();
    $CI->load->database();
    $CI->load->model('notification/notification_context_model');
    
    $result = $CI->notification_context_model->reset_daily_counters();
    
    $duration = round(microtime(true) - $start_time, 2);
    
    if ($result) {
        log_message("✓ Daily counters reset successfully (Duration: {$duration}s)");
        exit(0);
    } else {
        log_message("✗ Failed to reset daily counters");
        exit(1);
    }
    
} catch (Exception $e) {
    log_message("✗ FATAL ERROR: " . $e->getMessage());
    exit(1);
}

// ============================================================================
// Helper Functions
// ============================================================================

function log_message($message) {
    global $log_file;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[{$timestamp}] {$message}\n";
    
    echo $log_line;
    
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    file_put_contents($log_file, $log_line, FILE_APPEND);
}
