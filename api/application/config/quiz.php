<?php
/**
 * Quiz System Configuration
 * 
 * Configuration settings for WiziAI Quiz System including custom quiz limits
 * 
 * @author WiziAI Development Team  
 * @version 1.0
 * @date October 11, 2025
 * 
 * LOADING: This config file is auto-loaded via application/config/autoload.php
 * No need to manually load it in controllers or libraries.
 * 
 * USAGE: Access config values using $this->config->item('config_key')
 * Example: $this->config->item('quiz_custom_limit_free')
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Quiz System Configuration
|--------------------------------------------------------------------------
|
| This file contains all configuration settings for the WiziAI quiz system
| including custom quiz limits, user quotas, and quiz generation settings.
|
*/

// Custom Quiz Limits by User Type
$config['quiz_custom_limit_free'] = 20;          // Maximum custom quizzes for free users
$config['quiz_custom_limit_premium'] = 100;      // Maximum custom quizzes for premium users
$config['quiz_custom_limit_admin'] = -1;         // Unlimited for admin users (-1 = unlimited)

// Quiz Generation Limits
$config['quiz_max_questions_per_quiz'] = 60;     // Maximum questions per custom quiz
$config['quiz_min_questions_per_quiz'] = 1;      // Minimum questions per custom quiz

// User Subscription Types
$config['quiz_user_type_free'] = 'free';         // Free user identifier
$config['quiz_user_type_premium'] = 'premium';   // Premium user identifier
$config['quiz_user_type_admin'] = 'admin';       // Admin user identifier

// Default user type for new users and existing users without subscription type
$config['quiz_default_user_type'] = 'free';      // Default user type

// Quota Reset Configuration
$config['quiz_quota_reset_period'] = 'monthly';  // Options: 'monthly', 'yearly', 'never'
$config['quiz_quota_reset_day'] = 1;             // Day of month to reset (1-31)

// Feature Flags
$config['quiz_enable_custom_limits'] = true;     // Enable/disable custom quiz limits
$config['quiz_enable_quota_warnings'] = true;    // Show warnings when approaching limits
$config['quiz_quota_warning_threshold'] = 0.8;   // Warn when user reaches 80% of limit

// Error Messages
$config['quiz_error_limit_exceeded'] = 'You have reached your custom quiz limit. Upgrade to premium for more quizzes.';
$config['quiz_error_limit_check_failed'] = 'Unable to verify quiz limits. Please try again.';
$config['quiz_warning_approaching_limit'] = 'You are approaching your custom quiz limit ({remaining} quizzes remaining).';

// Logging Configuration
$config['quiz_log_quota_violations'] = true;     // Log when users exceed quotas
$config['quiz_log_quota_warnings'] = false;      // Log quota warnings