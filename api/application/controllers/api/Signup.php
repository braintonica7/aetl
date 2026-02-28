<?php

/*
 * Signup API Controller
 */

/**
 * Description of Signup
 *
 * @author WiziAI
 */
class Signup extends API_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function signup_post() {
        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request['firstName']) || 
            empty($request['email']) || empty($request['password'])) {
            $response = $this->get_failed_response(NULL, "All required fields must be filled!");
            $this->set_output($response);
            return;
        }

        // Validate email format
        if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            $response = $this->get_failed_response(NULL, "Invalid email format!");
            $this->set_output($response);
            return;
        }

        // Validate password length
        if (strlen($request['password']) < 8) {
            $response = $this->get_failed_response(NULL, "Password must be at least 8 characters long!");
            $this->set_output($response);
            return;
        }

        $this->load->model('user/user_model');
        $this->load->model('scholar/scholar_model');
        $this->load->model('user_point/user_point_model');

        // Check if email already exists
        if ($this->user_model->check_email_exists($request['email'])) {
            $response = $this->get_failed_response(NULL, "Email already exists!");
            $this->set_output($response);
            return;
        }

        try {
            // Create scholar record first
            $objScholar = new Scholar_object();
            $objScholar->name = $request['firstName'] . ' ' . $request['lastName'];
            $objScholar->alert_mobile_no = $request['phone'];
            $objScholar->scholar_no = $this->generate_scholar_no();
            $objScholar->session = date('Y'); // Current year as session
            $objScholar->exam = isset($request['examPreference']) ? $request['examPreference'] : '';
            $objScholar->grade = isset($request['grade']) ? $request['grade'] : '';
            $objScholar->is_active = 1;

            $addedScholar = $this->scholar_model->add_scholar($objScholar);
            
            if ($addedScholar === FALSE) {
                $response = $this->get_failed_response(NULL, "Failed to create student profile!");
                $this->set_output($response);
                return;
            }

            // Create user record
            $objUser = new User_object();
            $objUser->username = $request['email'];
            $objUser->password = password_hash($request['password'], PASSWORD_DEFAULT);
            $objUser->display_name = $request['firstName'] . ' ' . $request['lastName'];
            $objUser->role_id = 5; // 5 is the role id for student
            $objUser->reference_id = $addedScholar->id;
            $objUser->allow_login = 1;
            $objUser->token = $this->generate_token();

            $addedUser = $this->user_model->add_user($objUser);

            if ($addedUser === FALSE) {
                // Rollback scholar creation if user creation fails
                $this->scholar_model->delete_scholar($addedScholar->id);
                $response = $this->get_failed_response(NULL, "Failed to create user account!");
                $this->set_output($response);
                return;
            }

            // Award signup bonus points (configurable amount)
            $signup_bonus_points = $this->config->item('points_bonus_signup') ?: 120; // Default to 120 if config not found
            $system_admin_id = 1; // System admin ID for automated bonuses
            $bonus_result = $this->user_point_model->admin_adjust_points(
                $addedUser->id, 
                $signup_bonus_points, 
                $system_admin_id,
                'Welcome bonus for new account signup'
            );

            // Log the bonus award (success or failure)
            if ($bonus_result && $bonus_result['success']) {
                log_message('info', "Signup bonus awarded: {$signup_bonus_points} points to user {$addedUser->id}");
            } else {
                log_message('error', "Failed to award signup bonus to user {$addedUser->id}: " . 
                    (isset($bonus_result['error']) ? $bonus_result['error'] : 'Unknown error'));
            }

            // ✅ Generate JWT tokens for automatic login after signup
            $this->load->library('JWT_Auth');
            
            // Get device and IP information
            $device_info = isset($request['device_info']) ? $request['device_info'] : $this->get_device_info();
            $ip_address = $this->input->ip_address();
            $user_agent = $this->input->user_agent();
            
            // Create JWT payload
            $payload = $this->jwt_auth->create_user_payload($addedUser, $device_info);
            
            // Generate JWT tokens
            $jwt_access_token = $this->jwt_auth->generate_access_token($payload);
            $refresh_token = $this->jwt_auth->generate_refresh_token($payload);
            
            // Get token expiration times
            $access_expire = $this->jwt_auth->get_token_expire_time('access');
            $refresh_expire = $this->jwt_auth->get_token_expire_time('refresh');
            
            // Store session in database
            $session_created = $this->user_model->create_jwt_session(
                $addedUser->id,
                $jwt_access_token,
                $refresh_token,
                $device_info,
                $ip_address,
                $user_agent,
                $access_expire,
                $refresh_expire
            );
            
            if (!$session_created) {
                log_message('error', "Failed to create JWT session for signup user {$addedUser->id}");
                // Continue anyway - don't fail the signup for this
            }

            // Clean sensitive data
            $addedUser->password = '****';
            $addedUser->token = NULL; // Remove old token

            // Prepare response data with JWT tokens
            $userData = array(
                'user' => array(
                    'id' => $addedUser->id,
                    'username' => $addedUser->username,
                    'display_name' => $addedUser->display_name,
                    'role_id' => $addedUser->role_id
                ),
                'access_token' => $jwt_access_token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => $access_expire,
                'refresh_expires_in' => $refresh_expire,
                'mobile_verified' => false, // New accounts always require mobile verification
                'requires_mobile_verification' => true,
                'scholar' => array(
                    'id' => $addedScholar->id,
                    'name' => $addedScholar->name,
                    'scholar_no' => $addedScholar->scholar_no,
                    'phone' => $addedScholar->alert_mobile_no,
                    'exam' => $addedScholar->exam,
                    'grade' => $addedScholar->grade
                ),
                'signup_bonus' => array(
                    'points_awarded' => $signup_bonus_points,
                    'success' => ($bonus_result && $bonus_result['success']),
                    'message' => ($bonus_result && $bonus_result['success']) 
                        ? "Welcome! You've received {$signup_bonus_points} bonus points!" 
                        : "Welcome! Points will be credited shortly."
                )
            );

            $response = $this->get_success_response($userData, "Account created successfully!");
            $this->set_output($response);

        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "An error occurred during registration: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Google OAuth Signup Endpoint
     * Creates a new user account using Google OAuth data
     */
    public function google_signup_post() {
        $request = $this->get_request();
        
        // Validate required Google OAuth fields
        if (empty($request['googleId']) || empty($request['email']) || 
            empty($request['firstName']) || empty($request['accessToken'])) {
            $response = $this->get_failed_response(NULL, "Missing required Google OAuth data!");
            $this->set_output($response);
            return;
        }

        // Validate email format
        if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            $response = $this->get_failed_response(NULL, "Invalid email format!");
            $this->set_output($response);
            return;
        }

        $this->load->model('user/user_model');
        $this->load->model('scholar/scholar_model');
        $this->load->model('user_point/user_point_model');

        // Check if email already exists (regular or Google account)
        if ($this->user_model->check_email_exists($request['email'])) {
            $response = $this->get_failed_response(NULL, "An account with this email already exists!");
            $this->set_output($response);
            return;
        }

        // Check if Google ID already exists
        if ($this->user_model->check_google_id_exists($request['googleId'])) {
            $response = $this->get_failed_response(NULL, "This Google account is already registered!");
            $this->set_output($response);
            return;
        }

        try {
            // Verify Google access token (optional but recommended for security)
            $googleUserData = $this->verify_google_token($request['accessToken']);
            if (!$googleUserData || $googleUserData['sub'] !== $request['googleId']) {
                $response = $this->get_failed_response(NULL, "Invalid Google authentication token!");
                $this->set_output($response);
                return;
            }

            // Create scholar record first
            $objScholar = new Scholar_object();
            $objScholar->name = trim($request['firstName'] . ' ' . ($request['lastName'] ?? ''));
            $objScholar->alert_mobile_no = ''; // Will be updated later in profile
            $objScholar->scholar_no = $this->generate_scholar_no();
            $objScholar->session = date('Y'); // Current year as session
            $objScholar->exam = ''; // Default empty, user can update later
            $objScholar->grade = ''; // Default empty, user can update later

            $addedScholar = $this->scholar_model->add_scholar($objScholar);

            if ($addedScholar === FALSE) {
                $response = $this->get_failed_response(NULL, "Failed to create scholar record!");
                $this->set_output($response);
                return;
            }

            // Create user record with Google OAuth data
            $userData = array(
                'google_id' => $request['googleId'],
                'email' => $request['email'],
                'display_name' => trim($request['firstName'] . ' ' . ($request['lastName'] ?? '')),
                'auth_provider' => 'google',
                'profile_picture_url' => $request['profilePicture'] ?? null,
                'email_verified' => isset($request['emailVerified']) ? (int)$request['emailVerified'] : 1,
                'allow_login' => 1,
                'role_id' => 5 // Student role
            );

            $userId = $this->user_model->create_google_user($userData);

            if ($userId === FALSE) {
                // Rollback scholar creation if user creation fails
                $this->scholar_model->delete_scholar($addedScholar->id);
                $response = $this->get_failed_response(NULL, "Failed to create user account!");
                $this->set_output($response);
                return;
            }

            // Now we need to update the user record to add the reference_id and token
            // since create_google_user doesn't handle these fields
            $objUser = new User_object();
            $objUser->id = $userId;
            $objUser->username = $request['email'];
            $objUser->display_name = trim($request['firstName'] . ' ' . ($request['lastName'] ?? ''));
            $objUser->reference_id = $addedScholar->id;
            $objUser->token = $this->generate_token();
            
            // Update the user record with additional fields
            $updated = $this->user_model->update_user_reference_and_token($objUser);

            if ($updated === FALSE) {
                // Rollback both user and scholar creation if update fails
                $this->user_model->delete_user($userId);
                $this->scholar_model->delete_scholar($addedScholar->id);
                $response = $this->get_failed_response(NULL, "Failed to complete user setup!");
                $this->set_output($response);
                return;
            }

            // Get the complete user object for response
            $addedUser = $this->user_model->get_user_by_id($userId);

            // ✅ Generate JWT tokens for automatic login
            $this->load->library('JWT_Auth');
            
            // Get device and IP information
            $device_info = isset($request['device_info']) ? $request['device_info'] : $this->get_device_info();
            $ip_address = $this->input->ip_address();
            $user_agent = $this->input->user_agent();
            
            // Create JWT payload
            $payload = $this->jwt_auth->create_user_payload($addedUser, $device_info);
            
            // Generate JWT tokens
            $jwt_access_token = $this->jwt_auth->generate_access_token($payload);
            $refresh_token = $this->jwt_auth->generate_refresh_token($payload);
            
            // Get token expiration times
            $access_expire = $this->jwt_auth->get_token_expire_time('access');
            $refresh_expire = $this->jwt_auth->get_token_expire_time('refresh');
            
            // Store session in database
            $session_created = $this->user_model->create_jwt_session(
                $userId,
                $jwt_access_token,
                $refresh_token,
                $device_info,
                $ip_address,
                $user_agent,
                $access_expire,
                $refresh_expire
            );
            
            if (!$session_created) {
                log_message('error', "Failed to create JWT session for Google signup user {$userId}");
                // Continue anyway - don't fail the signup for this
            }

            if ($addedUser === FALSE) {
                // Rollback scholar creation if user creation fails
                $this->scholar_model->delete_scholar($addedScholar->id);
                $response = $this->get_failed_response(NULL, "Failed to create user account!");
                $this->set_output($response);
                return;
            }

            // Award signup bonus points (same as regular signup)
            $signup_bonus_points = $this->config->item('points_bonus_signup') ?: 120;
            $system_admin_id = 1;
            $bonus_result = $this->user_point_model->admin_adjust_points(
                $userId, 
                $signup_bonus_points, 
                $system_admin_id,
                'Welcome bonus for Google account signup'
            );

            // Log the bonus award
            if ($bonus_result && $bonus_result['success']) {
                log_message('info', "Google signup bonus awarded: {$signup_bonus_points} points to user {$userId}");
            } else {
                log_message('error', "Failed to award Google signup bonus to user {$userId}: " . 
                    (isset($bonus_result['error']) ? $bonus_result['error'] : 'Unknown error'));
            }

            // Prepare response data with JWT tokens for automatic login
            $userData = array(
                'user' => array(
                    'id' => $addedUser->id,
                    'username' => $addedUser->username,
                    'display_name' => $addedUser->display_name,
                    'role_id' => $addedUser->role_id,
                    'auth_provider' => 'google',
                    'profile_picture_url' => $addedUser->profile_picture_url,
                    'email_verified' => $addedUser->email_verified,
                    'first_name' => $addedUser->first_name,
                    'last_name' => $addedUser->last_name
                ),
                'access_token' => $jwt_access_token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => $access_expire,
                'refresh_expires_in' => $refresh_expire,
                'mobile_verified' => false, // New accounts always require mobile verification
                'requires_mobile_verification' => true,
                'scholar' => array(
                    'id' => $addedScholar->id,
                    'name' => $addedScholar->name,
                    'scholar_no' => $addedScholar->scholar_no,
                    'phone' => $addedScholar->alert_mobile_no,
                    'exam' => $addedScholar->exam,
                    'grade' => $addedScholar->grade
                ),
                'signup_bonus' => array(
                    'points_awarded' => $signup_bonus_points,
                    'success' => ($bonus_result && $bonus_result['success']),
                    'message' => ($bonus_result && $bonus_result['success']) 
                        ? "Welcome! You've received {$signup_bonus_points} bonus points!" 
                        : "Welcome! Points will be credited shortly."
                ),
                'next_steps' => array(
                    'complete_profile' => true,
                    'message' => 'Please complete your profile with phone number, exam preference, and grade.'
                )
            );

            $response = $this->get_success_response($userData, "Google account created successfully!");
            $this->set_output($response);

        } catch (Exception $e) {
            // Cleanup if error occurs
            if (isset($userId) && $userId) {
                $this->user_model->delete_user($userId);
            }
            if (isset($addedScholar->id)) {
                $this->scholar_model->delete_scholar($addedScholar->id);
            }
            
            $response = $this->get_failed_response(NULL, "An error occurred during Google registration: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Verify Google access token with Google's servers
     * @param string $accessToken The Google access token
     * @return array|false Google user data or false if invalid
     */
    private function verify_google_token($accessToken) {
        try {
            // Use Google's token verification endpoint
            $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($accessToken);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $userData = json_decode($response, true);
                if ($userData && isset($userData['sub'])) {
                    return $userData;
                }
            }
            
            return false;
        } catch (Exception $e) {
            log_message('error', 'Google token verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt sensitive token data
     * @param string $token The token to encrypt
     * @return string Encrypted token
     */
    private function encrypt_token($token) {
        // Simple base64 encoding for now - in production, use proper encryption
        // You should implement proper AES encryption here
        return base64_encode($token);
    }

    /**
     * Get device information from user agent
     * @return string Device information
     */
    private function get_device_info() {
        $user_agent = $this->input->user_agent();
        $device_info = "Unknown Device";
        
        if (strpos($user_agent, 'Mobile') !== false) {
            $device_info = "Mobile Device";
        } elseif (strpos($user_agent, 'Tablet') !== false) {
            $device_info = "Tablet";
        } elseif (strpos($user_agent, 'Desktop') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Firefox') !== false) {
            $device_info = "Desktop Browser";
        }
        
        return $device_info;
    }

    private function generate_scholar_no() {
        // Generate unique scholar number
        $year = date('Y');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return 'STU' . $year . $random;
    }

    private function generate_token() {
        // Generate unique token for authentication
        return bin2hex(random_bytes(32));
    }
}
