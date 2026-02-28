<?php

class Quiz_schedule_model extends CI_Model
{
    public function get_all_quiz_schedule($class_id)
    {
        $records = array();

        $sql = "SELECT Q.ID AS quiz_id, Q.NAME AS quiz_name, G.ID AS class_id, G.class_name,
		S.id AS SUBJECT_ID, S.subject AS SUBJECT_NAME, C.id AS chapter_id, C.name AS CHAPTER_NAME,
		T.id AS TOPIC_ID, T.name AS TOPIC_NAME, T.topic_desc, Q.start_date AS topic_schedule
		FROM QUIZ Q
		JOIN GENERE G ON G.id=Q.class_id
		JOIN subject S ON S.id= Q.subject_id
		JOIN chapter C ON C.id=Q.chapter_id
		JOIN topic T ON T.id= Q.topic_id AND T.chapter_id=C.id
		AND Q.is_live=1 WHERE Q.CLASS_ID=?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
		$statement->execute(array($class_id));
        while ($row = $statement->fetch()) {
            $objQuiz = new Quiz_schedule_object();
            $objQuiz->id = $row['quiz_id'];
            $objQuiz->name = $row['quiz_name'];
            $objQuiz->class_id = $row['class_id'];
            $objQuiz->subject_id = $row['subject_id'];
			$objQuiz->chapter_id = $row['chapter_id'];
            $objQuiz->topic_id = $row['topic_id'];
            $objQuiz->start_date = $row['start_date'];
            $records[] = $objQuiz;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
}
