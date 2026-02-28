<?php

class User_profile_object extends CI_Model {

    public $id;
    public $user_id;
    public $exam_type_id;
    public $current_level;
    public $current_score;
    public $subject_scores;
    public $previous_attempts;
    public $subject_strengths;
    public $study_pattern;
    public $sleep_pattern;
    public $commitments;
    public $available_study_slots;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->user_id = 0;
        $this->exam_type_id = null;
        $this->current_level = null;
        $this->current_score = null;
        $this->subject_scores = null;
        $this->previous_attempts = null;
        $this->subject_strengths = null;
        $this->study_pattern = null;
        $this->sleep_pattern = null;
        $this->commitments = null;
        $this->available_study_slots = null;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created_at = $dateTime;
        $this->updated_at = $dateTime;
    }

    /**
     * Convert JSON string fields to arrays for easier manipulation
     */
    public function decode_json_fields() {
        if ($this->subject_scores) {
            $this->subject_scores = json_decode($this->subject_scores, true);
        }
        if ($this->previous_attempts) {
            $this->previous_attempts = json_decode($this->previous_attempts, true);
        }
        if ($this->subject_strengths) {
            $this->subject_strengths = json_decode($this->subject_strengths, true);
        }
        if ($this->study_pattern) {
            $this->study_pattern = json_decode($this->study_pattern, true);
        }
        if ($this->sleep_pattern) {
            $this->sleep_pattern = json_decode($this->sleep_pattern, true);
        }
        if ($this->commitments) {
            $this->commitments = json_decode($this->commitments, true);
        }
        if ($this->available_study_slots) {
            $this->available_study_slots = json_decode($this->available_study_slots, true);
        }
    }

    /**
     * Convert array fields to JSON strings for database storage
     */
    public function encode_json_fields() {
        if (is_array($this->subject_scores)) {
            $this->subject_scores = json_encode($this->subject_scores);
        }
        if (is_array($this->previous_attempts)) {
            $this->previous_attempts = json_encode($this->previous_attempts);
        }
        if (is_array($this->subject_strengths)) {
            $this->subject_strengths = json_encode($this->subject_strengths);
        }
        if (is_array($this->study_pattern)) {
            $this->study_pattern = json_encode($this->study_pattern);
        }
        if (is_array($this->sleep_pattern)) {
            $this->sleep_pattern = json_encode($this->sleep_pattern);
        }
        if (is_array($this->commitments)) {
            $this->commitments = json_encode($this->commitments);
        }
        if (is_array($this->available_study_slots)) {
            $this->available_study_slots = json_encode($this->available_study_slots);
        }
    }

    /**
     * Validate required fields for profile creation/update
     */
    public function validate() {
        $errors = array();
        
        if (empty($this->user_id)) {
            $errors[] = 'User ID is required';
        }             

        
        return $errors;
    }

    /**
     * Convert object to array for API responses
     */
    public function to_array() {
        // Ensure JSON fields are decoded for API response
        $this->decode_json_fields();
        
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'exam_type_id' => $this->exam_type_id,
            'current_level' => $this->current_level,
            'current_score' => $this->current_score,
            'subject_scores' => $this->subject_scores,
            'previous_attempts' => $this->previous_attempts,
            'subject_strengths' => $this->subject_strengths,
            'study_pattern' => $this->study_pattern,
            'sleep_pattern' => $this->sleep_pattern,
            'commitments' => $this->commitments,
            'available_study_slots' => $this->available_study_slots,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        );
    }
}

?>