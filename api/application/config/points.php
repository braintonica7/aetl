<?php
/**
 * Point System Configuration
 * 
 * Configuration settings for WiziAI Point System
 * 
 * @author WiziAI Development Team  
 * @version 1.0
 * @date September 20, 2025
 * 
 * LOADING: This config file is auto-loaded via application/config/autoload.php
 * No need to manually load it in controllers or libraries.
 * 
 * USAGE: Access config values using $this->config->item('config_key')
 * Example: $this->config->item('points_weekly_limit_default')
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Point System Configuration
|--------------------------------------------------------------------------
|
| This file contains all configuration settings for the WiziAI point system
| including point values, limits, bonuses, and administrative settings.
|
*/

// Basic Point Values
$config['points_low_score'] = 120;         // Points for score < 50%
$config['points_medium_score'] = 240;      // Points for score 50-90%
$config['points_high_score'] = 360;        // Points for score > 90%

// Score Thresholds
$config['points_score_threshold_low'] = 50.0;   // Below this = low score
$config['points_score_threshold_high'] = 90.0;  // Above this = high score

// Bonus Points
$config['points_bonus_no_skip'] = 60;          // Bonus for no skipped questions
$config['points_bonus_hard_level'] = 120;        // Bonus for hard difficulty quiz
$config['points_bonus_perfect_speed'] = 360;     // Bonus for perfect score + high speed (future)
$config['points_bonus_signup'] = 120;            // Welcome bonus for new user signup

// System Limits and Requirements
$config['points_minimum_quiz_questions'] = 20;  // Minimum questions required for points
$config['points_weekly_limit_default'] = 1200;  // Default weekly point limit
$config['points_weekly_reset_day'] = 1;         // 1 = Monday (ISO standard)

// AI Tutor Configuration
$config['points_ai_tutor_rate'] = 120;      // Points per minute of AI tutor time

// Administrative Settings
$config['points_admin_override_enabled'] = false; // Global admin override for weekly limits
$config['points_system_enabled'] = true;        // Master switch for point system
$config['points_logging_enabled'] = true;       // Enable detailed transaction logging

// Anti-Gaming Settings
$config['points_max_daily_quizzes'] = 5;       // Maximum quizzes per day for points
$config['points_min_time_per_question'] = 30;    // Minimum seconds per question (anti-rapid clicking)
$config['points_duplicate_check_enabled'] = true; // Prevent duplicate point awards

// Validation Settings
$config['points_require_minimum_score'] = true; // Require minimum score for any points
$config['points_minimum_score_threshold'] = 20;   // Minimum score required (if enabled)
$config['points_minimum_questions_answered_percentage'] = 90; // Minimum % of questions that must be answered (not skipped)

// Feature Flags
$config['points_retroactive_enabled'] = false;   // Allow retroactive point calculation
$config['points_leaderboard_enabled'] = true;    // Enable point-based leaderboards
$config['points_notifications_enabled'] = true;  // Enable point earning notifications

// Cache Settings
$config['points_cache_enabled'] = false;         // Enable caching for point calculations
$config['points_cache_ttl'] = 300;              // Cache TTL in seconds (5 minutes)

// Error Handling
$config['points_fail_silently'] = false;        // Whether to fail silently on point errors
$config['points_log_level'] = 'info';           // Logging level: debug, info, warning, error

/*
|--------------------------------------------------------------------------
| Environment-Specific Overrides
|--------------------------------------------------------------------------
|
| You can override any of the above settings based on environment
| Uncomment and modify as needed for different environments
|
*/

// Development Environment Overrides
if (ENVIRONMENT === 'development') {
    // $config['points_weekly_limit_default'] = 5000;  // Higher limit for testing
    // $config['points_logging_enabled'] = true;       // Always log in development
    // $config['points_min_time_per_question'] = 1;    // Lower for faster testing
}

// Production Environment Overrides  
if (ENVIRONMENT === 'production') {
    // $config['points_fail_silently'] = true;         // Fail silently in production
    // $config['points_log_level'] = 'warning';        // Less verbose logging
    // $config['points_cache_enabled'] = true;         // Enable caching in production
}

// Testing Environment Overrides
if (ENVIRONMENT === 'testing') {
    // $config['points_system_enabled'] = false;       // Disable points in testing
    // $config['points_weekly_limit_default'] = 99999; // No limits for testing
}

/*
|--------------------------------------------------------------------------
| Point Calculation Formulas (for reference)
|--------------------------------------------------------------------------
|
| Base Points Calculation:
| - Score < 50%: 120 points  
| - Score 50-90%: 240 points
| - Score > 90%: 360 points
|
| Bonus Points:
| - No skipped questions: +60 points
| - Hard level quiz: +120 points  
| - Perfect score + high speed: +360 points (future implementation)
| - New user signup: +120 points (one-time welcome bonus)
|
| Requirements:
| - Quiz must have minimum 20 questions
| - Must answer at least 90% of questions (not skip them)
| - Weekly limit: 1200 points (resets Monday)
| - Points awarded only via complete_quiz_post() method
| - Signup bonus bypasses weekly limits (admin adjustment)
|
| AI Tutor Conversion:
| - 120 points = 1 minute of AI tutor time
| - Automatic conversion upon point earning
|
*/