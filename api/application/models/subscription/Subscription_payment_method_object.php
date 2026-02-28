<?php

class Subscription_payment_method_object extends CI_Model {

    public $id;
    public $user_id;
    public $payment_method_type;
    public $razorpay_token_id;
    public $razorpay_customer_id;
    public $card_last_four;
    public $card_network;
    public $card_type;
    public $upi_vpa;
    public $bank_name;
    public $wallet_name;
    public $is_default;
    public $is_active;
    public $expires_at;
    public $verified_at;
    public $last_used_at;
    public $metadata;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->payment_method_type = 'card';
        $this->razorpay_token_id = '';
        $this->razorpay_customer_id = '';
        $this->card_last_four = '';
        $this->card_network = '';
        $this->card_type = '';
        $this->upi_vpa = '';
        $this->bank_name = '';
        $this->wallet_name = '';
        $this->is_default = 0;
        $this->is_active = 1;
        $this->expires_at = '';
        $this->verified_at = '';
        $this->last_used_at = '';
        $this->metadata = '';
        $this->created_at = '';
        $this->updated_at = '';
    }
}