<?php

class User_subscription_object extends CI_Model {

    public $id;
    public $user_id;
    public $plan_id;
    public $billing_cycle;
    public $subscription_status;
    public $starts_at;
    public $expires_at;
    public $next_billing_date;
    public $auto_renew;
    public $academic_session_id;
    public $razorpay_subscription_id;
    public $razorpay_customer_id;
    public $current_period_start;
    public $current_period_end;
    public $trial_start;
    public $trial_end;
    public $cancelled_at;
    public $cancellation_reason;
    public $metadata;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->plan_id = 0;
        $this->billing_cycle = 'monthly';
        $this->subscription_status = 'active';
        $this->starts_at = '';
        $this->expires_at = '';
        $this->next_billing_date = '';
        $this->auto_renew = 1;
        $this->academic_session_id = null;
        $this->razorpay_subscription_id = '';
        $this->razorpay_customer_id = '';
        $this->current_period_start = '';
        $this->current_period_end = '';
        $this->trial_start = '';
        $this->trial_end = '';
        $this->cancelled_at = '';
        $this->cancellation_reason = '';
        $this->metadata = '';
        $this->created_at = '';
        $this->updated_at = '';
    }
}