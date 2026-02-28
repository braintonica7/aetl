<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SMS Service Library
 * Handles SMS sending via external SMS gateway
 */
class Sms_service {

    protected $CI;
    
    // SMS Gateway Configuration
    private $sms_api_url = 'https://sms.visionhlt.com/vendorsms/pushsms.aspx';
    private $sms_user = 'wiziai';
    private $sms_password = 'wiziai';
    private $sender_id = 'WIZIAI'; // Sender ID (sid parameter)
    private $flash_sms = '0'; // fl parameter: 0 = normal SMS, 1 = flash SMS
    private $gateway_id = '2'; // gwid parameter: Gateway ID

    public function __construct() {
        $this->CI =& get_instance();
        log_message('info', 'SMS Service Library initialized');
    }

    /**
     * Send OTP SMS to mobile number
     * 
     * @param string $mobile_number 10-digit mobile number (without country code)
     * @param string $otp_code 6-digit OTP code
     * @param int $valid_minutes Validity of OTP in minutes
     * @return array Success status and message
     */
    public function send_otp_sms($mobile_number, $otp_code, $valid_minutes = 10) {
        // Validate inputs
        if (empty($mobile_number) || empty($otp_code)) {
            return [
                'success' => false,
                'error' => 'Mobile number and OTP code are required'
            ];
        }

        // Clean mobile number (remove any non-digits)
        $mobile_number = preg_replace('/\D/', '', $mobile_number);

        // Validate mobile number format (10 digits starting with 6-9)
        if (!preg_match('/^[6-9]\d{9}$/', $mobile_number)) {
            return [
                'success' => false,
                'error' => 'Invalid mobile number format'
            ];
        }

        // Prepare SMS message
        $message = $this->prepare_otp_message($otp_code, $valid_minutes);
        log_message('info', 'Prepared OTP message: ' . $message);   
        // Send SMS
        return $this->send_sms($mobile_number, $message);
    }

    /**
     * Send verification success SMS
     * 
     * @param string $mobile_number 10-digit mobile number
     * @param string $user_name User's display name
     * @return array Success status and message
     */
    public function send_verification_success_sms($mobile_number, $user_name = '') {
        $message = $this->prepare_success_message($user_name);
        return $this->send_sms($mobile_number, $message);
    }

    /**
     * Generic SMS sending method
     * 
     * @param string $mobile_number 10-digit mobile number (without country code)
     * @param string $message SMS message text
     * @return array Success status and message
     */
    private function send_sms($mobile_number, $message) {
        try {
            // Add country code (91 for India)
            $mobile_with_country_code = '91' . $mobile_number;
            log_message('debug', 'mobile_with_country_code: ' . $mobile_with_country_code);
            // Prepare API parameters based on gateway specification
            // URL format: /vendorsms/pushsms.aspx?user=X&password=X&msisdn=91XXXXXXXXXX&sid=SENDERID&msg=MESSAGE&fl=0&gwid=2
            $params = [
                'user' => $this->sms_user,
                'password' => $this->sms_password,
                'msisdn' => $mobile_with_country_code, // Mobile number with country code
                'sid' => $this->sender_id, // Sender ID
                'msg' => $message, // SMS message text
                'fl' => $this->flash_sms, // Flash SMS flag (0=normal, 1=flash)
                'gwid' => $this->gateway_id // Gateway ID
            ];

            // Build URL with query parameters
            $url = $this->sms_api_url . '?' . http_build_query($params);
            log_message('debug', 'SMS API Request URL: ' . $url);

            // Log the SMS request (without sensitive data)
            log_message('debug', 'Sending SMS to: ' . $mobile_number);

            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production with proper SSL setup
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // Execute request
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            // Check for cURL errors
            if ($curl_error) {
                log_message('error', 'SMS cURL Error: ' . $curl_error);
                return [
                    'success' => false,
                    'error' => 'Failed to connect to SMS gateway. Please try again.'
                ];
            }

            // Check HTTP response code
            if ($http_code !== 200) {
                log_message('error', 'SMS API HTTP Error: ' . $http_code . ' - Response: ' . $response);
                return [
                    'success' => false,
                    'error' => 'SMS service temporarily unavailable. Please try again later.'
                ];
            }

            // Parse response (this may vary depending on your SMS gateway)
            // Assuming success response contains certain keywords
            // Adjust this based on actual API response format
            if (stripos($response, 'success') !== false || stripos($response, 'sent') !== false) {
                log_message('debug', 'SMS sent successfully to: ' . $mobile_number);
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'gateway_response' => $response
                ];
            } else {
                log_message('error', 'SMS sending failed. Gateway response: ' . $response);
                return [
                    'success' => false,
                    'error' => 'Failed to send SMS. Please try again.',
                    'gateway_response' => $response
                ];
            }

        } catch (Exception $e) {
            log_message('error', 'SMS Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while sending SMS. Please try again.'
            ];
        }
    }

    /**
     * Prepare OTP message text
     * 
     * @param string $otp_code OTP code
     * @param int $valid_minutes Validity in minutes
     * @return string Formatted SMS message
     */
    private function prepare_otp_message($otp_code, $valid_minutes) {
        // SMS message format - exact format as per gateway example
        // Format: "Dear customer your login otp is 123456 do not share with anyone https://wiziai.com/ WIZIAI APPLICATION"
        $message = "Dear customer your login otp is {$otp_code} do not share with anyone https://wiziai.com/ WIZIAI APPLICATION";
        log_message('debug', 'Message : ' . $message);
        // Message will be URL encoded by http_build_query
        return $message;
    }

    /**
     * Prepare verification success message
     * 
     * @param string $user_name User's name
     * @return string Formatted SMS message
     */
    private function prepare_success_message($user_name) {
        $greeting = !empty($user_name) ? "Dear {$user_name}, " : "Dear customer, ";
        $message = "{$greeting}Your mobile number has been successfully verified on WiziAI. Thank you! https://wiziai.com/ WIZIAI";
        
        // Message will be URL encoded by http_build_query
        return $message;
    }

    /**
     * Test SMS connectivity (for debugging)
     * 
     * @param string $mobile_number Test mobile number
     * @return array Test result
     */
    public function test_connection($mobile_number) {
        $test_message = "Test message from WiziAI. This is a connectivity test. https://wiziai.com/ WIZIAI";
        return $this->send_sms($mobile_number, $test_message);
    }

    /**
     * Update SMS configuration (for admin use)
     * 
     * @param array $config Configuration array
     * @return bool Success status
     */
    public function update_config($config) {
        if (isset($config['sms_user'])) {
            $this->sms_user = $config['sms_user'];
        }
        if (isset($config['sms_password'])) {
            $this->sms_password = $config['sms_password'];
        }
        if (isset($config['sender_id'])) {
            $this->sender_id = $config['sender_id'];
        }
        if (isset($config['gateway_id'])) {
            $this->gateway_id = $config['gateway_id'];
        }
        if (isset($config['flash_sms'])) {
            $this->flash_sms = $config['flash_sms'];
        }
        
        return true;
    }

    /**
     * Get current SMS configuration (without sensitive data)
     * 
     * @return array Configuration array
     */
    public function get_config() {
        return [
            'api_url' => $this->sms_api_url,
            'sender_id' => $this->sender_id,
            'flash_sms' => $this->flash_sms,
            'gateway_id' => $this->gateway_id
        ];
    }
}
