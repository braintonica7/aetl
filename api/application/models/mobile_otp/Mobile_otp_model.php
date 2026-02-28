<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mobile OTP Model
 * Handles OTP generation, verification, and rate limiting for mobile number verification
 */
class Mobile_otp_model extends CI_Model {

    private $table = 'mobile_otp';
    private $otp_validity_minutes = 10; // OTP valid for 10 minutes
    private $max_resend_attempts = 3; // Maximum 3 OTP send attempts (1 initial + 2 resends)
    private $max_verification_attempts = 3; // Maximum 3 wrong OTP entry attempts

    public function __construct() {
        parent::__construct();
    }

    /**
     * Generate and store a new OTP for mobile number
     * 
     * @param string $mobile_number Mobile number (10 digits)
     * @param int|null $user_id User ID (optional)
     * @param string $ip_address IP address of requester
     * @param string $user_agent User agent of requester
     * @return array Success status with OTP details or error message
     */
    public function generate_otp($mobile_number, $user_id = null, $ip_address = null, $user_agent = null) {
        // Clean mobile number (remove spaces, dashes, etc.)
        $mobile_number = $this->clean_mobile_number($mobile_number);

        // Validate mobile number format
        if (!$this->validate_mobile_number($mobile_number)) {
            return [
                'success' => false,
                'error' => 'Invalid mobile number format. Please enter a valid 10-digit Indian mobile number.'
            ];
        }

        // Check rate limiting (per mobile number)
        $rate_limit_check = $this->check_rate_limit($mobile_number);
        if (!$rate_limit_check['allowed']) {
            return [
                'success' => false,
                'error' => $rate_limit_check['message'],
                'retry_after' => $rate_limit_check['retry_after'] ?? null
            ];
        }

        // Generate 6-digit OTP
        $otp_code = $this->generate_otp_code();

        // Calculate expiration time (10 minutes from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $this->otp_validity_minutes . ' minutes'));

        // Get resend count for this mobile number
        $resend_count = $this->get_resend_count($mobile_number);

        // Insert OTP record
        $data = [
            'user_id' => $user_id,
            'mobile_number' => $mobile_number,
            'otp_code' => $otp_code,
            'expires_at' => $expires_at,
            'is_used' => 0,
            'is_verified' => 0,
            'attempts_count' => 0,
            'resend_count' => $resend_count,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $pdo = CDatabase::getPdo();
        $sql = "INSERT INTO mobile_otp (user_id, mobile_number, otp_code, expires_at, is_used, is_verified, 
                attempts_count, resend_count, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            $user_id,
            $mobile_number,
            $otp_code,
            $expires_at,
            0, // is_used
            0, // is_verified
            0, // attempts_count
            $resend_count,
            $ip_address,
            $user_agent,
            date('Y-m-d H:i:s')
        ));
        
        $otp_id = $pdo->lastInsertId();
        $statement = NULL;
        $pdo = NULL;

        if ($otp_id) {
            return [
                'success' => true,
                'otp_id' => $otp_id,
                'otp_code' => $otp_code, // Return for SMS sending
                'expires_at' => $expires_at,
                'valid_for_minutes' => $this->otp_validity_minutes,
                'attempts_remaining' => $this->max_verification_attempts,
                'resends_remaining' => $this->max_resend_attempts - $resend_count - 1
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to generate OTP. Please try again.'
        ];
    }

    /**
     * Verify OTP code for mobile number
     * 
     * @param string $mobile_number Mobile number
     * @param string $otp_code OTP code entered by user
     * @param int|null $user_id User ID (optional)
     * @return array Verification result
     */
    public function verify_otp($mobile_number, $otp_code, $user_id = null) {
        // Clean inputs
        $mobile_number = $this->clean_mobile_number($mobile_number);
        $otp_code = trim($otp_code);

        // Find the latest active OTP for this mobile number
        $pdo = CDatabase::getPdo();
        $sql = "SELECT * FROM mobile_otp 
                WHERE mobile_number = ? 
                AND is_used = 0 
                AND expires_at > ? 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($mobile_number, date('Y-m-d H:i:s')));
        $otp_record = $statement->fetch(PDO::FETCH_OBJ);
        $statement = NULL;

        if (!$otp_record) {
            $pdo = NULL;
            return [
                'success' => false,
                'error' => 'No active OTP found for this mobile number. Please request a new OTP.'
            ];
        }

        // Check if maximum verification attempts exceeded
        if ($otp_record->attempts_count >= $this->max_verification_attempts) {
            $pdo = NULL;
            return [
                'success' => false,
                'error' => 'Maximum verification attempts exceeded. Please request a new OTP.',
                'max_attempts_reached' => true
            ];
        }

        // Increment attempt count
        $sql_update = "UPDATE mobile_otp SET attempts_count = attempts_count + 1 WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute(array($otp_record->id));
        $stmt_update = NULL;

        // Verify OTP code
        if ($otp_record->otp_code === $otp_code) {
            // Mark OTP as used and verified
            $sql_verify = "UPDATE mobile_otp SET is_used = 1, is_verified = 1, verified_at = ? WHERE id = ?";
            $stmt_verify = $pdo->prepare($sql_verify);
            $stmt_verify->execute(array(date('Y-m-d H:i:s'), $otp_record->id));
            $stmt_verify = NULL;
            $pdo = NULL;

            return [
                'success' => true,
                'message' => 'Mobile number verified successfully!',
                'mobile_number' => $mobile_number
            ];
        } else {
            // Wrong OTP
            $attempts_remaining = $this->max_verification_attempts - ($otp_record->attempts_count + 1);
            $pdo = NULL;
            
            if ($attempts_remaining <= 0) {
                return [
                    'success' => false,
                    'error' => 'Maximum verification attempts exceeded. Please request a new OTP.',
                    'max_attempts_reached' => true,
                    'attempts_remaining' => 0
                ];
            }

            return [
                'success' => false,
                'error' => 'Invalid OTP code. Please try again.',
                'attempts_remaining' => $attempts_remaining
            ];
        }
    }

    /**
     * Check rate limiting for OTP requests
     * Per mobile number: Max 3 OTP requests total (1 initial + 2 resends)
     * 
     * @param string $mobile_number Mobile number
     * @return array Rate limit status
     */
    public function check_rate_limit($mobile_number) {
        $mobile_number = $this->clean_mobile_number($mobile_number);

        // Count OTP requests for this mobile number in current validity window
        $pdo = CDatabase::getPdo();
        $sql = "SELECT COUNT(*) as request_count, MAX(created_at) as last_request 
                FROM mobile_otp 
                WHERE mobile_number = ? 
                AND created_at > ?";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            $mobile_number,
            date('Y-m-d H:i:s', strtotime('-' . $this->otp_validity_minutes . ' minutes'))
        ));
        $result = $statement->fetch(PDO::FETCH_OBJ);
        $statement = NULL;
        $pdo = NULL;

        $request_count = $result->request_count;
        $last_request = $result->last_request;

        // If user has already made 3 requests
        if ($request_count >= $this->max_resend_attempts) {
            // Calculate when the oldest OTP will expire
            $oldest_otp_time = strtotime($last_request);
            $expiry_time = $oldest_otp_time + ($this->otp_validity_minutes * 60);
            $current_time = time();
            
            if ($current_time < $expiry_time) {
                $minutes_remaining = ceil(($expiry_time - $current_time) / 60);
                
                return [
                    'allowed' => false,
                    'message' => "Maximum OTP requests reached. Please try again after {$minutes_remaining} minute(s) or use the existing OTP.",
                    'retry_after' => $minutes_remaining
                ];
            }
        }

        return [
            'allowed' => true,
            'requests_remaining' => $this->max_resend_attempts - $request_count
        ];
    }

    /**
     * Get resend count for mobile number (within current validity window)
     * 
     * @param string $mobile_number Mobile number
     * @return int Resend count
     */
    private function get_resend_count($mobile_number) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT COUNT(*) as count FROM mobile_otp 
                WHERE mobile_number = ? 
                AND created_at > ?";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            $mobile_number,
            date('Y-m-d H:i:s', strtotime('-' . $this->otp_validity_minutes . ' minutes'))
        ));
        $result = $statement->fetch(PDO::FETCH_OBJ);
        $statement = NULL;
        $pdo = NULL;
        
        return $result ? (int)$result->count : 0;
    }

    /**
     * Check if mobile number is already verified by another user
     * 
     * @param string $mobile_number Mobile number
     * @param int|null $exclude_user_id User ID to exclude from check
     * @return bool True if mobile is already used
     */
    public function is_mobile_already_used($mobile_number, $exclude_user_id = null) {
        $mobile_number = $this->clean_mobile_number($mobile_number);
        
        $pdo = CDatabase::getPdo();
        
        if ($exclude_user_id) {
            $sql = "SELECT id FROM user WHERE mobile_number = ? AND mobile_verified = 1 AND id != ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($mobile_number, $exclude_user_id));
        } else {
            $sql = "SELECT id FROM user WHERE mobile_number = ? AND mobile_verified = 1";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($mobile_number));
        }
        
        $result = $statement->fetch();
        $statement = NULL;
        $pdo = NULL;
        
        return $result !== false;
    }

    /**
     * Get OTP status for mobile number
     * 
     * @param string $mobile_number Mobile number
     * @return array|null OTP status or null if no active OTP
     */
    public function get_otp_status($mobile_number) {
        $mobile_number = $this->clean_mobile_number($mobile_number);

        $pdo = CDatabase::getPdo();
        $sql = "SELECT id, expires_at, attempts_count, resend_count, created_at 
                FROM mobile_otp 
                WHERE mobile_number = ? 
                AND is_used = 0 
                AND expires_at > ? 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($mobile_number, date('Y-m-d H:i:s')));
        $otp = $statement->fetch(PDO::FETCH_OBJ);
        $statement = NULL;
        $pdo = NULL;

        if ($otp) {
            $expires_at_timestamp = strtotime($otp->expires_at);
            $current_time = time();
            $seconds_remaining = $expires_at_timestamp - $current_time;
            $minutes_remaining = ceil($seconds_remaining / 60);

            return [
                'has_active_otp' => true,
                'expires_at' => $otp->expires_at,
                'minutes_remaining' => max(0, $minutes_remaining),
                'attempts_used' => (int)$otp->attempts_count,
                'attempts_remaining' => max(0, $this->max_verification_attempts - (int)$otp->attempts_count),
                'resend_count' => (int)$otp->resend_count,
                'resends_remaining' => max(0, $this->max_resend_attempts - (int)$otp->resend_count - 1)
            ];
        }

        return [
            'has_active_otp' => false
        ];
    }

    /**
     * Cleanup expired OTPs (for maintenance)
     * Deletes OTPs older than 24 hours
     * 
     * @return int Number of deleted records
     */
    public function cleanup_expired_otps() {
        $pdo = CDatabase::getPdo();
        $sql = "DELETE FROM mobile_otp WHERE created_at < ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array(date('Y-m-d H:i:s', strtotime('-24 hours'))));
        $affected_rows = $statement->rowCount();
        $statement = NULL;
        $pdo = NULL;
        
        return $affected_rows;
    }

    /**
     * Generate 6-digit OTP code
     * 
     * @return string 6-digit OTP
     */
    private function generate_otp_code() {
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validate Indian mobile number format
     * 
     * @param string $mobile_number Mobile number
     * @return bool True if valid
     */
    private function validate_mobile_number($mobile_number) {
        // Must be exactly 10 digits and start with 6-9
        return preg_match('/^[6-9]\d{9}$/', $mobile_number) === 1;
    }

    /**
     * Clean mobile number (remove spaces, dashes, country code, etc.)
     * 
     * @param string $mobile_number Mobile number
     * @return string Cleaned mobile number
     */
    private function clean_mobile_number($mobile_number) {
        // Remove all non-digit characters
        $mobile_number = preg_replace('/\D/', '', $mobile_number);
        
        // Remove country code if present (91 for India)
        if (strlen($mobile_number) == 12 && substr($mobile_number, 0, 2) == '91') {
            $mobile_number = substr($mobile_number, 2);
        } elseif (strlen($mobile_number) == 11 && substr($mobile_number, 0, 1) == '0') {
            // Remove leading 0 if present
            $mobile_number = substr($mobile_number, 1);
        }
        
        return $mobile_number;
    }
}
