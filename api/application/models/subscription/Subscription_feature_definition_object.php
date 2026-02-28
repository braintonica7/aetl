<?php

class Subscription_feature_definition_object extends CI_Model {

    public $id;
    public $feature_key;
    public $feature_name;
    public $feature_description;
    public $feature_type;
    public $reset_cycle;
    public $is_active;
    public $sort_order;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->feature_key = '';
        $this->feature_name = '';
        $this->feature_description = '';
        $this->feature_type = 'quota';
        $this->reset_cycle = 'none';
        $this->is_active = 1;
        $this->sort_order = 0;
        $this->created_at = '';
        $this->updated_at = '';
    }
}