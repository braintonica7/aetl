<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MY_Controller
 *
 * @author Jawahar
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

use Restserver\Libraries\REST_Controller;

require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Format.php';

class API_Controller extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->add_headers();
    }

    public function index_options() {
        $this->add_headers();
    }

    public function get_request() {
        //$this->add_headers();
        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        if (empty($request)) {
            if (isset($_POST) && count($_POST) > 0)
                $request = $_POST;
            else if (isset($_GET) && count($_GET) > 0)
                $request = $_GET;
        }
        return $request;
    }

    public function add_headers() {
        
        // CORS Headers for JWT Authentication
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
        header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-API-KEY, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // 24 hours
        
        // Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    public function get_empty_response() {
        $response = array();
        $response['status'] = RESULT_SUCCESS;
        $response['result'] = '';
        $response['message'] = '';
        $response['total'] = 0;
        return $response;
    }

    public function get_success_response($data, $message) {
        $response = array();
        $response['status'] = RESULT_SUCCESS;
        $response['result'] = $data;
        $response['message'] = $message;
        return $response;
    }

    public function get_failed_response($data, $error) {
        $response = array();
        $response['status'] = RESULT_FAILED;
        $response['result'] = '';
        $response['message'] = $error;
        return $response;
    }

    public function set_output($response) {
        $this->add_headers();
        $this->response($response, REST_Controller::HTTP_OK, TRUE);
    }
    
    public function get_logged_user(){
        $objUser = NULL;
        $token = $this->input->get_request_header('authorization', TRUE);
        if ($token != NULL){
            $this->load->model('user/user_model');
            $objUser = $this->user_model->get_user_from_token($token);
        }
        return $objUser;
    }
    
    // ============================
    // JWT Authentication Methods
    // ============================
    
    /**
     * Get logged user from JWT token
     * Enhanced security with proper JWT validation
     * 
     * @return object|null User object or null if invalid/expired
     */
    public function get_jwt_logged_user() {
        $objUser = NULL;
        $token = $this->get_jwt_token_from_header();
        
        if ($token != NULL) {
            $this->load->library('JWT_Auth');
            $this->load->model('user/user_model');
            
            // Validate JWT token
            $payload = $this->jwt_auth->validate_token($token);
            
            if ($payload && isset($payload['user_id'])) {
                // Get user from database with JWT token
                $objUser = $this->user_model->get_user_from_jwt_token($token);
                
                // Verify user is still valid and allowed to login
                if ($objUser && $objUser->allow_login == 0) {
                    $objUser = NULL;
                }
            }
        }
        
        return $objUser;
    }
    
    /**
     * Get JWT token from Authorization header
     * Supports both Bearer token format and direct token
     * 
     * @return string|null JWT token or null if not found
     */
    public function get_jwt_token_from_header() {
        $auth_header = $this->input->get_request_header('Authorization', TRUE);
        
        if ($auth_header) {
            // Support Bearer token format
            if (strpos($auth_header, 'Bearer ') === 0) {
                return substr($auth_header, 7);
            }
            // Support direct token (for backward compatibility)
            return $auth_header;
        }
        
        return null;
    }
    
    /**
     * Require JWT authentication for endpoint
     * Call this method at the beginning of protected endpoints
     * 
     * @param bool $admin_required Whether admin role is required
     * @param array $allowed_roles Specific roles allowed (optional)
     * @return object|false User object if authenticated, sends error response and returns false otherwise
     */
    public function require_jwt_auth($admin_required = false, $allowed_roles = []) {
        $objUser = $this->get_jwt_logged_user();
        
        if (!$objUser) {
            $this->send_unauthorized_response("Authentication required. Please provide a valid JWT token.");
            return false;
        }
        
        // Check if admin is required
        if ($admin_required) {
            $admin_roles = [1, 2, 3, 4]; // Admin role IDs
            if (!in_array($objUser->role_id, $admin_roles)) {
                $this->send_forbidden_response("Admin privileges required.");
                return false;
            }
        }
        
        // Check specific allowed roles
        if (!empty($allowed_roles) && !in_array($objUser->role_id, $allowed_roles)) {
            $this->send_forbidden_response("Insufficient permissions for this action.");
            return false;
        }
        
        return $objUser;
    }
    
    /**
     * Check if JWT token is expired and handle accordingly
     * 
     * @return bool True if token is valid, false if expired
     */
    public function check_jwt_token_expiry() {
        $token = $this->get_jwt_token_from_header();
        
        if (!$token) {
            return false;
        }
        
        $this->load->library('JWT_Auth');
        
        if ($this->jwt_auth->is_token_expired($token)) {
            $this->send_token_expired_response();
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate JWT token and get payload
     * 
     * @return array|false Token payload or false if invalid
     */
    public function get_jwt_token_payload() {
        $token = $this->get_jwt_token_from_header();
        
        if (!$token) {
            return false;
        }
        
        $this->load->library('JWT_Auth');
        return $this->jwt_auth->validate_token($token);
    }
    
    /**
     * Check if current user has specific permission
     * 
     * @param string $permission Permission to check
     * @return bool True if user has permission
     */
    public function has_permission($permission) {
        $payload = $this->get_jwt_token_payload();
        
        if (!$payload) {
            return false;
        }
        
        // Check admin permissions
        if (isset($payload['admin_permissions'])) {
            return in_array('all', $payload['admin_permissions']) || 
                   in_array($permission, $payload['admin_permissions']);
        }
        
        return false;
    }
    
    // ============================
    // Response Helper Methods
    // ============================
    
    /**
     * Send unauthorized response (401)
     * 
     * @param string $message Error message
     */
    public function send_unauthorized_response($message = "Unauthorized access") {
        $response = $this->get_failed_response(NULL, $message);
        $this->response($response, REST_Controller::HTTP_UNAUTHORIZED, TRUE);
    }
    
    /**
     * Send forbidden response (403)
     * 
     * @param string $message Error message
     */
    public function send_forbidden_response($message = "Forbidden access") {
        $response = $this->get_failed_response(NULL, $message);
        $this->response($response, REST_Controller::HTTP_FORBIDDEN, TRUE);
    }
    
    /**
     * Send token expired response (401)
     * 
     * @param string $message Error message
     */
    public function send_token_expired_response($message = "Token expired. Please refresh your token or login again.") {
        $response = array(
            'status' => RESULT_FAILED,
            'result' => null,
            'message' => $message,
            'error_code' => 'TOKEN_EXPIRED'
        );
        $this->response($response, REST_Controller::HTTP_UNAUTHORIZED, TRUE);
    }
    
    /**
     * Send invalid token response (401)
     * 
     * @param string $message Error message
     */
    public function send_invalid_token_response($message = "Invalid token. Please login again.") {
        $response = array(
            'status' => RESULT_FAILED,
            'result' => null,
            'message' => $message,
            'error_code' => 'INVALID_TOKEN'
        );
        $this->response($response, REST_Controller::HTTP_UNAUTHORIZED, TRUE);
    }
    
    /**
     * Enhanced response method with proper HTTP status codes
     * 
     * @param array $response Response data
     * @param int $http_code HTTP status code
     */
    public function set_secure_output($response, $http_code = REST_Controller::HTTP_OK) {
        $this->add_headers();
        $this->response($response, $http_code, TRUE);
    }

    // ============================
    // Additional Error Response Helper Methods
    // ============================
    
    /**
     * Send internal server error response (500)
     * 
     * @param string $message Error message
     * @param mixed $data Optional data to include
     */
    public function send_internal_server_error($message = "Internal server error", $data = null) {
        $response = $this->get_failed_response($data, $message);
        $this->response($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR, TRUE);
    }
    
    /**
     * Send bad request response (400)
     * 
     * @param string $message Error message
     * @param mixed $data Optional data to include
     */
    public function send_bad_request_response($message = "Bad request", $data = null) {
        $response = $this->get_failed_response($data, $message);
        $this->response($response, REST_Controller::HTTP_BAD_REQUEST, TRUE);
    }
    
    /**
     * Send not found response (404)
     * 
     * @param string $message Error message
     * @param mixed $data Optional data to include
     */
    public function send_not_found_response($message = "Resource not found", $data = null) {
        $response = $this->get_failed_response($data, $message);
        $this->response($response, REST_Controller::HTTP_NOT_FOUND, TRUE);
    }
    
    /**
     * Send method not allowed response (405)
     * 
     * @param string $message Error message
     * @param mixed $data Optional data to include
     */
    public function send_method_not_allowed_response($message = "Method not allowed", $data = null) {
        $response = $this->get_failed_response($data, $message);
        $this->response($response, REST_Controller::HTTP_METHOD_NOT_ALLOWED, TRUE);
    }
    
    /**
     * Send validation error response (422)
     * 
     * @param string $message Error message
     * @param mixed $data Validation errors data
     */
    public function send_validation_error_response($message = "Validation failed", $data = null) {
        $response = $this->get_failed_response($data, $message);
        $this->response($response, 422, TRUE); // HTTP_UNPROCESSABLE_ENTITY
    }
    
    /**
     * Send success response with data (200)
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     */
    public function send_success_response($data, $message = "Operation successful") {
        $response = $this->get_success_response($data, $message);
        $this->response($response, REST_Controller::HTTP_OK, TRUE);
    }
    
    /**
     * Send created response (201)
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     */
    public function send_created_response($data, $message = "Resource created successfully") {
        $response = $this->get_success_response($data, $message);
        $this->response($response, REST_Controller::HTTP_CREATED, TRUE);
    }

}
