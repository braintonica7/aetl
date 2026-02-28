<?php

class Quiz_scholar_model extends CI_Model
{

    public function get_Quiz_scholar($id)
    {
        $objQuiz_scholar = NULL;
        $sql = "select * from quiz_scholar where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objQuiz_scholar = new Quiz_scholar_object();
            $objQuiz_scholar->id = $row['id'];
            $objQuiz_scholar->quiz_id = $row['quiz_id'];
            $objQuiz_scholar->scholar_id = $row['scholar_id'];
            $objQuiz_scholar->scholar_order = $row['scholar_order'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuiz_scholar;
    }

    public function get_all_Quiz_scholars()
    {
        $records = array();

        $sql = "select * from quiz_scholar";
        //$sql = "select * from quiz_scholar where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuiz_scholar = new Quiz_scholar_object();
            $objQuiz_scholar->id = $row['id'];
            $objQuiz_scholar->quiz_id = $row['quiz_id'];
            $objQuiz_scholar->scholar_id = $row['scholar_id'];
            $objQuiz_scholar->scholar_order = $row['scholar_order'];

            $records[] = $objQuiz_scholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Quiz_scholar($objQuiz_scholar)
    {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from quiz_scholar";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objQuiz_scholar->id = $row['mvalue'];
        else
            $objQuiz_scholar->id = 0;

        $sql = "select max(scholar_order) as mvalue from quiz_scholar where quiz_id=" . $objQuiz_scholar->quiz_id;
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objQuiz_scholar->scholar_order = $row['mvalue'] + 1;
        else
            $objQuiz_scholar->scholar_order = 0;


        $objQuiz_scholar->id = $objQuiz_scholar->id + 1;
        $sql = "insert into quiz_scholar(id,quiz_id,scholar_id, scholar_order ) values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objQuiz_scholar->id,
            $objQuiz_scholar->quiz_id,
            $objQuiz_scholar->scholar_id,
            $objQuiz_scholar->scholar_order
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objQuiz_scholar;
        return FALSE;
    }

    public function update_Quiz_scholar($objQuiz_scholar)
    {
        $sql = "update quiz_scholar set quiz_id = ?, scholar_id = ?, scholar_order = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objQuiz_scholar->quiz_id,
            $objQuiz_scholar->scholar_id,
            $objQuiz_scholar->scholar_order
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objQuiz_scholar;
        return FALSE;
    }

    public function delete_quiz_scholar($id)
    {
        $sql = "delete from quiz_scholar where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
        return 1;
    }

    public function get_Quiz_scholar_count()
    {
        $count = 0;
        $sql = "select count(id) as cnt from quiz_scholar";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Quiz_scholar($offset, $limit, $sortBy, $sortType, $filterString = NULL)
    {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from quiz_scholar order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from quiz_scholar where $filterString order by $sortBy $sortType limit $offset, $limit";

        $sql = "select * from quiz_scholar limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuiz_scholar = new Quiz_scholar_object();
            $objQuiz_scholar->id = $row['id'];
            $objQuiz_scholar->quiz_id = $row['quiz_id'];
            $objQuiz_scholar->scholar_id = $row['scholar_id'];
            $objQuiz_scholar->scholar_order = $row['scholar_order'];
            $records[] = $objQuiz_scholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_all_scholars_for_quiz($quiz_id)
    {
        $records = array();

        $sql = "select qz.id as quiz_id, q.id as scholar_id,qq.scholar_order,
                from scholar q
                join quiz_scholar qq on q.id=qq.scholar_id
                join quiz qz on qz.id = qq.quiz_id
                where qq.quiz_id= ?
                order by qq.scholar_order";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objscholar = new stdClass();
            $objscholar->quiz_id = $row['quiz_id'];
            $objscholar->scholar_id = $row['scholar_id'];
            $objscholar->scholar_order = $row['scholar_order'];
            $records[] = $objscholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
}
