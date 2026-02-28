<?php

class Time_limit_assignment_model extends CI_Model {

    public function get_tl_assignment($id) {
        $objTl_assignment = NULL;
        $sql = "select tl_assignment.*, genere.class_name, subject.subject from tl_assignment left join genere on tl_assignment.class_id = genere.id left join subject on tl_assignment.subject_id = subject.id where tl_assignment.id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objTl_assignment = new Time_limit_assignment_object();
            $objTl_assignment->id = $row['id'];
            $objTl_assignment->academic_session = $row['academic_session'];
            $objTl_assignment->title = $row['title'];
            $objTl_assignment->assignment_url = $row['assignment_url'];
            $objTl_assignment->class_id = $row['class_id'];
            $objTl_assignment->subject_id = $row['subject_id'];

            $from = NULL;
            $till = NULL;
            if ($row['available_from'] == NULL)
                $objTl_assignment->available_from = NULL;
            else {
                $from = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_from']);
                $objTl_assignment->available_from = $from->format('Y-m-d H:i:s');
                $objTl_assignment->from = $from->format('Y') . "," . $from->format('m') . "," . $from->format('d') . "," . $from->format('H') . "," . $from->format('i') . "," . $from->format('s');
            }

            if ($row['available_till'] == NULL)
                $objTl_assignment->available_till = NULL;
            else {
                $till = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_till']);
                $objTl_assignment->available_till = $till->format('Y-m-d H:i:s');
                $objTl_assignment->till = $till->format('Y') . "," . $till->format('m') . "," . $till->format('d') . "," . $till->format('H') . "," . $till->format('i') . "," . $till->format('s');
            }

            $objTl_assignment->uploaded_by = $row['uploaded_by'];
            $objTl_assignment->is_active = $row['is_active'] == 1;
            $objTl_assignment->is_approved = $row['is_approved'] == 1;
            if ($row['created'] == NULL)
                $objTl_assignment->created = NULL;
            else
                $objTl_assignment->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created'])->format('Y-m-d H:i:s');
            if ($row['updated'] == NULL)
                $objTl_assignment->updated = NULL;
            else
                $objTl_assignment->updated = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('Y-m-d H:i:s');

            $objTl_assignment->class_name = $row['class_name'];
            $objTl_assignment->subject = $row['subject'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objTl_assignment;
    }

    public function get_all_tl_assignments() {
        $records = array();

        $sql = "select tl_assignment.*, genere.class_name, subject.subject from tl_assignment left join genere on tl_assignment.class_id = genere.id left join subject on tl_assignment.subject_id = subject.id ";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTl_assignment = new Tl_assignment_object();
            $objTl_assignment->id = $row['id'];
            $objTl_assignment->academic_session = $row['academic_session'];
            $objTl_assignment->title = $row['title'];
            $objTl_assignment->assignment_url = $row['assignment_url'];
            $objTl_assignment->class_id = $row['class_id'];
            $objTl_assignment->subject_id = $row['subject_id'];
            if ($row['available_from'] == NULL)
                $objTl_assignment->available_from = NULL;
            else
                $objTl_assignment->available_from = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_from'])->format('Y-m-d H:i:s');
            if ($row['available_till'] == NULL)
                $objTl_assignment->available_till = NULL;
            else
                $objTl_assignment->available_till = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_till'])->format('Y-m-d H:i:s');
            $objTl_assignment->uploaded_by = $row['uploaded_by'];
            $objTl_assignment->is_active = $row['is_active'] == 1;
            $objTl_assignment->is_approved = $row['is_approved'] == 1;
            if ($row['created'] == NULL)
                $objTl_assignment->created = NULL;
            else
                $objTl_assignment->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created'])->format('Y-m-d H:i:s');
            if ($row['updated'] == NULL)
                $objTl_assignment->updated = NULL;
            else
                $objTl_assignment->updated = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('Y-m-d H:i:s');

            $objTl_assignment->class_name = $row['class_name'];
            $objTl_assignment->subject = $row['subject'];

            $records[] = $objTl_assignment;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_tl_assignment($objTl_assignment) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from tl_assignment";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTl_assignment->id = $row['mvalue'];
        else
            $objTl_assignment->id = 0;
        $objTl_assignment->id = $objTl_assignment->id + 1;
        $sql = "insert into tl_assignment values (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTl_assignment->id,
            $objTl_assignment->academic_session,
            $objTl_assignment->title,
            $objTl_assignment->assignment_url,
            $objTl_assignment->class_id,
            $objTl_assignment->subject_id,
            $objTl_assignment->available_from == NULL ? NULL : $objTl_assignment->available_from->format('Y-m-d H:i:s'),
            $objTl_assignment->available_till == NULL ? NULL : $objTl_assignment->available_till->format('Y-m-d H:i:s'),
            $objTl_assignment->uploaded_by,
            $objTl_assignment->is_active,
            $objTl_assignment->is_approved,
            $objTl_assignment->created == NULL ? NULL : $objTl_assignment->created->format('Y-m-d H:i:s'),
            $objTl_assignment->updated == NULL ? NULL : $objTl_assignment->updated->format('Y-m-d H:i:s')
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            if ($objTl_assignment->available_from != NULL)
                $objTl_assignment->available_from = $objTl_assignment->available_from->format('d-m-Y H:i:s');
            if ($objTl_assignment->available_till != NULL)
                $objTl_assignment->available_till = $objTl_assignment->available_till->format('d-m-Y H:i:s');
            $objTl_assignment->is_active = $objTl_assignment->is_active == 1;
            $objTl_assignment->is_approved = $objTl_assignment->is_approved == 1;
            if ($objTl_assignment->created != NULL)
                $objTl_assignment->created = $objTl_assignment->created->format('d-m-Y H:i:s');
            if ($objTl_assignment->updated != NULL)
                $objTl_assignment->updated = $objTl_assignment->updated->format('d-m-Y H:i:s');
            return $objTl_assignment;
        }
        return FALSE;
    }

    public function update_tl_assignment($objTl_assignment) {
        $sql = "update tl_assignment set academic_session = ?, title = ?, assignment_url = ?, class_id = ?, subject_id = ?, available_from = ?, available_till = ?, uploaded_by = ?, is_active = ?, is_approved = ?, created = ?, updated = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objTl_assignment->academic_session,
            $objTl_assignment->title,
            $objTl_assignment->assignment_url,
            $objTl_assignment->class_id,
            $objTl_assignment->subject_id,
            $objTl_assignment->available_from == NULL ? NULL : $objTl_assignment->available_from->format('Y-m-d H:i:s'),
            $objTl_assignment->available_till == NULL ? NULL : $objTl_assignment->available_till->format('Y-m-d H:i:s'),
            $objTl_assignment->uploaded_by,
            $objTl_assignment->is_active,
            $objTl_assignment->is_approved,
            $objTl_assignment->created == NULL ? NULL : $objTl_assignment->created->format('Y-m-d H:i:s'),
            $objTl_assignment->updated == NULL ? NULL : $objTl_assignment->updated->format('Y-m-d H:i:s'),
            $objTl_assignment->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            if ($objTl_assignment->available_from != NULL)
                $objTl_assignment->available_from = $objTl_assignment->available_from->format('d-m-Y H:i:s');
            if ($objTl_assignment->available_till != NULL)
                $objTl_assignment->available_till = $objTl_assignment->available_till->format('d-m-Y H:i:s');
            $objTl_assignment->is_active = $objTl_assignment->is_active == 1;
            $objTl_assignment->is_approved = $objTl_assignment->is_approved == 1;
            if ($objTl_assignment->created != NULL)
                $objTl_assignment->created = $objTl_assignment->created->format('d-m-Y H:i:s');
            if ($objTl_assignment->updated != NULL)
                $objTl_assignment->updated = $objTl_assignment->updated->format('d-m-Y H:i:s');
            return $objTl_assignment;
        }
        return FALSE;
    }

    public function delete_tl_assignment($id) {
        $sql = "delete from tl_assignment where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_tl_assignment_count() {
        $count = 0;
        $sql = "select count(id) as cnt from tl_assignment";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_tl_assignment($objUser, $offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select tl_assignment.*, genere.class_name, subject.subject from tl_assignment left join genere on tl_assignment.class_id = genere.id left join subject on tl_assignment.subject_id = subject.id order by $sortBy $sortType limit $offset, $limit";
        else {
            $sql = "select count(id) as rec_count from tl_assignment where $filterString";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select tl_assignment.*, genere.class_name, subject.subject from tl_assignment left join genere on tl_assignment.class_id = genere.id left join subject on tl_assignment.subject_id = subject.id where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        //echo $sql;
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        $CI = & get_instance();        
        $CI->load->model('time_limit_assignment_submission/tl_assignment_submission_model');
        
        while ($row = $statement->fetch()) { 

            $objTl_assignment = new time_limit_assignment_object();
            $objTl_assignment->id = $row['id'];
            $objTl_assignment->academic_session = $row['academic_session'];
            $objTl_assignment->title = $row['title'];
            $objTl_assignment->assignment_url = $row['assignment_url'];
            $objTl_assignment->class_id = $row['class_id'];
            $objTl_assignment->subject_id = $row['subject_id'];
            
            $from = NULL;
            $till = NULL;
            if ($row['available_from'] == NULL)
                $objTl_assignment->available_from = NULL;
            else {
                $from = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_from']);
                $objTl_assignment->available_from = $from->format('Y-m-d H:i:s');
                //$objTl_assignment->from = $from->format('Y-m-dH:i:s');  //$from->format('Y') . "," . $from->format('m') . "," . $from->format('d') . "," . $from->format('H') . "," . $from->format('i') . "," . $from->format('s');
                $objTl_assignment->from = $from->format('Y-m-d') .'T' . $from->format('H:i:s');
            }

            if ($row['available_till'] == NULL)
                $objTl_assignment->available_till = NULL;
            else {
                $till = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_till']);
                $objTl_assignment->available_till = $till->format('Y-m-d H:i:s');
                //$objTl_assignment->till = $till->format('Y-m-dTH:i:s');  //$till->format('Y') . "," . $till->format('m') . "," . $till->format('d') . "," . $till->format('H') . "," . $till->format('i') . "," . $till->format('s');
                $objTl_assignment->till = $till->format('Y-m-d') .'T' . $till->format('H:i:s');
            }
            /*
            if ($row['available_from'] == NULL)
                $objTl_assignment->available_from = NULL;
            else
                $objTl_assignment->available_from = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_from'])->format('d-m-Y H:i:s');

            if ($row['available_till'] == NULL)
                $objTl_assignment->available_till = NULL;
            else
                $objTl_assignment->available_till = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_till'])->format('d-m-Y H:i:s');
             */   
            $objTl_assignment->uploaded_by = $row['uploaded_by'];
            $objTl_assignment->is_active = $row['is_active'] == 1;
            $objTl_assignment->is_approved = $row['is_approved'] == 1;
            if ($row['created'] == NULL)
                $objTl_assignment->created = NULL;
            else
                $objTl_assignment->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created'])->format('d-m-Y H:i:s');
            if ($row['updated'] == NULL)
                $objTl_assignment->updated = NULL;
            else
                $objTl_assignment->updated = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('d-m-Y H:i:s');

            $objTl_assignment->class_name = $row['class_name'];
            $objTl_assignment->subject = $row['subject'];

            if (isset($objUser->scholar)) {
                $availableFrom = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_from']);
                $availableTill = DateTime::createFromFormat("Y-m-d H:i:s", $row['available_till']);
                date_default_timezone_set("Asia/Calcutta");
                $justNow = new DateTime();
                if ($justNow >= $availableFrom && $justNow < $availableTill) {
                    $objTl_assignment->can_submit_assignment = 'Yes';
                } else {
                    $objTl_assignment->can_submit_assignment = 'No';
                    $objTl_assignment->assignment_url = '';
                }
            }
            if ($objUser->role_id == 5){
                $objTl_assignment->assignment_submitted = $CI->tl_assignment_submission_model->is_tl_assignment_submitted_for_scholar($objTl_assignment->id, $objUser->scholar->id);
            }else{
                $objTl_assignment->assignment_submitted = TRUE;
            }
            //print_r($objTl_assignment);

            $records[] = $objTl_assignment;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

