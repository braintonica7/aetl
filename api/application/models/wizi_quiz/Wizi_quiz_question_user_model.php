<?php

class Wizi_quiz_question_user_model extends CI_Model
{

    public function get_wizi_quiz_question_user($id)
    {
        $obj = NULL;
        $sql = "SELECT * FROM wizi_quiz_question_user WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $obj;
    }

    public function get_questions_by_attempt($wizi_quiz_user_id)
    {
        $records = array();
        $sql = "SELECT wqqu.*, wq.question_img_url, wq.question_text, wq.option_count, wq.duration,
                       wq.option_a, wq.option_b, wq.option_c, wq.option_d, wq.solution, 
                       wq.subject_id, wq.chapter_name, wq.topic_id, wq.level, wq.difficulty, wq.question_type as wq_question_type
                FROM wizi_quiz_question_user wqqu
                JOIN wizi_question wq ON wqqu.wizi_question_id = wq.id
                WHERE wqqu.wizi_quiz_user_id = ?
                ORDER BY wqqu.question_order ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_user_id));
        while ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
            // Add question details
            $obj->question_img_url = isset($row['question_img_url']) ? $row['question_img_url'] : '';
            $obj->question_text = isset($row['question_text']) ? $row['question_text'] : '';
            $obj->option_count = isset($row['option_count']) ? $row['option_count'] : 4;
            $obj->duration = isset($row['duration']) ? $row['duration'] : 0;
            $obj->option_a = isset($row['option_a']) ? $row['option_a'] : '';
            $obj->option_b = isset($row['option_b']) ? $row['option_b'] : '';
            $obj->option_c = isset($row['option_c']) ? $row['option_c'] : '';
            $obj->option_d = isset($row['option_d']) ? $row['option_d'] : '';
            $obj->solution = isset($row['solution']) ? $row['solution'] : '';
            $obj->subject_id = isset($row['subject_id']) ? $row['subject_id'] : 0;
            $obj->chapter_name = isset($row['chapter_name']) ? $row['chapter_name'] : '';
            $obj->topic_id = isset($row['topic_id']) ? $row['topic_id'] : 0;
            $obj->level = isset($row['level']) ? $row['level'] : '';
            $obj->difficulty = isset($row['difficulty']) ? $row['difficulty'] : '';
            $records[] = $obj;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_question_by_order($wizi_quiz_user_id, $question_order)
    {
        $obj = NULL;
        $sql = "SELECT wqqu.*, wq.question_img_url, wq.question_text, wq.option_count, wq.duration,
                       wq.option_a, wq.option_b, wq.option_c, wq.option_d
                FROM wizi_quiz_question_user wqqu
                JOIN wizi_question wq ON wqqu.wizi_question_id = wq.id
                WHERE wqqu.wizi_quiz_user_id = ? AND wqqu.question_order = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_user_id, $question_order));
        if ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
            $obj->question_img_url = isset($row['question_img_url']) ? $row['question_img_url'] : '';
            $obj->question_text = isset($row['question_text']) ? $row['question_text'] : '';
            $obj->option_count = isset($row['option_count']) ? $row['option_count'] : 4;
            $obj->duration = isset($row['duration']) ? $row['duration'] : 0;
            $obj->option_a = isset($row['option_a']) ? $row['option_a'] : '';
            $obj->option_b = isset($row['option_b']) ? $row['option_b'] : '';
            $obj->option_c = isset($row['option_c']) ? $row['option_c'] : '';
            $obj->option_d = isset($row['option_d']) ? $row['option_d'] : '';
        }
        $statement = NULL;
        $pdo = NULL;
        return $obj;
    }

    public function add_wizi_quiz_question_user($obj)
    {
        $pdo = CDatabase::getPdo();

        $sql = "SELECT MAX(id) as mvalue FROM wizi_quiz_question_user";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $obj->id = $row['mvalue'];
        else
            $obj->id = 0;
        $obj->id = $obj->id + 1;
        
        $sql = "INSERT INTO wizi_quiz_question_user 
                (`id`, `wizi_quiz_user_id`, `wizi_quiz_question_id`, `wizi_question_id`, 
                 `question_order`, `marks`, `negative_marks`, `user_answer`, `correct_answer`, 
                 `is_correct`, `status`, `time_spent`, `marks_obtained`, `answered_at`) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $obj->id,
            $obj->wizi_quiz_user_id,
            $obj->wizi_quiz_question_id,
            $obj->wizi_question_id,
            $obj->question_order,
            $obj->marks,
            $obj->negative_marks,
            $obj->user_answer,
            $obj->correct_answer,
            $obj->is_correct,
            $obj->status,
            $obj->time_spent,
            $obj->marks_obtained,
            $obj->answered_at
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $obj;
        return FALSE;
    }

    public function update_wizi_quiz_question_user($obj)
    {
        $sql = "UPDATE wizi_quiz_question_user SET 
                user_answer = ?, is_correct = ?, status = ?, time_spent = ?, 
                marks_obtained = ?, answered_at = ?
                WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $obj->user_answer,
            $obj->is_correct,
            $obj->status,
            $obj->time_spent,
            $obj->marks_obtained,
            $obj->answered_at,
            $obj->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $obj;
        return FALSE;
    }

    public function copy_questions_to_user_attempt($wizi_quiz_user_id, $wizi_quiz_id)
    {
        $sql = "INSERT INTO wizi_quiz_question_user 
                (wizi_quiz_user_id, wizi_quiz_question_id, wizi_question_id, question_order, 
                 marks, negative_marks, question_type, correct_answer, status)
                SELECT ?, wqq.id, wqq.wizi_question_id, wqq.question_order, 
                       wqq.marks, wqq.negative_marks, wqq.question_type, wq.correct_option, 'not_attempted'
                FROM wizi_quiz_question wqq
                JOIN wizi_question wq ON wqq.wizi_question_id = wq.id
                WHERE wqq.wizi_quiz_id = ?
                ORDER BY wqq.question_order ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array($wizi_quiz_user_id, $wizi_quiz_id));
        $statement = NULL;
        $pdo = NULL;
        return $inserted;
    }

    public function get_statistics_by_attempt($wizi_quiz_user_id)
    {
        $stats = array(
            'total_questions' => 0,
            'answered' => 0,
            'skipped' => 0,
            'timeout' => 0,
            'marked_review' => 0,
            'not_attempted' => 0,
            'correct' => 0,
            'incorrect' => 0,
            'total_score' => 0
        );
        
        $sql = "SELECT 
                COUNT(*) as total_questions,
                SUM(CASE WHEN status IN ('answered', 'answered_marked_review') THEN 1 ELSE 0 END) as answered,
                SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped,
                SUM(CASE WHEN status = 'timeout' THEN 1 ELSE 0 END) as timeout,
                SUM(CASE WHEN status IN ('marked_review', 'answered_marked_review') THEN 1 ELSE 0 END) as marked_review,
                SUM(CASE WHEN status = 'not_attempted' THEN 1 ELSE 0 END) as not_attempted,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct,
                SUM(CASE WHEN is_correct = 0 AND status IN ('answered', 'answered_marked_review') THEN 1 ELSE 0 END) as incorrect,
                SUM(marks_obtained) as total_score
                FROM wizi_quiz_question_user
                WHERE wizi_quiz_user_id = ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_user_id));
        if ($row = $statement->fetch()) {
            $stats['total_questions'] = (int)$row['total_questions'];
            $stats['answered'] = (int)$row['answered'];
            $stats['skipped'] = (int)$row['skipped'];
            $stats['timeout'] = (int)$row['timeout'];
            $stats['marked_review'] = (int)$row['marked_review'];
            $stats['not_attempted'] = (int)$row['not_attempted'];
            $stats['correct'] = (int)$row['correct'];
            $stats['incorrect'] = (int)$row['incorrect'];
            $stats['total_score'] = (float)$row['total_score'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $stats;
    }

    private function mapRowToObject($row)
    {
        $obj = new Wizi_quiz_question_user_object();
        $obj->id = $row['id'];
        $obj->wizi_quiz_user_id = $row['wizi_quiz_user_id'];
        $obj->wizi_quiz_question_id = $row['wizi_quiz_question_id'];
        $obj->wizi_question_id = $row['wizi_question_id'];
        $obj->question_order = isset($row['question_order']) ? $row['question_order'] : 0;
        $obj->marks = isset($row['marks']) ? $row['marks'] : 4;
        $obj->negative_marks = isset($row['negative_marks']) ? $row['negative_marks'] : -1.0;
        $obj->question_type = isset($row['question_type']) ? $row['question_type'] : 'mcq';
        $obj->user_answer = isset($row['user_answer']) ? $row['user_answer'] : null;
        $obj->correct_answer = isset($row['correct_answer']) ? $row['correct_answer'] : '';
        $obj->is_correct = isset($row['is_correct']) ? $row['is_correct'] : 0;
        $obj->status = isset($row['status']) ? $row['status'] : 'not_attempted';
        $obj->time_spent = isset($row['time_spent']) ? $row['time_spent'] : 0;
        $obj->marks_obtained = isset($row['marks_obtained']) ? $row['marks_obtained'] : 0.0;
        $obj->answered_at = isset($row['answered_at']) ? $row['answered_at'] : null;
        $obj->created_at = isset($row['created_at']) ? $row['created_at'] : null;
        $obj->updated_at = isset($row['updated_at']) ? $row['updated_at'] : null;
        return $obj;
    }

}
