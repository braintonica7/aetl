<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
| example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
| https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
| $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
| $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
| $route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples: my-controller/index -> my_controller/index
|   my-controller/my-method -> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = true;

/*
| -------------------------------------------------------------------------
| Sample REST API Routes
| -------------------------------------------------------------------------
*/
$route['api/example/users/(:num)'] = 'api/example/users/id/$1'; // Example 4
$route['api/example/users/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'api/example/users/id/$1/format/$3$4'; // Example 8

/*
| -------------------------------------------------------------------------
| User Points API Routes
| -------------------------------------------------------------------------
*/
// User endpoints
$route['api/user_points/summary/(:num)'] = 'api/user_points/summary/$1';
$route['api/user_points/transactions/(:num)'] = 'api/user_points/transactions/$1';
$route['api/user_points/leaderboard'] = 'api/user_points/leaderboard';

// AI Tutor endpoints
$route['api/user_points/ai_tutor/start'] = 'api/user_points/ai_tutor_start';
$route['api/user_points/ai_tutor/end'] = 'api/user_points/ai_tutor_end';

// Admin endpoints
$route['api/user_points/admin/user_report/(:num)'] = 'api/user_points/admin_user_report/$1';
$route['api/user_points/admin/adjust'] = 'api/user_points/admin_adjust';
$route['api/user_points/admin/system_stats'] = 'api/user_points/admin_system_stats';

/*
| -------------------------------------------------------------------------
| Question API Routes
| -------------------------------------------------------------------------
*/
// Question flagging endpoints - these need to come before the generic question routes
$route['api/question/(:num)/flag'] = 'api/question/flag/$1';
$route['api/question/(:num)/unflag'] = 'api/question/unflag/$1';
$route['api/question/flagged'] = 'api/question/flagged';

/*
| -------------------------------------------------------------------------
| Subscription Management API Routes
| -------------------------------------------------------------------------
*/
// Subscription Plans (Admin)
$route['api/subscription-plans'] = 'api/subscription_admin/plans';
$route['api/subscription-plans/(:num)'] = 'api/subscription_admin/plans/$1';

// User Subscriptions (Admin)
$route['api/user-subscriptions'] = 'api/subscription_admin/subscriptions';
$route['api/user-subscriptions/(:num)'] = 'api/subscription_admin/subscriptions/$1';

// Subscription Analytics (Admin)
$route['api/subscription-analytics'] = 'api/subscription_admin/analytics';
$route['api/subscription-admin/analytics'] = 'api/subscription_admin/analytics';

// Subscription Transactions (Admin)
$route['api/subscription-transactions'] = 'api/subscription_admin/transactions';
$route['api/subscription-transactions/(:num)'] = 'api/subscription_admin/transactions/$1';

// Feature Definition Management (Admin)
$route['api/subscription-features'] = 'api/subscription_admin/features';
$route['api/subscription-features/(:num)'] = 'api/subscription_admin/features/$1';

// Plan Feature Assignment Management (Admin)
$route['api/plan-features/(:num)'] = 'api/subscription_admin/plan_features/$1';
$route['api/plan-features/(:num)/(:num)'] = 'api/subscription_admin/plan_features/$1/$2';

// User-facing Subscription APIs
$route['api/subscription/plans'] = 'api/subscription/plans';
$route['api/subscription/current'] = 'api/subscription/current';
$route['api/subscription/subscribe'] = 'api/subscription/subscribe';
$route['api/subscription/cancel'] = 'api/subscription/cancel';

// Payment APIs
$route['api/payment/create-order'] = 'api/payment/create_order';
$route['api/payment/verify'] = 'api/payment/verify';
$route['api/payment/webhook'] = 'api/payment/webhook';

/*
| -------------------------------------------------------------------------
| WiZi Quiz and Question Management Routes
| -------------------------------------------------------------------------
*/
// WiZi Quiz routes - map hyphenated URLs to underscored controllers
$route['api/wizi-quiz'] = 'api/wizi_quiz/index';
$route['api/wizi-quiz/(:num)'] = 'api/wizi_quiz/index/$1';

// WiZi Question routes - map hyphenated URLs to underscored controllers
$route['api/wizi-question'] = 'api/wizi_question/index';
$route['api/wizi-question/(:num)'] = 'api/wizi_question/index/$1';

// WiZi Quiz Question routes - map hyphenated URLs to underscored controllers
$route['api/wizi-quiz-question'] = 'api/wizi_quiz_question/index';
$route['api/wizi-quiz-question/available'] = 'api/wizi_quiz_question/available';
$route['api/wizi-quiz-question/bulk_add'] = 'api/wizi_quiz_question/bulk_add';
$route['api/wizi-quiz-question/(:num)'] = 'api/wizi_quiz_question/index/$1';

// WiZi Quiz User (Attempts) routes - map hyphenated URLs to underscored controllers
$route['api/wizi-quiz-user'] = 'api/wizi_quiz_user/index';
$route['api/wizi-quiz-user/(:num)'] = 'api/wizi_quiz_user/index_get_by_id/$1';

/*
| -------------------------------------------------------------------------
| User Notifications API Routes (For Web Dashboard)
| -------------------------------------------------------------------------
*/
$route['api/notifications/user-notifications'] = 'api/user_notifications/get_notifications';
$route['api/notifications/mark-read'] = 'api/user_notifications/mark_read';
$route['api/notifications/mark-all-read'] = 'api/user_notifications/mark_all_read';
$route['api/notifications/delete/(:num)'] = 'api/user_notifications/delete/$1';
