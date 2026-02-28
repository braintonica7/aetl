<?php

class Content_type_object extends CI_Model {

    public $id;
    public $content_type_name;
    public $is_active;
    public $auto_approve;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->content_type_name = '';
        $this->is_active = 0;
        $this->auto_approve = 0;
    }

}

?>
