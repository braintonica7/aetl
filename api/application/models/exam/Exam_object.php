<?php

class Exam_object extends CI_Model {

    public $id;
    public $exam_name;
    public $max_score;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->exam_name = '';
        $this->max_score = 0;
    }

}

?>
