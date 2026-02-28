<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Subscription_admin extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    /**
     * Get all subscription plans (admin view with all details)
     * GET /api/subscription_admin/plans
     */
    /**
     * Get subscription plans (admin)
     * GET /api/subscription_admin/plans - get all plans
     * GET /api/subscription_admin/plans/{id} - get specific plan
     */
    function plans_get($plan_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $this->load->model('subscription/subscription_plan_model');
            
            // If plan_id is provided, return single plan
            if ($plan_id !== NULL) {
                $plan = $this->subscription_plan_model->get_subscription_plan($plan_id);
                if ($plan) {
                    $plan_features = $this->subscription_plan_model->get_plan_features($plan->id);
                    
                    $plan_data = array(
                        'id' => $plan->id,
                        'plan_key' => $plan->plan_key,
                        'plan_name' => $plan->plan_name,
                        'plan_description' => $plan->plan_description,
                        'monthly_price_inr' => $plan->monthly_price_inr,
                        'academic_session_price_inr' => $plan->academic_session_price_inr,
                        'is_free' => $plan->is_free == 1,
                        'is_default' => $plan->is_default == 1,
                        'is_active' => $plan->is_active == 1,
                        'sort_order' => $plan->sort_order,
                        'plan_color' => $plan->plan_color,
                        'features' => $plan_features,
                        'created_at' => $plan->created_at,
                        'updated_at' => $plan->updated_at
                    );
                    
                    $response = $this->get_success_response($plan_data, "Subscription plan retrieved successfully");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "Subscription plan not found");
                    $this->set_output($response);
                }
                return;
            }
            
            // Get pagination parameters for list view
            $page = $this->input->get('page') ?: 1;
            $pageSize = $this->input->get('pagesize') ?: 25;
            $sortBy = $this->input->get('sortby') ?: 'sort_order';
            $sortOrder = $this->input->get('sortorder') ?: 'ASC';
            $filter = $this->input->get('filter');
            
            // Parse filter if provided
            $objFilter = NULL;
            if ($filter && $filter != '{}') {
                $objFilter = json_decode($filter, true);
            }
            
            // Build filter string
            $filterString = "";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    if (strpos($key, '~like') !== false) {
                        $field = str_replace('~like', '', $key);
                        $filterString .= "$field LIKE('%$value%') AND ";
                    } else if (strpos($key, '>=') !== false) {
                        $field = str_replace('>=', '', $key);
                        $filterString .= "$field >= '$value' AND ";
                    } else if (strpos($key, '<=') !== false) {
                        $field = str_replace('<=', '', $key);
                        $filterString .= "$field <= '$value' AND ";
                    } else {
                        $filterString .= "$key = '$value' AND ";
                    }
                }
                if (strlen($filterString) > 0) {
                    $filterString = substr($filterString, 0, strlen($filterString) - 5); // Remove last " AND "
                }
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM subscription_plans";
            if ($filterString) {
                $countSql .= " WHERE $filterString";
            }
            
            $pdo = CDatabase::getPdo();
            $countStatement = $pdo->prepare($countSql);
            $countStatement->execute();
            $totalCount = $countStatement->fetch()['total'];
            
            // Get paginated data
            $sql = "SELECT * FROM subscription_plans";
            if ($filterString) {
                $sql .= " WHERE $filterString";
            }
            $sql .= " ORDER BY $sortBy $sortOrder";
            
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT $pageSize OFFSET $offset";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $plans = array();
            while ($row = $statement->fetch()) {
                $plan_features = $this->subscription_plan_model->get_plan_features($row['id']);
                
                $plan = array(
                    'id' => $row['id'],
                    'plan_key' => $row['plan_key'],
                    'plan_name' => $row['plan_name'],
                    'plan_description' => $row['plan_description'],
                    'monthly_price_inr' => $row['monthly_price_inr'],
                    'academic_session_price_inr' => $row['academic_session_price_inr'],
                    'is_free' => $row['is_free'] == 1,
                    'is_default' => $row['is_default'] == 1,
                    'is_active' => $row['is_active'] == 1,
                    'sort_order' => $row['sort_order'],
                    'plan_color' => $row['plan_color'],
                    'features' => $plan_features,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                );
                
                $plans[] = $plan;
            }
            
            $statement = NULL;
            $countStatement = NULL;
            $pdo = NULL;
            
            if (count($plans) > 0) {
                $response = $this->get_success_response($plans, "Admin subscription plans retrieved successfully");
                $response['total'] = $totalCount;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response(array(), "No subscription plans found");
                $response['total'] = 0;
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Admin get subscription plans error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving subscription plans");
            $this->set_output($response);
        }
    }

    /**
     * Create new subscription plan
     * POST /api/subscription_admin/plans
     */
    function plans_post() {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request['plan_key']) || empty($request['plan_name'])) {
            $response = $this->get_failed_response(NULL, "Plan key and plan name are required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_plan_model');
            
            // Check if plan key already exists
            $existing_plan = $this->subscription_plan_model->get_subscription_plan_by_key($request['plan_key']);
            if ($existing_plan) {
                $response = $this->get_failed_response(NULL, "Plan key already exists");
                $this->set_output($response);
                return;
            }
            
            // Create plan object
            $objPlan = new Subscription_plan_object();
            $objPlan->plan_key = $request['plan_key'];
            $objPlan->plan_name = $request['plan_name'];
            $objPlan->plan_description = isset($request['plan_description']) ? $request['plan_description'] : '';
            $objPlan->monthly_price_inr = isset($request['monthly_price_inr']) ? $request['monthly_price_inr'] : 0.00;
            $objPlan->academic_session_price_inr = isset($request['academic_session_price_inr']) ? $request['academic_session_price_inr'] : 0.00;
            $objPlan->is_free = isset($request['is_free']) ? $request['is_free'] : 0;
            $objPlan->is_default = isset($request['is_default']) ? $request['is_default'] : 0;
            $objPlan->is_active = isset($request['is_active']) ? $request['is_active'] : 1;
            $objPlan->sort_order = isset($request['sort_order']) ? $request['sort_order'] : 0;
            $objPlan->plan_color = isset($request['plan_color']) ? $request['plan_color'] : '#007bff';
            
            // Save plan
            $result = $this->subscription_plan_model->add_subscription_plan($objPlan);
            
            if ($result !== FALSE) {
                $response = $this->get_success_response($result, "Subscription plan created successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to create subscription plan");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Admin create subscription plan error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error creating subscription plan: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Update subscription plan
     * PUT /api/subscription_admin/plans/{plan_id}
     */
    function plans_put($plan_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($plan_id == NULL) {
            $response = $this->get_failed_response(NULL, "Plan ID is required");
            $this->set_output($response);
            return;
        }

        $request = $this->get_request();

        try {
            $this->load->model('subscription/subscription_plan_model');
            
            // Get existing plan
            $objPlan = $this->subscription_plan_model->get_subscription_plan($plan_id);
            if (!$objPlan) {
                $response = $this->get_failed_response(NULL, "Subscription plan not found");
                $this->set_output($response);
                return;
            }
            
            // Update plan fields
            if (isset($request['plan_name'])) $objPlan->plan_name = $request['plan_name'];
            if (isset($request['plan_description'])) $objPlan->plan_description = $request['plan_description'];
            if (isset($request['monthly_price_inr'])) $objPlan->monthly_price_inr = $request['monthly_price_inr'];
            if (isset($request['academic_session_price_inr'])) $objPlan->academic_session_price_inr = $request['academic_session_price_inr'];
            if (isset($request['is_free'])) $objPlan->is_free = $request['is_free'];
            if (isset($request['is_default'])) $objPlan->is_default = $request['is_default'];
            if (isset($request['is_active'])) $objPlan->is_active = $request['is_active'];
            if (isset($request['sort_order'])) $objPlan->sort_order = $request['sort_order'];
            if (isset($request['plan_color'])) $objPlan->plan_color = $request['plan_color'];
            
            // Save plan
            $result = $this->subscription_plan_model->update_subscription_plan($objPlan);
            
            if ($result) {
                $response = $this->get_success_response($objPlan, "Subscription plan updated successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to update subscription plan");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Admin update subscription plan error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error updating subscription plan: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Delete subscription plan
     * DELETE /api/subscription_admin/plans/{plan_id}
     */
    function plans_delete($plan_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($plan_id == NULL) {
            $response = $this->get_failed_response(NULL, "Plan ID is required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_plan_model');
            
            // Check if plan exists
            $plan = $this->subscription_plan_model->get_subscription_plan($plan_id);
            if (!$plan) {
                $response = $this->get_failed_response(NULL, "Subscription plan not found");
                $this->set_output($response);
                return;
            }
            
            // Soft delete plan
            $result = $this->subscription_plan_model->delete_subscription_plan($plan_id);
            
            if ($result) {
                $response = $this->get_success_response(array('plan_id' => $plan_id), "Subscription plan deleted successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to delete subscription plan");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Admin delete subscription plan error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error deleting subscription plan: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get all user subscriptions with filters
     * GET /api/subscription_admin/subscriptions
     */
    /**
     * Get user subscriptions (admin)
     * GET /api/subscription_admin/subscriptions - get all subscriptions
     * GET /api/subscription_admin/subscriptions/{id} - get specific subscription
     */
    function subscriptions_get($subscription_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $this->load->model('subscription/user_subscription_model');
            
            // If subscription_id is provided, return single subscription
            if ($subscription_id !== NULL) {
                $subscription = $this->user_subscription_model->get_user_subscription($subscription_id);
                if ($subscription) {
                    $response = $this->get_success_response($subscription, "User subscription retrieved successfully");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "User subscription not found");
                    $this->set_output($response);
                }
                return;
            }
            
            // Get pagination parameters for list view
            $page = $this->input->get('page') ?: 1;
            $pageSize = $this->input->get('pagesize') ?: 25;
            $sortBy = $this->input->get('sortby') ?: 'created_at';
            $sortOrder = $this->input->get('sortorder') ?: 'DESC';
            $filter = $this->input->get('filter');
            
            // Parse filter if provided
            $objFilter = NULL;
            if ($filter && $filter != '{}') {
                $objFilter = json_decode($filter, true);
            }
            
            // Build filter string
            $filterString = "1=1";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    if (strpos($key, '~like') !== false) {
                        $field = str_replace('~like', '', $key);
                        if ($field == 'username') {
                            $filterString .= " AND u.username LIKE('%$value%')";
                        } else if ($field == 'user_display_name') {
                            $filterString .= " AND u.display_name LIKE('%$value%')";
                        } else {
                            $filterString .= " AND us.$field LIKE('%$value%')";
                        }
                    } else if (strpos($key, '>=') !== false) {
                        $field = str_replace('>=', '', $key);
                        $filterString .= " AND us.$field >= '$value'";
                    } else if (strpos($key, '<=') !== false) {
                        $field = str_replace('<=', '', $key);
                        $filterString .= " AND us.$field <= '$value'";
                    } else {
                        if ($key == 'user_id') {
                            $filterString .= " AND us.user_id = '$value'";
                        } else if ($key == 'plan_id') {
                            $filterString .= " AND us.plan_id = '$value'";
                        } else {
                            $filterString .= " AND us.$key = '$value'";
                        }
                    }
                }
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                        FROM user_subscriptions us 
                        JOIN subscription_plans sp ON us.plan_id = sp.id 
                        JOIN user u ON us.user_id = u.id 
                        WHERE $filterString";
            
            $pdo = CDatabase::getPdo();
            $countStatement = $pdo->prepare($countSql);
            $countStatement->execute();
            $totalCount = $countStatement->fetch()['total'];
            
            // Get paginated data
            $sql = "SELECT us.*, sp.plan_name, sp.plan_key, sp.plan_color, u.display_name, u.username 
                    FROM user_subscriptions us 
                    JOIN subscription_plans sp ON us.plan_id = sp.id 
                    JOIN user u ON us.user_id = u.id 
                    WHERE $filterString
                    ORDER BY us.$sortBy $sortOrder";
            
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT $pageSize OFFSET $offset";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $subscriptions = array();
            while ($row = $statement->fetch()) {
                $subscription = array(
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'user_display_name' => $row['display_name'],
                    'username' => $row['username'],
                    'plan_id' => $row['plan_id'],
                    'plan_name' => $row['plan_name'],
                    'plan_key' => $row['plan_key'],
                    'plan_color' => $row['plan_color'],
                    'billing_cycle' => $row['billing_cycle'],
                    'subscription_status' => $row['subscription_status'],
                    'starts_at' => $row['starts_at'],
                    'expires_at' => $row['expires_at'],
                    'next_billing_date' => $row['next_billing_date'],
                    'auto_renew' => $row['auto_renew'] == 1,
                    'cancelled_at' => $row['cancelled_at'],
                    'cancellation_reason' => $row['cancellation_reason'],
                    'razorpay_subscription_id' => $row['razorpay_subscription_id'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                );
                
                $subscriptions[] = $subscription;
            }
            
            $statement = NULL;
            $countStatement = NULL;
            $pdo = NULL;
            
            if (count($subscriptions) > 0) {
                $response = $this->get_success_response($subscriptions, "User subscriptions retrieved successfully");
                $response['total'] = $totalCount;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response(array(), "No user subscriptions found");
                $response['total'] = 0;
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Admin get user subscriptions error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving user subscriptions");
            $this->set_output($response);
        }
    }

    /**
     * Update user subscription (admin action)
     * PUT /api/subscription_admin/subscriptions/{subscription_id}
     */
    function subscriptions_put($subscription_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($subscription_id == NULL) {
            $response = $this->get_failed_response(NULL, "Subscription ID is required");
            $this->set_output($response);
            return;
        }

        $request = $this->get_request();

        try {
            $this->load->model('subscription/user_subscription_model');
            
            // Get existing subscription
            $subscription = $this->user_subscription_model->get_user_subscription($subscription_id);
            if (!$subscription) {
                $response = $this->get_failed_response(NULL, "Subscription not found");
                $this->set_output($response);
                return;
            }
            
            // Update subscription fields
            if (isset($request['subscription_status'])) $subscription->subscription_status = $request['subscription_status'];
            if (isset($request['expires_at'])) $subscription->expires_at = $request['expires_at'];
            if (isset($request['auto_renew'])) $subscription->auto_renew = $request['auto_renew'];
            if (isset($request['cancellation_reason'])) $subscription->cancellation_reason = $request['cancellation_reason'];
            
            // Save subscription
            $result = $this->user_subscription_model->update_user_subscription($subscription);
            
            if ($result) {
                $formatted_subscription = array(
                    'id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id,
                    'subscription_status' => $subscription->subscription_status,
                    'expires_at' => $subscription->expires_at,
                    'auto_renew' => $subscription->auto_renew,
                    'updated_at' => date('Y-m-d H:i:s')
                );
                
                $response = $this->get_success_response($formatted_subscription, "Subscription updated successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to update subscription");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Admin update subscription error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error updating subscription: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get subscription analytics and reports
     * GET /api/subscription_admin/analytics
     */
    function analytics_get() {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $start_date = $this->input->get('start_date') ?: date('Y-m-01'); // First day of current month
        $end_date = $this->input->get('end_date') ?: date('Y-m-d'); // Today

        try {
            $analytics = array();
            
            // Subscription counts by status
            $sql = "SELECT subscription_status, COUNT(*) as count FROM user_subscriptions GROUP BY subscription_status";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $subscription_counts = array();
            while ($row = $statement->fetch()) {
                $subscription_counts[$row['subscription_status']] = $row['count'];
            }
            $analytics['subscription_counts'] = $subscription_counts;
            
            // Subscription counts by plan
            $sql = "SELECT sp.plan_name, sp.plan_key, COUNT(us.id) as count 
                    FROM subscription_plans sp 
                    LEFT JOIN user_subscriptions us ON sp.id = us.plan_id AND us.subscription_status = 'active'
                    GROUP BY sp.id, sp.plan_name, sp.plan_key";
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $plan_counts = array();
            while ($row = $statement->fetch()) {
                $plan_counts[] = array(
                    'plan_name' => $row['plan_name'],
                    'plan_key' => $row['plan_key'],
                    'active_subscriptions' => $row['count']
                );
            }
            $analytics['plan_distribution'] = $plan_counts;
            
            // Revenue analytics
            $this->load->model('subscription/subscription_transaction_model');
            $revenue_report = $this->subscription_transaction_model->get_revenue_report($start_date, $end_date);
            $analytics['revenue_report'] = $revenue_report;
            
            // Total revenue
            $sql = "SELECT 
                        SUM(net_amount) as total_revenue,
                        COUNT(*) as total_transactions,
                        AVG(net_amount) as avg_transaction_value
                    FROM subscription_transactions 
                    WHERE payment_status = 'paid' 
                    AND paid_at BETWEEN ? AND ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($start_date, $end_date));
            
            $revenue_summary = $statement->fetch();
            $analytics['revenue_summary'] = array(
                'total_revenue' => $revenue_summary['total_revenue'] ?: 0,
                'total_transactions' => $revenue_summary['total_transactions'] ?: 0,
                'avg_transaction_value' => $revenue_summary['avg_transaction_value'] ?: 0,
                'period_start' => $start_date,
                'period_end' => $end_date
            );
            
            // Expiring subscriptions
            $this->load->model('subscription/user_subscription_model');
            $expiring_subscriptions = $this->user_subscription_model->get_expiring_subscriptions(7);
            $analytics['expiring_subscriptions'] = count($expiring_subscriptions);
            
            // Add an ID field for React Admin compatibility
            $analytics['id'] = 1;
            
            $statement = NULL;
            $pdo = NULL;
            
            // Return as array with single item for React Admin data provider compatibility
            $response = $this->get_success_response(array($analytics), "Subscription analytics retrieved successfully");
            $response['total'] = 1;
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Admin subscription analytics error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving subscription analytics");
            $this->set_output($response);
        }
    }

    /**
     * Get payment transactions for admin review
     * GET /api/subscription_admin/transactions
     */
    /**
     * Get subscription transactions (admin)
     * GET /api/subscription_admin/transactions - get all transactions
     * GET /api/subscription_admin/transactions/{id} - get specific transaction
     */
    function transactions_get($transaction_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $this->load->model('subscription/subscription_transaction_model');
            
            // If transaction_id is provided, return single transaction
            if ($transaction_id !== NULL) {
                $transaction = $this->subscription_transaction_model->get_subscription_transaction($transaction_id);
                if ($transaction) {
                    $response = $this->get_success_response($transaction, "Subscription transaction retrieved successfully");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "Subscription transaction not found");
                    $this->set_output($response);
                }
                return;
            }
            
            // Get pagination parameters for list view
            $page = $this->input->get('page') ?: 1;
            $pageSize = $this->input->get('pagesize') ?: 25;
            $sortBy = $this->input->get('sortby') ?: 'created_at';
            $sortOrder = $this->input->get('sortorder') ?: 'DESC';
            $filter = $this->input->get('filter');
            
            // Parse filter if provided
            $objFilter = NULL;
            if ($filter && $filter != '{}') {
                $objFilter = json_decode($filter, true);
            }
            
            // Build filter string
            $filterString = "1=1";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    if (strpos($key, '~like') !== false) {
                        $field = str_replace('~like', '', $key);
                        if ($field == 'username') {
                            $filterString .= " AND u.username LIKE('%$value%')";
                        } else if ($field == 'user_display_name') {
                            $filterString .= " AND u.display_name LIKE('%$value%')";
                        } else if ($field == 'razorpay_order_id') {
                            $filterString .= " AND st.razorpay_order_id LIKE('%$value%')";
                        } else if ($field == 'razorpay_payment_id') {
                            $filterString .= " AND st.razorpay_payment_id LIKE('%$value%')";
                        } else {
                            $filterString .= " AND st.$field LIKE('%$value%')";
                        }
                    } else if (strpos($key, '>=') !== false) {
                        $field = str_replace('>=', '', $key);
                        if ($field == 'amount_inr') {
                            $filterString .= " AND st.amount_inr >= '$value'";
                        } else if ($field == 'paid_at') {
                            $filterString .= " AND st.paid_at >= '$value'";
                        } else {
                            $filterString .= " AND st.$field >= '$value'";
                        }
                    } else if (strpos($key, '<=') !== false) {
                        $field = str_replace('<=', '', $key);
                        if ($field == 'amount_inr') {
                            $filterString .= " AND st.amount_inr <= '$value'";
                        } else if ($field == 'paid_at') {
                            $filterString .= " AND st.paid_at <= '$value'";
                        } else {
                            $filterString .= " AND st.$field <= '$value'";
                        }
                    } else {
                        if ($key == 'user_id') {
                            $filterString .= " AND st.user_id = '$value'";
                        } else if ($key == 'plan_id') {
                            $filterString .= " AND st.plan_id = '$value'";
                        } else {
                            $filterString .= " AND st.$key = '$value'";
                        }
                    }
                }
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                        FROM subscription_transactions st 
                        JOIN subscription_plans sp ON st.plan_id = sp.id 
                        JOIN user u ON st.user_id = u.id 
                        WHERE $filterString";
            
            $pdo = CDatabase::getPdo();
            $countStatement = $pdo->prepare($countSql);
            $countStatement->execute();
            $totalCount = $countStatement->fetch()['total'];
            
            // Get paginated data
            $sql = "SELECT st.*, sp.plan_name, sp.plan_key, u.display_name, u.username 
                    FROM subscription_transactions st 
                    JOIN subscription_plans sp ON st.plan_id = sp.id 
                    JOIN user u ON st.user_id = u.id 
                    WHERE $filterString
                    ORDER BY st.$sortBy $sortOrder";
            
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT $pageSize OFFSET $offset";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $transactions = array();
            while ($row = $statement->fetch()) {
                $transaction = array(
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'user_display_name' => $row['display_name'],
                    'username' => $row['username'],
                    'plan_id' => $row['plan_id'],
                    'plan_name' => $row['plan_name'],
                    'plan_key' => $row['plan_key'],
                    'transaction_type' => $row['transaction_type'],
                    'billing_cycle' => $row['billing_cycle'],
                    'amount_inr' => $row['amount_inr'],
                    'net_amount' => $row['net_amount'],
                    'payment_status' => $row['payment_status'],
                    'payment_gateway' => $row['payment_gateway'],
                    'payment_method' => $row['payment_method'],
                    'razorpay_order_id' => $row['razorpay_order_id'],
                    'razorpay_payment_id' => $row['razorpay_payment_id'],
                    'paid_at' => $row['paid_at'],
                    'failure_reason' => $row['failure_reason'],
                    'invoice_number' => $row['invoice_number'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                );
                
                $transactions[] = $transaction;
            }
            
            $statement = NULL;
            $countStatement = NULL;
            $pdo = NULL;
            
            if (count($transactions) > 0) {
                $response = $this->get_success_response($transactions, "Payment transactions retrieved successfully");
                $response['total'] = $totalCount;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response(array(), "No payment transactions found");
                $response['total'] = 0;
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Admin get payment transactions error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving payment transactions");
            $this->set_output($response);
        }
    }

    // ========================================
    // FEATURE DEFINITION MANAGEMENT
    // ========================================

    /**
     * Get subscription feature definitions (admin)
     * GET /api/subscription_admin/features - get all features
     * GET /api/subscription_admin/features/{id} - get specific feature
     */
    function features_get($feature_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // If feature_id is provided, return single feature
            if ($feature_id !== NULL) {
                $sql = "SELECT * FROM subscription_feature_definitions WHERE id = ?";
                $pdo = CDatabase::getPdo();
                $statement = $pdo->prepare($sql);
                $statement->execute(array($feature_id));
                
                if ($row = $statement->fetch()) {
                    $feature_data = array(
                        'id' => $row['id'],
                        'feature_key' => $row['feature_key'],
                        'feature_name' => $row['feature_name'],
                        'feature_description' => $row['feature_description'],
                        'feature_type' => $row['feature_type'],
                        'reset_cycle' => $row['reset_cycle'],
                        'is_active' => $row['is_active'] == 1,
                        'sort_order' => $row['sort_order'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at']
                    );
                    
                    $response = $this->get_success_response($feature_data, "Feature definition retrieved successfully");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "Feature definition not found");
                    $this->set_output($response);
                }
                return;
            }
            
            // Get pagination parameters for list view
            $page = $this->input->get('page') ?: 1;
            $pageSize = $this->input->get('pagesize') ?: 25;
            $sortBy = $this->input->get('sortby') ?: 'sort_order';
            $sortOrder = $this->input->get('sortorder') ?: 'ASC';
            $filter = $this->input->get('filter');
            
            // Parse filter if provided
            $objFilter = NULL;
            if ($filter && $filter != '{}') {
                $objFilter = json_decode($filter, true);
            }
            
            // Build filter string
            $filterString = "";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    if (strpos($key, '~like') !== false) {
                        $field = str_replace('~like', '', $key);
                        $filterString .= "$field LIKE('%$value%') AND ";
                    } else {
                        $filterString .= "$key = '$value' AND ";
                    }
                }
                if (strlen($filterString) > 0) {
                    $filterString = substr($filterString, 0, strlen($filterString) - 5); // Remove last " AND "
                }
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM subscription_feature_definitions";
            if ($filterString) {
                $countSql .= " WHERE $filterString";
            }
            
            $pdo = CDatabase::getPdo();
            $countStatement = $pdo->prepare($countSql);
            $countStatement->execute();
            $totalCount = $countStatement->fetch()['total'];
            
            // Get paginated data
            $sql = "SELECT * FROM subscription_feature_definitions";
            if ($filterString) {
                $sql .= " WHERE $filterString";
            }
            $sql .= " ORDER BY $sortBy $sortOrder";
            
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT $pageSize OFFSET $offset";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            $features = array();
            while ($row = $statement->fetch()) {
                $feature = array(
                    'id' => $row['id'],
                    'feature_key' => $row['feature_key'],
                    'feature_name' => $row['feature_name'],
                    'feature_description' => $row['feature_description'],
                    'feature_type' => $row['feature_type'],
                    'reset_cycle' => $row['reset_cycle'],
                    'is_active' => $row['is_active'] == 1,
                    'sort_order' => $row['sort_order'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                );
                
                $features[] = $feature;
            }
            
            $statement = NULL;
            $countStatement = NULL;
            $pdo = NULL;
            
            $response = $this->get_success_response($features, "Feature definitions retrieved successfully");
            $response['total'] = $totalCount;
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Admin get feature definitions error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving feature definitions");
            $this->set_output($response);
        }
    }

    /**
     * Create new feature definition
     * POST /api/subscription_admin/features
     */
    function features_post() {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $postData = json_decode($this->input->raw_input_stream, true);
            
            if (!$postData) {
                $response = $this->get_failed_response(NULL, "Invalid JSON data");
                $this->set_output($response);
                return;
            }
            
            // Validate required fields
            $required_fields = array('feature_key', 'feature_name', 'feature_type');
            foreach ($required_fields as $field) {
                if (!isset($postData[$field]) || empty($postData[$field])) {
                    $response = $this->get_failed_response(NULL, "Missing required field: $field");
                    $this->set_output($response);
                    return;
                }
            }
            
            // Check if feature_key already exists
            $sql = "SELECT COUNT(*) as count FROM subscription_feature_definitions WHERE feature_key = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute(array($postData['feature_key']));
            $existing = $statement->fetch()['count'];
            
            if ($existing > 0) {
                $response = $this->get_failed_response(NULL, "Feature key already exists");
                $this->set_output($response);
                return;
            }
            
            // Insert new feature definition
            $sql = "INSERT INTO subscription_feature_definitions (feature_key, feature_name, feature_description, feature_type, reset_cycle, is_active, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $postData['feature_key'],
                $postData['feature_name'],
                $postData['feature_description'] ?? '',
                $postData['feature_type'],
                $postData['reset_cycle'] ?? 'none',
                isset($postData['is_active']) ? ($postData['is_active'] ? 1 : 0) : 1,
                $postData['sort_order'] ?? 0
            ));
            
            if ($result) {
                $feature_id = $pdo->lastInsertId();
                $response = $this->get_success_response(array('id' => $feature_id), "Feature definition created successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error creating feature definition");
                $this->set_output($response);
            }
            
            $statement = NULL;
            $pdo = NULL;
            
        } catch (Exception $e) {
            log_message('error', "Admin create feature definition error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error creating feature definition: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Update feature definition
     * PUT /api/subscription_admin/features/{feature_id}
     */
    function features_put($feature_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($feature_id == NULL) {
            $response = $this->get_failed_response(NULL, "Feature ID is required");
            $this->set_output($response);
            return;
        }

        try {
            $putData = json_decode($this->input->raw_input_stream, true);
            
            if (!$putData) {
                $response = $this->get_failed_response(NULL, "Invalid JSON data");
                $this->set_output($response);
                return;
            }
            
            // Check if feature exists
            $sql = "SELECT COUNT(*) as count FROM subscription_feature_definitions WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute(array($feature_id));
            $exists = $statement->fetch()['count'];
            
            if ($exists == 0) {
                $response = $this->get_failed_response(NULL, "Feature definition not found");
                $this->set_output($response);
                return;
            }
            
            // Build update query
            $updateFields = array();
            $updateValues = array();
            
            $allowed_fields = array('feature_key', 'feature_name', 'feature_description', 'feature_type', 'reset_cycle', 'is_active', 'sort_order');
            
            foreach ($allowed_fields as $field) {
                if (isset($putData[$field])) {
                    $updateFields[] = "$field = ?";
                    if ($field == 'is_active') {
                        $updateValues[] = $putData[$field] ? 1 : 0;
                    } else {
                        $updateValues[] = $putData[$field];
                    }
                }
            }
            
            if (empty($updateFields)) {
                $response = $this->get_failed_response(NULL, "No valid fields to update");
                $this->set_output($response);
                return;
            }
            
            $updateValues[] = $feature_id;
            $sql = "UPDATE subscription_feature_definitions SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($updateValues);
            
            if ($result) {
                $response = $this->get_success_response(array('feature_id' => $feature_id), "Feature definition updated successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error updating feature definition");
                $this->set_output($response);
            }
            
            $statement = NULL;
            $pdo = NULL;
            
        } catch (Exception $e) {
            log_message('error', "Admin update feature definition error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error updating feature definition: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Delete feature definition
     * DELETE /api/subscription_admin/features/{feature_id}
     */
    function features_delete($feature_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($feature_id == NULL) {
            $response = $this->get_failed_response(NULL, "Feature ID is required");
            $this->set_output($response);
            return;
        }

        try {
            // Check if feature exists
            $sql = "SELECT COUNT(*) as count FROM subscription_feature_definitions WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute(array($feature_id));
            $exists = $statement->fetch()['count'];
            
            if ($exists == 0) {
                $response = $this->get_failed_response(NULL, "Feature definition not found");
                $this->set_output($response);
                return;
            }
            
            // Check if feature is used in any plans
            $sql = "SELECT COUNT(*) as count FROM subscription_plan_features WHERE feature_id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($feature_id));
            $used_in_plans = $statement->fetch()['count'];
            
            if ($used_in_plans > 0) {
                $response = $this->get_failed_response(NULL, "Cannot delete feature: it is currently used in $used_in_plans subscription plans");
                $this->set_output($response);
                return;
            }
            
            // Delete feature definition
            $sql = "DELETE FROM subscription_feature_definitions WHERE id = ?";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($feature_id));
            
            if ($result) {
                $response = $this->get_success_response(array('feature_id' => $feature_id), "Feature definition deleted successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error deleting feature definition");
                $this->set_output($response);
            }
            
            $statement = NULL;
            $pdo = NULL;
            
        } catch (Exception $e) {
            log_message('error', "Admin delete feature definition error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error deleting feature definition: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    // ========================================
    // PLAN FEATURE ASSIGNMENT MANAGEMENT
    // ========================================

    /**
     * Get plan features for a specific plan
     * GET /api/subscription_admin/plan-features/{plan_id}
     */
    function plan_features_get($plan_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($plan_id == NULL) {
            $response = $this->get_failed_response(NULL, "Plan ID is required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_plan_model');
            
            // Get all available features with current plan assignment status
            $sql = "SELECT 
                        sfd.id as feature_id,
                        sfd.feature_key,
                        sfd.feature_name,
                        sfd.feature_description,
                        sfd.feature_type,
                        sfd.reset_cycle,
                        sfd.is_active as feature_active,
                        spf.id as plan_feature_id,
                        spf.feature_limit,
                        spf.is_enabled as feature_enabled
                    FROM subscription_feature_definitions sfd
                    LEFT JOIN subscription_plan_features spf ON sfd.id = spf.feature_id AND spf.plan_id = ?
                    WHERE sfd.is_active = 1
                    ORDER BY sfd.sort_order ASC";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute(array($plan_id));
            
            $features = array();
            while ($row = $statement->fetch()) {
                $feature = array(
                    'id' => $row['plan_feature_id'] ?: ('new_' . $row['feature_id']),
                    'plan_id' => $plan_id,
                    'feature_id' => $row['feature_id'],
                    'feature_key' => $row['feature_key'],
                    'feature_name' => $row['feature_name'],
                    'feature_description' => $row['feature_description'],
                    'feature_type' => $row['feature_type'],
                    'reset_cycle' => $row['reset_cycle'],
                    'feature_limit' => $row['feature_limit'],
                    'is_enabled' => $row['feature_enabled'] == 1,
                    'is_assigned' => $row['plan_feature_id'] !== NULL
                );
                
                $features[] = $feature;
            }
            
            $statement = NULL;
            $pdo = NULL;
            
            $response = $this->get_success_response($features, "Plan features retrieved successfully");
            $response['total'] = count($features);
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Admin get plan features error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving plan features");
            $this->set_output($response);
        }
    }

    /**
     * Update plan feature assignment
     * PUT /api/subscription_admin/plan-features/{plan_id}/{feature_id}
     */
    function plan_features_put($plan_id = NULL, $feature_id = NULL) {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($plan_id == NULL || $feature_id == NULL) {
            $response = $this->get_failed_response(NULL, "Plan ID and Feature ID are required");
            $this->set_output($response);
            return;
        }

        try {
            $putData = json_decode($this->input->raw_input_stream, true);
            
            if (!$putData) {
                $response = $this->get_failed_response(NULL, "Invalid JSON data");
                $this->set_output($response);
                return;
            }
            
            $pdo = CDatabase::getPdo();
            
            // Check if assignment already exists
            $sql = "SELECT id FROM subscription_plan_features WHERE plan_id = ? AND feature_id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($plan_id, $feature_id));
            $existing = $statement->fetch();
            
            if ($existing) {
                // Update existing assignment
                $sql = "UPDATE subscription_plan_features SET feature_limit = ?, is_enabled = ? WHERE id = ?";
                $statement = $pdo->prepare($sql);
                $result = $statement->execute(array(
                    $putData['feature_limit'] ?? NULL,
                    isset($putData['is_enabled']) ? ($putData['is_enabled'] ? 1 : 0) : 1,
                    $existing['id']
                ));
            } else {
                // Create new assignment
                $sql = "INSERT INTO subscription_plan_features (plan_id, feature_id, feature_limit, is_enabled) VALUES (?, ?, ?, ?)";
                $statement = $pdo->prepare($sql);
                $result = $statement->execute(array(
                    $plan_id,
                    $feature_id,
                    $putData['feature_limit'] ?? NULL,
                    isset($putData['is_enabled']) ? ($putData['is_enabled'] ? 1 : 0) : 1
                ));
            }
            
            if ($result) {
                $response = $this->get_success_response(array('plan_id' => $plan_id, 'feature_id' => $feature_id), "Plan feature updated successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error updating plan feature");
                $this->set_output($response);
            }
            
            $statement = NULL;
            $pdo = NULL;
            
        } catch (Exception $e) {
            log_message('error', "Admin update plan feature error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error updating plan feature: " . $e->getMessage());
            $this->set_output($response);
        }
    }
}