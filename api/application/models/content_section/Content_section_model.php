<?php

class Content_section_model extends CI_Model {

    public function get_content_section($id) {
        $objContent_section = NULL;
        $sql = "select * from content_section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objContent_section = new Content_section_object();
            $objContent_section->id = $row['id'];
            $objContent_section->content_id = $row['content_id'];
            $objContent_section->section_id = $row['section_id'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objContent_section;
    }

    public function get_all_content_sections() {
        $records = array();

        $sql = "select * from content_section";
        $sql = "select * from content_section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_section = new Content_section_object();
            $objContent_section->id = $row['id'];
            $objContent_section->content_id = $row['content_id'];
            $objContent_section->section_id = $row['section_id'];

            $records[] = $objContent_section;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_content_section($objContent_section) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from content_section";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objContent_section->id = $row['mvalue'];
        else
            $objContent_section->id = 0;
        $objContent_section->id = $objContent_section->id + 1;
        $sql = "insert into content_section values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objContent_section->id,
            $objContent_section->content_id,
            $objContent_section->section_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objContent_section;
        return FALSE;
    }

    public function update_content_section($objContent_section) {
        $sql = "update content_section set content_id = ?, section_id = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objContent_section->content_id,
            $objContent_section->section_id,
            $objContent_section->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objContent_section;
        return FALSE;
    }

    public function delete_content_section($id) {
        $sql = "delete from content_section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_content_section_count() {
        $count = 0;
        $sql = "select count(id) as cnt from content_section";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_content_section($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from content_section order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from content_section where $filterString order by $sortBy $sortType limit $offset, $limit";

        $sql = "select * from content_section limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_section = new Content_section_object();
            $objContent_section->id = $row['id'];
            $objContent_section->content_id = $row['content_id'];
            $objContent_section->section_id = $row['section_id'];
            $records[] = $objContent_section;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

