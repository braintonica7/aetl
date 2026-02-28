<?php

class Meeting_participant_object extends CI_Model {

    public $id;
    public $meeting_id;
    public $class_id;
    public $section_id;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->meeting_id = 0;
        $this->class_id = 0;
        $this->section_id = 0;
    }

}

?>
