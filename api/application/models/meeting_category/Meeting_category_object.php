<?php

class Meeting_category_object extends CI_Model {

    public $id;
    public $category;
    public $is_active;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->category = '';
        $this->is_active = 0;
    }

}

?>
