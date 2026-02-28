<?php

class User_object extends CI_Model {

    public $id;
    public $username;
    public $password;
    public $display_name;
    public $role_id;
    public $reference_id;
    public $allow_login;
    public $token;
    public $last_login;
    
    // JWT token fields
    public $jwt_access_token;
    public $jwt_refresh_token;
    public $jwt_token_created_at;
    public $jwt_token_expires_at;
    public $jwt_refresh_expires_at;
    public $device_info;
    public $last_activity;
    
    // Google OAuth fields
    public $google_id;
    public $auth_provider;
    public $profile_picture_url;
    public $email_verified;
    public $google_access_token;
    public $google_refresh_token;
    public $google_token_expires_at;
    public $created_at;
    public $updated_at;
    
    // Subscription and quota fields
    public $subscription_type;
    public $subscription_starts_at;
    public $subscription_expires_at;
    public $current_subscription_id;
    public $razorpay_customer_id;
    public $custom_quiz_count;
    public $custom_quiz_limit;
    public $quota_reset_date;
    
    // FCM token and notification fields
    public $fcm_token;
    public $fcm_token_updated_at;
    public $notification_enabled;
    public $platform;
    
    // Mobile verification fields
    public $mobile_number;
    public $mobile_verified;
    public $mobile_verified_at;
    
    // Account deletion fields
    public $is_deleted;
    public $deletion_requested_at;
    public $deleted_at;
    public $deletion_reason;
    
    // Virtual field for email (username is used as email)
    public $email;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->username = '';
        $this->password = '';
        $this->display_name = '';
        $this->role_id = 0;
        $this->reference_id = 0;
        $this->allow_login = 0;
        $this->token = '';
        
        // Initialize JWT token fields
        $this->jwt_access_token = null;
        $this->jwt_refresh_token = null;
        $this->jwt_token_created_at = null;
        $this->jwt_token_expires_at = null;
        $this->jwt_refresh_expires_at = null;
        $this->device_info = null;
        $this->last_activity = null;
        
        // Initialize Google OAuth fields
        $this->google_id = null;
        $this->auth_provider = 'local';
        $this->profile_picture_url = null;
        $this->email_verified = 0;
        $this->google_access_token = null;
        $this->google_refresh_token = null;
        $this->google_token_expires_at = null;
        $this->created_at = null;
        $this->updated_at = null;
        
        // Initialize subscription fields
        $this->subscription_type = 'free';
        $this->subscription_starts_at = null;
        $this->subscription_expires_at = null;
        $this->current_subscription_id = null;
        $this->razorpay_customer_id = null;
        $this->custom_quiz_count = 0;
        $this->custom_quiz_limit = null;
        $this->quota_reset_date = null;
        
        // Initialize FCM token and notification fields
        $this->fcm_token = null;
        $this->fcm_token_updated_at = null;
        $this->notification_enabled = 1;
        $this->platform = null;
        
        // Initialize mobile verification fields
        $this->mobile_number = null;
        $this->mobile_verified = 0;
        $this->mobile_verified_at = null;
        
        // Initialize account deletion fields
        $this->is_deleted = 0;
        $this->deletion_requested_at = null;
        $this->deleted_at = null;
        $this->deletion_reason = null;
        
        // Initialize virtual email field (username serves as email)
        $this->email = null;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->last_login = $dateTime;
    }

}

?>
