<?php

class Student_tracking_model extends CI_Model {

    public function get_student_tracking($id) {
        $objStudent_tracking = NULL;
        $sql = "select * from student_tracking where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objStudent_tracking = new Student_tracking_object();
            $objStudent_tracking->id = $row['id'];
            $objStudent_tracking->student_id = $row['student_id'];
            $objStudent_tracking->content_id = $row['content_id'];
            $objStudent_tracking->view_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['view_date'])->format('Y-m-d');
        }
        $statement = NULL;
        $pdo = NULL;
        return $objStudent_tracking;
    }

    public function get_all_student_trackings() {
        $records = array();

        $sql = "select * from student_tracking";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objStudent_tracking = new Student_tracking_object();
            $objStudent_tracking->id = $row['id'];
            $objStudent_tracking->student_id = $row['student_id'];
            $objStudent_tracking->content_id = $row['content_id'];
            $objStudent_tracking->view_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['view_date']);

            $records[] = $objStudent_tracking;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_student_tracking($objStudent_tracking) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from student_tracking";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objStudent_tracking->id = $row['mvalue'];
        else
            $objStudent_tracking->id = 0;
        $objStudent_tracking->id = $objStudent_tracking->id + 1;
        $sql = "insert into student_tracking values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objStudent_tracking->id,
            $objStudent_tracking->student_id,
            $objStudent_tracking->content_id,
            $objStudent_tracking->view_date->format('Y-m-d H:i:s')
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted){
            $objStudent_tracking->view_date = $objStudent_tracking->view_date->format('Y-m-d');
            return $objStudent_tracking;
        }
        return FALSE;
    }

    public function update_student_tracking($objStudent_tracking) {
        $sql = "update student_tracking set student_id = ?, content_id = ?, view_date = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objStudent_tracking->student_id,
            $objStudent_tracking->content_id,
            $objStudent_tracking->view_date->format('Y-m-d H:i:s'),
            $objStudent_tracking->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated){
            $objStudent_tracking->view_date = $objStudent_tracking->view_date->format('Y-m-d');
            return $objStudent_tracking;
        }
        return FALSE;
    }

    public function delete_student_tracking($id) {
        $sql = "delete from student_tracking where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_student_tracking_count() {
        $count = 0;
        $sql = "select count(id) as cnt from student_tracking";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_student_tracking($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from student_tracking order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from student_tracking where $filterString order by $sortBy $sortType limit $offset, $limit";

        $sql = "select * from student_tracking limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objStudent_tracking = new Student_tracking_object();
            $objStudent_tracking->id = $row['id'];
            $objStudent_tracking->student_id = $row['student_id'];
            $objStudent_tracking->content_id = $row['content_id'];
            $objStudent_tracking->view_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['view_date'])->format('d-m-Y');
            $records[] = $objStudent_tracking;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

