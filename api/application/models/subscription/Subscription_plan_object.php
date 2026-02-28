<?php

class Subscription_plan_object extends CI_Model {

    public $id;
    public $plan_key;
    public $plan_name;
    public $plan_description;
    public $monthly_price_inr;
    public $academic_session_price_inr;
    public $is_free;
    public $is_default;
    public $is_active;
    public $sort_order;
    public $plan_color;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->plan_key = '';
        $this->plan_name = '';
        $this->plan_description = '';
        $this->monthly_price_inr = 0.00;
        $this->academic_session_price_inr = 0.00;
        $this->is_free = 0;
        $this->is_default = 0;
        $this->is_active = 1;
        $this->sort_order = 0;
        $this->plan_color = '#007bff';
        $this->created_at = '';
        $this->updated_at = '';
    }
}