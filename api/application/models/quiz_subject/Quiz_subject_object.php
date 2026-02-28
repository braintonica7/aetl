<?php

class Quiz_subject_object extends CI_Model {

    public $id;
    public $quiz_id;
    public $subject_id;
    public $created_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->quiz_id = 0;
        $this->subject_id = 0;
        $this->created_at = null;
    }

}

?>
