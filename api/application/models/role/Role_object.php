<?php

class Role_object extends CI_Model {

    public $id;
    public $role;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->role = '';
    }

}

?>
