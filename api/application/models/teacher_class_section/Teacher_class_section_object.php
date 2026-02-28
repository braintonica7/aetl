<?php

class Teacher_class_section_object extends CI_Model {

    public $id;
    public $employee_id;
    public $class_id;
    public $section_id;
    public $subject_id;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->employee_id = 0;
        $this->class_id = 0;
        $this->section_id = 0;
        $this->subject_id = 0;
    }

}

?>
