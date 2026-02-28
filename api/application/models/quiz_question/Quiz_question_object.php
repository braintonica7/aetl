<?php

class Quiz_question_object extends CI_Model {

    public $id;
    public $quiz_id;
    public $question_id;
    public $question_order;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->quiz_id = 0;
        $this->question_id = 0;
        $this->question_order = 0;
        
    }

}

?>
