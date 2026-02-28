<?php

class Discussion_model extends CI_Model
{

    public function get_Discussion($id)
    {
        $objDiscussion = NULL;
        $sql = "select * from discussion where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objDiscussion = new Discussion_object();
            $objDiscussion->id = $row['id'];
            $objDiscussion->topic = $row['topic'];
            $objDiscussion->class_id = $row['class_id'];
            $objDiscussion->subject_id = $row['subject_id'];
            $objDiscussion->start_date = $row['start_date'];
            $objDiscussion->live_stream_url = $row['created_by'];
            $objDiscussion->is_live = $row['is_live'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objDiscussion;
    }

    public function get_all_Discussions()
    {
        $records = array();

        $sql = "select * from discussion";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objDiscussion = new Discussion_object();
           	$objDiscussion->id = $row['id'];
            $objDiscussion->topic = $row['topic'];
            $objDiscussion->class_id = $row['class_id'];
            $objDiscussion->subject_id = $row['subject_id'];
            $objDiscussion->start_date = $row['start_date'];
            $objDiscussion->live_stream_url = $row['created_by'];
            $objDiscussion->is_live = $row['is_live'];
            $records[] = $objDiscussion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Discussion($objDiscussion)
    {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from discussion";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objDiscussion->id = $row['mvalue'];
        else
            $objDiscussion->id = 0;
        $objDiscussion->id = $objDiscussion->id + 1;
        $sql = "insert into discussion (`id`, `topic`, `class_id`, `subject_id`, `start_date`, `created_by`) values (?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objDiscussion->id,
            $objDiscussion->topic,
            $objDiscussion->class_id,
            $objDiscussion->subject_id,
            null,
            $objDiscussion->created_by,
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objDiscussion;
        return FALSE;
    }

    public function update_Discussion($objDiscussion)
    {
        $sql = "update discussion set topic = ?, class_id = ?, subject_id = ?, created_by=? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objDiscussion->topic,
            $objDiscussion->class_id,
            $objDiscussion->subject_id,
            $objDiscussion->created_by,
            $objDiscussion->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objDiscussion;
        return FALSE;
    }

    public function delete_Discussion($id)
    {
        $sql = "delete from discussion where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_Discussion_count()
    {
        $count = 0;
        $sql = "select count(id) as cnt from discussion";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Discussion($offset, $limit, $sortBy, $sortType, $filterString = NULL)
    {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from discussion order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from discussion where $filterString order by $sortBy $sortType limit $offset, $limit";


        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objDiscussio = new Discussion_object();
            $objDiscussio->id = $row['id'];
            $objDiscussio->topic = $row['topic'];
            $objDiscussio->class_id = $row['class_id'];
            $objDiscussio->subject_id = $row['subject_id'];
            $objDiscussio->start_date = $row['start_date'];
            $objDiscussio->created_by = $row['created_by'];
            $objDiscussio->is_live = $row['is_live'];
            $records[] = $objDiscussio;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function update_start_date($discussion_id, $start_date)
    {
        //$start_date = DateTime::createFromFormat('Y-m-d', $start_date);
        $start_date = date('Y-m-d h:i:s', strtotime($start_date));

        $sql = "update discussion set start_date = ?, is_live = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $start_date,
            1,
            $discussion_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }

    public function update_discussion_stop($discussion_id)
    {
        $sql = "update discussion set is_live = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            0,
            $discussion_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }

    public function update_live_status($discussion_id, $is_live = 0)
    {
        $sql = "update discussion set is_live = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $is_live,
            $discussion_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }    

	
}
