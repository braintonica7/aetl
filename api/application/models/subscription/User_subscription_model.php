<?php

class User_subscription_model extends CI_Model {

    public function get_user_subscription($id) {
        $objSubscription = NULL;
        $sql = "SELECT us.*, sp.plan_name, sp.plan_key, sp.plan_color 
                FROM user_subscriptions us 
                JOIN subscription_plans sp ON us.plan_id = sp.id 
                WHERE us.id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        
        if ($row = $statement->fetch()) {
            $objSubscription = $this->buildSubscriptionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objSubscription;
    }

    public function get_user_active_subscription($user_id) {
        $objSubscription = NULL;
        $sql = "SELECT us.*, sp.plan_name, sp.plan_key, sp.plan_color 
                FROM user_subscriptions us 
                JOIN subscription_plans sp ON us.plan_id = sp.id 
                WHERE us.user_id = ? AND us.subscription_status = 'active' 
                AND us.expires_at > NOW() 
                ORDER BY us.created_at DESC LIMIT 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        
        if ($row = $statement->fetch()) {
            $objSubscription = $this->buildSubscriptionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objSubscription;
    }

    public function get_user_subscription_history($user_id, $limit = 10) {
        $subscriptions = array();
        $sql = "SELECT us.*, sp.plan_name, sp.plan_key, sp.plan_color 
                FROM user_subscriptions us 
                JOIN subscription_plans sp ON us.plan_id = sp.id 
                WHERE us.user_id = ? 
                ORDER BY us.created_at DESC LIMIT ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $limit));
        
        while ($row = $statement->fetch()) {
            $subscriptions[] = $this->buildSubscriptionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $subscriptions;
    }

    public function add_user_subscription($objSubscription) {
        try {
            $sql = "INSERT INTO user_subscriptions (user_id, plan_id, billing_cycle, subscription_status, 
                    starts_at, expires_at, next_billing_date, auto_renew, academic_session_id, 
                    razorpay_subscription_id, razorpay_customer_id, current_period_start, 
                    current_period_end, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $metadata_json = is_array($objSubscription->metadata) ? json_encode($objSubscription->metadata) : $objSubscription->metadata;
            
            $result = $statement->execute(array(
                $objSubscription->user_id,
                $objSubscription->plan_id,
                $objSubscription->billing_cycle,
                $objSubscription->subscription_status,
                $objSubscription->starts_at,
                $objSubscription->expires_at,
                $objSubscription->next_billing_date,
                $objSubscription->auto_renew ? 1 : 0,
                $objSubscription->academic_session_id,
                $objSubscription->razorpay_subscription_id,
                $objSubscription->razorpay_customer_id,
                $objSubscription->current_period_start,
                $objSubscription->current_period_end,
                $metadata_json
            ));
            
            if ($result) {
                $objSubscription->id = $pdo->lastInsertId();
                $statement = NULL;
                $pdo = NULL;
                return $objSubscription;
            }
            
            $statement = NULL;
            $pdo = NULL;
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', "User subscription creation error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function update_user_subscription($objSubscription) {
        try {
            $sql = "UPDATE user_subscriptions SET 
                    plan_id = ?, billing_cycle = ?, subscription_status = ?, 
                    starts_at = ?, expires_at = ?, next_billing_date = ?, auto_renew = ?, 
                    academic_session_id = ?, razorpay_subscription_id = ?, razorpay_customer_id = ?, 
                    current_period_start = ?, current_period_end = ?, cancelled_at = ?, 
                    cancellation_reason = ?, metadata = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $metadata_json = is_array($objSubscription->metadata) ? json_encode($objSubscription->metadata) : $objSubscription->metadata;
            
            $result = $statement->execute(array(
                $objSubscription->plan_id,
                $objSubscription->billing_cycle,
                $objSubscription->subscription_status,
                $objSubscription->starts_at,
                $objSubscription->expires_at,
                $objSubscription->next_billing_date,
                $objSubscription->auto_renew ? 1 : 0,
                $objSubscription->academic_session_id,
                $objSubscription->razorpay_subscription_id,
                $objSubscription->razorpay_customer_id,
                $objSubscription->current_period_start,
                $objSubscription->current_period_end,
                $objSubscription->cancelled_at,
                $objSubscription->cancellation_reason,
                $metadata_json,
                $objSubscription->id
            ));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "User subscription update error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function cancel_user_subscription($subscription_id, $reason = '') {
        try {
            $sql = "UPDATE user_subscriptions SET 
                    subscription_status = 'cancelled', cancelled_at = NOW(), 
                    cancellation_reason = ?, auto_renew = 0, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array($reason, $subscription_id));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "User subscription cancellation error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function update_user_current_subscription($user_id, $subscription_id) {
        try {
            // Load required models
            $this->load->model('user/user_model');
            $this->load->model('subscription/Subscription_plan_model');
            
            // Fetch subscription object
            $subscription = $this->get_user_subscription($subscription_id);
            if (!$subscription) {
                log_message('error', "Subscription not found for update_user_current_subscription: " . $subscription_id);
                return FALSE;
            }
            // Fetch plan type
            $plan = $this->Subscription_plan_model->get_subscription_plan($subscription->plan_id);
            $subscription_type = $plan ? $plan->plan_key : 'free';
            // Prepare update fields
            $sql = "UPDATE user SET current_subscription_id = ?, subscription_type = ?, subscription_starts_at = ?, subscription_expires_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $subscription_id,
                $subscription_type,
                $subscription->starts_at,
                $subscription->expires_at,
                $user_id
            ));
            $statement = NULL;
            $pdo = NULL;
            return $result;
        } catch (Exception $e) {
            log_message('error', "User current subscription update error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function get_expiring_subscriptions($days_ahead = 7) {
        $subscriptions = array();
        $sql = "SELECT us.*, sp.plan_name, sp.plan_key, u.display_name, u.username 
                FROM user_subscriptions us 
                JOIN subscription_plans sp ON us.plan_id = sp.id 
                JOIN user u ON us.user_id = u.id 
                WHERE us.subscription_status = 'active' 
                AND us.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY) 
                ORDER BY us.expires_at ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($days_ahead));
        
        while ($row = $statement->fetch()) {
            $objSubscription = $this->buildSubscriptionObject($row);
            $objSubscription->user_display_name = $row['display_name'];
            $objSubscription->username = $row['username'];
            $subscriptions[] = $objSubscription;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $subscriptions;
    }

    public function get_subscription_by_razorpay_id($razorpay_subscription_id) {
        $objSubscription = NULL;
        $sql = "SELECT us.*, sp.plan_name, sp.plan_key, sp.plan_color 
                FROM user_subscriptions us 
                JOIN subscription_plans sp ON us.plan_id = sp.id 
                WHERE us.razorpay_subscription_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($razorpay_subscription_id));
        
        if ($row = $statement->fetch()) {
            $objSubscription = $this->buildSubscriptionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objSubscription;
    }

    private function buildSubscriptionObject($row) {
        $objSubscription = new User_subscription_object();
        $objSubscription->id = $row['id'];
        $objSubscription->user_id = $row['user_id'];
        $objSubscription->plan_id = $row['plan_id'];
        $objSubscription->billing_cycle = $row['billing_cycle'];
        $objSubscription->subscription_status = $row['subscription_status'];
        $objSubscription->starts_at = $row['starts_at'];
        $objSubscription->expires_at = $row['expires_at'];
        $objSubscription->next_billing_date = $row['next_billing_date'];
        $objSubscription->auto_renew = $row['auto_renew'] == 1;
        $objSubscription->academic_session_id = $row['academic_session_id'];
        $objSubscription->razorpay_subscription_id = $row['razorpay_subscription_id'];
        $objSubscription->razorpay_customer_id = $row['razorpay_customer_id'];
        $objSubscription->current_period_start = $row['current_period_start'];
        $objSubscription->current_period_end = $row['current_period_end'];
        $objSubscription->trial_start = $row['trial_start'];
        $objSubscription->trial_end = $row['trial_end'];
        $objSubscription->cancelled_at = $row['cancelled_at'];
        $objSubscription->cancellation_reason = $row['cancellation_reason'];
        $objSubscription->metadata = $row['metadata'];
        $objSubscription->created_at = $row['created_at'];
        $objSubscription->updated_at = $row['updated_at'];
        
        // Add plan details
        $objSubscription->plan_name = $row['plan_name'];
        $objSubscription->plan_key = $row['plan_key'];
        $objSubscription->plan_color = $row['plan_color'];
        
        return $objSubscription;
    }
}