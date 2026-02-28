<?php

class Meeting_participant_model extends CI_Model {

    public function get_meeting_participant($id) {
        $objMeeting_participant = NULL;
        $sql = "select meeting_participant.*, genere.class_name, section.section from meeting_participant left join genere on meeting_participant.class_id = genere.id left join section on meeting_participant.section_id = section.id where meeting_participant.id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objMeeting_participant = new Meeting_participant_object();
            $objMeeting_participant->id = $row['id'];
            $objMeeting_participant->meeting_id = $row['meeting_id'];
            $objMeeting_participant->class_id = $row['class_id'];
            $objMeeting_participant->section_id = $row['section_id'];
            $objMeeting_participant->class_name = $row['class_name'];
            $objMeeting_participant->section = $row['section'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objMeeting_participant;
    }

    public function get_all_meeting_participants() {
        $records = array();

        $sql = "select meeting_participant.*, genere.class_name, section.section from meeting_participant left join genere on meeting_participant.class_id = genere.id left join section on meeting_participant.section_id = section.id";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objMeeting_participant = new Meeting_participant_object();
            $objMeeting_participant->id = $row['id'];
            $objMeeting_participant->meeting_id = $row['meeting_id'];
            $objMeeting_participant->class_id = $row['class_id'];
            $objMeeting_participant->section_id = $row['section_id'];
            $objMeeting_participant->class_name = $row['class_name'];
            $objMeeting_participant->section = $row['section'];
            
            $records[] = $objMeeting_participant;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_participants_for_meeting($meeting_id){
        $participants = array();
        $pdo = CDatabase::getPdo();
        //$sql = "select meeting_participant.*, genere.class_name, section.section from meeting_participant left join genere on meeting_participant.class_id = genere.id left join section on meeting_participant.section_id = section.id from meeting_participant where meeting_id = ?";
        $sql = "select * from meeting_participant where meeting_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($meeting_id));
        while ($row = $statement->fetch()){
            $objMeeting_participant = new Meeting_participant_object();
            $objMeeting_participant->id = $row['id'];
            $objMeeting_participant->meeting_id = $row['meeting_id'];
            $objMeeting_participant->class_id = $row['class_id'];
            $objMeeting_participant->section_id = $row['section_id'];
            //$objMeeting_participant->class_name = $row['class_name'];
            //$objMeeting_participant->section = $row['section'];
            
            $participants[] = $objMeeting_participant;
        }
        $statement = NULL;
        $pdo = NULL;
        return $participants;
    }

    public function add_meeting_participant($objMeeting_participant) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from meeting_participant";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objMeeting_participant->id = $row['mvalue'];
        else
            $objMeeting_participant->id = 0;
        $objMeeting_participant->id = $objMeeting_participant->id + 1;
        $sql = "insert into meeting_participant values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objMeeting_participant->id,
            $objMeeting_participant->meeting_id,
            $objMeeting_participant->class_id,
            $objMeeting_participant->section_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            return $objMeeting_participant;
        }
        return FALSE;
    }

    public function update_meeting_participant($objMeeting_participant) {
        $sql = "update meeting_participant set meeting_id = ?, class_id = ?, section_id = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objMeeting_participant->meeting_id,
            $objMeeting_participant->class_id,
            $objMeeting_participant->section_id,
            $objMeeting_participant->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            return $objMeeting_participant;
        }
        return FALSE;
    }

    public function delete_meeting_participant($id) {
        $sql = "delete from meeting_participant where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_meeting_participant_count() {
        $count = 0;
        $sql = "select count(id) as cnt from meeting_participant";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_meeting_participant($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select meeting_participant.*, genere.class_name, section.section from meeting_participant left join genere on meeting_participant.class_id = genere.id left join section on meeting_participant.section_id = section.id order by $sortBy $sortType limit $offset, $limit";
        else {
            $sql = "select count(id) as rec_count from meeting_participant where $filterString";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select meeting_participant.*, genere.class_name, section.section from meeting_participant left join genere on meeting_participant.class_id = genere.id left join section on meeting_participant.section_id = section.id where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        
        //echo $sql . PHP_EOL;
        
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objMeeting_participant = new Meeting_participant_object();
            $objMeeting_participant->id = $row['id'];
            $objMeeting_participant->meeting_id = $row['meeting_id'];
            $objMeeting_participant->class_id = $row['class_id'];
            $objMeeting_participant->section_id = $row['section_id'];
            $objMeeting_participant->class_name = $row['class_name'];
            $objMeeting_participant->section = $row['section'];
            $records[] = $objMeeting_participant;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

