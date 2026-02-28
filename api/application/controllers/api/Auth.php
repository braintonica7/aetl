<?php

/**
 * Auth Controller for JWT token verification
 * Provides endpoints for token validation and user authentication checks
 */
class Auth extends API_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Verify JWT token and return user information
     * GET /auth/verify
     * 
     * @return JSON response with user data or error
     */
    public function verify_get() {
        // Get JWT token from header
        $token = $this->get_jwt_token_from_header();
        
        if (!$token) {
            $this->response([
                'success' => false,
                'message' => 'No token provided',
                'error_code' => 'TOKEN_MISSING'
            ], 401);
            return;
        }

        // Load JWT library and user model
        $this->load->library('JWT_Auth');
        $this->load->model('user/user_model');

        try {
            // Validate token
            $payload = $this->jwt_auth->validate_token($token);
            
            if (!$payload) {
                $this->response([
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'error_code' => 'TOKEN_INVALID'
                ], 401);
                return;
            }

            // Get user from token
            $user = $this->user_model->get_user_from_jwt_token($token);
            $user->password = ''; // Remove password for security

            
            if (!$user) {
                $this->response([
                    'success' => false,
                    'message' => 'User not found',
                    'error_code' => 'USER_NOT_FOUND'
                ], 401);
                return;
            }

            // Check if user is allowed to login
            if ($user->allow_login == 0) {
                $this->response([
                    'success' => false,
                    'message' => 'User account is disabled',
                    'error_code' => 'ACCOUNT_DISABLED'
                ], 401);
                return;
            }
            $mobile_verification = $this->user_model->get_mobile_verification_status($user->id);
      
            $user->mobile_verified = $mobile_verification['mobile_verified']; 
            $user->requires_mobile_verification = !$mobile_verification['mobile_verified']; 
           
            // Return success with user data
            $this->response([
                'success' => true,
                'message' => 'Token is valid',
                'user' => $user
            ], 200);

        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => 'Token validation failed: ' . $e->getMessage(),
                'error_code' => 'VALIDATION_ERROR'
            ], 401);
        }
    }

    /**
     * Get current user information (requires valid JWT token)
     * GET /auth/user
     * 
     * @return JSON response with user data
     */
    public function user_get() {
        $user = $this->get_jwt_logged_user();
        $user->password = ''; // Remove password for security
        $this->load->model('user/user_model');
        $mobile_verification = $this->user_model->get_mobile_verification_status($user->id);
      
        $user->mobile_verified = $mobile_verification['mobile_verified']; 
        $user->requires_mobile_verification = !$mobile_verification['mobile_verified']; 

        if (!$user) {
            $this->response([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTH_REQUIRED'
            ], 401);
            return;
        }

        $this->response([
            'success' => true,
            'message' => 'User data retrieved successfully',
            'user' => $user
        ], 200);
    }
}