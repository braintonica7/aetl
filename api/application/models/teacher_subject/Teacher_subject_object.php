<?php

class Teacher_subject_object extends CI_Model {

    public $id;
    public $employee_id;
    public $subject_id;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->employee_id = 0;
        $this->subject_id = 0;
    }

}

?>
