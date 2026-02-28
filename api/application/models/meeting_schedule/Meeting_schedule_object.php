<?php

class Meeting_schedule_object extends CI_Model {

    public $id;
    public $employee_id;
    public $meeting_date;
    public $meeting_start;
    public $meeting_end;
    public $meeting_in_progress;
    public $meeting_category_id;
    public $agenda;
    public $guid;
    public $room_name;
    public $room_code;
    public $is_active;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->employee_id = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->meeting_date = $dateTime;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->meeting_start = $dateTime;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->meeting_end = $dateTime;
        
        $this->meeting_in_progress = 0;

        $this->meeting_category_id = 0;
        $this->agenda = '';
        $this->guid = uniqid("est", true);
        $this->room_name = '';
        $this->room_code = $this->generate_room_code();
        $this->is_active = 0;
    }

    private function generate_room_code($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrs092u3tuvwxyzaskdhfhf9882323ABCDEFGHIJKLMNksadf9044OPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}

?>
