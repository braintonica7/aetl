<?php

class Tl_assignment_submission_model extends CI_Model {

    public function get_tl_assignment_submission($id) {
        $objTl_assignment_submission = NULL;
        $sql = "select * from tl_assignment_submission where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objTl_assignment_submission = new Tl_assignment_submission_object();
            $objTl_assignment_submission->id = $row['id'];
            $objTl_assignment_submission->academic_session = $row['academic_session'];
            $objTl_assignment_submission->tl_assignment_id = $row['tl_assignment_id'];
            if ($row['assignment_available_from'] == NULL)
                $objTl_assignment_submission->assignment_available_from = NULL;
            else
                $objTl_assignment_submission->assignment_available_from = DateTime::createFromFormat("Y-m-d H:i:s", $row['assignment_available_from'])->format('Y-m-d H:i:s');
            if ($row['assignment_available_till'] == NULL)
                $objTl_assignment_submission->assignment_available_till = NULL;
            else
                $objTl_assignment_submission->assignment_available_till = DateTime::createFromFormat("Y-m-d H:i:s", $row['assignment_available_till'])->format('Y-m-d H:i:s');
            $objTl_assignment_submission->scholar_id = $row['scholar_id'];
            $objTl_assignment_submission->assignment_submitted_to = $row['assignment_submitted_to'];
            $objTl_assignment_submission->submitted_assignment_url = $row['submitted_assignment_url'];
            $objTl_assignment_submission->part_no = $row['part_no'];
            if ($row['submission_time'] == NULL)
                $objTl_assignment_submission->submission_time = NULL;
            else
                $objTl_assignment_submission->submission_time = DateTime::createFromFormat("Y-m-d H:i:s", $row['submission_time'])->format('Y-m-d H:i:s');
        }
        $statement = NULL;
        $pdo = NULL;
        return $objTl_assignment_submission;
    }

    public function get_all_tl_assignment_submissions() {
        $records = array();

        $sql = "select * from tl_assignment_submission";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTl_assignment_submission = new Tl_assignment_submission_object();
            $objTl_assignment_submission->id = $row['id'];
            $objTl_assignment_submission->academic_session = $row['academic_session'];
            $objTl_assignment_submission->tl_assignment_id = $row['tl_assignment_id'];
            if ($row['assignment_available_from'] == NULL)
                $objTl_assignment_submission->assignment_available_from = NULL;
            else
                $objTl_assignment_submission->assignment_available_from = DateTime::createFromFormat("Y-m-d H:i:s", $row['assignment_available_from'])->format('Y-m-d H:i:s');
            if ($row['assignment_available_till'] == NULL)
                $objTl_assignment_submission->assignment_available_till = NULL;
            else
                $objTl_assignment_submission->assignment_available_till = DateTime::createFromFormat("Y-m-d H:i:s", $row['assignment_available_till'])->format('Y-m-d H:i:s');
            $objTl_assignment_submission->scholar_id = $row['scholar_id'];
            $objTl_assignment_submission->assignment_submitted_to = $row['assignment_submitted_to'];
            $objTl_assignment_submission->submitted_assignment_url = $row['submitted_assignment_url'];
            $objTl_assignment_submission->part_no = $row['part_no'];
            if ($row['submission_time'] == NULL)
                $objTl_assignment_submission->submission_time = NULL;
            else
                $objTl_assignment_submission->submission_time = DateTime::createFromFormat("Y-m-d H:i:s", $row['submission_time'])->format('Y-m-d H:i:s');

            $records[] = $objTl_assignment_submission;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function is_tl_assignment_submitted_for_scholar($tl_assignment_id, $scholar_id) {
        $submitted = FALSE;
        $pdo = CDatabase::getPdo();
        $sql = "select * from tl_assignment_submission where tl_assignment_id = ? and scholar_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($tl_assignment_id, $scholar_id));
        $rowCount = $statement->rowCount();
        $statement = NULL;
        $pdo = NULL;
        $submitted = $rowCount > 0;
        return $submitted;
    }

    public function add_tl_assignment_submission($objTl_assignment_submission) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from tl_assignment_submission";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTl_assignment_submission->id = $row['mvalue'];
        else
            $objTl_assignment_submission->id = 0;
        $objTl_assignment_submission->id = $objTl_assignment_submission->id + 1;
        $sql = "insert into tl_assignment_submission values (?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTl_assignment_submission->id,
            $objTl_assignment_submission->academic_session,
            $objTl_assignment_submission->tl_assignment_id,
            $objTl_assignment_submission->assignment_available_from == NULL ? NULL : $objTl_assignment_submission->assignment_available_from->format('Y-m-d H:i:s'),
            $objTl_assignment_submission->assignment_available_till == NULL ? NULL : $objTl_assignment_submission->assignment_available_till->format('Y-m-d H:i:s'),
            $objTl_assignment_submission->scholar_id,
            $objTl_assignment_submission->assignment_submitted_to,
            $objTl_assignment_submission->submitted_assignment_url,
            $objTl_assignment_submission->part_no,
            $objTl_assignment_submission->submission_time == NULL ? NULL : $objTl_assignment_submission->submission_time->format('Y-m-d H:i:s')
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            if ($objTl_assignment_submission->assignment_available_from != NULL)
                $objTl_assignment_submission->assignment_available_from = $objTl_assignment_submission->assignment_available_from->format('d-m-Y H:i:s');
            if ($objTl_assignment_submission->assignment_available_till != NULL)
                $objTl_assignment_submission->assignment_available_till = $objTl_assignment_submission->assignment_available_till->format('d-m-Y H:i:s');
            if ($objTl_assignment_submission->submission_time != NULL)
                $objTl_assignment_submission->submission_time = $objTl_assignment_submission->submission_time->format('d-m-Y H:i:s');
            return $objTl_assignment_submission;
        }
        return FALSE;
    }

    public function update_tl_assignment_submission($objTl_assignment_submission) {
        $sql = "update tl_assignment_submission set academic_session = ?, tl_assignment_id = ?, assignment_available_from = ?, assignment_available_till = ?, scholar_id = ?, assignment_submitted_to = ?, submitted_assignment_url = ?, part_no = ?, submission_time = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objTl_assignment_submission->academic_session,
            $objTl_assignment_submission->tl_assignment_id,
            $objTl_assignment_submission->assignment_available_from == NULL ? NULL : $objTl_assignment_submission->assignment_available_from->format('Y-m-d H:i:s'),
            $objTl_assignment_submission->assignment_available_till == NULL ? NULL : $objTl_assignment_submission->assignment_available_till->format('Y-m-d H:i:s'),
            $objTl_assignment_submission->scholar_id,
            $objTl_assignment_submission->assignment_submitted_to,
            $objTl_assignment_submission->submitted_assignment_url,
            $objTl_assignment_submission->part_no,
            $objTl_assignment_submission->submission_time == NULL ? NULL : $objTl_assignment_submission->submission_time->format('Y-m-d H:i:s'),
            $objTl_assignment_submission->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            if ($objTl_assignment_submission->assignment_available_from != NULL)
                $objTl_assignment_submission->assignment_available_from = $objTl_assignment_submission->assignment_available_from->format('d-m-Y H:i:s');
            if ($objTl_assignment_submission->assignment_available_till != NULL)
                $objTl_assignment_submission->assignment_available_till = $objTl_assignment_submission->assignment_available_till->format('d-m-Y H:i:s');
            if ($objTl_assignment_submission->submission_time != NULL)
                $objTl_assignment_submission->submission_time = $objTl_assignment_submission->submission_time->format('d-m-Y H:i:s');
            return $objTl_assignment_submission;
        }
        return FALSE;
    }

    public function delete_tl_assignment_submission($id) {
        $sql = "delete from tl_assignment_submission where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_tl_assignment_submission_count() {
        $count = 0;
        $sql = "select count(id) as cnt from tl_assignment_submission";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_tl_assignment_submission($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        $sortBy = "tl_assignment_submission.id";
        if ($filterString == NULL)
            $sql = "select* from tl_assignment_submission order by $sortBy $sortType limit $offset, $limit";
        else {
            //$sql = "select count(tl_assignment_submission.id) as rec_count, scholar.name as scholar_name from tl_assignment_submission left join scholar on tl_assignment_submission.scholar_id = scholar.id where $filterString group by scholar.id, scholar.name ";
            $sql = "select tl_assignment_submission.*, scholar.name, genere.class_name, section.section, tl_assignment_check.marks_obtained, tl_assignment_check.max_marks from tl_assignment_submission left join scholar on tl_assignment_submission.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id left join tl_assignment_check on tl_assignment_submission.tl_assignment_id = tl_assignment_check.tl_assignment_id and tl_assignment_submission.scholar_id = tl_assignment_check.scholar_id where $filterString order by $sortBy $sortType";
            //$sql = "select count(tl_assignment_submission.id) as rec_count from tl_assignment_submission where $filterString";
            //echo $sql;
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                //$filterRecordCount = $row['rec_count'];
                $filterRecordCount = $countStatement->rowCount();
            $countStatement = NULL;
            //$sql = "select tl_assignment_submission.*, scholar.name, genere.class_name, section.section from tl_assignment_submission left join scholar on tl_assignment_submission.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id where $filterString order by $sortBy $sortType limit $offset, $limit";
            $sql = "select tl_assignment_submission.*, scholar.name, genere.class_name, section.section, tl_assignment_check.marks_obtained, tl_assignment_check.max_marks from tl_assignment_submission left join scholar on tl_assignment_submission.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id left join tl_assignment_check on tl_assignment_submission.tl_assignment_id = tl_assignment_check.tl_assignment_id and tl_assignment_submission.scholar_id = tl_assignment_check.scholar_id where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        //echo $sql;
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        while ($row = $statement->fetch()) {
            $objTl_assignment_submission = new Tl_assignment_submission_object();
            $objTl_assignment_submission->id = $row['id'];
            $objTl_assignment_submission->academic_session = $row['academic_session'];
            $objTl_assignment_submission->tl_assignment_id = $row['tl_assignment_id'];

            $from = NULL;
            $till = NULL;
            if ($row['assignment_available_from'] == NULL)
                $objTl_assignment_submission->assignment_available_from = NULL;
            else {
                $from = DateTime::createFromFormat("Y-m-d H:i:s", $row['assignment_available_from']);
                $objTl_assignment_submission->assignment_available_from = $from->format('d-m-Y H:i:s');
                $objTl_assignment_submission->from = $from->format('Y') . "," . $from->format('m') . "," . $from->format('d') . "," . $from->format('H') . "," . $from->format('i') . "," . $from->format('s');
            }

            if ($row['assignment_available_till'] == NULL)
                $objTl_assignment_submission->assignment_available_till = NULL;
            else {
                $till = DateTime::createFromFormat("Y-m-d H:i:s", $row['assignment_available_till']);
                $objTl_assignment_submission->assignment_available_till = $till->format('d-m-Y H:i:s');
                $objTl_assignment_submission->till = $till->format('Y') . "," . $till->format('m') . "," . $till->format('d') . "," . $till->format('H') . "," . $till->format('i') . "," . $till->format('s');
            }

            $objTl_assignment_submission->scholar_id = $row['scholar_id'];
            $objTl_assignment_submission->assignment_submitted_to = $row['assignment_submitted_to'];
            $objTl_assignment_submission->submitted_assignment_url = $row['submitted_assignment_url'];
            $objTl_assignment_submission->part_no = $row['part_no'];
            if ($row['submission_time'] == NULL)
                $objTl_assignment_submission->submission_time = NULL;
            else
                $objTl_assignment_submission->submission_time = DateTime::createFromFormat("Y-m-d H:i:s", $row['submission_time'])->format('d-m-Y H:i:s');

            /*
              if ($from != NULL)
              $objTl_assignment_submission->from = $from->format('Y') . "," . $from->format('m') . ",". $from->format('d') . ",". $from->format('H') . "," . $from->format('i') . "," . $from->format('s');

              if ($till != NULL)
              $objTl_assignment_submission->till = $till->format('Y') . "," . $till->format('m') . ",". $till->format('d') . ",". $till->format('H') . "," . $till->format('i') . "," . $till->format('s');
             */

            $objTl_assignment_submission->scholar_name = $row['name'];
            $objTl_assignment_submission->class_name = $row['class_name'];
            $objTl_assignment_submission->section = $row['section'];


            if ($row['marks_obtained'] == NULL)
                $objTl_assignment_submission->marks_obtained = '';
            else
                $objTl_assignment_submission->marks_obtained = $row['marks_obtained'];

            if ($row['max_marks'] == NULL)
                $objTl_assignment_submission->max_marks = '';
            else
                $objTl_assignment_submission->max_marks = $row['max_marks'];

            $records[] = $objTl_assignment_submission;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

