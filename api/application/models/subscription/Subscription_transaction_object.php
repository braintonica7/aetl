<?php

class Subscription_transaction_object extends CI_Model {

    public $id;
    public $user_id;
    public $subscription_id;
    public $plan_id;
    public $transaction_type;
    public $billing_cycle;
    public $amount_inr;
    public $currency;
    public $payment_status;
    public $payment_gateway;
    public $razorpay_order_id;
    public $razorpay_payment_id;
    public $razorpay_invoice_id;
    public $razorpay_signature;
    public $payment_method;
    public $paid_at;
    public $failure_reason;
    public $invoice_number;
    public $tax_amount;
    public $discount_amount;
    public $net_amount;
    public $billing_period_start;
    public $billing_period_end;
    public $webhook_data;
    public $ip_address;
    public $user_agent;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->subscription_id = 0;
        $this->plan_id = 0;
        $this->transaction_type = 'subscription';
        $this->billing_cycle = 'monthly';
        $this->amount_inr = 0.00;
        $this->currency = 'INR';
        $this->payment_status = 'pending';
        $this->payment_gateway = 'razorpay';
        $this->razorpay_order_id = '';
        $this->razorpay_payment_id = '';
        $this->razorpay_invoice_id = '';
        $this->razorpay_signature = '';
        $this->payment_method = '';
        $this->paid_at = '';
        $this->failure_reason = '';
        $this->invoice_number = '';
        $this->tax_amount = 0.00;
        $this->discount_amount = 0.00;
        $this->net_amount = 0.00;
        $this->billing_period_start = '';
        $this->billing_period_end = '';
        $this->webhook_data = '';
        $this->ip_address = '';
        $this->user_agent = '';
        $this->created_at = '';
        $this->updated_at = '';
    }
}