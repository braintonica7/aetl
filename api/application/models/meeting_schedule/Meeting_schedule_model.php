<?php

class Meeting_schedule_model extends CI_Model {

    public function get_meeting_schedule($id) {
        $objMeeting_schedule = NULL;
        $sql = "select * from meeting_schedule where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objMeeting_schedule = new Meeting_schedule_object();
            $objMeeting_schedule->id = $row['id'];
            $objMeeting_schedule->employee_id = $row['employee_id'];
            if ($row['meeting_date'] == NULL)
                $objMeeting_schedule->meeting_date = NULL;
            else
                $objMeeting_schedule->meeting_date = DateTime::createFromFormat("Y-m-d", $row['meeting_date'])->format('Y-m-d');
            if ($row['meeting_start'] == NULL)
                $objMeeting_schedule->meeting_start = NULL;
            else
                $objMeeting_schedule->meeting_start = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_start'])->format('H:i');
            if ($row['meeting_end'] == NULL)
                $objMeeting_schedule->meeting_end = NULL;
            else
                $objMeeting_schedule->meeting_end = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_end'])->format('H:i');
            $objMeeting_schedule->meeting_in_progress = $row['meeting_in_progress'] == 1;
            $objMeeting_schedule->meeting_category_id = $row['meeting_category_id'];
            $objMeeting_schedule->agenda = $row['agenda'];
            $objMeeting_schedule->guid = $row['guid'];
            $objMeeting_schedule->room_name = $row['room_name'];
            $objMeeting_schedule->room_code = $row['room_code'];
            $objMeeting_schedule->is_active = $row['is_active'] == 1;
        }
        $statement = NULL;
        $pdo = NULL;
        return $objMeeting_schedule;
    }

    public function get_all_meeting_schedules() {
        $records = array();

        $sql = "select * from meeting_schedule";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objMeeting_schedule = new Meeting_schedule_object();
            $objMeeting_schedule->id = $row['id'];
            $objMeeting_schedule->employee_id = $row['employee_id'];
            if ($row['meeting_date'] == NULL)
                $objMeeting_schedule->meeting_date = NULL;
            else
                $objMeeting_schedule->meeting_date = DateTime::createFromFormat("Y-m-d", $row['meeting_date'])->format('Y-m-d');
            if ($row['meeting_start'] == NULL)
                $objMeeting_schedule->meeting_start = NULL;
            else
                $objMeeting_schedule->meeting_start = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_start'])->format('H:i');
            if ($row['meeting_end'] == NULL)
                $objMeeting_schedule->meeting_end = NULL;
            else
                $objMeeting_schedule->meeting_end = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_end'])->format('H:i');
            $objMeeting_schedule->meeting_in_progress = $row['meeting_in_progress'] == 1;
            $objMeeting_schedule->meeting_category_id = $row['meeting_category_id'];
            $objMeeting_schedule->agenda = $row['agenda'];
            $objMeeting_schedule->guid = $row['guid'];
            $objMeeting_schedule->room_name = $row['room_name'];
            $objMeeting_schedule->room_code = $row['room_code'];
            $objMeeting_schedule->is_active = $row['is_active'] == 1;

            $records[] = $objMeeting_schedule;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_meeting_schedule($objMeeting_schedule) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from meeting_schedule";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objMeeting_schedule->id = $row['mvalue'];
        else
            $objMeeting_schedule->id = 0;
        $objMeeting_schedule->id = $objMeeting_schedule->id + 1;
        $sql = "insert into meeting_schedule values (?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objMeeting_schedule->id,
            $objMeeting_schedule->employee_id,
            $objMeeting_schedule->meeting_date == NULL ? NULL : $objMeeting_schedule->meeting_date->format('Y-m-d'),
            $objMeeting_schedule->meeting_start == NULL ? NULL : $objMeeting_schedule->meeting_start->format('Y-m-d H:i:s'),
            $objMeeting_schedule->meeting_end == NULL ? NULL : $objMeeting_schedule->meeting_end->format('Y-m-d H:i:s'),
            $objMeeting_schedule->meeting_in_progress = 0,
            $objMeeting_schedule->meeting_category_id,
            $objMeeting_schedule->agenda,
            $objMeeting_schedule->guid,
            $objMeeting_schedule->room_name,
            $objMeeting_schedule->room_code,
            $objMeeting_schedule->is_active
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            if ($objMeeting_schedule->meeting_start != NULL)
                $objMeeting_schedule->meeting_start = $objMeeting_schedule->meeting_start->format('H:i');
            if ($objMeeting_schedule->meeting_end != NULL)
                $objMeeting_schedule->meeting_end = $objMeeting_schedule->meeting_end->format('H:i');
            $objMeeting_schedule->is_active = $objMeeting_schedule->is_active == 1;
            return $objMeeting_schedule;
        }
        return FALSE;
    }

    public function update_meeting_schedule($objMeeting_schedule) {
        $sql = "update meeting_schedule set employee_id = ?, meeting_date = ?, meeting_start = ?, meeting_end = ?, meeting_in_progress = ?,  meeting_category_id = ?, agenda = ?, guid = ?, room_name = ?, room_code = ?, is_active = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objMeeting_schedule->employee_id,
            $objMeeting_schedule->meeting_date == NULL ? NULL : $objMeeting_schedule->meeting_date->format('Y-m-d'),
            $objMeeting_schedule->meeting_start == NULL ? NULL : $objMeeting_schedule->meeting_start->format('Y-m-d H:i:s'),
            $objMeeting_schedule->meeting_end == NULL ? NULL : $objMeeting_schedule->meeting_end->format('Y-m-d H:i:s'),
            $objMeeting_schedule->meeting_in_progress,
            $objMeeting_schedule->meeting_category_id,
            $objMeeting_schedule->agenda,
            $objMeeting_schedule->guid,
            $objMeeting_schedule->room_name,
            $objMeeting_schedule->room_code,
            $objMeeting_schedule->is_active,
            $objMeeting_schedule->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            if ($objMeeting_schedule->meeting_start != NULL)
                $objMeeting_schedule->meeting_start = $objMeeting_schedule->meeting_start->format('H:i');
            if ($objMeeting_schedule->meeting_end != NULL)
                $objMeeting_schedule->meeting_end = $objMeeting_schedule->meeting_end->format('H:i');
            $objMeeting_schedule->is_active = $objMeeting_schedule->is_active == 1;
            return $objMeeting_schedule;
        }
        return FALSE;
    }

    public function delete_meeting_schedule($id) {
        $pdo = CDatabase::getPdo();
        $sql = "delete from meeting_participant where meeting_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;

        $sql = "delete from meeting_schedule where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_meeting_schedule_count($employeeId) {
        $count = 0;
        $sql = "select count(id) as cnt from meeting_schedule where employee_id = " . $employeeId;
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_meeting_schedule_count_for_scholar($objScholar) {
        $classId = $objScholar->class_id;
        $sectionId = $objScholar->section_id;
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $count = 0;
        $sql = "select count(meeting_schedule.id) as rec_count from meeting_schedule where meeting_schedule.id in(select distinct meeting_id from meeting_participant where class_id = ? and section_id = ?) and meeting_schedule.is_active = 1 and meeting_schedule.meeting_date >= ? order by meeting_schedule.meeting_date";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId, $sectionId, $dateTime->format('Y-m-d')));
        if ($row = $statement->fetch())
            $count = $row['rec_count'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_meeting_schedule($offset, $limit, $sortBy, $sortType, $employeeId, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";

        if ($filterString == NULL) {
            $extraWhere = " where employee_id = " . $employeeId . " ";

            $sql = "select count(id) as rec_count from meeting_schedule $extraWhere";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;

            $sql = "select* from meeting_schedule $extraWhere order by $sortBy $sortType limit $offset, $limit";
        } else {
            $extraWhere = " and employee_id = " . $employeeId . " ";
            $sql = "select count(id) as rec_count from meeting_schedule where $filterString $extraWhere";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select* from meeting_schedule where $filterString $extraWhere order by $sortBy $sortType limit $offset, $limit";
        }
        $statement = $pdo->prepare($sql);
        $statement->execute();

        date_default_timezone_set("Asia/Calcutta");
        $now = new DateTime();

        while ($row = $statement->fetch()) {
            $objMeeting_schedule = new Meeting_schedule_object();
            $objMeeting_schedule->id = $row['id'];
            $objMeeting_schedule->employee_id = $row['employee_id'];
            if ($row['meeting_date'] == NULL)
                $objMeeting_schedule->meeting_date = NULL;
            else
                $objMeeting_schedule->meeting_date = DateTime::createFromFormat("Y-m-d", $row['meeting_date'])->format('Y-m-d');
            if ($row['meeting_start'] == NULL)
                $objMeeting_schedule->meeting_start = NULL;
            else
                $objMeeting_schedule->meeting_start = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_start'])->format('h:i A');
            if ($row['meeting_end'] == NULL)
                $objMeeting_schedule->meeting_end = NULL;
            else
                $objMeeting_schedule->meeting_end = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_end'])->format('h:i A');
            $objMeeting_schedule->meeting_in_progress = $row['meeting_in_progress'] == 1;
            $objMeeting_schedule->meeting_category_id = $row['meeting_category_id'];
            $objMeeting_schedule->agenda = $row['agenda'];
            $objMeeting_schedule->guid = $row['guid'];
            $objMeeting_schedule->room_name = $row['room_name'];
            $objMeeting_schedule->room_code = $row['room_code'];
            $objMeeting_schedule->is_active = $row['is_active'] == 1;

            $meetingDate = DateTime::createFromFormat('Y-m-d', $row['meeting_date']);
            $meetingStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_start']);
            $meetingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_end']);
            $flag = 0;
            if ($now >= $meetingStart && $now < $meetingEnd) {
                $flag = 1;
            } else {
                $flag = 0;
            }
            $objMeeting_schedule->can_start_meeting = $flag == 1 ? "Yes" : "No";


            $records[] = $objMeeting_schedule;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_paginated_meeting_schedule_for_scholar($offset, $limit, $sortBy, $sortType, $objScholar, $filterString = NULL, &$filterRecordCount = -1) {
        $classId = $objScholar->class_id;
        $sectionId = $objScholar->section_id;
        $records = array();

        $pdo = CDatabase::getPdo();

        $sql = "select meeting_schedule.*, employee.name from meeting_schedule left join employee on meeting_schedule.employee_id = employee.id where meeting_schedule.id in(select distinct meeting_id from meeting_participant where class_id = ? and section_id = ?) and meeting_schedule.is_active = 1 and meeting_schedule.meeting_date >= ? order by meeting_schedule.meeting_date DESC";
        $sql = "";

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();

        if ($filterString == NULL) {
            $sql = "select count(meeting_schedule.id) as rec_count from meeting_schedule left join employee on meeting_schedule.employee_id = employee.id where meeting_schedule.id in(select distinct meeting_id from meeting_participant where class_id = ? and section_id = ?) and meeting_schedule.is_active = 1 and meeting_schedule.meeting_date >= ? order by meeting_schedule.meeting_date DESC";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute(array($classId, $sectionId, $dateTime->format('Y-m-d')));
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;

            $sql = "select meeting_schedule.*, employee.name from meeting_schedule left join employee on meeting_schedule.employee_id = employee.id where meeting_schedule.id in(select distinct meeting_id from meeting_participant where class_id = ? and section_id = ?) and meeting_schedule.is_active = 1 and meeting_schedule.meeting_date >= ? order by $sortBy $sortType limit $offset, $limit";
        }
        /*
          else {
          $sql = "select count(id) as rec_count from meeting_schedule where $filterString $extraWhere";
          $countStatement = $pdo->prepare($sql);
          $countStatement->execute();
          if ($row = $countStatement->fetch())
          $filterRecordCount = $row['rec_count'];
          $countStatement = NULL;
          $sql = "select* from meeting_schedule where $filterString $extraWhere order by $sortBy $sortType limit $offset, $limit";
          } */
        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId, $sectionId, $dateTime->format('Y-m-d')));

        date_default_timezone_set("Asia/Calcutta");
        $now = new DateTime();

        while ($row = $statement->fetch()) {
            $objMeeting_schedule = new Meeting_schedule_object();
            $objMeeting_schedule->id = $row['id'];
            $objMeeting_schedule->employee_id = $row['employee_id'];
            if ($row['meeting_date'] == NULL)
                $objMeeting_schedule->meeting_date = NULL;
            else
                $objMeeting_schedule->meeting_date = DateTime::createFromFormat("Y-m-d", $row['meeting_date'])->format('Y-m-d');
            if ($row['meeting_start'] == NULL)
                $objMeeting_schedule->meeting_start = NULL;
            else
                $objMeeting_schedule->meeting_start = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_start'])->format('h:i A');
            if ($row['meeting_end'] == NULL)
                $objMeeting_schedule->meeting_end = NULL;
            else
                $objMeeting_schedule->meeting_end = DateTime::createFromFormat("Y-m-d H:i:s", $row['meeting_end'])->format('h:i A');
            $objMeeting_schedule->meeting_in_progress = $row['meeting_in_progress'] == 1;
            $objMeeting_schedule->meeting_category_id = $row['meeting_category_id'];
            $objMeeting_schedule->agenda = $row['agenda'];
            $objMeeting_schedule->guid = $row['guid'];
            $objMeeting_schedule->room_name = $row['room_name'];
            $objMeeting_schedule->room_code = $row['room_code'];
            $objMeeting_schedule->is_active = $row['is_active'] == 1;

            $meetingDate = DateTime::createFromFormat('Y-m-d', $row['meeting_date']);
            $meetingStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_start']);
            $meetingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_end']);
            $flag = 0;
            if (($now >= $meetingStart && $now < $meetingEnd) || $objMeeting_schedule->meeting_in_progress) {
                $flag = 1;
            } else {
                $flag = 0; 
            }
            $objMeeting_schedule->can_start_meeting = "No";
            $objMeeting_schedule->can_scholar_join_meeting = $flag == 1 ? "Yes" : "No";
            
            

            $records[] = $objMeeting_schedule;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    function can_faculty_start_meeting($id, $employee_id) {
        $flag = 0;
        $pdo = CDatabase::getPdo();
        $sql = "select meeting_date, meeting_start, meeting_end from meeting_schedule where id = ? and employee_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id, $employee_id));
        if ($row = $statement->fetch()) {
            $meetingDate = DateTime::createFromFormat('Y-m-d', $row['meeting_date']);
            $meetingStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_start']);
            $meetingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_end']);

            date_default_timezone_set("Asia/Calcutta");
            $now = new DateTime();

            /* echo $meetingDate->format('Y-m-d') . PHP_EOL;
              echo $meetingStart->format('Y-m-d H:i:s') . PHP_EOL;
              echo $meetingEnd->format('Y-m-d H:i:s') . PHP_EOL;
              echo "Now : " . $now->format('Y-m-d H:i:s')  . PHP_EOL;
              echo "Now comes comparision" . PHP_EOL; */
            if ($now >= $meetingStart && $now < $meetingEnd) {
                $flag = 1;
            } else {
                $flag = 0;
            }
        }
        $statement = NULL;
        $pdo = NULL;
        return $flag;
    }

    function can_scholar_join_meeting($id, $objScholar, $meetingParticipants) {
        $flag = 0;
        $pdo = CDatabase::getPdo();
        $sql = "select meeting_date, meeting_start, meeting_end from meeting_schedule where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $meetingDate = DateTime::createFromFormat('Y-m-d', $row['meeting_date']);
            $meetingStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_start']);
            $meetingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_end']);

            date_default_timezone_set("Asia/Calcutta");
            $now = new DateTime();

            if ($now >= $meetingStart && $now < $meetingEnd) {
                $flag = 0;
                foreach ($meetingParticipants as $objMeetingParticipant) {
                    if ($objMeetingParticipant->class_id == $objScholar->class_id && $objMeetingParticipant->section_id == $objScholar->section_id) {
                        $flag = 1;
                        break;
                    }
                }
            } else {
                $flag = 0;
            }
        }
        $statement = NULL;
        $pdo = NULL;
        return $flag;
    }

    function can_scholar_join_meeting_2($id, $classId, $sectionId, $meetingParticipants) {
        $flag = 0;
        $pdo = CDatabase::getPdo();
        $sql = "select meeting_date, meeting_start, meeting_end, meeting_in_progress from meeting_schedule where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            if ($row['meeting_in_progress'] == 1) {
                $meetingDate = DateTime::createFromFormat('Y-m-d', $row['meeting_date']);
                $meetingStart = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_start']);
                $meetingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $row['meeting_end']);

                date_default_timezone_set("Asia/Calcutta");
                $now = new DateTime();

                if ($now >= $meetingStart && $now < $meetingEnd) {
                    $flag = 0;
                    foreach ($meetingParticipants as $objMeetingParticipant) {
                        if ($objMeetingParticipant->class_id == $classId && $objMeetingParticipant->section_id == $sectionId) {
                            $flag = 1;
                            break;
                        }
                    }
                } else {
                    $flag = 0;
                }
            } else {
                $flag = 0;
            }
        }
        $statement = NULL;
        $pdo = NULL;
        return $flag;
    }

    function update_meeting_in_progress_flag($id, $employee_id, $flag) {
        $pdo = CDatabase::getPdo();
        $sql = "update meeting_schedule set meeting_in_progress = ? where id = ? && employee_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($flag, $id, $employee_id));
        $statement = NULL;
        $pdo = NULL;
    }

}

?>