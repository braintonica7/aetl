<?php

class Content_query_model extends CI_Model {

    public function get_content_query($id) {
        $objContent_query = NULL;
        $sql = "select * from content_query where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objContent_query = new Content_query_object();
            $objContent_query->id = $row['id'];
            $objContent_query->content_id = $row['content_id'];
            $objContent_query->scholar_id = $row['scholar_id'];
            $objContent_query->query_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['query_date'])->format('Y-m-d H:i:s');
            $objContent_query->query = $row['query'];
            $objContent_query->query_submitted_to = $row['query_submitted_to'];
            $objContent_query->query_replied = $row['query_replied'] == 1;
            $objContent_query->query_replied_by = $row['query_replied_by'];
            $objContent_query->query_reply = $row['query_reply'];
            if ($row['query_reply_date'] == NULL)
                $objContent_query->query_reply_date = NULL;
            else
                $objContent_query->query_reply_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['query_reply_date'])->format('Y-m-d H:i:s');
            $objContent_query->reply_document_url = $row['reply_document_url'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objContent_query;
    }

    public function get_all_content_querys() {
        $records = array();

        $sql = "select * from content_query";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_query = new Content_query_object();
            $objContent_query->id = $row['id'];
            $objContent_query->content_id = $row['content_id'];
            $objContent_query->scholar_id = $row['scholar_id'];
            $objContent_query->query_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['query_date']);
            $objContent_query->query = $row['query'];
            $objContent_query->query_submitted_to = $row['query_submitted_to'];
            $objContent_query->query_replied = $row['query_replied'] == 1;
            $objContent_query->query_replied_by = $row['query_replied_by'];
            $objContent_query->query_reply = $row['query_reply'];
            if ($row['query_reply_date'] == NULL)
                $objContent_query->query_reply_date = NULL;
            else
                $objContent_query->query_reply_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['query_reply_date'])->format('Y-m-d');
            $objContent_query->reply_document_url = $row['reply_document_url'];

            $records[] = $objContent_query;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_content_query($objContent_query) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from content_query";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objContent_query->id = $row['mvalue'];
        else
            $objContent_query->id = 0;
        $objContent_query->id = $objContent_query->id + 1;
        $sql = "insert into content_query values (?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $queryReplyDate = $objContent_query->query_reply_date == NULL ? NULL : $objContent_query->query_reply_date->format('Y-m-d');
        $inserted = $statement->execute(array(
            $objContent_query->id,
            $objContent_query->content_id,
            $objContent_query->scholar_id,
            $objContent_query->query_date->format('Y-m-d H:i:s'),
            $objContent_query->query,
            $objContent_query->query_submitted_to, 
            $objContent_query->query_replied,
            $objContent_query->query_replied_by,
            $objContent_query->query_reply,
            $queryReplyDate,
            $objContent_query->reply_document_url
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted){
            if ($objContent_query->query_date != NULL)
                $objContent_query->query_date = $objContent_query->query_date->format('Y-m-d H:i:s');
            if ($objContent_query->query_reply_date != NULL)
                $objContent_query->query_reply_date = $objContent_query->query_reply_date->format('Y-m-d H:i:s');
               
            return $objContent_query;
        }
        
        
        
        return FALSE;
    }

    public function update_content_query($objContent_query) {
        $sql = "update content_query set content_id = ?, scholar_id = ?, query_date = ?, query = ?, query_submitted_to = ?, query_replied = ?, query_replied_by = ?, query_reply = ?, query_reply_date = ?, reply_document_url = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $queryReplyDate = $objContent_query->query_reply_date == NULL ? NULL : $objContent_query->query_reply_date->format('Y-m-d H:i:s');
        //print_r($objContent_query);
        $updated = $statement->execute(array(
            $objContent_query->content_id,
            $objContent_query->scholar_id,
            $objContent_query->query_date->format('Y-m-d H:i:s'),
            $objContent_query->query,
            $objContent_query->query_submitted_to,
            $objContent_query->query_replied,
            $objContent_query->query_replied_by,
            $objContent_query->query_reply,
            $queryReplyDate,
            //$objContent_query->query_reply_date->format('Y-m-d H:i:s'),
            $objContent_query->reply_document_url,
            $objContent_query->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated){
            if ($objContent_query->query_date != NULL)
                $objContent_query->query_date = $objContent_query->query_date->format('Y-m-d H:i:s');
            if ($objContent_query->query_reply_date != NULL)
                $objContent_query->query_reply_date = $objContent_query->query_reply_date->format('Y-m-d H:i:s');
            return $objContent_query;
        }
        return FALSE;
    }

    public function delete_content_query($id) {
        $sql = "delete from content_query where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_content_query_count($loggedUser) {
        $session = CPreference::$academicSession;
        if ($loggedUser->role_id == 4) {         //faculty
            $employeeId = $loggedUser->employee->id;
            $sql = "select count(content_query.id) as cnt from content_query left join content on content_query.content_id = content.id where content.academic_session = '$session' and content.uploaded_by = $employeeId";
        } else if ($loggedUser->role_id == 5) {   //student
            $classId = $loggedUser->scholar->class_id;
            $sql = "select count(content_query.id) as cnt from content_query left join content on content_query.content_id = content.id where content.academic_session = '$session' and scholar_id in (select distinct id from scholar where class_id = $classId and session = '$session')";
        }else{
            $sql = "select count(id) as cnt from content_query";
        }
                        
        $count = 0;
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_content_query($loggedUser, $offset, $limit, $sortBy, $sortType, $filterString = NULL, & $recCount) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL) {
            $session = CPreference::$academicSession;
            if ($loggedUser->role_id == 4) {         //faculty
                $employeeId = $loggedUser->employee->id;
                //$sql = "select content_query.*, scholar.name from content_query left join content on content_query.content_id = content.id left join scholar on content_query.scholar_id = scholar.id where content.academic_session = '$session' and content_query.uploaded_by = $employeeId order by $sortBy $sortType limit $offset, $limit";
                //$sql = "select content_query.*, scholar.name from content_query left join content on content_query.content_id = content.id left join scholar on content_query.scholar_id = scholar.id where content.academic_session = '$session' and content_query.query_submitted_to = $employeeId order by $sortBy $sortType limit $offset, $limit";                
                
                $sql = "select count(content_query.id) as query_count from content_query  left join content on content_query.content_id = content.id left join scholar on content_query.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id left join subject on content.subject_id = subject.id where content.academic_session = '$session' and content_query.query_submitted_to = $employeeId";
                $statement = $pdo->prepare($sql);
                $statement->execute();
                if ($row = $statement->fetch())
                        $recCount = $row['query_count'];
                $statement = NULL;
                                                
                $sql = "select content_query.*, scholar.name, scholar.scholar_no, genere.class_name, section.section, subject.subject from content_query left join content on content_query.content_id = content.id left join scholar on content_query.scholar_id = scholar.id left join genere on scholar.class_id = genere.id left join section on scholar.section_id = section.id left join subject on content.subject_id = subject.id where content.academic_session = '$session' and content_query.query_submitted_to = $employeeId order by $sortBy $sortType limit $offset, $limit"; 
            } else if ($loggedUser->role_id == 5) {   //student
                $classId = $loggedUser->scholar->class_id; 
                $sql = "select content_query.*, scholar.name from content_query left join content on content_query.content_id = content.id  left join scholar on content_query.scholar_id = scholar.id where content.academic_session = '$session' and scholar_id in (select distinct id from scholar where class_id = $classId and session = '$session') order by $sortBy $sortType limit $offset, $limit";
            }
            /*
              if ($role == 'student')
              $sql = "select * from content_query where scholar_id = $id order by $sortBy $sortType limit $offset, $limit";
              else if ($role = 'faculty')
              $sql = "select * from content_query where content_id in (select id from content where uploaded_by = $id) order by $sortBy $sortType limit $offset, $limit";
              //$sql = "select* from content_query order by $sortBy $sortType limit $offset, $limit"; */
        } else
            $sql = "select* from content_query where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objContent_query = new Content_query_object();
            $objContent_query->id = $row['id'];
            $objContent_query->content_id = $row['content_id'];
            $objContent_query->scholar_id = $row['scholar_id'];
            $objContent_query->query_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['query_date'])->format('d-m-Y H:i:s');
            $objContent_query->query = $row['query'];
            $objContent_query->query_submitted_to = $row['query_submitted_to'];
            $objContent_query->query_replied = $row['query_replied'] == 1;
            $objContent_query->query_replied_by = $row['query_replied_by'];
            $objContent_query->query_reply = $row['query_reply'];
            if ($row['query_reply_date'] == NULL)
                $objContent_query->query_reply_date = NULL;
            else
                $objContent_query->query_reply_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['query_reply_date'])->format('Y-m-d H:i:s');
            $objContent_query->reply_document_url = $row['reply_document_url'];
            
            
            $objContent_query->scholar_no = $row['scholar_no'];
            $objContent_query->class_name = $row['class_name'];
            $objContent_query->section = $row['section'];
            $objContent_query->subject = $row['subject'];
            
            
            $objContent_query->scholar_name = $row['name'];
            $records[] = $objContent_query;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    
    
}
?>

