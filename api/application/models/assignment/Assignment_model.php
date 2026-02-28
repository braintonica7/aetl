<?php

class Assignment_model extends CI_Model {

    public function get_assignment($id) {
        $objAssignment = NULL;
        $sql = "select * from assignment where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objAssignment = new Assignment_object();
            $objAssignment->id = $row['id'];
            $objAssignment->scholar_id = $row['scholar_id'];
            $objAssignment->content_id = $row['content_id'];
            $objAssignment->assignment_submitted_to = $row['assignment_submitted_to'];
            if ($row['last_submission_date'] != NULL) 
                $objAssignment->last_submission_date = DateTime::createFromFormat("Y-m-d", $row['last_submission_date'])->format('Y-m-d');
            else
                $objAssignment->last_submission_date = NULL;
            
            $objAssignment->assignment_url = $row['assignment_url'];
            
            if ($row['actual_submission_date'] != NULL)
                $objAssignment->actual_submission_date = DateTime::createFromFormat("Y-m-d", $row['actual_submission_date'])->format('Y-m-d');
            else
                $objAssignment->actual_submission_date = NULL;
            
            $objAssignment->reviewed = $row['reviewed'];
            $objAssignment->review_comments = $row['review_comments'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objAssignment;
    }

    public function get_all_assignments() {
        $records = array();

        $sql = "select * from assignment";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objAssignment = new Assignment_object();
            $objAssignment->id = $row['id'];
            $objAssignment->scholar_id = $row['scholar_id'];
            $objAssignment->content_id = $row['content_id'];
            $objAssignment->assignment_submitted_to = $row['assignment_submitted_to'];
            $objAssignment->last_submission_date = DateTime::createFromFormat("Y-m-d", $row['last_submission_date']);
            $objAssignment->assignment_url = $row['assignment_url'];
            $objAssignment->actual_submission_date = DateTime::createFromFormat("Y-m-d", $row['actual_submission_date']);
            $objAssignment->reviewed = $row['reviewed'];
            $objAssignment->review_comments = $row['review_comments'];

            $records[] = $objAssignment;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function is_assignment_already_submitted($contentId, $scholarId) {
        $pdo = CDatabase::getPdo();
        $sql = "select * from assignment where content_id = ? and scholar_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($contentId, $scholarId));
        $rowCount = $statement->rowCount();
        $statement = NULL;
        $pdo = NULL;
        return $rowCount > 0;
    }

    public function get_assignment_from_content_id_and_scholar_id($contentId, $scholarId) {
        $objAssignment = NULL;
        $pdo = CDatabase::getPdo();
        $sql = "select * from assignment where content_id = ? and scholar_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($contentId, $scholarId));
        if ($row = $statement->fetch()) {
            $objAssignment = new Assignment_object();
            $objAssignment->id = $row['id'];
            $objAssignment->scholar_id = $row['scholar_id'];
            $objAssignment->content_id = $row['content_id'];
            $objAssignment->assignment_submitted_to = $row['assignment_submitted_to'];
            $objAssignment->last_submission_date = DateTime::createFromFormat("Y-m-d", $row['last_submission_date']);
            $objAssignment->assignment_url = $row['assignment_url'];
            $objAssignment->actual_submission_date = DateTime::createFromFormat("Y-m-d", $row['actual_submission_date']);
            $objAssignment->reviewed = $row['reviewed'];
            $objAssignment->review_comments = $row['review_comments'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objAssignment;
    }

    public function add_assignment($objAssignment) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from assignment";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objAssignment->id = $row['mvalue'];
        else
            $objAssignment->id = 0;
        $objAssignment->id = $objAssignment->id + 1;
        $sql = "insert into assignment values (?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objAssignment->id,
            $objAssignment->scholar_id,
            $objAssignment->content_id,
            $objAssignment->assignment_submitted_to,
            $objAssignment->last_submission_date == NULL ? NULL: $objAssignment->last_submission_date->format('Y-m-d'),
            $objAssignment->assignment_url,
            $objAssignment->actual_submission_date->format('Y-m-d'),
            $objAssignment->reviewed,
            $objAssignment->review_comments
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted){
            if ($objAssignment->last_submission_date != NULL)
                $objAssignment->last_submission_date = $objAssignment->last_submission_date->format('Y-m-d');
            if ($objAssignment->actual_submission_date != NULL)
                $objAssignment->actual_submission_date = $objAssignment->actual_submission_date->format('Y-m-d');
            return $objAssignment;
        }
        return FALSE;
    }

    public function update_assignment($objAssignment) {
        $sql = "update assignment set scholar_id = ?, content_id = ?, assignment_submitted_to = ?, last_submission_date = ?, assignment_url = ?, actual_submission_date = ?, reviewed = ?, review_comments = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objAssignment->scholar_id,
            $objAssignment->content_id,
            $objAssignment->assignment_submitted_to,
            $objAssignment->last_submission_date == NULL ? NULL: $objAssignment->last_submission_date->format('Y-m-d'),
            $objAssignment->assignment_url,
            $objAssignment->actual_submission_date->format('Y-m-d'),
            $objAssignment->reviewed,
            $objAssignment->review_comments,
            $objAssignment->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objAssignment;
        return FALSE;
    }

    public function delete_assignment($id) {
        $sql = "delete from assignment where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_assignment_count() {
        $count = 0;
        $sql = "select count(id) as cnt from assignment";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_assignment($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array(); 
        $sql = "";
        if ($filterString == NULL)
            $sql = "select assignment.*, genere.class_name, section.section, scholar.name as scholar_name from assignment left join scholar on assignment.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id  order by $sortBy $sortType limit $offset, $limit";
        else{
            $sql = "select assignment.*, genere.class_name, section.section, scholar.name as scholar_name from assignment left join scholar on assignment.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id where $filterString order by $sortBy $sortType";            
            //echo $sql;
            //return;
            $statement = $pdo->prepare($sql);            
            $statement->execute();
            $filterRecordCount = $statement->rowCount();
            $statement = NULL;
            
            $sql = "select assignment.*, genere.class_name, section.section, scholar.name as scholar_name from assignment left join scholar on assignment.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        
        //echo $sql;
        //return $sql;
        //$sql = "select * from assignment limit $offset, $limit";
        
        $statement = $pdo->prepare($sql);
        $statement->execute();                        
        while ($row = $statement->fetch()) {
            $objAssignment = new Assignment_object();
            $objAssignment->id = $row['id'];
            $objAssignment->scholar_id = $row['scholar_id'];
            $objAssignment->content_id = $row['content_id'];
            $objAssignment->assignment_submitted_to = $row['assignment_submitted_to'];
            
            if ($row['last_submission_date'] != NULL)
                $objAssignment->last_submission_date = DateTime::createFromFormat("Y-m-d", $row['last_submission_date'])->format('d-m-Y');
            else
                $objAssignment->last_submission_date = NULL;
                     
            $objAssignment->assignment_url = $row['assignment_url'];
            if ($row['actual_submission_date'] != NULL)
                $objAssignment->actual_submission_date = DateTime::createFromFormat("Y-m-d", $row['actual_submission_date'])->format('d-m-Y');
            else
                $objAssignment->actual_submission_date = NULL;
            
            $objAssignment->reviewed = $row['reviewed'] == 1 ? TRUE : FALSE;
            $objAssignment->review_comments = $row['review_comments'];
            
            $objAssignment->class_name = $row['class_name'];
            $objAssignment->section = $row['section'];
            $objAssignment->scholar_name = $row['scholar_name']; 
            
            $records[] = $objAssignment;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function flag_assignment_as_reviewed($assignmentId, $reviewComments = NULL){
        $pdo = CDatabase::getPdo();
        $sql = "update assignment set reviewed = 1, review_comments = ? where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($reviewComments, $assignmentId));
        $statement = NULL;
        $pdo = NULL;
    }

}
?>

