<?php

class Content_model extends CI_Model {

    public function get_content($id) {
        $objContent = NULL;
        $sql = "select content.*, account.token from content left join account on content.account_id = account.id where content.id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objContent = new Content_object();
            $objContent->id = $row['id'];
            $objContent->academic_session = $row['academic_session'];
            $objContent->content_type_id = $row['content_type_id'];
            $objContent->content_url = $row['content_url'];
            $objContent->account_id = $row['account_id'];
            $objContent->class_id = $row['class_id'];
            $objContent->subject_id = $row['subject_id'];
            $objContent->topic = $row['topic'];
            $objContent->topic_note = $row['topic_note'];

            if ($row['submission_date'] == NULL)
                $objContent->submission_date = NULL;
            else
                $objContent->submission_date = DateTime::createFromFormat("Y-m-d", $row['submission_date'])->format('Y-m-d');

            $objContent->is_active = $row['is_active'] == 1;
            $objContent->is_approved = $row['is_approved'] == 1;
            $objContent->uploaded_by = $row['uploaded_by'];
            $objContent->approved_by = $row['approved_by'];

            if ($row['approval_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['approval_date'])->format('Y-m-d H:i:s');

            $objContent->sensored_by = $row['sensored_by'];

            if ($row['sensor_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['sensor_date'])->format('Y-m-d H:i:s');

            $objContent->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created']);

            if ($row['updated'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('Y-m-d H:i:s');

            $objContent->token = $row['token'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objContent;
    }

    public function get_all_contents() {
        $records = array();

        $sql = "select * from content";
        $sql = "select * from content where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent = new Content_object();
            $objContent->id = $row['id'];
            $objContent->academic_session = $row['academic_session'];
            $objContent->content_type_id = $row['content_type_id'];
            $objContent->content_url = $row['content_url'];
            $objContent->account_id = $row['account_id'];
            $objContent->class_id = $row['class_id'];
            $objContent->subject_id = $row['subject_id'];
            $objContent->topic = $row['topic'];
            $objContent->topic_note = $row['topic_note'];

            if ($row['submission_date'] == NULL)
                $objContent->submission_date = NULL;
            else
                $objContent->submission_date = DateTime::createFromFormat("Y-m-d", $row['submission_date'])->format('Y-m-d');

            $objContent->is_active = $row['is_active'] == 1;
            $objContent->is_approved = $row['is_approved'] == 1;
            $objContent->uploaded_by = $row['uploaded_by'];
            $objContent->approved_by = $row['approved_by'];

            if ($row['approval_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['approval_date'])->format('Y-m-d H:i:s');

            $objContent->sensored_by = $row['sensored_by'];

            if ($row['sensor_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['sensor_date'])->format('Y-m-d H:i:s');

            $objContent->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created']);

            if ($row['updated'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('Y-m-d H:i:s');

            $records[] = $objContent;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_content($objContent) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from content";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objContent->id = $row['mvalue'];
        else
            $objContent->id = 0;
        $objContent->id = $objContent->id + 1;

        $submissionDate = $objContent->submission_date == NULL ? NULL : $objContent->submission_date->format('Y-m-d');
        $approvalDate = $objContent->approval_date == NULL ? NULL : $objContent->approval_date->format('Y-m-d H:i:s');

        $sql = "insert into content values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objContent->id,
            $objContent->academic_session,
            $objContent->content_type_id,
            $objContent->content_url,
            $objContent->account_id,
            $objContent->class_id,
            $objContent->subject_id,
            $objContent->topic,
            $objContent->topic_note,
            $submissionDate,
            $objContent->is_active,
            $objContent->is_approved,
            $objContent->uploaded_by,
            $objContent->approved_by,
            $approvalDate,
            $objContent->sensored_by,
            NULL,
            $objContent->created->format('Y-m-d H:i:s'),
            $objContent->updated->format('Y-m-d H:i:s')
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objContent;
        return FALSE;
    }

    public function update_content($objContent) {
        $submissionDate = $objContent->submission_date;
        if ($submissionDate != NULL)
            $submissionDate = $submissionDate->format('Y-m-d');
        $sql = "update content set academic_session = ?, content_type_id = ?, content_url = ?, account_id = ?, class_id = ?, subject_id = ?, topic = ?, topic_note = ?, submission_date = ?, is_active = ?, is_approved = ?, uploaded_by = ?, approved_by = ?, approval_date = ?, sensored_by = ?, sensor_date = ?, created = ?, updated = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objContent->academic_session,
            $objContent->content_type_id,
            $objContent->content_url,
            $objContent->account_id,
            $objContent->class_id,
            $objContent->subject_id,
            $objContent->topic,
            $objContent->topic_note,
            $submissionDate,
            $objContent->is_active,
            $objContent->is_approved,
            $objContent->uploaded_by,
            $objContent->approved_by,
            $objContent->approval_date == NULL ? NULL : $objContent->approval_date->format('Y-m-d H:i:s'),
            $objContent->sensored_by,
            $objContent->sensor_date->format('Y-m-d H:i:s'),
            $objContent->created->format('Y-m-d H:i:s'),
            $objContent->updated->format('Y-m-d H:i:s'),
            $objContent->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            $objContent->approval_date = $objContent->approval_date == NULL ? NULL : $objContent->approval_date->format('Y-m-d');
            $objContent->sensor_date = $objContent->sensor_date == NULL ? NULL : $objContent->sensor_date->format('Y-m-d H:i:s');
            $objContent->created = $objContent->created == NULL ? NULL : $objContent->created->format('Y-m-d H:i:s');
            $objContent->updated = $objContent->updated == NULL ? NULL : $objContent->updated->format('Y-m-d H:i:s');
            return $objContent;
        }
        return FALSE;
    }

    public function delete_content($id) {
        $sql = "delete from content where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_content_count() {
        $count = 0;
        $sql = "select count(id) as cnt from content";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_content_count_for_class_subject($classId, $subjectId) {
        $academicSession = CPreference::$academicSession;
        $pdo = CDatabase::getPdo();
        $sql = "select count(id) as cnt from content where class_id = ? and subject_id = ? and academic_session = ? and is_active = 1 and is_approved = 1";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId, $subjectId, $academicSession));
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function approve_disapprove_content($id, $flag, $sensoredBy) {
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $pdo = CDatabase::getPdo();
        $sql = "update content set is_approved = ?, approved_by = ?, approval_date = ?, sensored_by = ?, sensor_date = ? where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($flag, $sensoredBy, $dateTime->format('Y-m-d H:i:s'), $sensoredBy, $dateTime->format('Y-m-d H:i:s'), $id));
        $statement->execute();
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_paginated_content($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$totalRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select * from content order by $sortBy $sortType limit $offset, $limit";
        else {
            $sql = "select count(id) as rec_count from content where $filterString";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $totalRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select * from content where $filterString order by $sortBy $sortType limit $offset, $limit";
        }

        //echo $sql ;

        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent = new Content_object();
            $objContent->id = $row['id'];
            $objContent->academic_session = $row['academic_session'];
            $objContent->content_type_id = $row['content_type_id'];
            $objContent->content_url = $row['content_url'];
            $objContent->account_id = $row['account_id'];
            $objContent->class_id = $row['class_id'];
            $objContent->subject_id = $row['subject_id'];
            $objContent->topic = $row['topic'];
            $objContent->topic_note = $row['topic_note'];

            if ($row['submission_date'] == NULL)
                $objContent->submission_date = NULL;
            else
                $objContent->submission_date = DateTime::createFromFormat("Y-m-d", $row['submission_date'])->format('Y-m-d');

            $objContent->is_active = $row['is_active'] == 1;
            $objContent->is_approved = $row['is_approved'] == 1;
            $objContent->uploaded_by = $row['uploaded_by'];
            $objContent->approved_by = $row['approved_by'];

            if ($row['approval_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['approval_date'])->format('Y-m-d H:i:s');

            $objContent->sensored_by = $row['sensored_by'];

            if ($row['sensor_date'] == NULL)
                $objContent->sensor_date = NULL;
            else
                $objContent->sensor_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['sensor_date'])->format('Y-m-d H:i:s');

            $objContent->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created']);

            if ($row['updated'] == NULL)
                $objContent->updated = NULL;
            else
                $objContent->updated = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('Y-m-d H:i:s');

            $records[] = $objContent;
        }
        $statement = NULL;
        $pdo = NULL;        
        return $records;
    }

    public function get_paginated_content_for_class_subject($scholarId, $classId, $subjectId, $offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $pdo = CDatabase::getPdo();
        $sql = "select content_id from content_read_status where scholar_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($scholarId));
        $readContents = array();
        while ($row = $statement->fetch()) {
            $readContents[] = $row['content_id'];
        }
        $statement = NULL;

        $academicSession = CPreference::$academicSession;
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            //$sql = "select * from content where class_id = ? and subject_id = ? and academic_session = ? and is_active = 1 and is_approved = 1 order by $sortBy $sortType limit $offset, $limit";
            $sql = "select * from content where class_id = ? and subject_id = ? and academic_session = ? and is_active = 1 and is_approved = 1 order by $sortBy $sortType";
        else
            //$sql = "select * from content where $filterString and class_id = ? and subject_id = ? and academic_session = ? and is_active = 1 and is_approved = 1 order by $sortBy $sortType limit $offset, $limit";
            $sql = "select * from content where $filterString and class_id = ? and subject_id = ? and academic_session = ? and is_active = 1 and is_approved = 1 order by $sortBy $sortType";
 

        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId, $subjectId, $academicSession));
        while ($row = $statement->fetch()) {
            $objContent = new Content_object();
            $objContent->id = $row['id'];
            $objContent->academic_session = $row['academic_session'];
            $objContent->content_type_id = $row['content_type_id'];
            $objContent->content_url = $row['content_url'];
            $objContent->account_id = $row['account_id'];
            $objContent->class_id = $row['class_id'];
            $objContent->subject_id = $row['subject_id'];
            $objContent->topic = $row['topic'];
            $objContent->topic_note = $row['topic_note'];

            if ($row['submission_date'] == NULL)
                $objContent->submission_date = NULL;
            else
                $objContent->submission_date = DateTime::createFromFormat("Y-m-d", $row['submission_date'])->format('d-m-Y');

            $objContent->is_active = $row['is_active'] == 1;
            $objContent->is_approved = $row['is_approved'] == 1;
            $objContent->uploaded_by = $row['uploaded_by'];
            $objContent->approved_by = $row['approved_by'];

            if ($row['approval_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['approval_date'])->format('d-m-Y H:i:s');

            $objContent->sensored_by = $row['sensored_by'];

            if ($row['sensor_date'] == NULL)
                $objContent->sensor_date = NULL;
            else
                $objContent->sensor_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['sensor_date'])->format('d-m-Y H:i:s');

            $objContent->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created']);

            if ($row['updated'] == NULL)
                $objContent->updated = NULL;
            else
                $objContent->updated = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('d-m-Y H:i:s');

            $objContent->read = in_array($objContent->id, $readContents);

            $records[] = $objContent;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

