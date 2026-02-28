<?php

class Content_type_model extends CI_Model {

    public function get_content_type($id) {
        $objContent_type = NULL;
        $sql = "select * from content_type where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objContent_type = new Content_type_object();
            $objContent_type->id = $row['id'];
            $objContent_type->content_type_name = $row['content_type_name'];
            $objContent_type->is_active = $row['is_active'] == 1;
            $objContent_type->auto_approve = $row['auto_approve'] == 1;
        }
        $statement = NULL;
        $pdo = NULL;
        return $objContent_type;
    }

    public function get_all_content_types() {
        $records = array();

        $sql = "select * from content_type";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_type = new Content_type_object();
            $objContent_type->id = $row['id'];
            $objContent_type->content_type_name = $row['content_type_name'];
            $objContent_type->is_active = $row['is_active'];
            $objContent_type->auto_approve = $row['auto_approve'] == 1;

            $records[] = $objContent_type;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_content_type($objContent_type) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from content_type";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objContent_type->id = $row['mvalue'];
        else
            $objContent_type->id = 0;
        $objContent_type->id = $objContent_type->id + 1;
        $sql = "insert into content_type values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objContent_type->id,
            $objContent_type->content_type_name,
            $objContent_type->is_active,
            $objContent_type->auto_approve
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objContent_type;
        return FALSE;
    }

    public function update_content_type($objContent_type) {
        $sql = "update content_type set content_type_name = ?, is_active = ?, auto_approve = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objContent_type->content_type_name,
            $objContent_type->is_active,
            $objContent_type->auto_approve,
            $objContent_type->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objContent_type;
        return FALSE;
    }

    public function delete_content_type($id) {
        $sql = "delete from content_type where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_content_type_count() {
        $count = 0;
        $sql = "select count(id) as cnt from content_type";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_content_type($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from content_type order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from content_type where $filterString order by $sortBy $sortType limit $offset, $limit";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_type = new Content_type_object();
            $objContent_type->id = $row['id'];
            $objContent_type->content_type_name = $row['content_type_name'];
            $objContent_type->is_active = $row['is_active'] == 1;
            $objContent_type->auto_approve = $row['auto_approve'] == 1;
            $records[] = $objContent_type;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

