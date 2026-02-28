<?php

class User_profile_model extends CI_Model {

    /**
     * Get user profile by user_id
     */
    public function get_user_profile($user_id) {
        $objUserProfile = NULL;
        $sql = "SELECT * FROM user_profile WHERE user_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        
        if ($row = $statement->fetch()) {
            $objUserProfile = new User_profile_object();
            $objUserProfile->id = $row['id'];
            $objUserProfile->user_id = $row['user_id'];
            $objUserProfile->exam_type_id = $row['exam_type_id'];
            $objUserProfile->current_level = $row['current_level'];
            $objUserProfile->current_score = $row['current_score'];
            $objUserProfile->subject_scores = $row['subject_scores'];
            $objUserProfile->previous_attempts = $row['previous_attempts'];
            $objUserProfile->subject_strengths = $row['subject_strengths'];
            $objUserProfile->study_pattern = $row['study_pattern'];
            $objUserProfile->sleep_pattern = $row['sleep_pattern'];
            $objUserProfile->commitments = $row['commitments'];
            $objUserProfile->available_study_slots = $row['available_study_slots'];
            $objUserProfile->created_at = $row['created_at'];
            $objUserProfile->updated_at = $row['updated_at'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objUserProfile;
    }

    /**
     * Get user profile by id
     */
    public function get_user_profile_by_id($id) {
        $objUserProfile = NULL;
        $sql = "SELECT * FROM user_profile WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        
        if ($row = $statement->fetch()) {
            $objUserProfile = new User_profile_object();
            $objUserProfile->id = $row['id'];
            $objUserProfile->user_id = $row['user_id'];
            $objUserProfile->exam_type_id = $row['exam_type_id'];
            $objUserProfile->current_level = $row['current_level'];
            $objUserProfile->current_score = $row['current_score'];
            $objUserProfile->subject_scores = $row['subject_scores'];
            $objUserProfile->previous_attempts = $row['previous_attempts'];
            $objUserProfile->subject_strengths = $row['subject_strengths'];
            $objUserProfile->study_pattern = $row['study_pattern'];
            $objUserProfile->sleep_pattern = $row['sleep_pattern'];
            $objUserProfile->commitments = $row['commitments'];
            $objUserProfile->available_study_slots = $row['available_study_slots'];
            $objUserProfile->created_at = $row['created_at'];
            $objUserProfile->updated_at = $row['updated_at'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objUserProfile;
    }

    /**
     * Create or update user profile
     */
    public function save_user_profile($objUserProfile) {
        // Validate the object
        $validation_errors = $objUserProfile->validate();
        if (!empty($validation_errors)) {
            return FALSE;
        }

        // Encode JSON fields for database storage
        $objUserProfile->encode_json_fields();

        $pdo = CDatabase::getPdo();
        
        // Check if profile already exists
        $existing_profile = $this->get_user_profile($objUserProfile->user_id);
        
        if ($existing_profile == NULL) {
            // Create new profile
            return $this->add_user_profile($objUserProfile);
        } else {
            // Update existing profile
            $objUserProfile->id = $existing_profile->id;
            return $this->update_user_profile($objUserProfile);
        }
    }

    /**
     * Add new user profile
     */
    public function add_user_profile($objUserProfile) {
        $pdo = CDatabase::getPdo();

        // Get next ID
        $sql = "SELECT MAX(id) as mvalue FROM user_profile";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch()) {
            $objUserProfile->id = $row['mvalue'];
        } else {
            $objUserProfile->id = 0;
        }
        $objUserProfile->id = $objUserProfile->id + 1;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $objUserProfile->created_at = $dateTime->format('Y-m-d H:i:s');
        $objUserProfile->updated_at = $dateTime->format('Y-m-d H:i:s');

        $sql = "INSERT INTO user_profile (
            id, user_id, exam_type_id, current_level, current_score,
            subject_scores, previous_attempts, subject_strengths, study_pattern,
            sleep_pattern, commitments, available_study_slots, created_at, updated_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"; 

        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objUserProfile->id,
            $objUserProfile->user_id,
            $objUserProfile->exam_type_id,
            $objUserProfile->current_level,
            $objUserProfile->current_score,
            $objUserProfile->subject_scores,
            $objUserProfile->previous_attempts,
            $objUserProfile->subject_strengths,
            $objUserProfile->study_pattern,
            $objUserProfile->sleep_pattern,
            $objUserProfile->commitments,
            $objUserProfile->available_study_slots,
            $objUserProfile->created_at,
            $objUserProfile->updated_at
        ));

        $statement = NULL;
        $pdo = NULL;
        
        if ($inserted) {
            // Decode JSON fields for return
            $objUserProfile->decode_json_fields();
            return $objUserProfile;
        }
        return FALSE;
    }

    /**
     * Update existing user profile
     */
    public function update_user_profile($objUserProfile) {
        $pdo = CDatabase::getPdo();

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $objUserProfile->updated_at = $dateTime->format('Y-m-d H:i:s');

        $sql = "UPDATE user_profile SET 
            exam_type_id = ?, current_level = ?, current_score = ?,
            subject_scores = ?, previous_attempts = ?, subject_strengths = ?, 
            study_pattern = ?, sleep_pattern = ?, commitments = ?, 
            available_study_slots = ?, updated_at = ?
            WHERE id = ?";

        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objUserProfile->exam_type_id,
            $objUserProfile->current_level,
            $objUserProfile->current_score,
            $objUserProfile->subject_scores,
            $objUserProfile->previous_attempts,
            $objUserProfile->subject_strengths,
            $objUserProfile->study_pattern,
            $objUserProfile->sleep_pattern,
            $objUserProfile->commitments,
            $objUserProfile->available_study_slots,
            $objUserProfile->updated_at,
            $objUserProfile->id
        ));

        $statement = NULL;
        $pdo = NULL;
        
        if ($updated) {
            // Decode JSON fields for return
            $objUserProfile->decode_json_fields();
            return $objUserProfile;
        }
        return FALSE;
    }

    /**
     * Delete user profile
     */
    public function delete_user_profile($user_id) {
        $pdo = CDatabase::getPdo();
        $sql = "DELETE FROM user_profile WHERE user_id = ?";
        $statement = $pdo->prepare($sql);
        $deleted = $statement->execute(array($user_id));
        $statement = NULL;
        $pdo = NULL;
        return $deleted;
    }

    /**
     * Check if user profile exists
     */
    public function profile_exists($user_id) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT COUNT(*) as count FROM user_profile WHERE user_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $row = $statement->fetch();
        $statement = NULL;
        $pdo = NULL;
        return $row['count'] > 0;
    }

    /**
     * Get profiles by exam type (for analytics/admin)
     */
    public function get_profiles_by_exam_type($exam_type, $limit = 10, $offset = 0) {
        $records = array();
        $sql = "SELECT up.*, u.display_name, u.username 
                FROM user_profile up 
                JOIN user u ON up.user_id = u.id 
                WHERE up.exam_type_id = ? 
                ORDER BY up.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($exam_type, $limit, $offset));
        
        while ($row = $statement->fetch()) {
            $objUserProfile = new User_profile_object();
            $objUserProfile->id = $row['id'];
            $objUserProfile->user_id = $row['user_id'];
            $objUserProfile->exam_type_id = $row['exam_type_id'];
            $objUserProfile->current_level = $row['current_level'];
            $objUserProfile->current_score = $row['current_score'];
            $objUserProfile->subject_scores = $row['subject_scores'];
            $objUserProfile->previous_attempts = $row['previous_attempts'];
            $objUserProfile->subject_strengths = $row['subject_strengths'];
            $objUserProfile->study_pattern = $row['study_pattern'];
            $objUserProfile->sleep_pattern = $row['sleep_pattern'];
            $objUserProfile->commitments = $row['commitments'];
            $objUserProfile->available_study_slots = $row['available_study_slots'];
            $objUserProfile->created_at = $row['created_at'];
            $objUserProfile->updated_at = $row['updated_at'];
            
            // Add user info
            $objUserProfile->display_name = $row['display_name'];
            $objUserProfile->username = $row['username'];
            
            $records[] = $objUserProfile;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get profile statistics
     */
    public function get_profile_stats() {
        $stats = array();
        $sql = "SELECT 
                    COUNT(*) as total_profiles,
                    COUNT(DISTINCT exam_type_id) as unique_exams,
                    AVG(current_score) as avg_current_score
                FROM user_profile 
                WHERE current_score IS NOT NULL";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        if ($row = $statement->fetch()) {
            $stats = array(
                'total_profiles' => (int)$row['total_profiles'],
                'unique_exams' => (int)$row['unique_exams'],
                'avg_current_score' => round((float)$row['avg_current_score'], 2)
            );
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $stats;
    }
}

?>