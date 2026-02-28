<?php

class Content_section_object extends CI_Model {

    public $id;
    public $content_id;
    public $section_id;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->content_id = 0;
        $this->section_id = 0;
    }

}

?>
