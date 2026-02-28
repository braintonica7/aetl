<?php

class User_question_object extends CI_Model {

    public $id;
    public $user_id;
    public $quiz_id;
    public $quiz_question_id;
    public $question_id;
    public $duration;
    public $option_answer;
    public $status;
    public $score;
    public $is_correct;
    public $created_at;
    
    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->quiz_id = 0;
        $this->quiz_question_id = 0;
        $this->question_id = 0;
        $this->duration = 0;
        $this->option_answer = '';
        $this->status = '';
        $this->score = 0;
        $this->is_correct = 0;
        $this->created_at = '';
    }

}

?>
