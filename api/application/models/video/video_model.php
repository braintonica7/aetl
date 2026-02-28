<?php

class Video_model extends CI_Model {

	public function get_all_video_lessons_for_class_subject($class_id, $subject_id)
    {
        $records = array();

        $sql = "SELECT C.ID, C.name, C.bg_img AS chapter_bgImg, S.id AS subject_id, S.subject AS subject_name,
		T.id AS topic_id, T.name AS topic_name, T.topic_desc, T.video_url AS topic_url, T.bg_img AS topic_bgImg, T.duration AS topic_duration
		FROM TOPIC T 
		JOIN CHAPTER C ON C.id= T.chapter_id 
		JOIN SUBJECT S ON C.subject_id = S.id
		JOIN genere_subject GS ON GS.subject_id=S.id order by t.name
		where gs.genre_id=? and gs.subject_id=?";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($class_id,$subject_id));
        while ($row = $statement->fetch()) {
			$objVideo = new Video_object();
            $objVideo->id = $row['id'];
            $objVideo->name = $row['name'];
            $objVideo->chapter_bgImg = $row['chapter_bgImg'];
            $objVideo->subject_id = $row['subject_id'];
            $objVideo->subject_name = $row['subject_name'];
            $objVideo->topic_id = $row['topic_id'];
            $objVideo->topic_name = $row['topic_name'];
            $objVideo->topic_desc = $row['topic_desc'];
            $objVideo->topic_url = $row['topic_url'];
            $objVideo->topic_bgImg = $row['topic_bgImg'];
            $objVideo->topic_duration = $row['topic_duration'];
            $records[] = $objVideo;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
}
?>

