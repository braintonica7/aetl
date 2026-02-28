<?php

class Problem_model extends CI_Model
{

    public function get_Problem($id)
    {
        $objProblem = NULL;
        $sql = "select * from problem where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objProblem = new Problem_object();
            $objProblem->id = $row['id'];
            $objProblem->topic = $row['topic'];
            $objProblem->is_picked = $row['is_picked'];
            $objProblem->is_resolved = $row['is_resolved'];
            $objProblem->board_data = $row['board_data'];
            $objProblem->uploaded_by = $row['uploaded_by'];
            $objProblem->update_date = $row['update_date'];
            $objProblem->discussion_id = $row['discussion_id'];
            $objProblem->picked_by = $row['picked_by'];
            $objProblem->resolved_by = $row['resolved_by'];
            $objProblem->file_url = $row['file_url'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objProblem;
    }

    public function get_all_Problems()
    {
        $records = array();

        $sql = "select * from problem";
        // $sql = "select * from Problem where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objProblem = new Problem_object();
            $objProblem->id = $row['id'];
            $objProblem->topic = $row['topic'];
			$objProblem->is_picked = $row['is_picked'];
            $objProblem->is_resolved = $row['is_resolved'];
            $objProblem->board_data = $row['board_data'];
            $objProblem->uploaded_by = $row['uploaded_by'];
            $objProblem->update_date = $row['update_date'];
            $objProblem->discussion_id = $row['discussion_id'];
			 $objProblem->picked_by = $row['picked_by'];
            $objProblem->resolved_by = $row['resolved_by'];
            $objProblem->file_url = $row['file_url'];
            $records[] = $objProblem;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Problem($objProblem)
    {
		$update_date = date('Y-m-d h:i:s', strtotime($objProblem->update_date));
        $pdo = CDatabase::getPdo();
        $sql = "select max(id) as mvalue from problem";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objProblem->id = $row['mvalue'];
        else
            $objProblem->id = 0;
        $objProblem->id = $objProblem->id + 1;
        $sql = "insert into problem (`id`, `topic`, `file_url`, `uploaded_by`, `update_date`, `discussion_id`) values (?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objProblem->id,
            $objProblem->topic,
            $objProblem->file_url,
            $objProblem->uploaded_by,
            $update_date,
            $objProblem->discussion_id,
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objProblem;
        return FALSE;
    }

    public function delete_Problem($id)
    {
        $sql = "delete from problem where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_Problem_count()
    {
        $count = 0;
        $sql = "select count(id) as cnt from problem";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Problem($offset, $limit, $sortBy, $sortType, $filterString = NULL)
    {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from problem order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from problem where $filterString order by $sortBy $sortType limit $offset, $limit";

        //$sql = "select * from Problem limit $offset, $limit";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objProblem = new Problem_object();
            $objProblem->id = $row['id'];
            $objProblem->topic = $row['topic'];
            $objProblem->is_picked = $row['is_picked'];
            $objProblem->is_resolved = $row['is_resolved'];
            $objProblem->board_data = $row['board_data'];
            $objProblem->uploaded_by = $row['uploaded_by'];
            $objProblem->update_date = $row['update_date'];
            $objProblem->discussion_id = $row['discussion_id'];
            $objProblem->resolved_by = $row['resolved_by'];
            $objProblem->picked_by = $row['picked_by'];
            $objProblem->file_url = $row['file_url'];
            $records[] = $objProblem;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_all_problems_for_discussion($discussion_id)
    {
        $records = array();

        $sql = "select * from problem
                where discussion_id= ?
                order by update_date desc";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($discussion_id));
        while ($row = $statement->fetch()) {
            $objProblem = new stdClass();
            $objProblem->id = $row['id'];
          	$objProblem->topic = $row['topic'];
            $objProblem->is_picked = $row['is_picked'];
            $objProblem->is_resolved = $row['is_resolved'];
            $objProblem->board_data = $row['board_data'];
            $objProblem->uploaded_by = $row['uploaded_by'];
            $objProblem->update_date = $row['update_date'];
            $objProblem->discussion_id = $row['discussion_id'];
            $objProblem->resolved_by = $row['resolved_by'];
            $objProblem->picked_by = $row['picked_by'];
            $objProblem->file_url = $row['file_url'];
            $records[] = $objProblem;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }   

	public function update_pick_status($picked_by, $problem_id, $update_date)
    {
		$update_date = date('Y-m-d h:i:s', strtotime($update_date));
        $sql = "update problem set is_picked = ?, picked_by=?, update_date=? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            1,          
            $picked_by,          
            $update_date,          
            $problem_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objProblem;
        return FALSE;
    }

	public function update_resolve_status($resolved_by, $update_date, $board_data, $problem_id)
    {
		$update_date = date('Y-m-d h:i:s', strtotime($update_date));
        $sql = "update problem set is_resolved = ?, resolved_by=?, update_date=?, board_data=? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            1,          
            $resolved_by,          
            $update_date,    
			$board_data,      
            $problem_id			
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objProblem;
        return FALSE;
    }
}
