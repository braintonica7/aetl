<?php

class Question_status_history_object extends CI_Model {

    public $id;
    public $user_id;
    public $question_id;
    public $reported_date;
    public $corrected_date;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->question_id = 0;
        $this->reported_date = null;
        $this->corrected_date = null;
        $this->status = 'reported'; // Default status
        $this->created_at = null;
        $this->updated_at = null;
    }

}

?>