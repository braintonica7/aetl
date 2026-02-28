<?php

class Wizi_quiz_question_object extends CI_Model {

    public $id;
    public $wizi_quiz_id;
    public $wizi_question_id;
    public $question_order;
    public $marks;
    public $negative_marks;
    public $created_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->wizi_quiz_id = 0;
        $this->wizi_question_id = 0;
        $this->question_order = 0;
        $this->marks = 4;
        $this->negative_marks = -1.0;
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created_at = $dateTime;
    }

}

?>
