<?php

class Quiz_subject_model extends CI_Model
{
    /**
     * Add multiple subjects to a quiz
     * @param int $quiz_id
     * @param array $subject_ids
     * @return bool
     */
    public function add_quiz_subjects($quiz_id, $subject_ids)
    {
        if (empty($subject_ids) || !is_array($subject_ids)) {
            return false;
        }

        $pdo = CDatabase::getPdo();
        
        try {
            $pdo->beginTransaction();
            
            // First, remove any existing subjects for this quiz
            $delete_sql = "DELETE FROM quiz_subjects WHERE quiz_id = ?";
            $delete_statement = $pdo->prepare($delete_sql);
            $delete_statement->execute(array($quiz_id));
            
            // Then add the new subjects
            $insert_sql = "INSERT INTO quiz_subjects (quiz_id, subject_id) VALUES (?, ?)";
            $insert_statement = $pdo->prepare($insert_sql);
            
            foreach ($subject_ids as $subject_id) {
                $insert_statement->execute(array($quiz_id, $subject_id));
            }
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            log_message('error', "Error adding quiz subjects: " . $e->getMessage());
            return false;
        } finally {
            $insert_statement = null;
            $delete_statement = null;
            $pdo = null;
        }
    }

    /**
     * Get all subjects for a quiz
     * @param int $quiz_id
     * @return array
     */
    public function get_quiz_subjects($quiz_id)
    {
        $subjects = array();
        
        $sql = "SELECT s.* FROM quiz_subjects qs 
                JOIN subject s ON qs.subject_id = s.id 
                WHERE qs.quiz_id = ? 
                ORDER BY s.subject";
                
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        
        while ($row = $statement->fetch()) {
            $objSubject = new Subject_object();
            $objSubject->id = $row['id'];
            $objSubject->subject_name = $row['subject'];
            $objSubject->class_id = isset($row['class_id']) ? $row['class_id'] : 0;
            
            $subjects[] = $objSubject;
        }
        
        $statement = null;
        $pdo = null;
        
        return $subjects;
    }

    /**
     * Get subject IDs for a quiz
     * @param int $quiz_id
     * @return array
     */
    public function get_quiz_subject_ids($quiz_id)
    {
        $subject_ids = array();
        
        $sql = "SELECT subject_id FROM quiz_subjects WHERE quiz_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_id));
        
        while ($row = $statement->fetch()) {
            $subject_ids[] = $row['subject_id'];
        }
        
        $statement = null;
        $pdo = null;
        
        return $subject_ids;
    }

    /**
     * Remove all subjects from a quiz
     * @param int $quiz_id
     * @return bool
     */
    public function remove_quiz_subjects($quiz_id)
    {
        $sql = "DELETE FROM quiz_subjects WHERE quiz_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $result = $statement->execute(array($quiz_id));
        
        $statement = null;
        $pdo = null;
        
        return $result;
    }
}

?>
