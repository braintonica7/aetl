<?php

class Org_object extends CI_Model {

    public $id;
    public $name;
    public $address;
    public $logo_url;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->name = '';
        $this->address = '';
        $this->logo_url = '';
    }

}

?>
