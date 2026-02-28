<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mobile Verification API Controller
 * Handles mobile number verification via OTP
 */
class Mobile_verification extends API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('mobile_otp/mobile_otp_model');
        $this->load->library('sms_service');
    }

    /**
     * Send OTP to mobile number
     * POST /api/mobile_verification/send_otp
     * 
     * Request body:
     * {
     *   "mobile_number": "9876543210"
     * }
     */
    public function send_otp_post() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        // Validate mobile number
        if (empty($request['mobile_number'])) {
            $response = $this->get_failed_response(NULL,'Mobile number is required');
            $this->set_output($response);
            return;
        }

        $mobile_number = $request['mobile_number'];
        $user_id = $objUser->id;
        $ip_address = $this->input->ip_address();
        $user_agent = $this->input->user_agent();

        try {
            // Check if mobile number is already verified by another user
            if ($this->mobile_otp_model->is_mobile_already_used($mobile_number, $user_id)) {
                $response = $this->get_failed_response(NULL,'This mobile number is already registered with another account');
                $this->set_output($response);
                return;
            }

            // Generate OTP
            $otp_result = $this->mobile_otp_model->generate_otp(
                $mobile_number,
                $user_id,
                $ip_address,
                $user_agent
            );

            if (!$otp_result['success']) {
                $response = $this->get_failed_response(NULL, $otp_result['error'], [
                    'retry_after' => $otp_result['retry_after'] ?? null
                ]);
                $this->set_output($response);
                return;
            }

            // Send OTP via SMS
            $sms_result = $this->sms_service->send_otp_sms(
                $mobile_number,
                $otp_result['otp_code'],
                $otp_result['valid_for_minutes']
            );

            if (!$sms_result['success']) {
                log_message('error', 'Failed to send OTP SMS to ' . $mobile_number . ': ' . $sms_result['error']);
                
                // Still return success to user since OTP is generated
                // In production, you might want to handle this differently
                $response_data = [
                    'mobile_number' => $mobile_number,
                    'otp_sent' => false,
                    'expires_in_minutes' => $otp_result['valid_for_minutes'],
                    'attempts_remaining' => $otp_result['attempts_remaining'],
                    'resends_remaining' => $otp_result['resends_remaining'],
                    'warning' => 'OTP generated but SMS delivery may be delayed. Please check your phone.'
                ];

                $this->send_success_response($response_data, 'OTP generated successfully');
                return;
            }

            // Success response
            $response_data = [
                'mobile_number' => $mobile_number,
                'otp_sent' => true,
                'expires_in_minutes' => $otp_result['valid_for_minutes'],
                'attempts_remaining' => $otp_result['attempts_remaining'],
                'resends_remaining' => $otp_result['resends_remaining']
            ];

            $this->send_success_response($response_data, 'OTP sent successfully to your mobile number');

        } catch (Exception $e) {
            log_message('error', 'Send OTP Exception: ' . $e->getMessage());
            $this->send_internal_error_response('An error occurred while sending OTP');
        }
    }

    /**
     * Verify OTP code
     * POST /api/mobile_verification/verify_otp
     * 
     * Request body:
     * {
     *   "mobile_number": "9876543210",
     *   "otp_code": "123456"
     * }
     */
    public function verify_otp_post() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();

        // Validate inputs
        if (empty($request['mobile_number']) || empty($request['otp_code'])) {
            $this->send_validation_error_response('Mobile number and OTP code are required');
            return;
        }

        $mobile_number = $request['mobile_number'];
        $otp_code = $request['otp_code'];
        $user_id = $objUser->id;

        try {
            // Verify OTP
            $verify_result = $this->mobile_otp_model->verify_otp(
                $mobile_number,
                $otp_code,
                $user_id
            );

            if (!$verify_result['success']) {
                $this->send_bad_request_response($verify_result['error'], [
                    'attempts_remaining' => $verify_result['attempts_remaining'] ?? 0,
                    'max_attempts_reached' => $verify_result['max_attempts_reached'] ?? false
                ]);
                return;
            }

            // Update user's mobile verification status
            $this->load->model('user/user_model');
            $update_result = $this->user_model->update_mobile_verification(
                $user_id,
                $mobile_number,
                true
            );

            if (!$update_result) {
                log_message('error', 'Failed to update mobile verification status for user: ' . $user_id);
                $this->send_internal_error_response('Verification successful but failed to update account');
                return;
            }

            // Send verification success email
            $this->send_verification_email($objUser, $mobile_number);

            // Success response
            $response_data = [
                'mobile_number' => $mobile_number,
                'verified' => true,
                'verified_at' => date('Y-m-d H:i:s')
            ];

            $this->send_success_response($response_data, 'Mobile number verified successfully!');

        } catch (Exception $e) {
            log_message('error', 'Verify OTP Exception: ' . $e->getMessage());
            $this->send_internal_error_response('An error occurred during verification');
        }
    }

    /**
     * Resend OTP
     * POST /api/mobile_verification/resend_otp
     * 
     * Request body:
     * {
     *   "mobile_number": "9876543210"
     * }
     */
    public function resend_otp_post() {
        // Just call send_otp_post() - it handles rate limiting
        $this->send_otp_post();
    }

    /**
     * Check verification status
     * GET /api/mobile_verification/status
     */
    public function status_get() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        try {
            $this->load->model('user/user_model');
            
            $verification_status = $this->user_model->get_mobile_verification_status($objUser->id);

            $response_data = [
                'mobile_verified' => $verification_status['mobile_verified'],
                'mobile_number' => $verification_status['mobile_number'],
                'verified_at' => $verification_status['mobile_verified_at']
            ];

            // If not verified, get OTP status if exists
            if (!$verification_status['mobile_verified'] && !empty($verification_status['mobile_number'])) {
                $otp_status = $this->mobile_otp_model->get_otp_status($verification_status['mobile_number']);
                $response_data['otp_status'] = $otp_status;
            }

            $this->send_success_response($response_data, 'Verification status retrieved successfully');

        } catch (Exception $e) {
            log_message('error', 'Get Status Exception: ' . $e->getMessage());
            $this->send_internal_error_response('An error occurred while fetching status');
        }
    }

    /**
     * Get OTP attempts and resend information
     * GET /api/mobile_verification/otp_info?mobile_number=9876543210
     */
    public function otp_info_get() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $mobile_number = $this->input->get('mobile_number');

        if (empty($mobile_number)) {
            $this->send_validation_error_response('Mobile number is required');
            return;
        }

        try {
            $otp_status = $this->mobile_otp_model->get_otp_status($mobile_number);

            $this->send_success_response($otp_status, 'OTP information retrieved successfully');

        } catch (Exception $e) {
            log_message('error', 'Get OTP Info Exception: ' . $e->getMessage());
            $this->send_internal_error_response('An error occurred while fetching OTP information');
        }
    }

    /**
     * Send verification success email (private method)
     * 
     * @param object $user User object
     * @param string $mobile_number Verified mobile number
     */
    private function send_verification_email($user, $mobile_number) {
        try {
            $this->load->library('email');

            $config = array(
                'mailtype' => 'html',
                'charset' => 'utf-8',
                'wordwrap' => TRUE
            );
            $this->email->initialize($config);

            $this->email->from('noreply@wiziai.com', 'WiziAI');
            $this->email->to($user->username);
            $this->email->subject('Mobile Number Verified Successfully - WiziAI');

            // Email body
            $masked_mobile = substr($mobile_number, 0, 2) . 'XXXXXX' . substr($mobile_number, -2);
            $verification_date = date('d M Y, h:i A');

            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                    .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
                    .info-box { background: white; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Mobile Verification Successful!</h2>
                    </div>
                    <div class='content'>
                        <div class='success-icon'>✅</div>
                        <p>Dear " . htmlspecialchars($user->display_name) . ",</p>
                        <p>Your mobile number has been successfully verified on WiziAI!</p>
                        
                        <div class='info-box'>
                            <strong>Verification Details:</strong><br>
                            Mobile Number: " . $masked_mobile . "<br>
                            Verified On: " . $verification_date . "<br>
                            Account: " . htmlspecialchars($user->username) . "
                        </div>
                        
                        <p>Your account is now fully activated and you can access all WiziAI features.</p>
                        
                        <p><strong>Security Note:</strong> If you did not perform this verification, please contact our support team immediately.</p>
                        
                        <p>Thank you for choosing WiziAI!</p>
                        
                        <div class='footer'>
                            <p>This is an automated email. Please do not reply to this message.</p>
                            <p>&copy; " . date('Y') . " WiziAI. All rights reserved.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";

            $this->email->message($message);
            
            if ($this->email->send()) {
                log_message('info', 'Verification success email sent to: ' . $user->username);
            } else {
                log_message('error', 'Failed to send verification email to: ' . $user->username);
            }

        } catch (Exception $e) {
            log_message('error', 'Email sending exception: ' . $e->getMessage());
        }
    }
}
