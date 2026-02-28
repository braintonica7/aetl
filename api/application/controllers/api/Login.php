<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Login
 *
 * @author Jawahar
 */
class Login extends API_Controller {

    //put your code here

    public function __constructor() {
        parent::__construct();
    }

    public function logout_get() {
        $objUser = $this->get_logged_user();
        if ($objUser != NULL) {
            $this->load->model('user/user_model');
            $this->user_model->unset_token($objUser->id);
            $response = $this->get_success_response(NULL, "User logged off successfull...!");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "No logged user found...!");
            $this->set_output($response);
        }
    }

    public function login_post() {
        $request = $this->get_request();
        $this->load->model('user/user_model');
        $objUser = $this->user_model->get_user_from_username_password($request['username'], $request['password']);
        if ($objUser == NULL || $objUser->id == 0) {
            $response = $this->get_failed_response(NULL, "Invalid username or password...!");
            $this->set_output($response);
        } else {
            if ($objUser->allow_login == 0) {
                $response = $this->get_failed_response(NULL, "Your login permission has been revoked...!");
                $this->set_output($response);
            } else {
                // Check if account is marked for deletion (soft delete)
                $userFullDetails = $this->user_model->get_user($objUser->id);
                if ($userFullDetails && isset($userFullDetails->is_deleted) && $userFullDetails->is_deleted == 1) {
                    $response = $this->get_failed_response(NULL, "Your account is scheduled for deletion. Please contact support to restore your account.");
                    $this->set_output($response);
                } else {
                    $objUser->password = '****';
                    $objUser->role_id = '@';
                    $response = $this->get_success_response($objUser, "User authentication successfull...!");
                    $this->set_output($response);
                }
            }
        }
    }

    public function admin_login_post() {
        $request = $this->get_request();
        $this->load->model('user/user_model');
        $objUser = $this->user_model->get_user_from_username_password($request['username'], $request['password']);
        
        if ($objUser == NULL || $objUser->id == 0) {
            $response = $this->get_failed_response(NULL, "Invalid username or password...!");
            $this->set_output($response);
        } else {
            if ($objUser->allow_login == 0) {
                $response = $this->get_failed_response(NULL, "Your login permission has been revoked...!");
                $this->set_output($response);
            } else {
                // Check if user has admin role (role_id: 1, 2, 3, or 4)
                $allowedRoles = [1, 2, 3, 4];
                if (!in_array($objUser->role_id, $allowedRoles)) {
                    $response = $this->get_failed_response(NULL, "Access denied. Admin privileges required...!");
                    $this->set_output($response);
                } else {
                    // Hide sensitive information
                    $objUser->password = '****';
                    $response = $this->get_success_response($objUser, "Admin authentication successful...!");
                    $this->set_output($response);
                }
            }
        }
    }

    // ============================
    // JWT Authentication Endpoints
    // ============================

    /**
     * JWT-based login endpoint
     * Generates access and refresh tokens for secure authentication
     */
    public function jwt_login_post() {
        $request = $this->get_request();
        
        // Log the start of JWT login attempt
        log_message('debug', 'JWT Login Debug - Login attempt started for username: ' . (isset($request['username']) ? $request['username'] : 'NOT_PROVIDED'));
        
        // Validate required fields
        if (!isset($request['username']) || !isset($request['password'])) {
            log_message('error', 'JWT Login Error - Missing username or password in request');
            $response = $this->get_failed_response(NULL, "Username and password are required");
            $this->set_output($response);
            return;
        }
        
        $this->load->model('user/user_model');
        $this->load->library('JWT_Auth');
        
        log_message('debug', 'JWT Login Debug - Models and libraries loaded, attempting user authentication');
        
        $objUser = $this->user_model->get_user_from_username_password($request['username'], $request['password']);
        
        if ($objUser == NULL || $objUser->id == 0) {
            log_message('debug', 'JWT Login Debug - Authentication failed for username: ' . $request['username']);
            $response = $this->get_failed_response(NULL, "Invalid username or password");
            $this->set_output($response);
            return;
        }
        
        log_message('debug', 'JWT Login Debug - User authenticated successfully, user_id: ' . $objUser->id);
        
        if ($objUser->allow_login == 0) {
            log_message('debug', 'JWT Login Debug - Login permission revoked for user_id: ' . $objUser->id);
            $response = $this->get_failed_response(NULL, "Your login permission has been revoked");
            $this->set_output($response);
            return;
        }

        // Check if account is marked for deletion (soft delete)
        log_message('debug', 'JWT Login Debug - Checking soft delete status for user_id: ' . $objUser->id);
        $userFullDetails = $this->user_model->get_user($objUser->id);
        if ($userFullDetails && isset($userFullDetails->is_deleted) && $userFullDetails->is_deleted == 1) {
            log_message('debug', 'JWT Login Debug - Account marked for deletion, user_id: ' . $objUser->id);
            $response = $this->get_failed_response(NULL, "Your account is scheduled for deletion. Please contact support to restore your account.");
            $this->set_output($response);
            return;
        }
        
        log_message('debug', 'JWT Login Debug - All validation checks passed for user_id: ' . $objUser->id);
        
        // Get device and IP information
        $device_info = isset($request['device_info']) ? $request['device_info'] : $this->get_device_info();
        $ip_address = $this->input->ip_address();
        $user_agent = $this->input->user_agent();
        
        log_message('debug', 'JWT Login Debug - Device info gathered: ' . $device_info . ', IP: ' . $ip_address);
        
        // Create JWT payload
        try {
            $payload = $this->jwt_auth->create_user_payload($objUser, $device_info);
            log_message('debug', 'JWT Login Debug - JWT payload created successfully for user_id: ' . $objUser->id);
        } catch (Exception $e) {
            log_message('error', 'JWT Login Error - Failed to create JWT payload: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to create authentication payload. Please try again.");
            $this->set_output($response);
            return;
        }
        
        // Generate tokens
        try {
            $access_token = $this->jwt_auth->generate_access_token($payload);
            $refresh_token = $this->jwt_auth->generate_refresh_token($payload);
            log_message('debug', 'JWT Login Debug - Tokens generated successfully for user_id: ' . $objUser->id);
        } catch (Exception $e) {
            log_message('error', 'JWT Login Error - Failed to generate tokens: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to generate authentication tokens. Please try again.");
            $this->set_output($response);
            return;
        }
        
        // Get token expiration times
        $access_expire = $this->jwt_auth->get_token_expire_time('access');
        $refresh_expire = $this->jwt_auth->get_token_expire_time('refresh');
        
        log_message('debug', 'JWT Login Debug - Token expiration times set - Access: ' . $access_expire . ', Refresh: ' . $refresh_expire);
        
        // Store session in database
        try {
            log_message('debug', 'JWT Login Debug - Attempting to create JWT session for user_id: ' . $objUser->id);
            $session_created = $this->user_model->create_jwt_session(
                $objUser->id,
                $access_token,
                $refresh_token,
                $device_info,
                $ip_address,
                $user_agent,
                $access_expire,
                $refresh_expire
            );
            
            if (!$session_created) {
                log_message('error', 'JWT Login Error - Failed to create session in database for user_id: ' . $objUser->id);
                $response = $this->get_failed_response(NULL, "Failed to create session. Please try again.");
                $this->set_output($response);
                return;
            }
            
            log_message('debug', 'JWT Login Debug - JWT session created successfully for user_id: ' . $objUser->id);
        } catch (Exception $e) {
            log_message('error', 'JWT Login Error - Exception while creating session: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to create session. Please try again.");
            $this->set_output($response);
            return;
        }
        
        // Clean sensitive data
        $objUser->password = '****';
        $objUser->token = NULL; // Remove old token
        
      
        // Get mobile verification status
        try {
            log_message('debug', 'JWT Login Debug - Getting mobile verification status for user_id: ' . $objUser->id);
            $mobile_verification = $this->user_model->get_mobile_verification_status($objUser->id);
        
            $objUser->mobile_verified = $mobile_verification['mobile_verified']; 
            $objUser->requires_mobile_verification = !$mobile_verification['mobile_verified']; 
            
            log_message('debug', 'JWT Login Debug - Mobile verification status retrieved - Verified: ' . 
                ($mobile_verification['mobile_verified'] ? 'true' : 'false'));
        } catch (Exception $e) {
            log_message('error', 'JWT Login Error - Failed to get mobile verification status: ' . $e->getMessage());
            // Set default values if mobile verification check fails
            $mobile_verification = array('mobile_verified' => false);
            $objUser->mobile_verified = false;
            $objUser->requires_mobile_verification = true;
        }
        
        // Prepare response
        $auth_data = array(
            'user' => $objUser,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'token_type' => 'Bearer',
            'expires_in' => $access_expire,
            'refresh_expires_in' => $refresh_expire,
            'mobile_verified' => $mobile_verification['mobile_verified'],
            'requires_mobile_verification' => !$mobile_verification['mobile_verified']
        );
        
        log_message('debug', 'JWT Login Debug - Authentication response prepared successfully for user_id: ' . $objUser->id);
        
        $response = $this->get_success_response($auth_data, "JWT authentication successful");
        $this->set_output($response);
        
        log_message('debug', 'JWT Login Debug - JWT login completed successfully for user_id: ' . $objUser->id);
    }

    /**
     * JWT-based admin login endpoint
     * Generates access and refresh tokens for admin users only
     */
    public function jwt_admin_login_post() {
        $request = $this->get_request();
        
        // Validate required fields
        if (!isset($request['username']) || !isset($request['password'])) {
            $response = $this->get_failed_response(NULL, "Username and password are required");
            $this->set_output($response);
            return;
        }
        
        $this->load->model('user/user_model');
        $this->load->library('JWT_Auth');
        
        $objUser = $this->user_model->get_user_from_username_password($request['username'], $request['password']);
        
        if ($objUser == NULL || $objUser->id == 0) {
            $response = $this->get_failed_response(NULL, "Invalid username or password");
            $this->set_output($response);
            return;
        }
        
        if ($objUser->allow_login == 0) {
            $response = $this->get_failed_response(NULL, "Your login permission has been revoked");
            $this->set_output($response);
            return;
        }
        
        // Check if user has admin role (role_id: 1, 2, 3, or 4)
        $allowedRoles = [1, 2, 3, 4];
        if (!in_array($objUser->role_id, $allowedRoles)) {
            $response = $this->get_failed_response(NULL, "Access denied. Admin privileges required");
            $this->set_output($response);
            return;
        }
        
        // Get device and IP information
        $device_info = isset($request['device_info']) ? $request['device_info'] : $this->get_device_info();
        $ip_address = $this->input->ip_address();
        $user_agent = $this->input->user_agent();
        
        // Create JWT payload with admin flag
        $payload = $this->jwt_auth->create_user_payload($objUser, $device_info);
        $payload['is_admin'] = true;
        $payload['admin_permissions'] = $this->get_admin_permissions($objUser->role_id);
        
        // Generate tokens
        $access_token = $this->jwt_auth->generate_access_token($payload);
        $refresh_token = $this->jwt_auth->generate_refresh_token($payload);
        
        // Get token expiration times
        $access_expire = $this->jwt_auth->get_token_expire_time('access');
        $refresh_expire = $this->jwt_auth->get_token_expire_time('refresh');
        
        // Store session in database
        $session_created = $this->user_model->create_jwt_session(
            $objUser->id,
            $access_token,
            $refresh_token,
            $device_info,
            $ip_address,
            $user_agent,
            $access_expire,
            $refresh_expire
        );
        
        if (!$session_created) {
            $response = $this->get_failed_response(NULL, "Failed to create admin session. Please try again.");
            $this->set_output($response);
            return;
        }
        
        // Clean sensitive data
        $objUser->password = '****';
        $objUser->token = NULL; // Remove old token
        
        // Prepare response
        $auth_data = array(
            'user' => $objUser,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'token_type' => 'Bearer',
            'expires_in' => $access_expire,
            'refresh_expires_in' => $refresh_expire,
            'is_admin' => true,
            'admin_permissions' => $payload['admin_permissions']
        );
        
        $response = $this->get_success_response($auth_data, "JWT admin authentication successful");
        $this->set_output($response);
    }

    /**
     * Refresh JWT token endpoint
     * Generates new access token using valid refresh token
     */
    public function jwt_refresh_post() {
        $request = $this->get_request();
        
        if (!isset($request['refresh_token'])) {
            $response = $this->get_failed_response(NULL, "Refresh token is required");
            $this->set_output($response);
            return;
        }
        
        $this->load->library('JWT_Auth');
        $this->load->model('user/user_model');
        
        // Validate refresh token
        $payload = $this->jwt_auth->validate_refresh_token($request['refresh_token']);
        
        if (!$payload) {
            $response = $this->get_failed_response(NULL, "Invalid or expired refresh token");
            $this->set_output($response);
            return;
        }
        
        // Get user from payload
        $objUser = $this->user_model->get_user($payload['user_id']);
        
        if (!$objUser || $objUser->allow_login == 0) {
            $response = $this->get_failed_response(NULL, "User account is no longer valid");
            $this->set_output($response);
            return;
        }
        
        // Create new payload (preserve original device info and admin status)
        $new_payload = $this->jwt_auth->create_user_payload($objUser, 
            isset($payload['device_info']) ? $payload['device_info'] : null);
        
        if (isset($payload['is_admin'])) {
            $new_payload['is_admin'] = $payload['is_admin'];
            $new_payload['admin_permissions'] = isset($payload['admin_permissions']) ? 
                $payload['admin_permissions'] : $this->get_admin_permissions($objUser->role_id);
        }
        
        // Generate new tokens
        $new_access_token = $this->jwt_auth->generate_access_token($new_payload);
        $new_refresh_token = $this->jwt_auth->generate_refresh_token($new_payload);
        
        // Get token expiration times
        $access_expire = $this->jwt_auth->get_token_expire_time('access');
        $refresh_expire = $this->jwt_auth->get_token_expire_time('refresh');
        
        // Update session in database
        $session_updated = $this->user_model->refresh_jwt_tokens(
            $request['refresh_token'],
            $new_access_token,
            $new_refresh_token,
            $access_expire,
            $refresh_expire
        );
        
        if (!$session_updated) {
            $response = $this->get_failed_response(NULL, "Failed to refresh session. Please login again.");
            $this->set_output($response);
            return;
        }
        
        // Prepare response
        $auth_data = array(
            'access_token' => $new_access_token,
            'refresh_token' => $new_refresh_token,
            'token_type' => 'Bearer',
            'expires_in' => $access_expire,
            'refresh_expires_in' => $refresh_expire
        );
        
        $response = $this->get_success_response($auth_data, "Token refreshed successfully");
        $this->set_output($response);
    }

    /**
     * JWT logout endpoint
     * Revokes the current session
     */
    public function jwt_logout_post() {
        $access_token = $this->get_jwt_token_from_header();
        
        if (!$access_token) {
            $response = $this->get_failed_response(NULL, "No valid session found");
            $this->set_output($response);
            return;
        }
        
        $this->load->model('user/user_model');
        
        // Revoke session
        $revoked = $this->user_model->revoke_jwt_session($access_token, 'User logout');
        
        if ($revoked) {
            $response = $this->get_success_response(NULL, "Logged out successfully");
        } else {
            $response = $this->get_failed_response(NULL, "Failed to logout. Please try again.");
        }
        
        $this->set_output($response);
    }

    /**
     * JWT logout from all devices endpoint
     * Revokes all user sessions
     */
    public function jwt_logout_all_post() {
        $objUser = $this->get_jwt_logged_user();
        
        if (!$objUser) {
            $response = $this->get_failed_response(NULL, "No valid session found");
            $this->set_output($response);
            return;
        }
        
        $this->load->model('user/user_model');
        
        // Revoke all user sessions
        $revoked = $this->user_model->revoke_all_user_sessions($objUser->id, 'User logout from all devices');
        
        if ($revoked) {
            $response = $this->get_success_response(NULL, "Logged out from all devices successfully");
        } else {
            $response = $this->get_failed_response(NULL, "Failed to logout from all devices. Please try again.");
        }
        
        $this->set_output($response);
    }

    /**
     * Get user sessions endpoint
     * Returns all active sessions for the current user
     */
    public function jwt_sessions_get() {
        $objUser = $this->get_jwt_logged_user();
        
        if (!$objUser) {
            $response = $this->get_failed_response(NULL, "No valid session found");
            $this->set_output($response);
            return;
        }
        
        $this->load->model('user/user_model');
        
        $sessions = $this->user_model->get_user_active_sessions($objUser->id);
        
        $response = $this->get_success_response($sessions, "Active sessions retrieved successfully");
        $this->set_output($response);
    }

    // ============================
    // Helper Methods
    // ============================

    /**
     * Get device information from request
     * 
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

    /**
     * Get admin permissions based on role
     * 
     * @param int $role_id Role ID
     * @return array Admin permissions
     */
    private function get_admin_permissions($role_id) {
        $permissions = array();
        
        switch ($role_id) {
            case 1: // Super Admin
                $permissions = ['all', 'users', 'quiz', 'reports', 'settings'];
                break;
            case 2: // Admin
                $permissions = ['users', 'quiz', 'reports'];
                break;
            case 3: // Principal
                $permissions = ['quiz', 'reports', 'limited_users'];
                break;
            case 4: // Faculty
                $permissions = ['quiz', 'limited_reports'];
                break;
            default:
                $permissions = [];
        }
        
        return $permissions;
    }

    /**
     * Get logged user from JWT token
     * 
     * @return object|null User object or null
     */
    public function get_jwt_logged_user() {
        $token = $this->get_jwt_token_from_header();
        
        if (!$token) {
            return null;
        }
        
        $this->load->library('JWT_Auth');
        $this->load->model('user/user_model');
        
        $payload = $this->jwt_auth->validate_token($token);
        
        if (!$payload) {
            return null;
        }
        
        return $this->user_model->get_user_from_jwt_token($token);
    }

    /**
     * Google OAuth Login endpoint
     * Handles login for existing users with Google OAuth
     */
    public function google_login_post() {
        $request = $this->get_request();
        
        // Get Google OAuth data from request
        $google_id = isset($request['google_id']) ? $request['google_id'] : null;
        $email = isset($request['email']) ? $request['email'] : null;
        $access_token = isset($request['access_token']) ? $request['access_token'] : null;

        // Validate required fields
        if (empty($google_id) || empty($email) || empty($access_token)) {
            $response = $this->get_failed_response(NULL, "Missing required fields for Google login");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('user/user_model');
            $this->load->library('JWT_Auth');

            // Debug logging
            log_message('debug', 'Google Login Debug - google_id: ' . $google_id . ', email: ' . $email);

            // Verify Google token (reuse method from signup)
            $google_user = $this->user_model->verify_google_token($access_token);
            
            if (!$google_user) {
                log_message('error', 'Google token verification failed for email: ' . $email);
                $response = $this->get_failed_response(NULL, "Invalid Google token");
                $this->set_output($response);
                return;
            }

            log_message('debug', 'Google token verified successfully for: ' . $email);

            // Check if user exists with this Google ID
            $existing_user = $this->user_model->get_user_by_google_id($google_id);
            
            if (!$existing_user) {
                log_message('debug', 'No user found with Google ID: ' . $google_id . ', checking email');
                
                // Check if user exists with this email but different auth provider
                // Note: email is stored in the 'username' field in our database
                $email_user = $this->user_model->get_user_from_username($email);
                if ($email_user && $email_user->auth_provider !== 'google') {
                    log_message('debug', 'User exists with email but different auth provider: ' . $email_user->auth_provider);
                    $response = $this->get_failed_response(NULL, "Account exists with this email but using different login method. Please use email/password login or link your Google account.");
                    $this->set_output($response);
                    return;
                } else {
                    log_message('debug', 'No user found with email: ' . $email . ' for Google login');
                    $response = $this->get_failed_response(NULL, "No account found with this Google account. Please sign up first.");
                    $this->set_output($response);
                    return;
                }
            }

            log_message('debug', 'User found with Google ID: ' . $google_id);

            // Check if user is allowed to login
            if ($existing_user->allow_login == 0) {
                $response = $this->get_failed_response(NULL, "Your login permission has been revoked");
                $this->set_output($response);
                return;
            }

            // Get device and IP information
            $device_info = isset($request['device_info']) ? $request['device_info'] : $this->get_device_info();
            $ip_address = $this->input->ip_address();
            $user_agent = $this->input->user_agent();
            
            // Create JWT payload
            $payload = $this->jwt_auth->create_user_payload($existing_user, $device_info);
            
            // Generate tokens
            $jwt_access_token = $this->jwt_auth->generate_access_token($payload);
            $refresh_token = $this->jwt_auth->generate_refresh_token($payload);
            
            // Get token expiration times
            $access_expire = $this->jwt_auth->get_token_expire_time('access');
            $refresh_expire = $this->jwt_auth->get_token_expire_time('refresh');
            
            // Store session in database
            $session_created = $this->user_model->create_jwt_session(
                $existing_user->id,
                $jwt_access_token,
                $refresh_token,
                $device_info,
                $ip_address,
                $user_agent,
                $access_expire,
                $refresh_expire
            );
            
            if (!$session_created) {
                $response = $this->get_failed_response(NULL, "Failed to create session. Please try again.");
                $this->set_output($response);
                return;
            }

            // Clean sensitive data
            $existing_user->password = '****';
            $existing_user->token = NULL;
            
            
            // Get mobile verification status
            $mobile_verification = $this->user_model->get_mobile_verification_status($existing_user->id);
            
            $existing_user->mobile_verified = $mobile_verification['mobile_verified']; 
            $existing_user->requires_mobile_verification = !$mobile_verification['mobile_verified'];

            // Prepare response
            $auth_data = array(
                'user' => $existing_user,
                'access_token' => $jwt_access_token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => $access_expire,
                'refresh_expires_in' => $refresh_expire,
                'mobile_verified' => $mobile_verification['mobile_verified'],
                'requires_mobile_verification' => !$mobile_verification['mobile_verified']
            );
            
            $response = $this->get_success_response($auth_data, "Google login successful");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'Google login error: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Login failed. Please try again.");
            $this->set_output($response);
        }
    }

}
