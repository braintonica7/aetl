<?php

class Section_object extends CI_Model {

    public $id;
    public $class_id;
    public $section;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->class_id = 0;
        $this->section = '';
    }

}

?>
