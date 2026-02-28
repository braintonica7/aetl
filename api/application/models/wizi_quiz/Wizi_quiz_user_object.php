<?php

class Wizi_quiz_user_object extends CI_Model {

    public $id;
    public $wizi_quiz_id;
    public $user_id;
    public $attempt_number;
    public $attempt_status;
    public $started_at;
    public $completed_at;
    public $time_spent;
    public $current_question_index;
    public $total_questions;
    public $answered_questions;
    public $correct_answers;
    public $incorrect_answers;
    public $skipped_questions;
    public $total_score;
    public $total_marks;
    public $accuracy_percentage;
    public $is_passed;
    public $rank;
    public $best_attempt;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->wizi_quiz_id = 0;
        $this->user_id = 0;
        $this->attempt_number = 1;
        $this->attempt_status = 'not_started';
        $this->started_at = null;
        $this->completed_at = null;
        $this->time_spent = 0;
        $this->current_question_index = 0;
        $this->total_questions = 0;
        $this->answered_questions = 0;
        $this->correct_answers = 0;
        $this->incorrect_answers = 0;
        $this->skipped_questions = 0;
        $this->total_score = 0;
        $this->total_marks = 0;
        $this->accuracy_percentage = 0.00;
        $this->is_passed = null;
        $this->rank = null;
        $this->best_attempt = 0;
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created_at = $dateTime;
        $this->updated_at = $dateTime;
    }

}

?>
