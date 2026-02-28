<?php

class Meeting_category_model extends CI_Model {

    public function get_meeting_category($id) {
        $objMeeting_category = NULL;
        $sql = "select * from meeting_category where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objMeeting_category = new Meeting_category_object();
            $objMeeting_category->id = $row['id'];
            $objMeeting_category->category = $row['category'];
            $objMeeting_category->is_active = $row['is_active'] == 1;
        }
        $statement = NULL;
        $pdo = NULL;
        return $objMeeting_category;
    }

    public function get_all_meeting_categorys() {
        $records = array();

        $sql = "select * from meeting_category";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objMeeting_category = new Meeting_category_object();
            $objMeeting_category->id = $row['id'];
            $objMeeting_category->category = $row['category'];
            $objMeeting_category->is_active = $row['is_active'] == 1;

            $records[] = $objMeeting_category;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_meeting_category($objMeeting_category) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from meeting_category";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objMeeting_category->id = $row['mvalue'];
        else
            $objMeeting_category->id = 0;
        $objMeeting_category->id = $objMeeting_category->id + 1;
        $sql = "insert into meeting_category values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objMeeting_category->id,
            $objMeeting_category->category,
            $objMeeting_category->is_active
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            $objMeeting_category->is_active = $objMeeting_category->is_active == 1;
            return $objMeeting_category;
        }
        return FALSE;
    }

    public function update_meeting_category($objMeeting_category) {
        $sql = "update meeting_category set category = ?, is_active = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objMeeting_category->category,
            $objMeeting_category->is_active,
            $objMeeting_category->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            $objMeeting_category->is_active = $objMeeting_category->is_active == 1;
            return $objMeeting_category;
        }
        return FALSE;
    }

    public function delete_meeting_category($id) {
        $sql = "delete from meeting_category where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_meeting_category_count() {
        $count = 0;
        $sql = "select count(id) as cnt from meeting_category";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_meeting_category($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from meeting_category order by $sortBy $sortType limit $offset, $limit";
        else {
            $sql = "select count(id) as rec_count from meeting_category where $filterString";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select* from meeting_category where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objMeeting_category = new Meeting_category_object();
            $objMeeting_category->id = $row['id'];
            $objMeeting_category->category = $row['category'];
            $objMeeting_category->is_active = $row['is_active'] == 1;
            $records[] = $objMeeting_category;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

