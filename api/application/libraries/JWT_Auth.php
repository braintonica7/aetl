<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * JWT Auth Library
 * 
 * Custom JWT implementation for WiziAI Educational Platform
 * Provides secure token generation, validation, and refresh functionality
 * 
 * @author WiziAI Development Team
 * @version 1.0
 */
class JWT_Auth {
    
    private $CI;
    private $secret;
    private $algorithm;
    private $issuer;
    private $audience;
    private $access_token_expire;
    private $refresh_token_expire;
    private $leeway;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('config');
        
        // Load JWT configuration
        $this->secret = $this->CI->config->item('jwt_secret');
        $this->algorithm = $this->CI->config->item('jwt_algorithm');
        $this->issuer = $this->CI->config->item('jwt_issuer');
        $this->audience = $this->CI->config->item('jwt_audience');
        $this->access_token_expire = $this->CI->config->item('jwt_access_token_expire');
        $this->refresh_token_expire = $this->CI->config->item('jwt_refresh_token_expire');
        $this->leeway = $this->CI->config->item('jwt_leeway');
    }
    
    /**
     * Generate JWT Access Token
     * 
     * @param array $payload User data and claims
     * @return string JWT token
     */
    public function generate_access_token($payload) {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];
        
        $payload = array_merge($payload, [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => time(),
            'exp' => time() + $this->access_token_expire,
            'nbf' => time(),
            'jti' => $this->generate_jti()
        ]);
        
        return $this->encode_jwt($header, $payload);
    }
    
    /**
     * Generate JWT Refresh Token
     * 
     * @param array $payload User data and claims
     * @return string JWT refresh token
     */
    public function generate_refresh_token($payload) {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];
        
        $payload = array_merge($payload, [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => time(),
            'exp' => time() + $this->refresh_token_expire,
            'nbf' => time(),
            'jti' => $this->generate_jti(),
            'type' => 'refresh'
        ]);
        
        return $this->encode_jwt($header, $payload);
    }
    
    /**
     * Validate JWT Token
     * 
     * @param string $token JWT token to validate
     * @return array|false Decoded payload or false if invalid
     */
    public function validate_token($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            $header = json_decode($this->base64url_decode($parts[0]), true);
            $payload = json_decode($this->base64url_decode($parts[1]), true);
            $signature = $parts[2];
            
            // Verify signature
            $expected_signature = $this->base64url_encode(
                hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->secret, true)
            );
            
            if (!hash_equals($signature, $expected_signature)) {
                return false;
            }
            
            // Check expiration with leeway
            if (isset($payload['exp']) && (time() - $this->leeway) > $payload['exp']) {
                return false;
            }
            
            // Check not before with leeway
            if (isset($payload['nbf']) && (time() + $this->leeway) < $payload['nbf']) {
                return false;
            }
            
            // Check issuer
            if (isset($payload['iss']) && $payload['iss'] !== $this->issuer) {
                return false;
            }
            
            // Check audience
            if (isset($payload['aud']) && $payload['aud'] !== $this->audience) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if token is expired
     * 
     * @param string $token JWT token
     * @return bool True if expired, false otherwise
     */
    public function is_token_expired($token) {
        $payload = $this->validate_token($token);
        if (!$payload) {
            return true;
        }
        
        return isset($payload['exp']) && time() > $payload['exp'];
    }
    
    /**
     * Extract user ID from token
     * 
     * @param string $token JWT token
     * @return int|false User ID or false if invalid
     */
    public function get_user_id_from_token($token) {
        $payload = $this->validate_token($token);
        if (!$payload) {
            return false;
        }
        
        return isset($payload['user_id']) ? (int)$payload['user_id'] : false;
    }
    
    /**
     * Generate JWT payload for user
     * 
     * @param object $user User object
     * @param string $device_info Device information
     * @return array JWT payload
     */
    public function create_user_payload($user, $device_info = null) {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'role_id' => $user->role_id,
            'reference_id' => $user->reference_id
        ];
        
        if ($device_info) {
            $payload['device_info'] = $device_info;
        }
        
        return $payload;
    }
    
    /**
     * Encode JWT Token
     * 
     * @param array $header JWT header
     * @param array $payload JWT payload
     * @return string Encoded JWT token
     */
    private function encode_jwt($header, $payload) {
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        
        $signature = $this->base64url_encode(
            hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $this->secret, true)
        );
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }
    
    /**
     * Generate unique JWT ID
     * 
     * @return string Unique JWT ID
     */
    private function generate_jti() {
        return hash('sha256', uniqid() . time() . mt_rand());
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Base64 URL encoded string
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded string
     */
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Get token expiration time in seconds
     * 
     * @param string $type Token type (access or refresh)
     * @return int Expiration time in seconds
     */
    public function get_token_expire_time($type = 'access') {
        return $type === 'refresh' ? $this->refresh_token_expire : $this->access_token_expire;
    }
    
    /**
     * Validate refresh token and check if it's a refresh token
     * 
     * @param string $token Refresh token to validate
     * @return array|false Decoded payload or false if invalid
     */
    public function validate_refresh_token($token) {
        $payload = $this->validate_token($token);
        if (!$payload) {
            return false;
        }
        
        // Check if it's a refresh token
        if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
            return false;
        }
        
        return $payload;
    }
}
