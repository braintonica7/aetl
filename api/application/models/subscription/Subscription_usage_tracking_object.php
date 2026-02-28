<?php

class Subscription_usage_tracking_object extends CI_Model {

    public $id;
    public $user_id;
    public $subscription_id;
    public $feature_id;
    public $usage_period_type;
    public $usage_period_start;
    public $usage_period_end;
    public $current_usage;
    public $usage_limit;
    public $last_reset_at;
    public $next_reset_at;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->subscription_id = 0;
        $this->feature_id = 0;
        $this->usage_period_type = 'monthly';
        $this->usage_period_start = '';
        $this->usage_period_end = '';
        $this->current_usage = 0;
        $this->usage_limit = null;
        $this->last_reset_at = '';
        $this->next_reset_at = '';
        $this->created_at = '';
        $this->updated_at = '';
    }
}