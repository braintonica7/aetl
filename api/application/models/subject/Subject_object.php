<?php

class Subject_object extends CI_Model {

    public $id;
    public $subject;
    public $question_count; // For quiz builder endpoints

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->subject = '';
        $this->question_count = 0;
    }

}

?>
