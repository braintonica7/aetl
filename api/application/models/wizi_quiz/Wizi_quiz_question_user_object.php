<?php

class Wizi_quiz_question_user_object extends CI_Model {

    public $id;
    public $wizi_quiz_user_id;
    public $wizi_quiz_question_id;
    public $wizi_question_id;
    public $question_order;
    public $marks;
    public $negative_marks;
    public $user_answer;
    public $correct_answer;
    public $is_correct;
    public $status;
    public $time_spent;
    public $marks_obtained;
    public $answered_at;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->wizi_quiz_user_id = 0;
        $this->wizi_quiz_question_id = 0;
        $this->wizi_question_id = 0;
        $this->question_order = 0;
        $this->marks = 4;
        $this->negative_marks = -1.0;
        $this->user_answer = null;
        $this->correct_answer = '';
        $this->is_correct = 0;
        $this->status = 'not_attempted';
        $this->time_spent = 0;
        $this->marks_obtained = 0.0;
        $this->answered_at = null;
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created_at = $dateTime;
        $this->updated_at = $dateTime;
    }

}

?>
