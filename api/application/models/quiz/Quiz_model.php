<?php

class Quiz_model extends CI_Model
{

    public function get_Quiz($id)
    {
        $objQuiz = NULL;
        $sql = "select * from quiz where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objQuiz = new Quiz_object();
            $objQuiz->id = $row['id'];
            $objQuiz->name = $row['name'];
            $objQuiz->description = $row['description'];
            $objQuiz->subject_id = $row['subject_id'];
            $objQuiz->start_date = $row['start_date'];
            $objQuiz->quiz_detail_image = $row['quiz_detail_image'];
            $objQuiz->is_live = $row['is_live'];
            $objQuiz->marking = $row['marking'];
            $objQuiz->quiz_type = isset($row['quiz_type']) ? $row['quiz_type'] : 'private';
            $objQuiz->user_id = isset($row['user_id']) ? $row['user_id'] : 0;
            $objQuiz->quiz_reference = isset($row['quiz_reference']) ? $row['quiz_reference'] : '';
            $objQuiz->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
            $objQuiz->level = isset($row['level']) ? $row['level'] : 'Elementary';
            $objQuiz->quiz_question_type = isset($row['quiz_question_type']) ? $row['quiz_question_type'] : 'regular';
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuiz;
    }

    public function get_Quiz_by_reference($quiz_reference)
    {
        $objQuiz = NULL;
        $sql = "select * from quiz where quiz_reference = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $result = $statement->execute(array($quiz_reference));
        
        if ($row = $statement->fetch()) {
            $objQuiz = new Quiz_object();
            $objQuiz->id = $row['id'];
            $objQuiz->name = $row['name'];
            $objQuiz->description = $row['description'];
            $objQuiz->subject_id = $row['subject_id'];
            $objQuiz->start_date = $row['start_date'];
            $objQuiz->quiz_detail_image = $row['quiz_detail_image'];
            $objQuiz->is_live = $row['is_live'];
            $objQuiz->marking = $row['marking'];
            $objQuiz->quiz_type = isset($row['quiz_type']) ? $row['quiz_type'] : 'private';
            $objQuiz->user_id = isset($row['user_id']) ? $row['user_id'] : 0;
            $objQuiz->quiz_reference = isset($row['quiz_reference']) ? $row['quiz_reference'] : '';
            $objQuiz->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
            $objQuiz->level = isset($row['level']) ? $row['level'] : 'Elementary';
            $objQuiz->quiz_question_type = isset($row['quiz_question_type']) ? $row['quiz_question_type'] : 'regular';
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuiz;
    }

    public function get_all_Quizs()
    {
        $records = array();

        $sql = "select * from quiz";
        // $sql = "select * from quiz where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuiz = new Quiz_object();
            $objQuiz->id = $row['id'];
            $objQuiz->name = $row['name'];
            $objQuiz->description = $row['description'];
            $objQuiz->subject_id = $row['subject_id'];
            $objQuiz->start_date = $row['start_date'];
            $objQuiz->quiz_detail_image = $row['quiz_detail_image'];
            $objQuiz->is_live = $row['is_live'];
            $objQuiz->marking = $row['marking'];
            $objQuiz->quiz_type = isset($row['quiz_type']) ? $row['quiz_type'] : 'private';
            $objQuiz->user_id = isset($row['user_id']) ? $row['user_id'] : 0;
            $objQuiz->quiz_reference = isset($row['quiz_reference']) ? $row['quiz_reference'] : '';
            $objQuiz->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
            $objQuiz->level = isset($row['level']) ? $row['level'] : 'Elementary';
            $objQuiz->quiz_question_type = isset($row['quiz_question_type']) ? $row['quiz_question_type'] : 'regular';
            $records[] = $objQuiz;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Quiz($objQuiz)
    {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from quiz";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objQuiz->id = $row['mvalue'];
        else
            $objQuiz->id = 0;
        $objQuiz->id = $objQuiz->id + 1;
        $sql = "insert into quiz (`id`, `name`, `description`, `subject_id`, `start_date`, `quiz_detail_image`, `is_live`, `marking`, `quiz_type`, `user_id`, `quiz_reference`, `exam_id`, `level`, `quiz_question_type`) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objQuiz->id,
            $objQuiz->name,
            $objQuiz->description,
            $objQuiz->subject_id,
            null,
            $objQuiz->quiz_detail_image,
            $objQuiz->is_live,
            $objQuiz->marking,
            $objQuiz->quiz_type,
            $objQuiz->user_id,
            $objQuiz->quiz_reference,
            $objQuiz->exam_id,
            isset($objQuiz->level) ? $objQuiz->level : 'Elementary',
            isset($objQuiz->quiz_question_type) ? $objQuiz->quiz_question_type : 'regular'
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objQuiz;
        return FALSE;
    }

    public function update_Quiz($objQuiz)
    {
        $sql = "update quiz set name = ?, description = ?, subject_id = ?, quiz_detail_image = ?, is_live = ?, marking = ?, quiz_type = ?, user_id = ?, quiz_reference = ?, exam_id = ?, level = ?, quiz_question_type = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objQuiz->name,
            $objQuiz->description,
            $objQuiz->subject_id,
            $objQuiz->quiz_detail_image,
            $objQuiz->is_live,
            $objQuiz->marking,
            $objQuiz->quiz_type,
            $objQuiz->user_id,
            $objQuiz->quiz_reference,
            $objQuiz->exam_id,
            isset($objQuiz->level) ? $objQuiz->level : 'Elementary',
            isset($objQuiz->quiz_question_type) ? $objQuiz->quiz_question_type : 'regular',
            $objQuiz->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objQuiz;
        return FALSE;
    }

    public function delete_Quiz($id)
    {
        $sql = "delete from quiz where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_Quiz_count()
    {
        $count = 0;
        $sql = "select count(id) as cnt from quiz";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Quiz($offset, $limit, $sortBy, $sortType, $filterString = NULL)
    {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select quiz.*, 
                    COALESCE(qq_stats.total_questions, 0) as total_questions,
                    COALESCE(uq_stats.correct_answers, 0) as correct_answers,
                    COALESCE(uq_stats.incorrect_answers, 0) as incorrect_answers,
                    COALESCE(uq_stats.total_score, 0) as total_score
                    from quiz 
                    LEFT JOIN (
                        SELECT quiz_id, COUNT(id) as total_questions
                        FROM quiz_question
                        GROUP BY quiz_id
                    ) qq_stats ON quiz.id = qq_stats.quiz_id
                    LEFT JOIN (
                        SELECT quiz_id, user_id,
                            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                            SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect_answers,
                            SUM(score) as total_score
                        FROM user_question
                        GROUP BY quiz_id, user_id
                    ) uq_stats ON quiz.id = uq_stats.quiz_id AND quiz.user_id = uq_stats.user_id
                    order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select quiz.*, 
                    COALESCE(qq_stats.total_questions, 0) as total_questions,
                    COALESCE(uq_stats.correct_answers, 0) as correct_answers,
                    COALESCE(uq_stats.incorrect_answers, 0) as incorrect_answers,
                    COALESCE(uq_stats.total_score, 0) as total_score
                    from quiz 
                    LEFT JOIN (
                        SELECT quiz_id, COUNT(id) as total_questions
                        FROM quiz_question
                        GROUP BY quiz_id
                    ) qq_stats ON quiz.id = qq_stats.quiz_id
                    LEFT JOIN (
                        SELECT quiz_id, user_id,
                            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                            SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as incorrect_answers,
                            SUM(score) as total_score
                        FROM user_question
                        GROUP BY quiz_id, user_id
                    ) uq_stats ON quiz.id = uq_stats.quiz_id AND quiz.user_id = uq_stats.user_id
                    where $filterString 
                    order by $sortBy $sortType limit $offset, $limit";

        //$sql = "select * from quiz limit $offset, $limit";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuiz = new Quiz_object();
            $objQuiz->id = $row['id'];
            $objQuiz->name = $row['name'];
            $objQuiz->description = $row['description'];
            $objQuiz->subject_id = $row['subject_id'];
            $objQuiz->start_date = $row['start_date'];
            $objQuiz->quiz_detail_image = $row['quiz_detail_image'];
            $objQuiz->is_live = $row['is_live'];
            $objQuiz->marking = $row['marking'];
            $objQuiz->quiz_type = isset($row['quiz_type']) ? $row['quiz_type'] : 'private';
            $objQuiz->user_id = isset($row['user_id']) ? $row['user_id'] : 0;
            $objQuiz->quiz_reference = isset($row['quiz_reference']) ? $row['quiz_reference'] : '';
            $objQuiz->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
            $objQuiz->level = isset($row['level']) ? $row['level'] : 'Elementary';
            $objQuiz->quiz_question_type = isset($row['quiz_question_type']) ? $row['quiz_question_type'] : 'regular';
            $objQuiz->total_questions = isset($row['total_questions']) ? (int)$row['total_questions'] : 0;
            $objQuiz->correct_answers = isset($row['correct_answers']) ? (int)$row['correct_answers'] : 0;
            $objQuiz->incorrect_answers = isset($row['incorrect_answers']) ? (int)$row['incorrect_answers'] : 0;
            $objQuiz->total_score = isset($row['total_score']) ? (int)$row['total_score'] : 0;
            
            $records[] = $objQuiz;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

   public function get_all_scholars_for_quiz($quiz_id)
    {
        $records = array();

        $sql = "select qs.id, qs.quiz_id as quiz_id,s.id as scholar_id, s.scholar_no as scholar_no, s.name as name, s.gender as gender,
                s.alert_mobile_no as mobile_no, g.class_name as class_name, sc.section as section
                from scholar s
                join quiz_scholar qs on s.id=qs.scholar_id 
                join genere g on g.id = s.class_id 
                join section sc on sc.id = s.section_id 
                where qs.quiz_id= ?
                order by qs.scholar_order";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objScholar = new stdClass();
            $objScholar->id = $row['id'];
            $objScholar->quiz_id = $row['quiz_id'];
            $objScholar->scholar_id = $row['scholar_id'];
            $objScholar->scholar_no = $row['scholar_no'];
            $objScholar->name = $row['name'];
            $objScholar->gender = $row['gender'];
            $objScholar->mobile_no = $row['mobile_no'];
            $objScholar->class_name = $row['class_name'];
            $objScholar->section = $row['section'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    public function get_all_questions_for_quiz($quiz_id)
    {
        $records = array();

        $sql = "select qq.id, qz.id as quiz_id, qz.name as quiz_name, qz.description as quiz_desc, q.id as question_id,
                q.question_img_url, q.duration, q.has_multiple_answer, q.option_count, q.correct_option, qq.question_order, 
                q.chapter_id, c.chapter_name, q.topic_id, t.topic_name, q.exam_id, e.exam_name,
                q.subject_id, s.subject as subject_name, q.level, q.question_type, q.year
                from question q
                join quiz_question qq on q.id=qq.question_id
                join quiz qz on qz.id = qq.quiz_id
                left join subject s on q.subject_id = s.id
                left join chapter c on q.chapter_id = c.id
                left join topic t on q.topic_id = t.id
                left join exam e on q.exam_id = e.id
                where qq.quiz_id= ? AND (q.invalid_question IS NULL OR q.invalid_question = 0)
                order by qq.question_order";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objQuestion = new stdClass();
            $objQuestion->id = $row['id'];
            $objQuestion->quiz_id = $row['quiz_id'];
            $objQuestion->quiz_name = $row['quiz_name'];
            $objQuestion->quiz_desc = $row['quiz_desc'];
            $objQuestion->question_id = $row['question_id'];
            $objQuestion->question_img_url = $row['question_img_url'];
            $objQuestion->has_multiple_answer = $row['has_multiple_answer'];
            $objQuestion->duration = $row['duration'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->subject_id = $row['subject_id'];
            $objQuestion->subject_name = isset($row['subject_name']) ? $row['subject_name'] : null;
            $objQuestion->chapter_id = isset($row['chapter_id']) ? $row['chapter_id'] : null;
            $objQuestion->chapter_name = isset($row['chapter_name']) ? $row['chapter_name'] : null;
            $objQuestion->topic_id = isset($row['topic_id']) ? $row['topic_id'] : null;
            $objQuestion->topic_name = isset($row['topic_name']) ? $row['topic_name'] : null;
            $objQuestion->exam_id = isset($row['exam_id']) ? $row['exam_id'] : null;
            $objQuestion->exam_name = isset($row['exam_name']) ? $row['exam_name'] : null;
            $objQuestion->level = isset($row['level']) ? $row['level'] : 'Elementary';
            $objQuestion->question_type = isset($row['question_type']) ? $row['question_type'] : 'regular';
            $objQuestion->year = isset($row['year']) ? $row['year'] : null;
            $objQuestion->question_order = $row['question_order'];
            $objQuestion->topic = $row['topic_id'];
            $objQuestion->correct_option = $row['correct_option'];
            $records[] = $objQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_question_leaderboard($question_id, $quiz_id)
    {
        $records = array();

        $sql = "select uq.option_answer, q.option_count, q.correct_option
                from user_question uq join question q on q.id=uq.question_id
                where q.id=? and uq.quiz_id = ?";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($question_id, $quiz_id));
        while ($row = $statement->fetch()) {
            $objQuestion = new stdClass();
            $objQuestion->option_answer = $row['option_answer'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->correct_option = $row['correct_option'];
            $records[] = $objQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

  
    public function get_quiz_for_device($device_id)
    {
        $sql = "select q.id as quiz_id, q.current_question_id as question_id, q.current_question_time as question_time, 
        u.id as user_id from quiz q 
        inner join quiz_scholar qs on q.id = qs.quiz_id
        inner JOIN scholar s on s.id = qs.scholar_id
        inner join user u on u.reference_id = s.id
        WHERE s.device_no = ?
        and q.current_question_id <> 0
        and q.is_live = 1";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($device_id));
        $objQuiz = NULL;
        if ($row = $statement->fetch()) {
          $objQuiz = new stdClass();
            $objQuiz->quiz_id = $row['quiz_id'];
            $objQuiz->question_id = $row['question_id'];
            $objQuiz->question_time = $row['question_time'];
            $objQuiz->user_id = $row['user_id']; 
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuiz;
    }
  
    public function get_user_leaderboard($quiz_id)
    {
        $records = array();

        // $sql = "select u.display_name, uq.score
        //         from user_question uq join user u on u.id=uq.user_id
        //         join quiz_question qq on qq.question_id=uq.question_id
        //         where qq.quiz_id=? and uq.score > 0
        // 		order by uq.score desc
        // 		limit 10";
        $sql = "select u.display_name,s.father as school, s.mother as city, sum(uq.score) as score
                from user_question uq join user u on u.id=uq.user_id
                join scholar s on u.reference_id = s.id
                join quiz_question qq on qq.question_id=uq.question_id and qq.quiz_id = ?
                where uq.quiz_id=?
                group by u.display_name, s.father, s.mother
                order by sum(uq.score) desc, sum(uq.duration) desc
                limit 30";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id, $quiz_id));
        while ($row = $statement->fetch()) {
            $objQuestion = new stdClass();
            $objQuestion->username = $row['display_name'];
            $objQuestion->score = $row['score'];
           $objQuestion->school = $row['school'];
            $objQuestion->city = $row['city'];
            $records[] = $objQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function update_start_date($quiz_id, $start_date)
    {
        //$start_date = DateTime::createFromFormat('Y-m-d', $start_date);
        $start_date = date('Y-m-d h:i:s', strtotime($start_date));

        $sql = "update quiz set start_date = ?, is_live = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $start_date,
            1,
            $quiz_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }

    public function update_quiz_current_question($quiz_id, $question_id)
    {
        $dateTime = new DateTime();
        $current_question_time = $dateTime;

        $sql = "update quiz set current_question_id = ?, current_question_time=? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $question_id,
            $current_question_time->format('Y-m-d H:i:s.v'),
            $quiz_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }
  
    public function update_quiz_stop($quiz_id)
    {
        $sql = "update quiz set is_live = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            0,
            $quiz_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }

    public function update_live_status($quiz_id, $is_live = 0)
    {

        $sql = "update quiz set is_live = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $is_live,
            $quiz_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $updated;
        return FALSE;
    }

      public function add_user_answer_device($user_id, $quiz_id, $question_id, $duration, $option_answer)
    {
        $pdo = CDatabase::getPdo();

        $sqlDelete = "delete from user_question where user_id = ? and quiz_id=? and question_id=?";
        $statementDelete = $pdo->prepare($sqlDelete);
        $statementDelete->execute(array($user_id, $quiz_id, $question_id));
        $statementDelete = NULL;
        
        $sql = "insert into user_question (id, user_id,quiz_id, question_id, duration, option_answer,score) values (DEFAULT,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $user_id,
            $quiz_id,
            $question_id,
            $duration,
            $option_answer,
            0
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return TRUE;
        return FALSE;
    }
  
    public function add_user_answer($user_id, $quiz_id, $question_id, $duration, $option_answer)
    {
        $pdo = CDatabase::getPdo();

        $sqlDelete = "delete from user_question where user_id = ? and quiz_id=? and question_id=?";
        $statementDelete = $pdo->prepare($sqlDelete);
        $statementDelete->execute(array($user_id, $quiz_id, $question_id));
        $statementDelete = NULL;

        $sql = "insert into user_question (id, user_id,quiz_id, question_id, duration, option_answer,score) values (DEFAULT,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $user_id,
            $quiz_id,
            $question_id,
            $duration,
            $option_answer,
            0
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return TRUE;
        return FALSE;
    }

    public function update_question_score($question_id, $quiz_id)
    {

        $quiz = $this->get_Quiz($quiz_id);
        $marking = "Regular";
        if($quiz){
            $marking = $quiz->marking;
        }

        $sql = "SELECT user_question.id as id FROM user_question join question on user_question.question_id = question.id where user_question.option_answer = question.correct_option and user_question.question_id = ? and user_question.quiz_id = ? order by user_question.duration asc";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($question_id, $quiz_id));
        $score = 1000;
        if($marking == "Negative") {
            $score = 4;
        }
        while ($row = $statement->fetch()) {
            $id = $row['id'];
            $scoreSql = "update user_question set score = ? where id=? ";
            $scoreStatement = $pdo->prepare($scoreSql);
            $scoreStatement->execute(array($score, $id));
            if($marking == "Regular")
                $score = $score - 10;
            else if($marking == "Negative") {
                $score = 4;
            }
        }
        $statement = NULL;
        if($marking == "Negative") {
            $score = -1;
            $sql = "SELECT user_question.id as id FROM user_question join question on user_question.question_id = question.id where user_question.option_answer != question.correct_option and user_question.question_id = ? and user_question.quiz_id = ? order by user_question.duration asc";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($question_id, $quiz_id));
            while ($row = $statement->fetch()) {
                $id = $row['id'];
                $scoreSql = "update user_question set score = ? where id=? ";
                $scoreStatement = $pdo->prepare($scoreSql);
                $scoreStatement->execute(array($score, $id));
            }
            $statement = NULL;
        }

        $pdo = NULL;
    }
  
  /** Functions for Quiz Report */
    public function get_quiz_score_report($quiz_id)
    {
        $records = array();

        $sql = "SELECT user.id as uid, display_name, sum(score) as score FROM quiz 
        join user_question on user_question.quiz_id =quiz.id join user 
        on user_question.user_id = user.id where quiz.id = ? 
        GROUP BY uid, display_name 
        order by score DESC";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objScholar = new stdClass();
            $objScholar->uid = $row['uid'];
            $objScholar->display_name = $row['display_name'];
            $objScholar->score = $row['score'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_quiz_details_report($quiz_id)
    {
        $records = array();

        $sql = "SELECT q.name, q.description, class_name, subject, start_date, count(quiz_scholar.scholar_id) as scholars FROM quiz as q
        join genere on genere.id = q.class_id
        join subject on subject.id = q.subject_id
        JOIN quiz_scholar on quiz_scholar.quiz_id = q.id 
        where q.id = ?
        GROUP by  q.name, q.description, class_name, subject, start_date";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objScholar = new stdClass();
            $objScholar->name = $row['name'];
            $objScholar->description = $row['description'];
            $objScholar->class_name = $row['class_name'];
            $objScholar->subject = $row['subject'];
            $objScholar->start_date = $row['start_date'];
            $objScholar->scholars = $row['scholars'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_quiz_total_questions_report($quiz_id)
    {
        $records = array();

        $sql = "SELECT q.id, count(q.id) as total_questions FROM quiz as q 
        join quiz_question on quiz_question.quiz_id = q.id 
        where q.id = ? 
        group by q.id";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        while ($row = $statement->fetch()) {
            $objScholar = new stdClass();
            $objScholar->id = $row['id'];
            $objScholar->total_questions = $row['total_questions'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    public function get_quiz_student_report($quiz_id, $user_id)
    {
        $records = array();

        $sql = "Select * From user_question
        where quiz_id = ? 
        and user_id = ?
        order by id asc";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id, $user_id));
        while ($row = $statement->fetch()) {
            $objScholar = new stdClass();
            $objScholar->id = $row['id'];
            $objScholar->user_id = $row['user_id'];
            $objScholar->quiz_id = $row['quiz_id'];
            $objScholar->question_id = $row['question_id'];
            $objScholar->duration = $row['duration'];
            $objScholar->option_answer = $row['option_answer'];
            $objScholar->score = $row['score'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get count of custom quizzes for a specific user
     * @param int $user_id User ID
     * @return int Count of custom quizzes
     */
    public function get_custom_quiz_count_by_user($user_id)
    {
        $count = 0;
        $sql = "SELECT COUNT(id) as cnt FROM quiz WHERE user_id = ? AND user_id IS NOT NULL";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        if ($row = $statement->fetch()) {
            $count = $row['cnt'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    /**
     * Get unattempted quizzes for a specific user
     * Returns quizzes created by the user that have start_date = NULL and no entries in user_question table
     * @param int $user_id User ID
     * @return array Array of quiz objects
     */
    public function get_unattempted_quizzes_by_user($user_id)
    {
        $records = array();
        
        // Query to get quizzes where:
        // 1. Quiz is created by the user (user_id matches)
        // 2. Quiz has start_date = NULL (never started) OR User has no entries in user_question table for this quiz (never attempted)
        // 3. Include comma-separated subject names
        $sql = "SELECT DISTINCT q.*, 
                       GROUP_CONCAT(s.subject ORDER BY s.subject SEPARATOR ', ') as subject_names
                FROM quiz q 
                LEFT JOIN quiz_subjects qs ON q.id = qs.quiz_id
                LEFT JOIN subject s ON qs.subject_id = s.id
                WHERE q.user_id = ? 
                AND (
                    q.start_date IS NULL 
                    OR NOT EXISTS (
                        SELECT 1 FROM user_question uq 
                        WHERE uq.quiz_id = q.id AND uq.user_id = ?
                    )
                )
                GROUP BY q.id, q.name, q.description, q.subject_id, q.start_date, 
                         q.quiz_detail_image, q.is_live, q.marking, q.quiz_type, 
                         q.user_id, q.quiz_reference, q.exam_id, q.level
                ORDER BY q.id DESC";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $user_id));
        
        while ($row = $statement->fetch()) {
            $objQuiz = new Quiz_object();
            $objQuiz->id = $row['id'];
            $objQuiz->name = $row['name'];
            $objQuiz->description = $row['description'];
            $objQuiz->subject_id = $row['subject_id'];
            $objQuiz->start_date = $row['start_date'];
            $objQuiz->quiz_detail_image = $row['quiz_detail_image'];
            $objQuiz->is_live = $row['is_live'];
            $objQuiz->marking = $row['marking'];
            $objQuiz->quiz_type = isset($row['quiz_type']) ? $row['quiz_type'] : 'private';
            $objQuiz->user_id = isset($row['user_id']) ? $row['user_id'] : 0;
            $objQuiz->quiz_reference = isset($row['quiz_reference']) ? $row['quiz_reference'] : '';
            $objQuiz->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
            $objQuiz->level = isset($row['level']) ? $row['level'] : 'Elementary';
            $objQuiz->subject_names = isset($row['subject_names']) ? $row['subject_names'] : '';
            $records[] = $objQuiz;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get the total number of questions in a quiz
     * @param int $quiz_id The quiz ID
     * @return int Number of questions in the quiz
     */
    public function get_quiz_question_count($quiz_id)
    {
        $sql = "SELECT COUNT(*) as question_count 
                FROM quiz_question qq 
                JOIN question q ON qq.question_id = q.id 
                WHERE qq.quiz_id = ? 
                AND (q.invalid_question IS NULL OR q.invalid_question = 0)";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return $result ? (int)$result['question_count'] : 0;
    }

    // ========================================================================
    // Quiz Status Management Methods
    // Added: 2025-12-18 - Quiz completion tracking feature
    // ========================================================================

    /**
     * Valid quiz status values
     */
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABANDONED = 'abandoned';
    const STATUS_TIMEOUT = 'timeout';

    /**
     * Update quiz status for a specific quiz record
     * 
     * @param int $quiz_id Quiz ID
     * @param string $status New status (not_started|in_progress|completed|abandoned|timeout)
     * @param string|null $completed_at Completion timestamp (YYYY-MM-DD HH:MM:SS) or null
     * @return bool Success/failure
     */
    public function update_quiz_status($quiz_id, $status, $completed_at = null) {
        try {
            // Validate quiz_id
            if (empty($quiz_id) || !is_numeric($quiz_id)) {
                log_message('error', 'Quiz_model::update_quiz_status - Invalid quiz_id: ' . $quiz_id);
                return false;
            }

            // Validate status
            $valid_statuses = [
                self::STATUS_NOT_STARTED,
                self::STATUS_IN_PROGRESS,
                self::STATUS_COMPLETED,
                self::STATUS_ABANDONED,
                self::STATUS_TIMEOUT
            ];
            if (!in_array($status, $valid_statuses)) {
                log_message('error', 'Quiz_model::update_quiz_status - Invalid status: ' . $status);
                return false;
            }

            // Build query
            $sql = "UPDATE quiz SET quiz_status = ?";
            $params = [$status];

            // Add completed_at if status is completed and timestamp provided
            if ($status === self::STATUS_COMPLETED && $completed_at !== null) {
                $sql .= ", completed_at = ?";
                $params[] = $completed_at;
            } elseif ($status === self::STATUS_COMPLETED && $completed_at === null) {
                // Auto-set completed_at to current time if not provided
                $sql .= ", completed_at = NOW()";
            }

            $sql .= " WHERE id = ?";
            $params[] = $quiz_id;

            // Execute query using PDO pattern
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $success = $statement->execute($params);
            $affected_rows = $statement->rowCount();
            $statement = null;

            if ($success && $affected_rows > 0) {
                log_message('info', "Quiz_model::update_quiz_status - Updated quiz {$quiz_id} to status {$status}");
                return true;
            } else {
                log_message('warning', "Quiz_model::update_quiz_status - No rows affected for quiz {$quiz_id}");
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Quiz_model::update_quiz_status - Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get quiz status and completion information
     * 
     * @param int $quiz_id Quiz ID
     * @return object|null Object with quiz_status and completed_at, or null if not found
     */
    public function get_quiz_status($quiz_id) {
        try {
            // Validate quiz_id
            if (empty($quiz_id) || !is_numeric($quiz_id)) {
                log_message('error', 'Quiz_model::get_quiz_status - Invalid quiz_id: ' . $quiz_id);
                return null;
            }

            $sql = "SELECT quiz_status, completed_at FROM quiz WHERE id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$quiz_id]);
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;

            return $result ?: null;

        } catch (Exception $e) {
            log_message('error', 'Quiz_model::get_quiz_status - Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a quiz is completed
     * 
     * @param int $quiz_id Quiz ID
     * @return bool True if completed, false otherwise
     */
    public function is_quiz_completed($quiz_id) {
        try {
            // Validate quiz_id
            if (empty($quiz_id) || !is_numeric($quiz_id)) {
                log_message('error', 'Quiz_model::is_quiz_completed - Invalid quiz_id: ' . $quiz_id);
                return false;
            }

            $sql = "SELECT quiz_status FROM quiz WHERE id = ? AND quiz_status = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$quiz_id, self::STATUS_COMPLETED]);
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;

            return $result !== false;

        } catch (Exception $e) {
            log_message('error', 'Quiz_model::is_quiz_completed - Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's quiz activity data for notification context
     * Includes completed quiz count, last quiz date, first quiz date
     * 
     * @param int $user_id User ID
     * @param int $days_back How many days back to look (default 30)
     * @return object|null Activity data or null
     */
    public function get_user_quiz_activity($user_id, $days_back = 30) {
        try {
            // Validate user_id
            if (empty($user_id) || !is_numeric($user_id)) {
                log_message('error', 'Quiz_model::get_user_quiz_activity - Invalid user_id: ' . $user_id);
                return null;
            }

            $sql = "SELECT 
                        COUNT(*) as total_completed,
                        MAX(completed_at) as last_quiz_date,
                        MIN(completed_at) as first_quiz_date,
                        COUNT(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as quizzes_last_7_days,
                        COUNT(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as quizzes_last_30_days
                    FROM quiz 
                    WHERE user_id = ? 
                    AND quiz_status = ?
                    AND completed_at IS NOT NULL
                    AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$user_id, self::STATUS_COMPLETED, $days_back]);
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;

            return $result ?: null;

        } catch (Exception $e) {
            log_message('error', 'Quiz_model::get_user_quiz_activity - Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get quiz distribution by type for a user
     * 
     * @param int $user_id User ID
     * @return object|null Distribution data with custom/pyq counts
     */
    public function get_quiz_distribution($user_id) {
        try {
            // Validate user_id
            if (empty($user_id) || !is_numeric($user_id)) {
                log_message('error', 'Quiz_model::get_quiz_distribution - Invalid user_id: ' . $user_id);
                return null;
            }

            $sql = "SELECT 
                        COUNT(*) as total_completed,
                        COUNT(CASE WHEN quiz_type = 'custom' THEN 1 END) as custom_quizzes,
                        COUNT(CASE WHEN quiz_question_type = 'pyq' THEN 1 END) as pyq_quizzes,
                        COUNT(CASE WHEN quiz_question_type = 'regular' THEN 1 END) as regular_quizzes
                    FROM quiz 
                    WHERE user_id = ? AND quiz_status = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$user_id, self::STATUS_COMPLETED]);
            $result = $statement->fetch(PDO::FETCH_OBJ);
            $statement = null;

            return $result ?: null;

        } catch (Exception $e) {
            log_message('error', 'Quiz_model::get_quiz_distribution - Error: ' . $e->getMessage());
            return null;
        }
    }
}
