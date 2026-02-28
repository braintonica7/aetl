<?php

class Employee_object extends CI_Model {

    public $id;
    public $name;
    public $mobile_no;
    public $faculty_type;
    public $designation;
    public $is_active;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->name = '';
        $this->mobile_no = '';
        $this->faculty_type = '';
        $this->designation = '';
        $this->is_active = 0;
    }

}

?>
