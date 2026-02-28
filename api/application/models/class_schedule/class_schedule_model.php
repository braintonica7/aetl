<?php

class Class_Schedule_model extends CI_Model {

	public function get_class_schedule($id)
    {
        $objClassSchedule = NULL;
        $sql = "select * from class_schedule where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
			$objClassSchedule = new Class_Schedule_object();
            $objClassSchedule->id = $row['id'];
            $objClassSchedule->class_id = $row['class_id'];
            $objClassSchedule->subject_id = $row['subject_id'];
			$objClassSchedule->chapter_id = $row['chapter_id'];
            $objClassSchedule->topic_id = $row['topic_id'];
            $objClassSchedule->topic_schedule = $row['schedule_date'];
            $records[] = $objClassSchedule;
        }
        $statement = NULL;
        $pdo = NULL;
        return $objClassSchedule;
    }
	public function get_live_class_schedules($class_id)
    {
        $records = array();

        $sql = "SELECT CS.id, CS.class_id, G.class_name, GS.subject_id, S.subject AS  subject_name,
		CS.chapter_id, C.name AS chapter_name, CS.topic_id, T.name AS topic_name, 
		T.topic_desc, CS.schedule_date AS topic_schedule
		FROM class_schedule CS
		JOIN GENERE G ON G.ID=CS.class_id
		JOIN SUBJECT S ON S.id=CS.subject_id
		JOIN genere_subject GS ON GS.genere_id=CS.class_id AND GS.subject_id=CS.subject_id
		JOIN CHAPTER C ON C.id=CS.chapter_id
		JOIN topic T ON T.id= CS.topic_id AND T.chapter_id=CS.chapter_id
		WHERE G.id = ?";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($class_id));
        while ($row = $statement->fetch()) {
			$objClassSchedule = new Class_Schedule_object();
            $objClassSchedule->id = $row['id'];
            $objClassSchedule->class_id = $row['class_id'];
            $objClassSchedule->class_name = $row['class_name'];
            $objClassSchedule->subject_id = $row['subject_id'];
            $objClassSchedule->subject_name = $row['subject_name'];
			$objClassSchedule->subject_id = $row['chapter_id'];
            $objClassSchedule->subject_name = $row['chapter_name'];
            $objClassSchedule->topic_id = $row['topic_id'];
            $objClassSchedule->topic_name = $row['topic_name'];
            $objClassSchedule->topic_desc = $row['topic_desc'];
            $objClassSchedule->topic_schedule = $row['topic_schedule'];
            $records[] = $objClassSchedule;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
	public function add_Class_Schedule($objClassSchedule)
    {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from class_schedule";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objClassSchedule->id = $row['mvalue'];
        else
            $objClassSchedule->id = 0;
        $objClassSchedule->id = $objClassSchedule->id + 1;
        $sql = "insert into class_schedule (`id`, `class_id`, `subject_id`, `chapter_id`, `topic_id`, `scheduled_date`) values (?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objClassSchedule->id,
            $objClassSchedule->class_id,
            $objClassSchedule->subject_id,
            $objClassSchedule->chapter_id,
            $objClassSchedule->topic_id,
            $objClassSchedule->topic_schedule
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objClassSchedule;
        return FALSE;
    }

    public function update_Class_Schedule($objClassSchedule)
    {
        $sql = "update class_schedule set class_id = ?, subject_id = ?, chapter_id = ?, topic_id = ?, topic_schedule=? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objClassSchedule->class_id,
            $objClassSchedule->subject_id,
            $objClassSchedule->chapter_id,
            $objClassSchedule->topic_id,
            $objClassSchedule->topic_schedule,
            $objClassSchedule->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objClassSchedule;
        return FALSE;
    }

    public function delete_Class_Schedule($id)
    {
        $sql = "delete from class_schedule where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }
}
?>

