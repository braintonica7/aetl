<?php

class Tl_assignment_check_model extends CI_Model {

    public function get_tl_assignment_check($id) {
        $objTl_assignment_check = NULL;
        $sql = "select * from tl_assignment_check where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objTl_assignment_check = new Tl_assignment_check_object();
            $objTl_assignment_check->id = $row['id'];
            $objTl_assignment_check->academic_session = $row['academic_session'];
            $objTl_assignment_check->tl_assignment_id = $row['tl_assignment_id'];
            $objTl_assignment_check->scholar_id = $row['scholar_id'];
            $objTl_assignment_check->checked_by_employee_id = $row['checked_by_employee_id'];
            $objTl_assignment_check->max_marks = $row['max_marks'];
            $objTl_assignment_check->marks_obtained = $row['marks_obtained'];
            $objTl_assignment_check->review_comments = $row['review_comments'];
            $objTl_assignment_check->attachments = $this->get_attachments_for_assignment_check_submission($objTl_assignment_check->id);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objTl_assignment_check;
    }

    public function get_all_tl_assignment_checks() {
        $records = array();

        $sql = "select * from tl_assignment_check";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTl_assignment_check = new Tl_assignment_check_object();
            $objTl_assignment_check->id = $row['id'];
            $objTl_assignment_check->academic_session = $row['academic_session'];
            $objTl_assignment_check->tl_assignment_id = $row['tl_assignment_id'];
            $objTl_assignment_check->scholar_id = $row['scholar_id'];
            $objTl_assignment_check->checked_by_employee_id = $row['checked_by_employee_id'];
            $objTl_assignment_check->max_marks = $row['max_marks'];
            $objTl_assignment_check->marks_obtained = $row['marks_obtained'];
            $objTl_assignment_check->review_comments = $row['review_comments'];
            $objTl_assignment_check->attachments = $this->get_attachments_for_assignment_check_submission($objTl_assignment_check->id);

            $records[] = $objTl_assignment_check;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_tl_assignment_check($objTl_assignment_check) {
        $pdo = CDatabase::getPdo();
        
        
        $sql = "select * from tl_assignment_check where tl_assignment_id = ? and  scholar_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($objTl_assignment_check->tl_assignment_id, $objTl_assignment_check->scholar_id));
        //$rowCount = $statement->rowCount;
        
        if ($row = $statement->fetch()){
            $objTl_assignment_check->id = $row['id'];
            $statement = NULL;
            $pdo = NULL;
            $objTl_assignment_check = $this->update_tl_assignment_check($objTl_assignment_check);
            return $objTl_assignment_check;
        }
        $statement = NULL;
        
        $sql = "select max(id) as mvalue from tl_assignment_check";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTl_assignment_check->id = $row['mvalue'];
        else
            $objTl_assignment_check->id = 0;
        $objTl_assignment_check->id = $objTl_assignment_check->id + 1;
        $sql = "insert into tl_assignment_check values (?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTl_assignment_check->id,
            $objTl_assignment_check->academic_session,
            $objTl_assignment_check->tl_assignment_id,
            $objTl_assignment_check->scholar_id,
            $objTl_assignment_check->checked_by_employee_id,
            $objTl_assignment_check->max_marks,
            $objTl_assignment_check->marks_obtained,
            $objTl_assignment_check->review_comments
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            if (is_array($objTl_assignment_check_submission->attachments)) {
                foreach ($objTl_assignment_check_submission->attachments as $attachment) {
                    $attachment->tl_assignment_check_id = $objTl_assignment_check->id;
                    $this->add_tl_assignment_check_submission($attachment);
                }
            }
            return $objTl_assignment_check;
        }
        return FALSE;
    }

    public function update_tl_assignment_check($objTl_assignment_check) {
        $sql = "update tl_assignment_check set academic_session = ?, tl_assignment_id = ?, scholar_id = ?, checked_by_employee_id = ?, max_marks = ?, marks_obtained = ?, review_comments = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objTl_assignment_check->academic_session,
            $objTl_assignment_check->tl_assignment_id,
            $objTl_assignment_check->scholar_id,
            $objTl_assignment_check->checked_by_employee_id,
            $objTl_assignment_check->max_marks,
            $objTl_assignment_check->marks_obtained,
            $objTl_assignment_check->review_comments,
            $objTl_assignment_check->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {             
            if (is_array($objTl_assignment_check_submission->attachments)) {
                $this->delete_tl_assignment_check_submission($objTl_assignment_check->id);
                foreach ($objTl_assignment_check_submission->attachments as $attachment) {
                    $attachment->tl_assignment_check_id = $objTl_assignment_check->id;
                    $this->add_tl_assignment_check_submission($attachment);
                }
            }
            return $objTl_assignment_check;
        }
        return FALSE;
    }

    public function delete_tl_assignment_check($id) {
        $this->delete_tl_assignment_check_submission($id);
        $sql = "delete from tl_assignment_check where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_tl_assignment_check_count() {
        $count = 0;
        $sql = "select count(id) as cnt from tl_assignment_check";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_tl_assignment_check($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from tl_assignment_check order by $sortBy $sortType limit $offset, $limit";
        else {
            $sql = "select count(id) as rec_count from tl_assignment_check where $filterString";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select* from tl_assignment_check where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTl_assignment_check = new Tl_assignment_check_object();
            $objTl_assignment_check->id = $row['id'];
            $objTl_assignment_check->academic_session = $row['academic_session'];
            $objTl_assignment_check->tl_assignment_id = $row['tl_assignment_id'];
            $objTl_assignment_check->scholar_id = $row['scholar_id'];
            $objTl_assignment_check->checked_by_employee_id = $row['checked_by_employee_id'];
            $objTl_assignment_check->max_marks = $row['max_marks'];
            $objTl_assignment_check->marks_obtained = $row['marks_obtained'];
            $objTl_assignment_check->review_comments = $row['review_comments'];
            $objTl_assignment_check->attachments = $this->get_attachments_for_assignment_check_submission($objTl_assignment_check->id);
            $records[] = $objTl_assignment_check;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    private function delete_tl_assignment_check_submission($tl_assignment_check_id) {
        $sql = "delete from tl_assignment_check_submission where tl_assignment_check_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($tl_assignment_check_id));
        $statement = NULL;
        $pdo = NULL;
    }

    private function add_tl_assignment_check_submission($objTl_assignment_check_submission) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from tl_assignment_check_submission";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTl_assignment_check_submission->id = $row['mvalue'];
        else
            $objTl_assignment_check_submission->id = 0;
        $objTl_assignment_check_submission->id = $objTl_assignment_check_submission->id + 1;
        $sql = "insert into tl_assignment_check_submission values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTl_assignment_check_submission->id,
            $objTl_assignment_check_submission->tl_assignment_check_id,
            $objTl_assignment_check_submission->url
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            return $objTl_assignment_check_submission;
        }
        return FALSE;
    }

    private function get_attachments_for_assignment_check_submission($tl_assignment_check_id) {
        $attachments = array();
        $sql = "select * from tl_assignment_check_submission where tl_assignment_check_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($tl_assignment_check_id));
        while ($row = $statement->fetch()) {
            $objTl_assignment_check_submission = new Tl_assignment_check_submission_object();
            $objTl_assignment_check_submission->id = $row['id'];
            $objTl_assignment_check_submission->tl_assignment_check_id = $row['tl_assignment_check_id'];
            $objTl_assignment_check_submission->url = $row['url'];

            $attachments[] = $objTl_assignment_check_submission;
        }
        $statement = NULL;
        $pdo = NULL;
        return $attachments;
    }

}
?>

