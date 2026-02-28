<?php

class Content_query_object extends CI_Model {

    public $id;
    public $content_id;
    public $scholar_id;
    public $query_date;
    public $query;
    public $query_submitted_to;
    public $query_replied;
    public $query_replied_by;
    public $query_reply;
    public $query_reply_date;
    public $reply_document_url;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->content_id = 0;
        $this->scholar_id = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->query_date = $dateTime;

        $this->query = '';
        $query_submitted_to = 0;
        $this->query_replied = 0;
        $this->query_replied_by = 0;
        $this->query_reply = '';

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->query_reply_date = $dateTime;

        $this->reply_document_url = '';
    }

}

?>
