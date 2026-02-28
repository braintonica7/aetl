<?php

class Student_object extends CI_Model {

    public $applicationid;
    public $student_name;
    public $father_name;
    public $card_no;
    public $id;

    public function __construct() {
        parent::__construct();

        $this->applicationid = '';
        $this->student_name = '';
        $this->father_name = '';
        $this->card_no = '';
        $this->id = 0;
    }

}

?>
