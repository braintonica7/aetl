<?php

class Chapter_object extends CI_Model {

    public $id;
    public $chapter_name;
    public $subject_id;
    public $question_count; // For quiz builder endpoints

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->subject_id = 0;
        $this->chapter_name = '';
        $this->question_count = 0;
    }

}

?>
