<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payment extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    /**
     * Create Razorpay order for subscription purchase
     * POST /api/payment/create_order
     */
    function create_order_post() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
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
            if ($request['billing_cycle'] == 'monthly') {
                $amount = $plan->monthly_price_inr;
            } else if ($request['billing_cycle'] == 'academic_session') {
                $amount = $plan->academic_session_price_inr;
            } else {
                $response = $this->get_failed_response(NULL, "Invalid billing cycle");
                $this->set_output($response);
                return;
            }
            
            // Check if plan is free
            if ($plan->is_free || $amount <= 0) {
                $response = $this->get_failed_response(NULL, "This plan does not require payment");
                $this->set_output($response);
                return;
            }
            
            // Load Razorpay configuration
            $razorpay_key_id = $this->config->item('razorpay_key_id');
            $razorpay_key_secret = $this->config->item('razorpay_key_secret');
            
            if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
                log_message('error', "Razorpay credentials not configured");
                $response = $this->get_failed_response(NULL, "Payment gateway not configured");
                $this->set_output($response);
                return;
            }
            
            // Calculate amounts (convert to paise for Razorpay)
            $base_amount = $amount * 100; // Convert to paise
            $tax_amount = 0; // Add GST calculation if needed
            $discount_amount = 0; // Add discount logic if needed
            $net_amount = $base_amount + $tax_amount - $discount_amount;
            
            // Create Razorpay order
            $order_data = array(
                'amount' => $net_amount,
                'currency' => 'INR',
                'receipt' => 'wzai_' . $objUser->id . '_' . time(),
                'notes' => array(
                    'user_id' => $objUser->id,
                    'plan_id' => $request['plan_id'],
                    'billing_cycle' => $request['billing_cycle'],
                    'plan_name' => $plan->plan_name
                )
            );
            
            $razorpay_order = $this->createRazorpayOrder($order_data, $razorpay_key_id, $razorpay_key_secret);
            
            if (!$razorpay_order) {
                $response = $this->get_failed_response(NULL, "Failed to create payment order");
                $this->set_output($response);
                return;
            }
            
            // Calculate billing period
            $billing_period_start = date('Y-m-d H:i:s');
            if ($request['billing_cycle'] == 'monthly') {
                $billing_period_end = date('Y-m-d H:i:s', strtotime('+1 month'));
            } else {
                $billing_period_end = date('Y-m-d H:i:s', strtotime('+1 year'));
            }

            // Create user subscription first
            $this->load->model('subscription/user_subscription_model');
            $objSubscription = new User_subscription_object();
            $objSubscription->user_id = $objUser->id;
            $objSubscription->plan_id = $request['plan_id'];
            $objSubscription->billing_cycle = $request['billing_cycle'];
            $objSubscription->subscription_status = 'pending';
            $objSubscription->starts_at = $billing_period_start;
            $objSubscription->expires_at = $billing_period_end;
            $objSubscription->next_billing_date = $billing_period_end;
            $objSubscription->auto_renew = 1;
            $objSubscription->metadata = [];
            $createdSubscription = $this->user_subscription_model->add_user_subscription($objSubscription);

            log_message('debug', "Created User subscription: " . json_encode($createdSubscription));

            if (!$createdSubscription || empty($createdSubscription->id)) {
                $response = $this->get_failed_response(NULL, "Failed to create user subscription");
                $this->set_output($response);
                return;
            }

            // Create transaction record with valid subscription_id
            $objTransaction = new Subscription_transaction_object();
            $objTransaction->user_id = $objUser->id;
            $objTransaction->subscription_id = $createdSubscription->id;
            $objTransaction->plan_id = $request['plan_id'];
            $objTransaction->transaction_type = 'subscription';
            $objTransaction->billing_cycle = $request['billing_cycle'];
            $objTransaction->amount_inr = $amount;
            $objTransaction->currency = 'INR';
            $objTransaction->payment_status = 'pending';
            $objTransaction->payment_gateway = 'razorpay';
            $objTransaction->razorpay_order_id = $razorpay_order['id'];
            $objTransaction->tax_amount = $tax_amount / 100; // Convert back to rupees
            $objTransaction->discount_amount = $discount_amount / 100;
            $objTransaction->net_amount = $net_amount / 100;
            $objTransaction->billing_period_start = $billing_period_start;
            $objTransaction->billing_period_end = $billing_period_end;
            $objTransaction->ip_address = $this->input->ip_address();
            $objTransaction->user_agent = $this->input->user_agent();

            $transaction = $this->subscription_transaction_model->add_subscription_transaction($objTransaction);

            if ($transaction) {
                // Return order details for frontend
                $order_response = array(
                    'order_id' => $razorpay_order['id'],
                    'amount' => $net_amount,
                    'currency' => 'INR',
                    'key_id' => $razorpay_key_id,
                    'transaction_id' => $transaction->id,
                    'plan_name' => $plan->plan_name,
                    'billing_cycle' => $request['billing_cycle'],
                    'user_details' => array(
                        'name' => $objUser->display_name,
                        'email' => $objUser->username // Assuming username is email
                    )
                );
                
                $response = $this->get_success_response($order_response, "Payment order created successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Failed to create transaction record");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('debug', "Create payment order error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error creating payment order: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Verify Razorpay payment and activate subscription
     * POST /api/payment/verify
     */
    function verify_post() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request['razorpay_order_id']) || empty($request['razorpay_payment_id']) || empty($request['razorpay_signature'])) {
            $response = $this->get_failed_response(NULL, "Payment verification data is required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('subscription/subscription_transaction_model');
            $this->load->model('subscription/user_subscription_model');
            $this->load->model('subscription/subscription_plan_model');
            
            // Get transaction by order ID
            $transaction = $this->subscription_transaction_model->get_transaction_by_razorpay_order_id($request['razorpay_order_id']);
            if (!$transaction) {
                $response = $this->get_failed_response(NULL, "Transaction not found");
                $this->set_output($response);
                return;
            }
            
            // Verify payment belongs to current user
            if ($transaction->user_id != $objUser->id) {
                $response = $this->get_failed_response(NULL, "Unauthorized payment verification");
                $this->set_output($response);
                return;
            }
            
            // Verify Razorpay signature
            $razorpay_key_secret = $this->config->item('razorpay_key_secret');
            $signature_valid = $this->verifyRazorpaySignature(
                $request['razorpay_order_id'], 
                $request['razorpay_payment_id'], 
                $request['razorpay_signature'], 
                $razorpay_key_secret
            );
            
            if (!$signature_valid) {
                // Update transaction as failed
                $this->subscription_transaction_model->update_transaction_payment_failed($transaction->id, "Invalid payment signature");
                
                $response = $this->get_failed_response(NULL, "Payment verification failed");
                $this->set_output($response);
                return;
            }
            
            // Update transaction as successful
            $payment_method = isset($request['payment_method']) ? $request['payment_method'] : '';
            $update_result = $this->subscription_transaction_model->update_transaction_payment_success(
                $transaction->id, 
                $request['razorpay_payment_id'], 
                $request['razorpay_signature'], 
                $payment_method
            );
            
            if (!$update_result) {
                $response = $this->get_failed_response(NULL, "Failed to update payment status");
                $this->set_output($response);
                return;
            }
            
            // Generate invoice number
            $invoice_number = $this->subscription_transaction_model->generate_invoice_number($transaction->id);
            
            // Create or update subscription
            $subscription_result = $this->createOrUpdateSubscription($transaction, $objUser->id);
            
            if ($subscription_result) {
                // Update transaction with subscription ID
                $subscription_result_obj = $this->user_subscription_model->get_user_subscription($subscription_result);
                
                $response_data = array(
                    'payment_id' => $request['razorpay_payment_id'],
                    'order_id' => $request['razorpay_order_id'],
                    'subscription_id' => $subscription_result,
                    'invoice_number' => $invoice_number,
                    'subscription_status' => 'active',
                    'plan_name' => $transaction->plan_name
                );
                
                $response = $this->get_success_response($response_data, "Payment verified and subscription activated successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Payment verified but failed to activate subscription");
                $this->set_output($response);
            }
            
        } catch (Exception $e) {
            log_message('error', "Payment verification error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error verifying payment: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Handle Razorpay webhooks
     * POST /api/payment/webhook
     */
    function webhook_post() {
        // Get webhook payload
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
        
        // Verify webhook signature
        $webhook_secret = $this->config->item('razorpay_webhook_secret');
        if (!$this->verifyWebhookSignature($payload, $signature, $webhook_secret)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }
        
        $event = json_decode($payload, true);
        
        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            return;
        }
        
        try {
            $this->load->model('subscription/subscription_transaction_model');
            
            // Handle different webhook events
            switch ($event['event']) {
                case 'payment.captured':
                    $this->handlePaymentCaptured($event);
                    break;
                    
                case 'payment.failed':
                    $this->handlePaymentFailed($event);
                    break;
                    
                case 'subscription.charged':
                    $this->handleSubscriptionCharged($event);
                    break;
                    
                case 'subscription.cancelled':
                    $this->handleSubscriptionCancelled($event);
                    break;
                    
                default:
                    log_message('error', "Unhandled webhook event: " . $event['event']);
            }
            
            // Update webhook data in transaction
            if (isset($event['payload']['payment']['entity']['order_id'])) {
                $order_id = $event['payload']['payment']['entity']['order_id'];
                $transaction = $this->subscription_transaction_model->get_transaction_by_razorpay_order_id($order_id);
                
                if ($transaction) {
                    $this->subscription_transaction_model->update_transaction_webhook_data($transaction->id, $event);
                }
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
            
        } catch (Exception $e) {
            log_message('error', "Webhook processing error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Processing failed']);
        }
    }

    /**
     * Create Razorpay order using cURL
     */
    private function createRazorpayOrder($order_data, $key_id, $key_secret) {
        $url = 'https://api.razorpay.com/v1/orders';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($key_id . ':' . $key_secret)
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return json_decode($response, true);
        } else {
            log_message('error', "Razorpay order creation failed: " . $response);
            return false;
        }
    }

    /**
     * Verify Razorpay payment signature
     */
    private function verifyRazorpaySignature($order_id, $payment_id, $signature, $secret) {
        $payload = $order_id . '|' . $payment_id;
        $expected_signature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected_signature, $signature);
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($payload, $signature, $secret) {
        $expected_signature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected_signature, $signature);
    }

    /**
     * Create or update user subscription after successful payment
     */
    private function createOrUpdateSubscription($transaction, $user_id) {
        try {
            $this->load->model('subscription/user_subscription_model');
            
            // Calculate subscription dates
            $starts_at = date('Y-m-d H:i:s');
            $expires_at = $transaction->billing_period_end;
            $next_billing_date = ($transaction->billing_cycle == 'monthly') ? $expires_at : null;
            
            // Create subscription object
            $objSubscription = new User_subscription_object();
            $objSubscription->user_id = $user_id;
            $objSubscription->plan_id = $transaction->plan_id;
            $objSubscription->billing_cycle = $transaction->billing_cycle;
            $objSubscription->subscription_status = 'active';
            $objSubscription->starts_at = $starts_at;
            $objSubscription->expires_at = $expires_at;
            $objSubscription->next_billing_date = $next_billing_date;
            $objSubscription->auto_renew = ($transaction->billing_cycle == 'monthly') ? 1 : 0;
            
            // Save subscription
            $result = $this->user_subscription_model->add_user_subscription($objSubscription);
            
            if ($result !== FALSE) {
                // Update user's current subscription
                $this->user_subscription_model->update_user_current_subscription($user_id, $result->id);
                return $result->id;
            }
            
            return false;
            
        } catch (Exception $e) {
            log_message('error', "Create subscription error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle payment captured webhook
     */
    private function handlePaymentCaptured($event) {
        // Implementation for payment captured event
    log_message('error', "Payment captured: " . json_encode($event));
    }

    /**
     * Handle payment failed webhook
     */
    private function handlePaymentFailed($event) {
        // Implementation for payment failed event
    log_message('error', "Payment failed: " . json_encode($event));
    }

    /**
     * Handle subscription charged webhook
     */
    private function handleSubscriptionCharged($event) {
        // Implementation for subscription charged event
    log_message('error', "Subscription charged: " . json_encode($event));
    }

    /**
     * Handle subscription cancelled webhook
     */
    private function handleSubscriptionCancelled($event) {
        // Implementation for subscription cancelled event
    log_message('error', "Subscription cancelled: " . json_encode($event));
    }
}