<?php

class Notification_service {

    private $CI;
    private $service_account_path;
    private $fcm_endpoint;
    private $access_token;
    private $token_expires_at;

    public function __construct() {
        $this->CI =& get_instance();
        
        // Path to your service account JSON file
        // You should store this outside the web root for security
        $this->service_account_path = $this->CI->config->item('fcm_service_account_path') ?: APPPATH . '../config/firebase-service-account.json';
        $this->fcm_endpoint = 'https://fcm.googleapis.com/v1/projects/{project-id}/messages:send';
    }

    /**
     * Get OAuth 2.0 access token using service account
     */
    private function getAccessToken() {
        try {
            // Check if token is still valid (with 5-minute buffer)
            if ($this->access_token && $this->token_expires_at && (time() + 300) < $this->token_expires_at) {
                return $this->access_token;
            }

            // Load service account credentials
            if (!file_exists($this->service_account_path)) {
                throw new Exception('Service account file not found at: ' . $this->service_account_path);
            }

            $service_account = json_decode(file_get_contents($this->service_account_path), true);
            if (!$service_account) {
                throw new Exception('Invalid service account JSON file');
            }

            // Create JWT for token request
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $now = time();
            $payload = json_encode([
                'iss' => $service_account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600 // 1 hour
            ]);

            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signature = '';
            $success = openssl_sign($base64Header . '.' . $base64Payload, $signature, $service_account['private_key'], OPENSSL_ALGO_SHA256);
            
            if (!$success) {
                throw new Exception('Failed to sign JWT token');
            }

            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

            // Request access token
            $tokenResponse = $this->requestAccessToken($jwt);
            
            if (!$tokenResponse || !isset($tokenResponse['access_token'])) {
                throw new Exception('Failed to get access token from Google');
            }

            $this->access_token = $tokenResponse['access_token'];
            $this->token_expires_at = time() + ($tokenResponse['expires_in'] ?? 3600);

            return $this->access_token;

        } catch (Exception $e) {
            log_message('error', 'Error getting FCM access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Request access token from Google OAuth 2.0
     */
    private function requestAccessToken($jwt) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            return json_decode($response, true);
        }

        log_message('error', 'Token request failed with HTTP ' . $http_code . ': ' . $response);
        return null;
    }

    /**
     * Get project ID from service account
     */
    private function getProjectId() {
        if (!file_exists($this->service_account_path)) {
            return null;
        }

        $service_account = json_decode(file_get_contents($this->service_account_path), true);
        return $service_account['project_id'] ?? null;
    }

    /**
     * Send notification to a single device (auto-detects Expo vs FCM token)
     */
    public function send_notification($token, $title, $body, $data = []) {
        // Check if it's an Expo Push Token
        if (strpos($token, 'ExponentPushToken[') === 0) {
            return $this->send_expo_notification($token, $title, $body, $data);
        } else {
            return $this->send_fcm_notification($token, $title, $body, $data);
        }
    }

    /**
     * Send FCM notification to a single device using HTTP v1 API
     */
    public function send_fcm_notification($fcm_token, $title, $body, $data = []) {
        try {
            $access_token = $this->getAccessToken();
            if (!$access_token) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token',
                    'response' => null
                ];
            }

            $project_id = $this->getProjectId();
            if (!$project_id) {
                return [
                    'success' => false,
                    'message' => 'Project ID not found in service account',
                    'response' => null
                ];
            }

            $url = str_replace('{project-id}', $project_id, $this->fcm_endpoint);

            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ];

            $message = [
                'message' => [
                    'token' => $fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => array_map('strval', $data), // FCM requires all data values to be strings
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                        ]
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1
                            ]
                        ]
                    ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response_data = json_decode($response, true);

            if ($http_code >= 200 && $http_code < 300) {
                return [
                    'success' => true,
                    'message' => 'Notification sent successfully',
                    'response' => $response_data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send notification',
                    'response' => $response_data,
                    'http_code' => $http_code
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending notification: ' . $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Send FCM notification to multiple devices
     */
    public function send_fcm_notification_multicast($fcm_tokens, $title, $body, $data = []) {
        try {
            $results = [];
            $success_count = 0;
            $failure_count = 0;

            // Send individual notifications (HTTP v1 API doesn't support multicast)
            foreach ($fcm_tokens as $token) {
                $result = $this->send_fcm_notification($token, $title, $body, $data);
                $results[] = [
                    'token' => $token,
                    'result' => $result
                ];

                if ($result['success']) {
                    $success_count++;
                } else {
                    $failure_count++;
                }
            }

            return [
                'success' => true,
                'message' => 'Multicast notifications processed',
                'results' => $results,
                'success_count' => $success_count,
                'failure_count' => $failure_count,
                'total_count' => count($fcm_tokens)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending multicast notifications: ' . $e->getMessage(),
                'results' => []
            ];
        }
    }

    /**
     * Validate token format (supports both FCM and Expo tokens)
     */
    public function validate_token($token) {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        // Check for Expo Push Token format
        if (strpos($token, 'ExponentPushToken[') === 0 && substr($token, -1) === ']') {
            return strlen($token) > 20; // Basic length check
        }

        // Check for FCM token format
        return strlen($token) > 100;
    }

    /**
     * Validate FCM token format (legacy method)
     */
    public function validate_fcm_token($token) {
        return $this->validate_token($token);
    }

    /**
     * Check if token is an Expo Push Token
     */
    public function is_expo_token($token) {
        return !empty($token) && strpos($token, 'ExponentPushToken[') === 0;
    }

    /**
     * Check if token is an FCM token
     */
    public function is_fcm_token($token) {
        return !empty($token) && strpos($token, 'ExponentPushToken[') !== 0 && strlen($token) > 100;
    }

    /**
     * Send test notification (for debugging)
     */
    public function send_test_notification($fcm_token) {
        return $this->send_fcm_notification(
            $fcm_token,
            'Test Notification',
            'This is a test notification from WiziAI',
            [
                'test' => 'true',
                'timestamp' => (string)time()
            ]
        );
    }

    /**
     * Create notification payload for Expo Push Notifications
     * This method can be used if you want to support Expo's push service as well
     */
    public function create_expo_notification($expo_token, $title, $body, $data = []) {
        return [
            'to' => $expo_token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'badge' => 1
        ];
    }

    /**
     * Send notification via Expo Push API (alternative to FCM)
     */
    public function send_expo_notification($expo_token, $title, $body, $data = []) {
        try {
            $notification = $this->create_expo_notification($expo_token, $title, $body, $data);

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Accept-Encoding: gzip, deflate'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://exp.host/--/api/v2/push/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response_data = json_decode($response, true);

            if ($http_code >= 200 && $http_code < 300) {
                // Check if Expo returned success
                if (isset($response_data['data']) && isset($response_data['data']['status']) && $response_data['data']['status'] === 'ok') {
                    return [
                        'success' => true,
                        'message' => 'Expo notification sent successfully',
                        'response' => $response_data
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Expo notification failed: ' . ($response_data['data']['message'] ?? 'Unknown error'),
                        'response' => $response_data,
                        'http_code' => $http_code
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send Expo notification',
                    'response' => $response_data,
                    'http_code' => $http_code
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending Expo notification: ' . $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Legacy method - kept for backward compatibility
     */
    private function send_expo_notification_old($expo_token, $title, $body, $data = []) {
        try {
            $notification = $this->create_expo_notification($expo_token, $title, $body, $data);

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://exp.host/--/api/v2/push/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response_data = json_decode($response, true);

            if ($http_code == 200) {
                return [
                    'success' => true,
                    'message' => 'Expo notification sent successfully',
                    'response' => $response_data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send Expo notification',
                    'response' => $response_data,
                    'http_code' => $http_code
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending Expo notification: ' . $e->getMessage(),
                'response' => null
            ];
        }
    }
}