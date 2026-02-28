<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  | -------------------------------------------------------------------
  | AUTO-LOADER
  | -------------------------------------------------------------------
  | This file specifies which systems should be loaded by default.
  |
  | In order to keep the framework as light-weight as possible only the
  | absolute minimal resources are loaded by default. For example,
  | the database is not connected to automatically since no assumption
  | is made regarding whether you intend to use it.  This file lets
  | you globally define which systems you would like loaded with every
  | request.
  |
  | -------------------------------------------------------------------
  | Instructions
  | -------------------------------------------------------------------
  |
  | These are the things you can load automatically:
  |
  | 1. Packages
  | 2. Libraries
  | 3. Drivers
  | 4. Helper files
  | 5. Custom config files
  | 6. Language files
  | 7. Models
  |
 */

/*
  | -------------------------------------------------------------------
  |  Auto-load Packages
  | -------------------------------------------------------------------
  | Prototype:
  |
  |  $autoload['packages'] = array(APPPATH.'third_party', '/usr/local/shared');
  |
 */
$autoload['packages'] = array();

/*
  | -------------------------------------------------------------------
  |  Auto-load Libraries
  | -------------------------------------------------------------------
  | These are the classes located in system/libraries/ or your
  | application/libraries/ directory, with the addition of the
  | 'database' library, which is somewhat of a special case.
  |
  | Prototype:
  |
  |	$autoload['libraries'] = array('database', 'email', 'session');
  |
  | You can also supply an alternative library name to be assigned
  | in the controller:
  |
  |	$autoload['libraries'] = array('user_agent' => 'ua');
 */
$autoload['libraries'] = array('CDatabase', 'CUtility', 'CPreference');

/*
  | -------------------------------------------------------------------
  |  Auto-load Drivers
  | -------------------------------------------------------------------
  | These classes are located in system/libraries/ or in your
  | application/libraries/ directory, but are also placed inside their
  | own subdirectory and they extend the CI_Driver_Library class. They
  | offer multiple interchangeable driver options.
  |
  | Prototype:
  |
  |	$autoload['drivers'] = array('cache');
  |
  | You can also supply an alternative property name to be assigned in
  | the controller:
  |
  |	$autoload['drivers'] = array('cache' => 'cch');
  |
 */
$autoload['drivers'] = array();

/*
  | -------------------------------------------------------------------
  |  Auto-load Helper Files
  | -------------------------------------------------------------------
  | Prototype:
  |
  |	$autoload['helper'] = array('url', 'file');
 */
$autoload['helper'] = array('url');

/*
  | -------------------------------------------------------------------
  |  Auto-load Config files
  | -------------------------------------------------------------------
  | Prototype:
  |
  |	$autoload['config'] = array('config1', 'config2');
  |
  | NOTE: This item is intended for use ONLY if you have created custom
  | config files.  Otherwise, leave it blank.
  |
 */
$autoload['config'] = array('points', 'quiz');

/*
  | -------------------------------------------------------------------
  |  Auto-load Language files
  | -------------------------------------------------------------------
  | Prototype:
  |
  |	$autoload['language'] = array('lang1', 'lang2');
  |
  | NOTE: Do not include the "_lang" part of your file.  For example
  | "codeigniter_lang.php" would be referenced as array('codeigniter');
  |
 */
$autoload['language'] = array();

/*
  | -------------------------------------------------------------------
  |  Auto-load Models
  | -------------------------------------------------------------------
  | Prototype:
  |
  |	$autoload['model'] = array('first_model', 'second_model');
  |
  | You can also supply an alternative model name to be assigned
  | in the controller:
  |
  |	$autoload['model'] = array('first_model' => 'first');
 */
$autoload['model'] = array(
    'role/role_object',
    'scholar/scholar_object',
    'employee/employee_object',
    'user/user_object',
    'subject/subject_object',
    'org/org_object',
    'account/account_object',
    'quiz/quiz_object',
		'question/question_object',
		'quiz_question/quiz_question_object',
    'quiz_scholar/quiz_scholar_object',
    'student/student_object',
    'exam/exam_object',
    'topic/topic_object',
    'chapter/chapter_object',
    'quiz_subject/quiz_subject_object',
    'user_question/user_question_object',
    'user_performance/user_performance_object',
    'user_performance_summary/user_performance_summary_object',
    'question/question_status_history_object',
    'user/user_profile_object',
    'exam/exam_subject_object',
    'subscription/Subscription_plan_object',
    'subscription/User_subscription_object',
    'subscription/Subscription_transaction_object',
    'subscription/Subscription_usage_tracking_object',
    'subscription/Subscription_payment_method_object',
    'subscription/Subscription_feature_definition_object',
    'wizi_quiz/Wizi_quiz_object',
    'wizi_quiz/Wizi_quiz_question_object',
		'wizi_quiz/Wizi_quiz_question_user_object',
    'wizi_quiz/Wizi_quiz_user_object',
    'wizi_quiz/Wizi_question_object',
    'wizi_quiz/Wizi_quiz_user_object'
);
