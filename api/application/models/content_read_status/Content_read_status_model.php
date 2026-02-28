<?php

class Content_read_status_model extends CI_Model {

    public function get_content_read_status($id) {
        $objContent_read_status = NULL;
        $sql = "select * from content_read_status where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objContent_read_status = new Content_read_status_object();
            $objContent_read_status->id = $row['id'];
            $objContent_read_status->content_id = $row['content_id'];
            $objContent_read_status->scholar_id = $row['scholar_id'];
            $objContent_read_status->read_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['read_date'])->format('Y-m-d');
        }
        $statement = NULL;
        $pdo = NULL;
        return $objContent_read_status;
    }

    public function get_all_content_read_status() {
        $records = array();

        $sql = "select * from content_read_status";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_read_status = new Content_read_status_object();
            $objContent_read_status->id = $row['id'];
            $objContent_read_status->content_id = $row['content_id'];
            $objContent_read_status->scholar_id = $row['scholar_id'];
            $objContent_read_status->read_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['read_date']);
            $records[] = $objContent_read_status;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_content_read_status($objContent_read_status) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from content_read_status";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objContent_read_status->id = $row['mvalue'];
        else
            $objContent_read_status->id = 0;
        $objContent_read_status->id = $objContent_read_status->id + 1;
        $sql = "insert into content_read_status values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objContent_read_status->id,
            $objContent_read_status->content_id,
            $objContent_read_status->scholar_id,
            $objContent_read_status->read_date->format('Y-m-d H:i:s')
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted){
            $objContent_read_status->read_date = $objContent_read_status->read_date->format('Y-m-d');
            return $objContent_read_status;
        }
        return FALSE;
    }

    public function update_content_read_status($objContent_read_status) {
        $sql = "update content_read_status set content_id = ?, scholar_id = ?, read_date = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objContent_read_status->content_id,
            $objContent_read_status->scholar_id,
            $objContent_read_status->read_date->format('Y-m-d H:i:s'),
            $objContent_read_status->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated){
            $objContent_read_status->read_date = $objContent_read_status->read_date->format('Y-m-d');
            return $objContent_read_status;
        }
        return FALSE;
    }

    public function delete_content_read_status($id) {
        $sql = "delete from content_read_status where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_content_read_status_count() {
        $count = 0;
        $sql = "select count(id) as cnt from content_read_status";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_content_read_status($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from content_read_status order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from content_read_status where $filterString order by $sortBy $sortType limit $offset, $limit";

        $sql = "select * from content_read_status limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_read_status = new Content_read_status_object();
            $objContent_read_status->id = $row['id'];
            $objContent_read_status->content_id = $row['content_id'];
            $objContent_read_status->scholar_id = $row['scholar_id'];
            $objContent_read_status->read_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['read_date'])->format('d-m-Y');
            $records[] = $objContent_read_status;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

