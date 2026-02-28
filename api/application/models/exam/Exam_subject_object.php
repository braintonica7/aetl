<?php

class Exam_subject_object extends CI_Model {

    public $id;
    public $exam_id;
    public $subject_id;
    public $max_score;
    public $subject_name;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->exam_id = 0;
        $this->subject_id = 0;
        $this->max_score = 0;
        $this->subject_name = '';
    }

}

?>
