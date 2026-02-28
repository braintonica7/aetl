<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Question_status_history_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Add a new question status history record
     * @param int $user_id - User who reported the question
     * @param int $question_id - Question being reported
     * @param string $status - Status (reported, corrected, dismissed)
     * @return int - ID of the created record or false on failure
     */
    public function add_status_record($user_id, $question_id, $status = 'reported') {
        $sql = "INSERT INTO question_status_history (user_id, question_id, status, reported_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        
        $datetime = date('Y-m-d H:i:s');
        $result = $statement->execute(array(
            $user_id,
            $question_id,
            $status,
            $datetime,
            $datetime,
            $datetime
        ));
        
        if ($result) {
            $insertId = $pdo->lastInsertId();
            $statement = null;
            $pdo = null;
            return $insertId;
        }
        
        $statement = null;
        $pdo = null;
        return false;
    }

    /**
     * Update status record when question is corrected
     * @param int $question_id - Question ID
     * @param string $status - New status (corrected, dismissed)
     * @param int $admin_user_id - Admin user who resolved the issue
     * @return bool - Success status
     */
    public function update_status($question_id, $status, $admin_user_id = null) {
        $datetime = date('Y-m-d H:i:s');
        
        if ($status === 'corrected' || $status === 'dismissed') {
            $sql = "UPDATE question_status_history SET status = ?, corrected_date = ?, updated_at = ? WHERE question_id = ? AND status = 'reported'";
            $params = array($status, $datetime, $datetime, $question_id);
        } else {
            $sql = "UPDATE question_status_history SET status = ?, updated_at = ? WHERE question_id = ? AND status = 'reported'";
            $params = array($status, $datetime, $question_id);
        }

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $result = $statement->execute($params);
        $statement = null;
        $pdo = null;

        return $result;
    }

    /**
     * Get status history for a specific question
     * @param int $question_id - Question ID
     * @return array - History records
     */
    public function get_question_history($question_id) {
        $sql = "SELECT qsh.*, u.name as reporter_name, u.email as reporter_email 
                FROM question_status_history qsh 
                LEFT JOIN user u ON u.id = qsh.user_id 
                WHERE qsh.question_id = ? 
                ORDER BY qsh.created_at DESC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($question_id));
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = $row;
        }
        
        $statement = null;
        $pdo = null;
        return $results;
    }

    /**
     * Check if a user has already reported a question
     * @param int $user_id - User ID
     * @param int $question_id - Question ID
     * @return bool - True if already reported
     */
    public function has_user_reported($user_id, $question_id) {
        $sql = "SELECT COUNT(*) as count FROM question_status_history WHERE user_id = ? AND question_id = ? AND status = 'reported'";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $question_id));
        $result = $statement->fetch();
        $statement = null;
        $pdo = null;
        
        return intval($result['count']) > 0;
    }

    /**
     * Get all status history records with pagination
     * @param int $limit - Records per page
     * @param int $offset - Offset for pagination
     * @param array $filters - Additional filters
     * @return array - Status records with question and user details
     */
    public function get_all_status_history($limit = 50, $offset = 0, $filters = array()) {
        $whereClause = "WHERE 1=1";
        $params = array();
        
        // Apply filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND qsh.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['question_id'])) {
            $whereClause .= " AND qsh.question_id = ?";
            $params[] = $filters['question_id'];
        }

        if (!empty($filters['user_id'])) {
            $whereClause .= " AND qsh.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        $sql = "SELECT qsh.*, q.question_text, q.question_img_url, u.name as reporter_name, u.email as reporter_email 
                FROM question_status_history qsh 
                LEFT JOIN question q ON q.id = qsh.question_id 
                LEFT JOIN user u ON u.id = qsh.user_id 
                $whereClause 
                ORDER BY qsh.created_at DESC 
                LIMIT ?, ?";
        
        $params[] = $offset;
        $params[] = $limit;
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = $row;
        }
        
        $statement = null;
        $pdo = null;
        return $results;
    }

    /**
     * Get count of status history records
     * @param array $filters - Filters to apply
     * @return int - Total count
     */
    public function get_status_history_count($filters = array()) {
        $whereClause = "WHERE 1=1";
        $params = array();
        
        // Apply same filters as get_all_status_history
        if (!empty($filters['status'])) {
            $whereClause .= " AND qsh.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['question_id'])) {
            $whereClause .= " AND qsh.question_id = ?";
            $params[] = $filters['question_id'];
        }

        if (!empty($filters['user_id'])) {
            $whereClause .= " AND qsh.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        $sql = "SELECT COUNT(*) as total FROM question_status_history qsh $whereClause";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $result = $statement->fetch();
        $statement = null;
        $pdo = null;
        
        return intval($result['total']);
    }

}

?>