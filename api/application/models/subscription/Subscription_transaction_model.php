<?php

class Subscription_transaction_model extends CI_Model {

    public function get_subscription_transaction($id) {
        $objTransaction = NULL;
        $sql = "SELECT st.*, sp.plan_name, sp.plan_key, u.display_name, u.username 
                FROM subscription_transactions st 
                JOIN subscription_plans sp ON st.plan_id = sp.id 
                JOIN user u ON st.user_id = u.id 
                WHERE st.id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        
        if ($row = $statement->fetch()) {
            $objTransaction = $this->buildTransactionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objTransaction;
    }

    public function get_transaction_by_razorpay_order_id($razorpay_order_id) {
        $objTransaction = NULL;
        $sql = "SELECT st.*, sp.plan_name, sp.plan_key, u.display_name, u.username 
                FROM subscription_transactions st 
                JOIN subscription_plans sp ON st.plan_id = sp.id 
                JOIN user u ON st.user_id = u.id 
                WHERE st.razorpay_order_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($razorpay_order_id));
        
        if ($row = $statement->fetch()) {
            $objTransaction = $this->buildTransactionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objTransaction;
    }

    public function get_transaction_by_razorpay_payment_id($razorpay_payment_id) {
        $objTransaction = NULL;
        $sql = "SELECT st.*, sp.plan_name, sp.plan_key, u.display_name, u.username 
                FROM subscription_transactions st 
                JOIN subscription_plans sp ON st.plan_id = sp.id 
                JOIN user u ON st.user_id = u.id 
                WHERE st.razorpay_payment_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($razorpay_payment_id));
        
        if ($row = $statement->fetch()) {
            $objTransaction = $this->buildTransactionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objTransaction;
    }

    public function get_user_transactions($user_id, $limit = 10) {
        $transactions = array();
        $sql = "SELECT st.*, sp.plan_name, sp.plan_key 
                FROM subscription_transactions st 
                JOIN subscription_plans sp ON st.plan_id = sp.id 
                WHERE st.user_id = ? 
                ORDER BY st.created_at DESC LIMIT ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $limit));
        
        while ($row = $statement->fetch()) {
            $transactions[] = $this->buildTransactionObject($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $transactions;
    }

    public function add_subscription_transaction($objTransaction) {
        try {
            // Ensure billing_period_start is always a valid datetime
            if (empty($objTransaction->billing_period_start)) {
                $objTransaction->billing_period_start = date('Y-m-d H:i:s');
            }
            // Ensure billing_period_end is always a valid datetime
            if (empty($objTransaction->billing_period_end)) {
                $objTransaction->billing_period_end = date('Y-m-d H:i:s');
            }
            log_message("debug", "Adding subscription transaction: " .  json_encode($objTransaction));

            $sql = "INSERT INTO subscription_transactions (user_id, subscription_id, plan_id, 
                    transaction_type, billing_cycle, amount_inr, currency, payment_status, 
                    payment_gateway, razorpay_order_id, payment_method, tax_amount, 
                    discount_amount, net_amount, billing_period_start, billing_period_end, 
                    ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $objTransaction->user_id,
                $objTransaction->subscription_id,
                $objTransaction->plan_id,
                $objTransaction->transaction_type,
                $objTransaction->billing_cycle,
                $objTransaction->amount_inr,
                $objTransaction->currency,
                $objTransaction->payment_status,
                $objTransaction->payment_gateway,
                $objTransaction->razorpay_order_id,
                $objTransaction->payment_method,
                $objTransaction->tax_amount,
                $objTransaction->discount_amount,
                $objTransaction->net_amount,
                $objTransaction->billing_period_start,
                $objTransaction->billing_period_end,
                $objTransaction->ip_address,
                $objTransaction->user_agent
            ));
            
            if ($result) {
                $objTransaction->id = $pdo->lastInsertId();
                $statement = NULL;
                $pdo = NULL;
                return $objTransaction;
            }
            
            $statement = NULL;
            $pdo = NULL;
            return FALSE;
            
        } catch (Exception $e) {
            log_message("debug", "Subscription transaction creation error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function update_transaction_payment_success($transaction_id, $razorpay_payment_id, $razorpay_signature, $payment_method = '') {
        try {
            $sql = "UPDATE subscription_transactions SET 
                    payment_status = 'paid', razorpay_payment_id = ?, razorpay_signature = ?, 
                    payment_method = ?, paid_at = NOW(), updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array(
                $razorpay_payment_id,
                $razorpay_signature,
                $payment_method,
                $transaction_id
            ));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message("debug", "Transaction payment success update error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function update_transaction_payment_failed($transaction_id, $failure_reason) {
        try {
            $sql = "UPDATE subscription_transactions SET 
                    payment_status = 'failed', failure_reason = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array($failure_reason, $transaction_id));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message("debug", "Transaction payment failed update error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function update_transaction_webhook_data($transaction_id, $webhook_data) {
        try {
            $sql = "UPDATE subscription_transactions SET 
                    webhook_data = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $webhook_json = is_array($webhook_data) ? json_encode($webhook_data) : $webhook_data;
            $result = $statement->execute(array($webhook_json, $transaction_id));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message("debug", "Transaction webhook data update error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function generate_invoice_number($transaction_id) {
        $invoice_number = 'WZI-' . date('Y') . '-' . str_pad($transaction_id, 6, '0', STR_PAD_LEFT);
        
        try {
            $sql = "UPDATE subscription_transactions SET 
                    invoice_number = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array($invoice_number, $transaction_id));
            
            $statement = NULL;
            $pdo = NULL;
            return $result ? $invoice_number : FALSE;
            
        } catch (Exception $e) {
            log_message("debug", "Invoice number generation error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function get_revenue_report($start_date, $end_date) {
        $report = array();
        $sql = "SELECT 
                    DATE(paid_at) as payment_date,
                    COUNT(*) as transaction_count,
                    SUM(net_amount) as total_revenue,
                    AVG(net_amount) as avg_transaction_value,
                    sp.plan_name,
                    st.billing_cycle
                FROM subscription_transactions st 
                JOIN subscription_plans sp ON st.plan_id = sp.id 
                WHERE st.payment_status = 'paid' 
                AND st.paid_at BETWEEN ? AND ? 
                GROUP BY DATE(paid_at), sp.plan_name, st.billing_cycle 
                ORDER BY payment_date DESC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($start_date, $end_date));
        
        while ($row = $statement->fetch()) {
            $report[] = array(
                'payment_date' => $row['payment_date'],
                'transaction_count' => $row['transaction_count'],
                'total_revenue' => $row['total_revenue'],
                'avg_transaction_value' => $row['avg_transaction_value'],
                'plan_name' => $row['plan_name'],
                'billing_cycle' => $row['billing_cycle']
            );
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $report;
    }

    private function buildTransactionObject($row) {
        $objTransaction = new Subscription_transaction_object();
        $objTransaction->id = $row['id'];
        $objTransaction->user_id = $row['user_id'];
        $objTransaction->subscription_id = $row['subscription_id'];
        $objTransaction->plan_id = $row['plan_id'];
        $objTransaction->transaction_type = $row['transaction_type'];
        $objTransaction->billing_cycle = $row['billing_cycle'];
        $objTransaction->amount_inr = $row['amount_inr'];
        $objTransaction->currency = $row['currency'];
        $objTransaction->payment_status = $row['payment_status'];
        $objTransaction->payment_gateway = $row['payment_gateway'];
        $objTransaction->razorpay_order_id = $row['razorpay_order_id'];
        $objTransaction->razorpay_payment_id = $row['razorpay_payment_id'];
        $objTransaction->razorpay_invoice_id = $row['razorpay_invoice_id'];
        $objTransaction->razorpay_signature = $row['razorpay_signature'];
        $objTransaction->payment_method = $row['payment_method'];
        $objTransaction->paid_at = $row['paid_at'];
        $objTransaction->failure_reason = $row['failure_reason'];
        $objTransaction->invoice_number = $row['invoice_number'];
        $objTransaction->tax_amount = $row['tax_amount'];
        $objTransaction->discount_amount = $row['discount_amount'];
        $objTransaction->net_amount = $row['net_amount'];
        $objTransaction->billing_period_start = $row['billing_period_start'];
        $objTransaction->billing_period_end = $row['billing_period_end'];
        $objTransaction->webhook_data = $row['webhook_data'];
        $objTransaction->ip_address = $row['ip_address'];
        $objTransaction->user_agent = $row['user_agent'];
        $objTransaction->created_at = $row['created_at'];
        $objTransaction->updated_at = $row['updated_at'];
        
        // Add plan and user details if available
        if (isset($row['plan_name'])) {
            $objTransaction->plan_name = $row['plan_name'];
            $objTransaction->plan_key = $row['plan_key'];
        }
        if (isset($row['display_name'])) {
            $objTransaction->user_display_name = $row['display_name'];
            $objTransaction->username = $row['username'];
        }
        
        return $objTransaction;
    }
}