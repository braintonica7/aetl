<?php

class Account_object extends CI_Model {

    public $id;
    public $username;
    public $password;
    public $token;
    public $display_name;
    public $plan;
    public $is_active;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->username = '';
        $this->password = '';
        $this->token = '';
        $this->display_name = '';
        $this->plan = '';
        $this->is_active = 0;
    }

}

?>
