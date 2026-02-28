<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Quiz_schedule extends API_Controller
{

    public function __constructor()
    {
        parent::__construct();
    }
 
    function get_quiz_schedules_for_class_get()
    {
        $class_id = $this->input->get('class_id', true);
        $this->load->model('quiz_schedule/quiz_schedule_model');
        $objquizlist = $this->quiz_schedule_model->get_all_quiz_schedule($class_id);
        if ($objquizlist === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while getting quiz list for class...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquizlist, "quiz list for class!");
            $this->set_output($response);
        }
    }
}
