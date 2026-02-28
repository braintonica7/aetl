<?php

class Quiz_question_model extends CI_Model {

    public function get_Quiz_question($id) {
        $objQuiz_question = NULL;
        $sql = "select * from quiz_question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objQuiz_question = new Quiz_question_object();
            $objQuiz_question->id = $row['id'];
            $objQuiz_question->quiz_id = $row['quiz_id'];
            $objQuiz_question->question_id = $row['question_id'];
            $objQuiz_question->question_order = $row['question_order'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuiz_question;
    }

    public function get_all_Quiz_questions() {
        $records = array();

        $sql = "select * from quiz_question";
        //$sql = "select * from quiz_question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuiz_question = new Quiz_question_object();
            $objQuiz_question->id = $row['id'];
            $objQuiz_question->quiz_id = $row['quiz_id'];
            $objQuiz_question->question_id = $row['question_id'];
            $objQuiz_question->question_order = $row['question_order'];

            $records[] = $objQuiz_question;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Quiz_question($objQuiz_question) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from quiz_question";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objQuiz_question->id = $row['mvalue'];
        else
            $objQuiz_question->id = 0;
      
       $sql = "select max(question_order) as mvalue from quiz_question where quiz_id=" . $objQuiz_question->quiz_id;
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objQuiz_question->question_order = $row['mvalue'] + 1 ;
        else
            $objQuiz_question->question_order = 0;
      
      
        $objQuiz_question->id = $objQuiz_question->id + 1;
        $sql = "insert into quiz_question(id,quiz_id,question_id, question_order ) values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objQuiz_question->id,
            $objQuiz_question->quiz_id,
            $objQuiz_question->question_id,
            $objQuiz_question->question_order
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objQuiz_question;
        return FALSE;
    }

    public function update_Quiz_question($objQuiz_question) {
        $sql = "update quiz_question set quiz_id = ?, question_id = ?, question_order = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objQuiz_question->quiz_id,
            $objQuiz_question->question_id,
            $objQuiz_question->question_order
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objQuiz_question;
        return FALSE;
    }

    public function delete_quiz_question($id) {
        $sql = "delete from quiz_question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
        return true;
    }

    public function get_Quiz_question_count() {
        $count = 0;
        $sql = "select count(id) as cnt from quiz_question";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Quiz_question($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from quiz_question order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from quiz_question where $filterString order by $sortBy $sortType limit $offset, $limit";

        $sql = "select * from quiz_question limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuiz_question = new Quiz_question_object();
            $objQuiz_question->id = $row['id'];
            $objQuiz_question->quiz_id = $row['quiz_id'];
            $objQuiz_question->question_id = $row['question_id'];
            $objQuiz_question->question_order = $row['question_order'];
            $records[] = $objQuiz_question;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_all_questions_for_quiz($quiz_id) {
        $records = array();

        $sql = "select qz.id as quiz_id, qz.name as quiz_name, qz.description as quiz_desc, q.id as question_id,
                q.question_img_url, q.duration, q.has_multiple_answer, q.option_count, q.correct_option, qq.question_order, q.chapter_name, q.topic,
                q.class_id, q.subject_id
                from question q
                join quiz_question qq on q.id=qq.question_id
                join quiz qz on qz.id = qq.quiz_id
                where qq.quiz_id= ?
                order by qq.question_order";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objQuestion = new stdClass();
            $objQuestion->quiz_id = $row['quiz_id'];
            $objQuestion->quiz_name = $row['quiz_name'];
            $objQuestion->quiz_desc = $row['quiz_desc'];
            $objQuestion->question_id = $row['question_id'];
            $objQuestion->question_img_url = $row['question_img_url'];
            $objQuestion->has_multiple_answer = $row['has_multiple_answer'];
            $objQuestion->duration = $row['duration'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->class_id = $row['class_id'];
            $objQuestion->subject_id = $row['subject_id'];
            $objQuestion->chapter_name = $row['chapter_name'];
            $objQuestion->question_order = $row['question_order'];
            $objQuestion->topic = $row['topic'];
            $objQuestion->correct_option = $row['correct_option'];
            $records[] = $objQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

