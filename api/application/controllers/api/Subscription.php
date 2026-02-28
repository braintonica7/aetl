<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// Razorpay PHP SDK - Install via: composer require razorpay/razorpay
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class Subscription extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    /**
     * Get all available subscription plans
     * GET /api/subscription/plans
     */
    function plans_get() {
        try {
            $this->load->model('subscription/subscription_plan_model');
            $plans = $this->subscription_plan_model->get_all_active_plans();
            
            if ($plans) {
                // Format plans for frontend with features
                $formatted_plans = array();
                foreach ($plans as $plan) {
                    $plan_features = $this->subscription_plan_model->get_plan_features($plan->id);
                    
                    $formatted_plan = array(
                        'id' => $plan->id,
                        'plan_key' => $plan->plan_key,
                        'plan_name' => $plan->plan_name,
                        'plan_description' => $plan->plan_description,
                        'monthly_price_inr' => $plan->monthly_price_inr,
                        'academic_session_price_inr' => $plan->academic_session_price_inr,
                        'is_free' => $plan->is_free,
                        'is_default' => $plan->is_default,
                        'plan_color' => $plan->plan_color,
                        'features' => $plan_features
                    );
                    
                    $formatted_plans[] = $formatted_plan;
                }
                
                $response = $this->get_success_response($formatted_plans, "Subscription plans retrieved successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_success_response(array(), "No subscription plans found");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Get subscription plans error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving subscription plans");
            $this->set_output($response);
        }
    }

    /**
     * Get specific subscription plan by ID or key
     * GET /api/subscription/plan/{plan_id_or_key}
     */
    function plan_get($plan_identifier = NULL) {
        if ($plan_identifier == NULL) {
            $response = $this->get_failed_response(NULL, "Plan identifier is required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_plan_model');
            
            // Try to get by ID first, then by key
            if (is_numeric($plan_identifier)) {
                $plan = $this->subscription_plan_model->get_subscription_plan($plan_identifier);
            } else {
                $plan = $this->subscription_plan_model->get_subscription_plan_by_key($plan_identifier);
            }
            
            if ($plan) {
                $plan_features = $this->subscription_plan_model->get_plan_features($plan->id);
                
                $formatted_plan = array(
                    'id' => $plan->id,
                    'plan_key' => $plan->plan_key,
                    'plan_name' => $plan->plan_name,
                    'plan_description' => $plan->plan_description,
                    'monthly_price_inr' => $plan->monthly_price_inr,
                    'academic_session_price_inr' => $plan->academic_session_price_inr,
                    'is_free' => $plan->is_free,
                    'is_default' => $plan->is_default,
                    'is_active' => $plan->is_active,
                    'plan_color' => $plan->plan_color,
                    'features' => $plan_features,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                );
                
                $response = $this->get_success_response($formatted_plan, "Subscription plan retrieved successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Subscription plan not found");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Get subscription plan error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving subscription plan");
            $this->set_output($response);
        }
    }

    /**
     * Get user's current subscription
     * GET /api/subscription/current
     */
    function current_get() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $this->load->model('subscription/user_subscription_model');
            $subscription = $this->user_subscription_model->get_user_active_subscription($objUser->id);
            
            if ($subscription) {
                $formatted_subscription = $this->formatSubscriptionForResponse($subscription);
                $response = $this->get_success_response($formatted_subscription, "Current subscription retrieved successfully");
                $this->set_output($response);
            } else {
                // User has no active subscription, return default plan info
                $this->load->model('subscription/subscription_plan_model');
                $default_plan = $this->subscription_plan_model->get_default_plan();
                
                if ($default_plan) {
                    $plan_features = $this->subscription_plan_model->get_plan_features($default_plan->id);
                    
                    $default_subscription = array(
                        'subscription_status' => 'free',
                        'plan_id' => $default_plan->id,
                        'plan_key' => $default_plan->plan_key,
                        'plan_name' => $default_plan->plan_name,
                        'plan_color' => $default_plan->plan_color,
                        'is_free' => true,
                        'features' => $plan_features
                    );
                    
                    $response = $this->get_success_response($default_subscription, "Default subscription plan retrieved");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No subscription found");
                    $this->set_output($response);
                }
            }
            
        } catch (Exception $e) {
            log_message("debug", "Get current subscription error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving current subscription");
            $this->set_output($response);
        }
    }

    /**
     * Get user's subscription history
     * GET /api/subscription/history
     */
    function history_get() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $limit = $this->input->get('limit') ?: 10;

        try {
            $this->load->model('subscription/user_subscription_model');
            $history = $this->user_subscription_model->get_user_subscription_history($objUser->id, $limit);
            
            if ($history) {
                $formatted_history = array();
                foreach ($history as $subscription) {
                    $formatted_history[] = $this->formatSubscriptionForResponse($subscription);
                }
                
                $response = $this->get_success_response($formatted_history, "Subscription history retrieved successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_success_response(array(), "No subscription history found");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Get subscription history error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving subscription history");
            $this->set_output($response);
        }
    }

    /**
     * Create new subscription (for admin use)
     * POST /api/subscription/create
     */
    function create_post() {
        // Require admin authentication
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request['user_id']) || empty($request['plan_id']) || empty($request['billing_cycle'])) {
            $response = $this->get_failed_response(NULL, "User ID, Plan ID, and Billing Cycle are required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/user_subscription_model');
            $this->load->model('subscription/subscription_plan_model');
            
            // Validate plan exists
            $plan = $this->subscription_plan_model->get_subscription_plan($request['plan_id']);
            if (!$plan) {
                $response = $this->get_failed_response(NULL, "Invalid subscription plan");
                $this->set_output($response);
                return;
            }
            
            // Calculate subscription dates
            $starts_at = isset($request['starts_at']) ? $request['starts_at'] : date('Y-m-d H:i:s');
            
            if ($request['billing_cycle'] == 'monthly') {
                $expires_at = date('Y-m-d H:i:s', strtotime($starts_at . ' +1 month'));
                $next_billing_date = $expires_at;
            } else { // academic_session
                $expires_at = date('Y-m-d H:i:s', strtotime($starts_at . ' +1 year'));
                $next_billing_date = null; // Academic sessions don't auto-renew
            }
            
            // Create subscription object
            $objSubscription = new User_subscription_object();
            $objSubscription->user_id = $request['user_id'];
            $objSubscription->plan_id = $request['plan_id'];
            $objSubscription->billing_cycle = $request['billing_cycle'];
            $objSubscription->subscription_status = isset($request['subscription_status']) ? $request['subscription_status'] : 'active';
            $objSubscription->starts_at = $starts_at;
            $objSubscription->expires_at = $expires_at;
            $objSubscription->next_billing_date = $next_billing_date;
            $objSubscription->auto_renew = isset($request['auto_renew']) ? $request['auto_renew'] : 1;
            $objSubscription->razorpay_customer_id = isset($request['razorpay_customer_id']) ? $request['razorpay_customer_id'] : '';
            $objSubscription->metadata = isset($request['metadata']) ? json_encode($request['metadata']) : '{}';
            
            // Save subscription
            $result = $this->user_subscription_model->add_user_subscription($objSubscription);
            
            if ($result !== FALSE) {
                // Update user's current subscription
                $this->user_subscription_model->update_user_current_subscription($request['user_id'], $result->id);
                
                $formatted_subscription = $this->formatSubscriptionForResponse($result);
                $response = $this->get_success_response($formatted_subscription, "Subscription created successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to create subscription");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Create subscription error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error creating subscription: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Cancel user subscription
     * POST /api/subscription/cancel/{subscription_id}
     */
    function cancel_post($subscription_id = NULL) {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($subscription_id == NULL) {
            $response = $this->get_failed_response(NULL, "Subscription ID is required");
            $this->set_output($response);
            return;
        }

        $request = $this->get_request();
        $cancellation_reason = isset($request['reason']) ? $request['reason'] : 'User requested cancellation';

        try {
            $this->load->model('subscription/user_subscription_model');
            
            // Verify subscription belongs to user (or user is admin)
            $subscription = $this->user_subscription_model->get_user_subscription($subscription_id);
            if (!$subscription) {
                $response = $this->get_failed_response(NULL, "Subscription not found");
                $this->set_output($response);
                return;
            }
            
            // Check if user owns this subscription or is admin
            $admin_roles = [1, 2, 3, 4];
            if ($subscription->user_id != $objUser->id && !in_array($objUser->role_id, $admin_roles)) {
                $response = $this->get_failed_response(NULL, "You don't have permission to cancel this subscription");
                $this->set_output($response);
                return;
            }
            
            // Cancel subscription
            $result = $this->user_subscription_model->cancel_user_subscription($subscription_id, $cancellation_reason);
            
            if ($result) {
                $response = $this->get_success_response(array('subscription_id' => $subscription_id), "Subscription cancelled successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to cancel subscription");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Cancel subscription error: " . $e->getMessage()); 
            $response = $this->get_failed_response(NULL, "Error cancelling subscription: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get subscription transactions for user
     * GET /api/subscription/transactions
     */
    function transactions_get() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $limit = $this->input->get('limit') ?: 10;

        try {
            $this->load->model('subscription/subscription_transaction_model');
            $transactions = $this->subscription_transaction_model->get_user_transactions($objUser->id, $limit);
            
            if ($transactions) {
                $formatted_transactions = array();
                foreach ($transactions as $transaction) {
                    $formatted_transactions[] = $this->formatTransactionForResponse($transaction);
                }
                
                $response = $this->get_success_response($formatted_transactions, "Subscription transactions retrieved successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_success_response(array(), "No subscription transactions found");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message("debug", "Get subscription transactions error: " . $e->getMessage());   
            $response = $this->get_failed_response(NULL, "Error retrieving subscription transactions");
            $this->set_output($response);
        }
    }

    /**
     * Format subscription object for API response
     */
    private function formatSubscriptionForResponse($subscription) {
        return array(
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'plan_key' => isset($subscription->plan_key) ? $subscription->plan_key : '',
            'plan_name' => isset($subscription->plan_name) ? $subscription->plan_name : '',
            'plan_color' => isset($subscription->plan_color) ? $subscription->plan_color : '#007bff',
            'billing_cycle' => $subscription->billing_cycle,
            'subscription_status' => $subscription->subscription_status,
            'starts_at' => $subscription->starts_at,
            'expires_at' => $subscription->expires_at,
            'next_billing_date' => $subscription->next_billing_date,
            'auto_renew' => $subscription->auto_renew,
            'cancelled_at' => $subscription->cancelled_at,
            'cancellation_reason' => $subscription->cancellation_reason,
            'created_at' => $subscription->created_at,
            'updated_at' => $subscription->updated_at
        );
    }

    /**
     * Create Razorpay order for subscription payment
     * POST /api/subscription/create-order
     */
    function create_order_post() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request['plan_id']) || empty($request['billing_cycle'])) {
            $response = $this->get_failed_response(NULL, "Plan ID and Billing Cycle are required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_plan_model');
            $this->load->model('subscription/subscription_transaction_model');
            
            // Get plan details
            $plan = $this->subscription_plan_model->get_subscription_plan($request['plan_id']);
            if (!$plan) {
                $response = $this->get_failed_response(NULL, "Invalid subscription plan");
                $this->set_output($response);
                return;
            }

            // Calculate amount based on billing cycle
            $amount = $request['billing_cycle'] == 'monthly' 
                ? floatval($plan->monthly_price_inr)
                : floatval($plan->academic_session_price_inr);

            // Free plan doesn't require payment
            if ($plan->is_free || $amount <= 0) {
                $response = $this->get_failed_response(NULL, "This plan does not require payment");
                $this->set_output($response);
                return;
            }

            // Calculate tax and net amount (18% GST for India)
            $tax_amount = 0; //round($amount * 0.18, 2);
            $net_amount = $amount + $tax_amount;

            // Create Razorpay order
            $api = new Api($this->config->item('razorpay_key_id'), $this->config->item('razorpay_key_secret'));
            
            $orderData = [
                'receipt' => 'sub_' . $objUser->id . '_' . time(),
                'amount' => $net_amount * 100, // Convert to paise
                'currency' => 'INR',
                'notes' => [
                    'user_id' => $objUser->id,
                    'plan_id' => $plan->id,
                    'plan_key' => $plan->plan_key,
                    'billing_cycle' => $request['billing_cycle']
                ]
            ];

            $razorpayOrder = $api->order->create($orderData);

            // Create user subscription first
            $this->load->model('subscription/user_subscription_model');
            $objSubscription = new User_subscription_object();
            $objSubscription->user_id = $objUser->id;
            $objSubscription->plan_id = $plan->id;
            $objSubscription->billing_cycle = $request['billing_cycle'];
            $objSubscription->subscription_status = 'pending_payment';
            $objSubscription->starts_at = date('Y-m-d H:i:s');
            if ($request['billing_cycle'] == 'monthly') {
                $objSubscription->expires_at = date('Y-m-d H:i:s', strtotime('+1 month'));
                $objSubscription->next_billing_date = $objSubscription->expires_at;
            } else {
                $objSubscription->expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
                $objSubscription->next_billing_date = $objSubscription->expires_at;
            }
            $objSubscription->auto_renew = 1;
            $objSubscription->metadata = [];
            // Ensure all DATETIME fields are valid
            $now = date('Y-m-d H:i:s');
            if (empty($objSubscription->starts_at)) {
                $objSubscription->starts_at = $now;
            }
            if (empty($objSubscription->expires_at)) {
                $objSubscription->expires_at = $objSubscription->starts_at;
            }
            if (empty($objSubscription->next_billing_date)) {
                $objSubscription->next_billing_date = $objSubscription->expires_at;
            }
            if (empty($objSubscription->current_period_start)) {
                $objSubscription->current_period_start = $objSubscription->starts_at;
            }
            if (empty($objSubscription->current_period_end)) {
                $objSubscription->current_period_end = $objSubscription->expires_at;
            }
            if (empty($objSubscription->trial_start)) {
                $objSubscription->trial_start = $objSubscription->starts_at;
            }
            if (empty($objSubscription->trial_end)) {
                $objSubscription->trial_end = $objSubscription->expires_at;
            }
            if (empty($objSubscription->cancelled_at)) {
                $objSubscription->cancelled_at = null;
            }
            if (empty($objSubscription->created_at)) {
                $objSubscription->created_at = $now;
            }
            if (empty($objSubscription->updated_at)) {
                $objSubscription->updated_at = $now;
            }
            $createdSubscription = $this->user_subscription_model->add_user_subscription($objSubscription);

            if (!$createdSubscription || empty($createdSubscription->id)) {
                $response = $this->get_failed_response(NULL, "Failed to create user subscription");
                $this->set_output($response);
                return;
            }

            // Create transaction record with valid subscription_id
            $objTransaction = new Subscription_transaction_object();
            $objTransaction->user_id = $objUser->id;
            $objTransaction->subscription_id = $createdSubscription->id;
            $objTransaction->plan_id = $plan->id;
            $objTransaction->transaction_type = 'subscription';
            $objTransaction->billing_cycle = $request['billing_cycle'];
            $objTransaction->amount_inr = $amount;
            $objTransaction->tax_amount = $tax_amount;
            $objTransaction->net_amount = $net_amount;
            $objTransaction->payment_status = 'pending';
            $objTransaction->razorpay_order_id = $razorpayOrder['id'];
            $objTransaction->ip_address = $this->input->ip_address();
            $objTransaction->user_agent = $this->input->user_agent();

            $transaction = $this->subscription_transaction_model->add_subscription_transaction($objTransaction);

            if ($transaction) {
                $response_data = [
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $net_amount,
                    'currency' => 'INR',
                    'key_id' => $this->config->item('razorpay_key_id'),
                    'transaction_id' => $transaction->id,
                    'plan_name' => $plan->plan_name,
                    'user_name' => $objUser->display_name ?: $objUser->username,
                    'user_email' => $objUser->email,
                    'user_phone' => $objUser->phone_number ?: ''
                ];

                $response = $this->get_success_response($response_data, "Order created successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to create transaction");
                $this->set_output($response);
            }

        } catch (Exception $e) {
            log_message("debug", "Create Razorpay order error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error creating payment order: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Verify Razorpay payment and activate subscription
     * POST /api/subscription/verify-payment
     */
    function verify_payment_post() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request['razorpay_order_id']) || 
            empty($request['razorpay_payment_id']) || 
            empty($request['razorpay_signature'])) {
            $response = $this->get_failed_response(NULL, "Payment verification parameters are required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_transaction_model');
            $this->load->model('subscription/user_subscription_model');
            
            // Get transaction by order ID
            $transaction = $this->subscription_transaction_model->get_transaction_by_razorpay_order_id($request['razorpay_order_id']);
            
            if (!$transaction) {
                $response = $this->get_failed_response(NULL, "Transaction not found");
                $this->set_output($response);
                return;
            }

            // Verify payment signature
            $api = new Api($this->config->item('razorpay_key_id'), $this->config->item('razorpay_key_secret'));
            
            $attributes = [
                'razorpay_order_id' => $request['razorpay_order_id'],
                'razorpay_payment_id' => $request['razorpay_payment_id'],
                'razorpay_signature' => $request['razorpay_signature']
            ];

            try {
                $api->utility->verifyPaymentSignature($attributes);
                
                // Payment verified successfully, update transaction
                // Update transaction payment status using model method
                $this->subscription_transaction_model->update_transaction_payment_success(
                    $transaction->id,
                    $request['razorpay_payment_id'],
                    $request['razorpay_signature'],
                    isset($transaction->payment_method) ? $transaction->payment_method : ''
                );

                // Update existing user subscription
                $this->load->model('subscription/user_subscription_model');
                $subscription = $this->user_subscription_model->get_user_subscription($transaction->subscription_id);
                if ($subscription) {
                    $starts_at = $subscription->starts_at;
                    if ($transaction->billing_cycle == 'monthly') {
                        $expires_at = date('Y-m-d H:i:s', strtotime($starts_at . ' +1 month'));
                        $next_billing_date = $expires_at;
                    } else {
                        $expires_at = date('Y-m-d H:i:s', strtotime($starts_at . ' +1 year'));
                        $next_billing_date = null;
                    }
                    $subscription->subscription_status = 'active';
                    $subscription->expires_at = $expires_at;
                    $subscription->next_billing_date = $next_billing_date;
                    $subscription->updated_at = date('Y-m-d H:i:s');
                    $this->user_subscription_model->update_user_subscription($subscription);
                    // Update user's current subscription pointer
                    $this->user_subscription_model->update_user_current_subscription($objUser->id, $subscription->id);
                    $response_data = [
                        'transaction_id' => $transaction->id,
                        'subscription_id' => $subscription->id,
                        'payment_status' => 'paid',
                        'message' => 'Payment successful! Your subscription is now active.'
                    ];
                    $response = $this->get_success_response($response_data, "Payment verified and subscription activated successfully");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "Failed to update subscription");
                    $this->set_output($response);
                }

            } catch (SignatureVerificationError $e) {
                // Signature verification failed
                $transaction->payment_status = 'failed';
                $transaction->failure_reason = 'Signature verification failed';
                $this->subscription_transaction_model->update_subscription_transaction($transaction);

                $response = $this->get_failed_response(NULL, "Payment verification failed");
                $this->set_output($response);
            }

        } catch (Exception $e) {
            log_message("debug", "Verify payment error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error verifying payment: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Format transaction object for API response
     */
    private function formatTransactionForResponse($transaction) {
        return array(
            'id' => $transaction->id,
            'plan_name' => isset($transaction->plan_name) ? $transaction->plan_name : '',
            'transaction_type' => $transaction->transaction_type,
            'billing_cycle' => $transaction->billing_cycle,
            'amount_inr' => $transaction->amount_inr,
            'net_amount' => $transaction->net_amount,
            'payment_status' => $transaction->payment_status,
            'payment_method' => $transaction->payment_method,
            'paid_at' => $transaction->paid_at,
            'invoice_number' => $transaction->invoice_number,
            'created_at' => $transaction->created_at
        );
    }
}