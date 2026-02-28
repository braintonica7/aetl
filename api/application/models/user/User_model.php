<?php

class User_model extends CI_Model {

    /**
     * Create User_object from database row
     * Maps all database fields to User_object properties
     * 
     * @param array $row Database row from user table
     * @param bool $load_references Whether to load scholar/employee references (default: true)
     * @return User_object Populated user object
     */
    private function create_user_from_row($row, $load_references = true) {
        $objUser = new User_object();
        
        // Basic fields
        $objUser->id = $row['id'];
        $objUser->username = $row['username'];
        $objUser->email = $row['username'];
        $objUser->password = $row['password'];
        $objUser->display_name = $row['display_name'];
        $objUser->role_id = $row['role_id'];
        $objUser->reference_id = $row['reference_id'];
        $objUser->allow_login = $row['allow_login'] == 1;
        $objUser->token = isset($row['token']) ? $row['token'] : null;
        $objUser->created_at = DateTime::createFromFormat("Y-m-d H:i:s", $row['created_at'])->format('Y-m-d H:i:s');
        $objUser->updated_at = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated_at'])->format('Y-m-d H:i:s');
        
        // Mobile verification fields
        $objUser->mobile_number = isset($row['mobile_number']) ? $row['mobile_number'] : null;
        $objUser->mobile_verified = isset($row['mobile_verified']) ? $row['mobile_verified'] == 1 : false;
        $objUser->mobile_verified_at = isset($row['mobile_verified_at']) ? $row['mobile_verified_at'] : null;
        
        // Google OAuth fields
        $objUser->google_id = isset($row['google_id']) ? $row['google_id'] : null;
        $objUser->auth_provider = isset($row['auth_provider']) ? $row['auth_provider'] : null;
        $objUser->profile_picture_url = isset($row['profile_picture_url']) ? $row['profile_picture_url'] : null;
        $objUser->email_verified = isset($row['email_verified']) ? $row['email_verified'] == 1 : false;
        
        // JWT fields
        $objUser->jwt_access_token = isset($row['jwt_access_token']) ? $row['jwt_access_token'] : null;
        $objUser->jwt_refresh_token = isset($row['jwt_refresh_token']) ? $row['jwt_refresh_token'] : null;
        
        // Role field (sometimes included in queries with JOIN)
        if (isset($row['role'])) {
            $objUser->role = $row['role'];
        }
        
        // Subscription and quota fields
        $objUser->subscription_type = isset($row['subscription_type']) ? $row['subscription_type'] : 'free';
        $objUser->subscription_starts_at = isset($row['subscription_starts_at']) ? $row['subscription_starts_at'] : null;
        $objUser->subscription_expires_at = isset($row['subscription_expires_at']) ? $row['subscription_expires_at'] : null;
        $objUser->custom_quiz_count = isset($row['custom_quiz_count']) ? $row['custom_quiz_count'] : 0;
        $objUser->custom_quiz_limit = isset($row['custom_quiz_limit']) ? $row['custom_quiz_limit'] : null;
        $objUser->quota_reset_date = isset($row['quota_reset_date']) ? $row['quota_reset_date'] : null;
        
        // Last login field
        if (isset($row['last_login']) && $row['last_login'] != NULL) {
            $objUser->last_login = DateTime::createFromFormat("Y-m-d H:i:s", $row['last_login'])->format('Y-m-d H:i:s');
        } else {
            $objUser->last_login = NULL;
        }
        
        // Load related scholar/employee data if needed
        if ($load_references && $objUser->reference_id != 0) {
            if ($objUser->role_id == 5) {  // Student role
                $CI = & get_instance();
                $this->load->model('scholar/scholar_model');
                $objScholar = $this->scholar_model->get_scholar($objUser->reference_id);
                if ($objScholar != NULL) {
                    $objUser->scholar = $objScholar;
                }
            } else if ($objUser->role_id == 4 || $objUser->role_id == 3) {  // Faculty/Principal role
                $CI = & get_instance();
                $this->load->model('employee/employee_model');
                $objEmployee = $this->employee_model->get_employee($objUser->reference_id);
                if ($objEmployee != NULL) {
                    $objUser->employee = $objEmployee;
                }
            }
        }
        
        return $objUser;
    }

    public function get_user($id) {
        $objUser = NULL;
        $sql = "select * from user where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUser;
    }

    public function get_user_from_token($token) {
        $objUser = NULL;
        $sql = "select * from user where token = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($token));
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUser;
    }

    public function get_all_users() {
        $records = array();

        $sql = "select * from user";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row, false);
            $records[] = $objUser;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function change_password($userId, $newPassword){
        $pdo = CDatabase::getPdo();
        $sql = "update user set password = ? where id = ?";
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($newPassword, $userId));
        $statement = NULL;
        $pdo = NULL;
        return $updated; 
    }

    public function get_user_from_username_password($username, $password) {
        $objUser = NULL;        
        $pdo = CDatabase::getPdo();
        // First get user by username only
        $sql = "select user.*, role.role as role from user left join role on user.role_id = role.id where username = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($username));
        if ($row = $statement->fetch()) {
            // Verify the password against the stored hash
            if (password_verify($password, $row['password'])) {
                $objUser = $this->create_user_from_row($row);
            }
        }
        $statement = NULL;
        $pdo = NULL;
        if ($objUser != null) {
            if ($objUser->allow_login == 1) {
                if (empty($objUser->token)) {
                    $objUser->token = CUtility::get_UUID();
                    $this->update_last_login_and_token($objUser->id, $objUser->token);
                }
                
                //$objUser->token = CUtility::get_UUID();
               // $this->update_last_login_and_token($objUser->id, $objUser->token);
            }
        }
        return $objUser;
    }
    
    public function get_user_from_username_password_for_mobile_login($username, $password) {
        $objUser = NULL;        
        $pdo = CDatabase::getPdo();
        // First get user by username only
        $sql = "select user.*, role.role as role from user left join role on user.role_id = role.id where username = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($username));
        if ($row = $statement->fetch()) {
            // Verify the password against the stored hash
            if (password_verify($password, $row['password'])) {
                $objUser = $this->create_user_from_row($row);
            }
        }
        $statement = NULL;
        $pdo = NULL;
        if ($objUser != null) {
            if ($objUser->allow_login == 1) {
                if (empty($objUser->token)) {
                    $objUser->token = CUtility::get_UUID();
                    $this->update_last_login_and_token($objUser->id, $objUser->token);
                }
                //$objUser->token = CUtility::get_UUID();
                //$this->update_last_login_and_token($objUser->id, $objUser->token);
            }
        }
        return $objUser;
    }
    
    public function get_user_from_username($username) {
        $objUser = NULL;        
        $pdo = CDatabase::getPdo();
        $sql = "select * from user where username = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($username));
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
        }
        $statement = NULL;
        $pdo = NULL;        
        return $objUser;
    }

    public function add_user($objUser) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from user";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objUser->id = $row['mvalue'];
        else
            $objUser->id = 0;
        $objUser->id = $objUser->id + 1;
        $sql = "INSERT INTO user (id, username, password, display_name, role_id, reference_id, allow_login, token, jwt_access_token, jwt_refresh_token, jwt_token_created_at, jwt_token_expires_at, jwt_refresh_expires_at, device_info, last_activity, last_login) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objUser->id,
            $objUser->username,
            $objUser->password,
            $objUser->display_name,
            $objUser->role_id,
            $objUser->reference_id,
            $objUser->allow_login,
            $objUser->token,
            NULL, // jwt_access_token
            NULL, // jwt_refresh_token  
            NULL, // jwt_token_created_at
            NULL, // jwt_token_expires_at
            NULL, // jwt_refresh_expires_at
            NULL, // device_info
            NULL, // last_activity
            NULL  // last_login
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objUser;
        return FALSE;
    }

    public function update_user($objUser) {
        $sql = "update user set username = ?, password = ?, display_name = ?, role_id = ?, reference_id = ?, allow_login = ?, last_login = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $lastLogin = $objUser->last_login == NULL ? NULL : $objUser->last_login->format('Y-m-d H:i:s');
        $updated = $statement->execute(array(
            $objUser->username,
            $objUser->password,
            $objUser->display_name,
            $objUser->role_id,
            $objUser->reference_id,
            $objUser->allow_login,
            $lastLogin,
            $objUser->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated){
            if ($objUser->last_login != NULL)
                $objUser->last_login = $objUser->last_login->format('Y-m-d H:i:s');
            return $objUser;
        }
        return FALSE;
    }
    // update user FCM details only (not other details)
    /**
     * Update user FCM details
     * 'fcm_token' => $fcm_token,    'platform' => $platform,     'fcm_token_updated_at' => date('Y-m-d H:i:s'),     'notification_enabled' => 1
     */
    public function update_user_fcm_details($userId, $fcmToken, $platform, $fcm_token_updated_at, $notification_enabled) {
        $sql = "update user set fcm_token = ?, platform = ?, fcm_token_updated_at = ?, notification_enabled = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $fcmToken,
            $platform,
            $fcm_token_updated_at,
            $notification_enabled,
            $userId
        ));
        $statement = NULL;
        $pdo = NULL;
        return $updated;
    }   

    public function delete_user($id) {
        $sql = "delete from user where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    /**
     * Mark user account for deletion (soft delete)
     * Sets is_deleted flag and deletion_requested_at timestamp
     * User cannot login after this, but data remains for 30-day grace period
     * 
     * @param int $user_id User ID to mark for deletion
     * @param string $reason Optional reason for deletion
     * @return bool Success status
     */
    public function mark_user_for_deletion($user_id, $reason = null) {
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $deletion_requested_at = $dateTime->format('Y-m-d H:i:s');

        $sql = "UPDATE user SET 
                is_deleted = 1, 
                deletion_requested_at = ?, 
                deletion_reason = ?,
                allow_login = 0
                WHERE id = ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($deletion_requested_at, $reason, $user_id));
        
        $statement = NULL;
        $pdo = NULL;
        
        return $updated;
    }

    /**
     * Restore a user account that was marked for deletion
     * Clears soft delete flags and re-enables login
     * 
     * @param int $user_id User ID to restore
     * @return bool Success status
     */
    public function restore_user_account($user_id) {
        $sql = "UPDATE user SET 
                is_deleted = 0, 
                deletion_requested_at = NULL, 
                deletion_reason = NULL,
                allow_login = 1
                WHERE id = ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($user_id));
        
        $statement = NULL;
        $pdo = NULL;
        
        return $updated;
    }

    /**
     * Get all users marked for deletion
     * Used by admin to see accounts pending deletion
     * 
     * @return array Array of user objects marked for deletion
     */
    public function get_users_pending_deletion() {
        $records = array();
        $sql = "SELECT * FROM user WHERE is_deleted = 1 ORDER BY deletion_requested_at DESC";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        while ($row = $statement->fetch()) {
            $objUser = new User_object();
            $objUser->id = $row['id'];
            $objUser->username = $row['username'];
            $objUser->display_name = $row['display_name'];
            $objUser->role_id = $row['role_id'];
            $objUser->deletion_requested_at = $row['deletion_requested_at'];
            $objUser->deletion_reason = $row['deletion_reason'];
            
            // Calculate days until deletion (30 days grace period)
            if ($row['deletion_requested_at'] != NULL) {
                $requestedDate = new DateTime($row['deletion_requested_at']);
                $now = new DateTime();
                $daysElapsed = $now->diff($requestedDate)->days;
                $objUser->days_until_deletion = 30 - $daysElapsed;
            }
            
            $records[] = $objUser;
        }
        
        $statement = NULL;
        $pdo = NULL;
        
        return $records;
    }

    /**
     * Clear all active sessions for a user
     * Used when marking account for deletion
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function clear_user_sessions($user_id) {
        $sql = "UPDATE user_sessions SET is_active = 0, revoked_at = NOW() WHERE user_id = ? AND is_active = 1";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($user_id));
        
        $statement = NULL;
        $pdo = NULL;
        
        return $updated;
    }

    public function get_user_count() {
        $count = 0;
        $sql = "select count(id) as cnt from user";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_user($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select * from user order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from user where $filterString order by $sortBy $sortType limit $offset, $limit";

        log_message('debug', 'User_model::get_paginated_user SQL: ' . $sql);
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row, false);
            $records[] = $objUser;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function is_username_available($username) {
        $pdo = CDatabase::getPdo();
        $sql = "select * from user where username = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($username));
        $rowCount = $statement->rowCount();
        $statement = NULL;
        $pdo = NULL;
        return $rowCount == 0;
    }

    public function update_last_login_and_token($id, $token) {
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $pdo = CDatabase::getPdo();
        $sql = "update user set token = ?, last_login = ? where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($token, $dateTime->format('Y-m-d H:i:s'), $id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function unset_token($id) {
        $pdo = CDatabase::getPdo();
        $sql = "update user set token = null where id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function check_email_exists($email) {
        $sql = "select count(*) as count from user where username = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($email));
        $row = $statement->fetch();
        $statement = NULL;
        $pdo = NULL;
        return $row['count'] > 0;
    }

    // ============================
    // JWT Authentication Methods
    // ============================

    /**
     * Create new JWT session for user
     * 
     * @param int $user_id User ID
     * @param string $access_token JWT access token
     * @param string $refresh_token JWT refresh token
     * @param string $device_info Device information
     * @param string $ip_address IP address
     * @param string $user_agent User agent
     * @param int $access_expire Access token expiration in seconds
     * @param int $refresh_expire Refresh token expiration in seconds
     * @return bool Success status
     */
    public function create_jwt_session($user_id, $access_token, $refresh_token, $device_info = null, $ip_address = null, $user_agent = null, $access_expire = 3600, $refresh_expire = 604800) {
        date_default_timezone_set("Asia/Calcutta");
        $now = new DateTime();
        $expires_at = clone $now;
        $expires_at->add(new DateInterval('PT' . $access_expire . 'S'));
        $refresh_expires_at = clone $now;
        $refresh_expires_at->add(new DateInterval('PT' . $refresh_expire . 'S'));
        
        $pdo = CDatabase::getPdo();
        
        try {
            // Insert into user_sessions table
            $sql = "INSERT INTO user_sessions (user_id, jwt_access_token, jwt_refresh_token, device_info, ip_address, user_agent, created_at, last_activity, expires_at, refresh_expires_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $user_id,
                $access_token,
                $refresh_token,
                $device_info,
                $ip_address,
                $user_agent,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                $expires_at->format('Y-m-d H:i:s'),
                $refresh_expires_at->format('Y-m-d H:i:s')
            ));
            
            // Also update the user table for backward compatibility
            $sql_user = "UPDATE user SET jwt_access_token = ?, jwt_refresh_token = ?, jwt_token_created_at = ?, jwt_token_expires_at = ?, jwt_refresh_expires_at = ?, device_info = ?, last_activity = ? WHERE id = ?";
            $statement_user = $pdo->prepare($sql_user);
            $statement_user->execute(array(
                $access_token,
                $refresh_token,
                $now->format('Y-m-d H:i:s'),
                $expires_at->format('Y-m-d H:i:s'),
                $refresh_expires_at->format('Y-m-d H:i:s'),
                $device_info,
                $now->format('Y-m-d H:i:s'),
                $user_id
            ));
            
            $statement = NULL;
            $statement_user = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            $statement = NULL;
            $pdo = NULL;
            return false;
        }
    }

    /**
     * Get user by JWT access token
     * 
     * @param string $access_token JWT access token
     * @return object|null User object or null if not found
     */
    public function get_user_from_jwt_token($access_token) {
        $objUser = NULL;
        $sql = "SELECT u.*, us.last_activity, us.expires_at, us.device_info as session_device_info 
                FROM user u 
                LEFT JOIN user_sessions us ON u.id = us.user_id AND us.jwt_access_token = ? 
                WHERE (u.jwt_access_token = ? OR us.jwt_access_token = ?) 
                AND (us.is_active = 1 OR us.is_active IS NULL)
                AND (us.expires_at > NOW() OR us.expires_at IS NULL)";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($access_token, $access_token, $access_token));
        
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
            
            // Update last activity
            $this->update_session_activity($access_token);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objUser;
    }

    /**
     * Update session last activity
     * 
     * @param string $access_token JWT access token
     */
    public function update_session_activity($access_token) {
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        
        $pdo = CDatabase::getPdo();
        $sql = "UPDATE user_sessions SET last_activity = ? WHERE jwt_access_token = ? AND is_active = 1";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($dateTime->format('Y-m-d H:i:s'), $access_token));
        
        // Also update user table
        $sql_user = "UPDATE user SET last_activity = ? WHERE jwt_access_token = ?";
        $statement_user = $pdo->prepare($sql_user);
        $statement_user->execute(array($dateTime->format('Y-m-d H:i:s'), $access_token));
        
        $statement = NULL;
        $statement_user = NULL;
        $pdo = NULL;
    }

    /**
     * Revoke JWT session
     * 
     * @param string $access_token JWT access token
     * @param string $reason Revocation reason
     * @return bool Success status
     */
    public function revoke_jwt_session($access_token, $reason = 'User logout') {
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        
        $pdo = CDatabase::getPdo();
        
        try {
            // Deactivate session
            $sql = "UPDATE user_sessions SET is_active = 0, revoked_at = ? WHERE jwt_access_token = ?";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($dateTime->format('Y-m-d H:i:s'), $access_token));
            
            // Clear JWT tokens from user table
            $sql_user = "UPDATE user SET jwt_access_token = NULL, jwt_refresh_token = NULL WHERE jwt_access_token = ?";
            $statement_user = $pdo->prepare($sql_user);
            $statement_user->execute(array($access_token));
            
            $statement = NULL;
            $statement_user = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            $statement = NULL;
            $pdo = NULL;
            return false;
        }
    }

    /**
     * Revoke all user sessions
     * 
     * @param int $user_id User ID
     * @param string $reason Revocation reason
     * @return bool Success status
     */
    public function revoke_all_user_sessions($user_id, $reason = 'Security action') {
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        
        $pdo = CDatabase::getPdo();
        
        try {
            // Deactivate all user sessions
            $sql = "UPDATE user_sessions SET is_active = 0, revoked_at = ? WHERE user_id = ? AND is_active = 1";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($dateTime->format('Y-m-d H:i:s'), $user_id));
            
            // Clear JWT tokens from user table
            $sql_user = "UPDATE user SET jwt_access_token = NULL, jwt_refresh_token = NULL WHERE id = ?";
            $statement_user = $pdo->prepare($sql_user);
            $statement_user->execute(array($user_id));
            
            $statement = NULL;
            $statement_user = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            $statement = NULL;
            $pdo = NULL;
            return false;
        }
    }

    /**
     * Refresh JWT tokens
     * 
     * @param string $refresh_token Current refresh token
     * @param string $new_access_token New access token
     * @param string $new_refresh_token New refresh token
     * @param int $access_expire Access token expiration in seconds
     * @param int $refresh_expire Refresh token expiration in seconds
     * @return bool Success status
     */
    public function refresh_jwt_tokens($refresh_token, $new_access_token, $new_refresh_token, $access_expire = 3600, $refresh_expire = 604800) {
        date_default_timezone_set("Asia/Calcutta");
        $now = new DateTime();
        $expires_at = clone $now;
        $expires_at->add(new DateInterval('PT' . $access_expire . 'S'));
        $refresh_expires_at = clone $now;
        $refresh_expires_at->add(new DateInterval('PT' . $refresh_expire . 'S'));
        
        $pdo = CDatabase::getPdo();
        
        try {
            // Update session with new tokens
            $sql = "UPDATE user_sessions SET jwt_access_token = ?, jwt_refresh_token = ?, last_activity = ?, expires_at = ?, refresh_expires_at = ? WHERE jwt_refresh_token = ? AND is_active = 1 AND refresh_expires_at > NOW()";
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array(
                $new_access_token,
                $new_refresh_token,
                $now->format('Y-m-d H:i:s'),
                $expires_at->format('Y-m-d H:i:s'),
                $refresh_expires_at->format('Y-m-d H:i:s'),
                $refresh_token
            ));
            
            // Also update user table
            $sql_user = "UPDATE user SET jwt_access_token = ?, jwt_refresh_token = ?, jwt_token_created_at = ?, jwt_token_expires_at = ?, jwt_refresh_expires_at = ?, last_activity = ? WHERE jwt_refresh_token = ?";
            $statement_user = $pdo->prepare($sql_user);
            $statement_user->execute(array(
                $new_access_token,
                $new_refresh_token,
                $now->format('Y-m-d H:i:s'),
                $expires_at->format('Y-m-d H:i:s'),
                $refresh_expires_at->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                $refresh_token
            ));
            
            $statement = NULL;
            $statement_user = NULL;
            $pdo = NULL;
            
            return $result;
            
        } catch (Exception $e) {
            $statement = NULL;
            $pdo = NULL;
            return false;
        }
    }

    /**
     * Get all active sessions for user
     * 
     * @param int $user_id User ID
     * @return array Active sessions
     */
    public function get_user_active_sessions($user_id) {
        $sessions = array();
        
        $sql = "SELECT id, device_info, ip_address, created_at, last_activity, expires_at FROM user_sessions WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() ORDER BY last_activity DESC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        
        while ($row = $statement->fetch()) {
            $session = array(
                'id' => $row['id'],
                'device_info' => $row['device_info'],
                'ip_address' => $row['ip_address'],
                'created_at' => $row['created_at'],
                'last_activity' => $row['last_activity'],
                'expires_at' => $row['expires_at']
            );
            $sessions[] = $session;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $sessions;
    }

    /**
     * Clean up expired sessions
     * 
     * @return int Number of cleaned sessions
     */
    public function cleanup_expired_sessions() {
        $pdo = CDatabase::getPdo();
        
        // Deactivate expired sessions
        $sql = "UPDATE user_sessions SET is_active = 0, revoked_at = NOW() WHERE expires_at < NOW() AND is_active = 1";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $affected_rows = $statement->rowCount();
        
        $statement = NULL;
        $pdo = NULL;
        
        return $affected_rows;
    }

    // ============================
    // Google OAuth Methods
    // ============================

    /**
     * Check if Google ID already exists in the database
     * @param string $googleId The Google OAuth user ID
     * @return bool True if Google ID exists, false otherwise
     */
    public function check_google_id_exists($googleId) {
        $sql = "select count(*) as count from user where google_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($googleId));
        $row = $statement->fetch();
        $statement = NULL;
        $pdo = NULL;
        return $row['count'] > 0;
    }

    /**
     * Get user by Google ID
     * @param string $googleId The Google OAuth user ID
     * @return User_object|null User object if found, null otherwise
     */
    public function get_user_by_google_id($googleId) {
        $objUser = NULL;
        $sql = "select * from user where google_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($googleId));
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUser;
    }

    /**
     * Update Google OAuth tokens for a user
     * @param int $userId User ID
     * @param string $accessToken Google access token (will be encrypted)
     * @param string $refreshToken Google refresh token (will be encrypted, optional)
     * @param int $expiresIn Token expiration time in seconds
     * @return bool True if updated successfully, false otherwise
     */
    public function update_google_tokens($userId, $accessToken, $refreshToken = null, $expiresIn = 3600) {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        $encryptedAccessToken = base64_encode($accessToken); // Simple encoding - use proper encryption in production
        $encryptedRefreshToken = $refreshToken ? base64_encode($refreshToken) : null;
        
        $sql = "UPDATE user SET 
                google_access_token = ?, 
                google_refresh_token = ?, 
                google_token_expires_at = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $encryptedAccessToken,
            $encryptedRefreshToken,
            $expiresAt,
            $userId
        ));
        $statement = NULL;
        $pdo = NULL;
        
        return $updated;
    }

    /**
     * Get user by email address
     * @param string $email User's email address
     * @return object|null User object if found, null if not found
     */
    public function get_user_by_email($email) {
        $objUser = NULL;
        $sql = "select * from user where email = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($email));
        
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objUser;
    }

    /**
     * Get user by user ID
     * @param int $userId User's ID
     * @return object|null User object if found, null if not found
     */
    public function get_user_by_id($userId) {
        $objUser = NULL;
        $sql = "select * from user where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($userId));
        
        if ($row = $statement->fetch()) {
            $objUser = $this->create_user_from_row($row);
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objUser;
    }

    /**
     * Verify Google OAuth token
     * @param string $idToken Google ID token from OAuth response
     * @return array|false User data from Google if valid, false if invalid
     */
    public function verify_google_token($idToken) {
        try {
            // For production, you should verify the token with Google's servers
            // For now, we'll decode and validate the JWT token structure
            
            if (empty($idToken)) {
                return false;
            }
            
            // Basic JWT structure validation
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return false;
            }
            
            // Decode the payload (middle part of JWT)
            $payload = $parts[1];
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            $payload = base64_decode($payload);
            
            if (!$payload) {
                return false;
            }
            
            $userInfo = json_decode($payload, true);
            
            if (!$userInfo || !isset($userInfo['sub']) || !isset($userInfo['email'])) {
                return false;
            }
            
            // Validate token issuer (should be Google)
            if (!isset($userInfo['iss']) || 
                ($userInfo['iss'] !== 'https://accounts.google.com' && 
                 $userInfo['iss'] !== 'accounts.google.com')) {
                return false;
            }
            
            // Check if token is expired
            if (isset($userInfo['exp']) && $userInfo['exp'] < time()) {
                return false;
            }
            
            // Return validated user information
            return array(
                'id' => $userInfo['sub'],
                'email' => $userInfo['email'],
                'name' => isset($userInfo['name']) ? $userInfo['name'] : '',
                'given_name' => isset($userInfo['given_name']) ? $userInfo['given_name'] : '',
                'family_name' => isset($userInfo['family_name']) ? $userInfo['family_name'] : '',
                'picture' => isset($userInfo['picture']) ? $userInfo['picture'] : '',
                'email_verified' => isset($userInfo['email_verified']) ? $userInfo['email_verified'] : false
            );
            
        } catch (Exception $e) {
            // Log error for debugging
            log_message('error', 'Google token verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create user with Google OAuth data
     * @param array $userData User data array containing Google OAuth information
     * @return int|false User ID if successful, false if failed
     */
    public function create_google_user($userData) {
        try {
            $sql = "INSERT INTO user (
                        google_id, 
                        username,
                        display_name,
                        auth_provider, 
                        profile_picture_url, 
                        email_verified,
                        allow_login,
                        role_id,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $inserted = $statement->execute(array(
                $userData['google_id'],
                $userData['email'],
                $userData['display_name'],
                $userData['auth_provider'],
                isset($userData['profile_picture_url']) ? $userData['profile_picture_url'] : null,
                isset($userData['email_verified']) ? $userData['email_verified'] : 0,
                isset($userData['allow_login']) ? $userData['allow_login'] : 1,
                isset($userData['role_id']) ? $userData['role_id'] : 5 // Default to student role
            ));
            
            $userId = false;
            if ($inserted) {
                $userId = $pdo->lastInsertId();
            }
            
            $statement = NULL;
            $pdo = NULL;
            
            return $userId;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to create Google user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user with reference_id and token after Google OAuth signup
     * @param User_object $objUser User object with id, reference_id, and token
     * @return bool True if successful, false if failed
     */
    public function update_user_reference_and_token($objUser) {
        try {
            $sql = "UPDATE user SET 
                        reference_id = ?, 
                        token = ?, 
                        display_name = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $updated = $statement->execute(array(
                $objUser->reference_id,
                $objUser->token,
                $objUser->display_name,
                $objUser->id
            ));
            
            $statement = NULL;
            $pdo = NULL;
            
            return $updated;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to update user reference and token: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Get users with valid FCM tokens for broadcasting
     */
    public function get_users_with_fcm_tokens() {
        $users = [];
        try {
            $sql = "SELECT id, username, fcm_token, platform FROM user WHERE fcm_token IS NOT NULL AND fcm_token != ''";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            while ($row = $statement->fetch()) {
                $users[] = (object) [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'fcm_token' => $row['fcm_token'],
                    'platform' => $row['platform']
                ];
            }
            $statement = NULL;
        } catch (Exception $e) {
            log_message('error', 'Error getting users with FCM tokens: ' . $e->getMessage());
        }
        return $users;
    }

    /**
     * Get specific user with FCM token
     */
    public function get_user_with_fcm_token($user_id = null) {
        $user = null;
        try {
            if ($user_id) {
                $sql = "SELECT id, username, fcm_token, platform FROM user WHERE id = ? AND fcm_token IS NOT NULL AND fcm_token != '' LIMIT 1";
                $params = [$user_id];
            } else {
                $sql = "SELECT id, username, fcm_token, platform FROM user WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 1";
                $params = [];
            }
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            
            if ($row = $statement->fetch()) {
                $user = (object) [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'fcm_token' => $row['fcm_token'],
                    'platform' => $row['platform']
                ];
            }
            $statement = NULL;
        } catch (Exception $e) {
            log_message('error', 'Error getting user with FCM token: ' . $e->getMessage());
        }
        return $user;
    }

    /**
     * Update user's FCM token
     */
    public function update_fcm_token($user_id, $fcm_token, $platform = null) {
        try {
            if ($platform) {
                $sql = "UPDATE user SET fcm_token = ?, fcm_token_updated_at = ?, platform = ? WHERE id = ?";
                $params = [$fcm_token, date('Y-m-d H:i:s'), $platform, $user_id];
            } else {
                $sql = "UPDATE user SET fcm_token = ?, fcm_token_updated_at = ? WHERE id = ?";
                $params = [$fcm_token, date('Y-m-d H:i:s'), $user_id];
            }
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $statement = NULL;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Error updating FCM token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear user's FCM token (on logout)
     */
    public function clear_fcm_token($user_id) {
        try {
            $sql = "UPDATE user SET fcm_token = NULL, fcm_token_updated_at = ? WHERE id = ?";
            $params = [date('Y-m-d H:i:s'), $user_id];
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $statement = NULL;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Error clearing FCM token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's subscription features with limits and usage
     * Returns all features based on active subscription or free plan
     * 
     * @param int $user_id User ID
     * @return array Subscription features data including limits and usage
     */
    public function get_user_subscription_features($user_id) {
        try {
            $CI = & get_instance();
            $CI->load->model('subscription/user_subscription_model');
            $CI->load->model('subscription/subscription_plan_model');
            $CI->load->model('quiz/quiz_model');
            
            // Try to get active subscription
            $subscription = $CI->user_subscription_model->get_user_active_subscription($user_id);
            
            // If no active subscription, fall back to free plan
            if (!$subscription) {
                $free_plan = $CI->subscription_plan_model->get_subscription_plan_by_key('free');
                if (!$free_plan) {
                    throw new Exception("Free plan not found in database");
                }
                
                $result = array(
                    'has_active_subscription' => false,
                    'subscription_plan_key' => 'free',
                    'subscription_plan_name' => $free_plan->plan_name,
                    'subscription_status' => 'free',
                    'expires_at' => null,
                    'plan_id' => $free_plan->id,
                    'plan_color' => $free_plan->plan_color
                );
                
                $plan_id = $free_plan->id;
            } else {
                $result = array(
                    'has_active_subscription' => true,
                    'subscription_id' => $subscription->id,
                    'subscription_plan_key' => $subscription->plan_key,
                    'subscription_plan_name' => $subscription->plan_name,
                    'subscription_status' => $subscription->subscription_status,
                    'starts_at' => $subscription->starts_at,
                    'expires_at' => $subscription->expires_at,
                    'billing_cycle' => $subscription->billing_cycle,
                    'auto_renew' => $subscription->auto_renew,
                    'plan_id' => $subscription->plan_id,
                    'plan_color' => $subscription->plan_color
                );
                
                $plan_id = $subscription->plan_id;
            }
            
            // Get all features for this plan
            $plan_features = $CI->subscription_plan_model->get_plan_features($plan_id);
            
            // Build features array
            $features = array();
            
            foreach ($plan_features as $feature) {
                $feature_key = $feature['feature_key'];
                $feature_type = $feature['feature_type'];
                $feature_limit = $feature['feature_limit'];
                $is_enabled = $feature['is_enabled'];
                
                // Initialize feature data
                $feature_data = array(
                    'feature_name' => $feature['feature_name'],
                    'feature_description' => $feature['feature_description'],
                    'feature_type' => $feature_type,
                    'reset_cycle' => $feature['reset_cycle']
                );
                
                // Handle based on feature type
                if ($feature_type === 'boolean') {
                    // Boolean feature - just enabled/disabled
                    $feature_data['enabled'] = $is_enabled ? true : false;
                    
                } else if ($feature_type === 'quota' || $feature_type === 'credits') {
                    // Quota/Credits feature - has limits and usage
                    
                    // Determine limit (NULL = unlimited, 0 with disabled = disabled, positive = limit)
                    if (!$is_enabled) {
                        $limit = 0;
                        $unlimited = false;
                    } else if ($feature_limit === null) {
                        $limit = -1;  // -1 represents unlimited
                        $unlimited = true;
                    } else {
                        $limit = intval($feature_limit);
                        $unlimited = false;
                    }
                    
                    // Get current usage based on feature
                    $current_usage = 0;
                    if ($feature_key === 'custom_quiz') {
                        $current_usage = $CI->quiz_model->get_custom_quiz_count_by_user($user_id);
                    }
                    // For other features (pyqs, mock_tests, ai_mentor, etc.), return 0 for now
                    // Will be implemented when those features are tracked
                    
                    // Calculate remaining
                    if ($unlimited) {
                        $remaining = -1;  // -1 represents unlimited
                    } else if ($limit == 0) {
                        $remaining = 0;
                    } else {
                        $remaining = max(0, $limit - $current_usage);
                    }
                    
                    // Check if quota exceeded
                    $quota_exceeded = (!$unlimited && $limit > 0 && $current_usage >= $limit);
                    
                    $feature_data['enabled'] = $is_enabled ? true : false;
                    $feature_data['current_usage'] = $current_usage;
                    $feature_data['limit'] = $limit;
                    $feature_data['remaining'] = $remaining;
                    $feature_data['quota_exceeded'] = $quota_exceeded;
                    $feature_data['unlimited'] = $unlimited;
                }
                
                $features[$feature_key] = $feature_data;
            }
            
            $result['features'] = $features;
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Error getting subscription features for user {$user_id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update mobile verification status
     * 
     * @param int $user_id User ID
     * @param string $mobile_number Mobile number
     * @param bool $verified Verification status
     * @return bool Success status
     */
    public function update_mobile_verification($user_id, $mobile_number, $verified = true) {
        try {
            $pdo = CDatabase::getPdo();
            
            if ($verified) {
                $sql = "UPDATE user SET mobile_number = ?, mobile_verified = 1, mobile_verified_at = ? WHERE id = ?";
                $statement = $pdo->prepare($sql);
                $statement->execute(array($mobile_number, date('Y-m-d H:i:s'), $user_id));
            } else {
                $sql = "UPDATE user SET mobile_number = ?, mobile_verified = 0 WHERE id = ?";
                $statement = $pdo->prepare($sql);
                $statement->execute(array($mobile_number, $user_id));
            }

            $affected_rows = $statement->rowCount();
            $statement = NULL;
            $pdo = NULL;

            return $affected_rows > 0;
        } catch (Exception $e) {
            log_message('error', 'Failed to update mobile verification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mobile verification status for user
     * 
     * @param int $user_id User ID
     * @return array Verification status
     */
    public function get_mobile_verification_status($user_id) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT mobile_number, mobile_verified, mobile_verified_at FROM user WHERE id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $row = $statement->fetch(PDO::FETCH_OBJ);
        $statement = NULL;
        $pdo = NULL;

        if ($row) {
            return [
                'mobile_number' => $row->mobile_number,
                'mobile_verified' => (bool)$row->mobile_verified,
                'mobile_verified_at' => $row->mobile_verified_at
            ];
        }

        return [
            'mobile_number' => null,
            'mobile_verified' => false,
            'mobile_verified_at' => null
        ];
    }

    /**
     * Check if user's mobile is verified
     * 
     * @param int $user_id User ID
     * @return bool True if verified
     */
    public function is_mobile_verified($user_id) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT mobile_verified FROM user WHERE id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $row = $statement->fetch(PDO::FETCH_OBJ);
        $statement = NULL;
        $pdo = NULL;

        return $row ? (bool)$row->mobile_verified : false;
    }

    /**
     * Update mobile number (without verification)
     * 
     * @param int $user_id User ID
     * @param string $mobile_number Mobile number
     * @return bool Success status
     */
    public function update_mobile_number($user_id, $mobile_number) {
        try {
            $pdo = CDatabase::getPdo();
            $sql = "UPDATE user SET mobile_number = ?, mobile_verified = 0, mobile_verified_at = NULL WHERE id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($mobile_number, $user_id));
            $affected_rows = $statement->rowCount();
            $statement = NULL;
            $pdo = NULL;

            return $affected_rows > 0;
        } catch (Exception $e) {
            log_message('error', 'Failed to update mobile number: ' . $e->getMessage());
            return false;
        }
    }

}
?>