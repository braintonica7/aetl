<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Content_type_summary_object
 *
 * @author Jawahar
 */
class Content_type_summary_object extends CI_Model {

    public $videoLecture;
    public $videoLink;
    public $studyMaterial;
    public $assignment;

    public function __construct() {
        parent::__construct();
        $this->videoLecture = 0;
        $this->videoLink = 0;
        $this->studyMaterial = 0;
        $this->assignment = 0;
    }

}
