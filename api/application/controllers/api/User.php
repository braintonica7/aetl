<?php

class User extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('user/user_model');
        $recordCount = $this->user_model->get_user_count();

        if ($id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);
            $searchQuery = $this->input->get('q', true); // Get search query parameter

            $arr = (array) $objFilter;
            if (!$arr)
                $objFilter = NULL;

            if (empty($pageSize))
                $pageSize = 10;

            if (empty($page))
                $page = 1;

            if (empty($sortBy))
                $sortBy = "id";

            if (empty($sortOrder))
                $sortOrder = 'desc';

            if (empty($objFilter))
                $objFilter = NULL;
            else
                $objFilter = json_decode($objFilter);

            if (empty($multipleIds))
                $multipleIds = "";
            else {
                $pageSize = 100;
                $multipleIds = trim($multipleIds);
                if (CUtility::endsWith($multipleIds, ",")) {
                    $multipleIds = substr($multipleIds, 0, strlen($multipleIds) - 1);
                }
            }

            $filterString = "";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    foreach ($objFilter as $key => $value) {
                        if (
                                CUtility::endsWith($key, "=") ||
                                CUtility::endsWith($key, "!=") ||
                                CUtility::endsWith($key, ">") ||
                                CUtility::endsWith($key, ">=") ||
                                CUtility::endsWith($key, "<") ||
                                CUtility::endsWith($key, "<=")
                        )
                            $filterString .= $key . $value . " and ";
                        else
                            $filterString .= $key . " like('%" . $value . "%') and ";
                    }
                }
                if (CUtility::endsWith($filterString, " and "))                        
                    $filterString = substr($filterString, 0, strlen($filterString) - 5);
            }
            log_message('debug', 'User::index_get filterString before searchQuery and multipleIds: ' . $filterString);

            if (strlen($multipleIds) > 0) {
                if (strlen($filterString) == 0)
                    $filterString = "id in (" . $multipleIds . ")";
                else
                    $filterString .= " and id in (" . $multipleIds . ")";
            }


            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $users = $this->user_model->get_paginated_user($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($users) > 0) {
                $response = $this->get_success_response($users, 'User page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($users);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($users, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objUser = $this->user_model->get_user($id);
            if ($objUser == NULL) {
                $response = $this->get_failed_response(NULL, "User not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objUser, "User details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('user/user_model');
        $username_available = $this->user_model->is_username_available($request['username']);
        if (!$username_available) {
            $response = $this->get_failed_response(NULL, "Desired username already taken...!");
            $this->set_output($response);
        }

        $objUser = new User_object();
        $objUser->id = 0;
        $objUser->username = $request['username'];
        $objUser->password = $request['password'];
        $objUser->display_name = $request['display_name'];
        $objUser->role_id = $request['role_id'];
        $objUser->allow_login = $request['allow_login'];
        $objUser->last_login = NULL;

        $this->load->model('user/user_model');
        $objUser = $this->user_model->add_user($objUser);
        if ($objUser === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating user...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objUser, "User created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($id) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('user/user_model');
        $objUserOriginal = $this->user_model->get_user($id);

        $objUser = new User_object();
        $objUser->id = $objUserOriginal->id;
        $objUser->username = $request['username'];
        $objUser->password = $request['password'];
        $objUser->display_name = $request['display_name'];
        $objUser->role_id = $request['role_id'];
        $objUser->allow_login = $request['allow_login'];
        $objUser->last_login = $objUserOriginal->last_login;
        $objUser = $this->user_model->update_user($objUser);
        if ($objUser === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating user...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objUser, "User updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id != NULL) {
            $this->load->model('user/user_model');
            $deleted = $this->user_model->delete_user($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "User deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "User deletion failed...!");
                $this->set_output($response);
            }
        }
    }
 
    function update_password_post() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        $existingPassword = $request['existing_password'];
        $newPassword = $request['new_password'];

        $objUser = $this->get_logged_user();
        if ($objUser != NULL) {
            if ($objUser->password === $existingPassword) {
                $this->load->model('user/user_model');
                $updated = $this->user_model->change_password($objUser->id, $newPassword);
                if ($updated) {                    
                    $response = $this->get_success_response(NULL, "Password updated successfully...!");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "Password update Failed...!");
                    $this->set_output($response);
                }
            } else {
                $response = $this->get_failed_response(NULL, "Incorrect existing password...!");
                $this->set_output($response);
            }
        } else {
            $response = $this->get_failed_response(NULL, "Password update failed...!");
            $this->set_output($response);
        }
    }

    /**
     * Get current user's profile
     * GET /api/user/profile
     */
    function profile_get() {
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('user/user_profile_model');
        $objUserProfile = $this->user_profile_model->get_user_profile($objUser->id);
        
        // Get FCM token information for debugging
        $this->load->model('user/user_model');
        $userWithToken = $this->user_model->get_user_with_fcm_token($objUser->id);
        
        if ($objUserProfile == NULL) {
            // No profile found - return basic user info with FCM token for debugging
            $profileData = [
                'user_id' => $objUser->id,
                'username' => $objUser->username,
                'fcm_token' => $userWithToken->fcm_token ?? null,
                'fcm_token_updated_at' => $userWithToken->fcm_token_updated_at ?? null,
                'platform' => $userWithToken->platform ?? null,
            ];
            $response = $this->get_success_response($profileData, "No profile found for user. Please create one.");
            $this->set_output($response);
        } else {
            // Add FCM token information to existing profile data
            $profileArray = $objUserProfile->to_array();
            $profileArray['fcm_token'] = $userWithToken->fcm_token ?? null;
            $profileArray['fcm_token_updated_at'] = $userWithToken->fcm_token_updated_at ?? null;
            $profileArray['platform'] = $userWithToken->platform ?? null;
            
            $response = $this->get_success_response($profileArray, "User profile retrieved successfully...!");
            $this->set_output($response);
        }
    }

    /**
     * Create or update current user's profile
     * POST /api/user/profile
     */
    function profile_post() {
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        
        // Validate required fields
        if (empty($request)) {
            $response = $this->get_failed_response(NULL, "Profile data is required...!");
            $this->set_output($response);
            return;
        }

        $this->load->model('user/user_profile_model');
        
        $objUserProfile = new User_profile_object();
        $objUserProfile->user_id = $objUser->id;
        
        // Set fields from request
        if (isset($request['exam_type_id'])) $objUserProfile->exam_type_id = $request['exam_type_id'];
        if (isset($request['current_level'])) $objUserProfile->current_level = $request['current_level'];
        if (isset($request['current_score'])) $objUserProfile->current_score = $request['current_score'];
        if (isset($request['subject_scores'])) $objUserProfile->subject_scores = $request['subject_scores'];
        if (isset($request['previous_attempts'])) $objUserProfile->previous_attempts = $request['previous_attempts'];
        if (isset($request['subject_strengths'])) $objUserProfile->subject_strengths = $request['subject_strengths'];
        if (isset($request['study_pattern'])) $objUserProfile->study_pattern = $request['study_pattern'];
        if (isset($request['sleep_pattern'])) $objUserProfile->sleep_pattern = $request['sleep_pattern'];
        if (isset($request['commitments'])) $objUserProfile->commitments = $request['commitments'];
        if (isset($request['available_study_slots'])) $objUserProfile->available_study_slots = $request['available_study_slots'];

        // Validate the profile data
        $validation_errors = $objUserProfile->validate();
        if (!empty($validation_errors)) {
            $response = $this->get_failed_response(NULL, "Validation failed: " . implode(', ', $validation_errors));
            $this->set_output($response);
            return;
        }

        $savedProfile = $this->user_profile_model->save_user_profile($objUserProfile);
        
        if ($savedProfile === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while saving user profile...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($savedProfile, "User profile saved successfully...!");
            $this->set_output($response);
        }
    }

    /**
     * Get available time slots
     * GET /api/user/time-slots
     */
    function time_slots_get() {
        $this->load->model('time_slots/time_slots_model');
        $timeSlots = $this->time_slots_model->get_all_time_slots();
        
        $response = $this->get_success_response($timeSlots, "Time slots retrieved successfully...!");
        $this->set_output($response);
    }

    /**
     * Get user quota information including custom quiz limits
     * GET /api/user/quota
     */
    function quota_get() {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // Get user's current subscription type (default to free if not set)
            $subscription_type = isset($objUser->subscription_type) ? $objUser->subscription_type : $this->config->item('quiz_default_user_type');
            
            // Get the limit for this user type
            $limit_key = 'quiz_custom_limit_' . $subscription_type;
            $user_limit = $this->config->item($limit_key);
            
            // Check for custom user-specific limit override
            if (isset($objUser->custom_quiz_limit) && $objUser->custom_quiz_limit !== null) {
                $user_limit = $objUser->custom_quiz_limit;
            }
            
            // Get current count of custom quizzes using Quiz model
            $this->load->model('quiz/quiz_model');
            $current_count = $this->quiz_model->get_custom_quiz_count_by_user($objUser->id);
            
            // Calculate remaining quizzes
            $remaining = ($user_limit == -1) ? -1 : max(0, $user_limit - $current_count);
            $quota_exceeded = ($user_limit != -1) && ($current_count >= $user_limit);
            
            // Prepare response data
            $quota_data = array(
                'subscription_type' => $subscription_type,
                'custom_quiz_count' => $current_count,
                'custom_quiz_limit' => $user_limit,
                'remaining_quizzes' => $remaining,
                'quota_exceeded' => $quota_exceeded,
                'unlimited' => ($user_limit == -1)
            );
            
            // Add warning if approaching limit
            if (!$quota_exceeded && $user_limit != -1) {
                $warning_threshold = $this->config->item('quiz_quota_warning_threshold');
                $is_approaching_limit = ($current_count / $user_limit) >= $warning_threshold;
                
                if ($is_approaching_limit) {
                    $warning_message = str_replace('{remaining}', $remaining, $this->config->item('quiz_warning_approaching_limit'));
                    $quota_data['warning'] = $warning_message;
                    $quota_data['approaching_limit'] = true;
                }
            }
            
            $response = $this->get_success_response($quota_data, "User quota retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error getting user quota for user {$objUser->id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve quota information");
            $this->set_output($response);
        }
    }

    /**
     * Get user's subscription features with limits and usage
     * 
     * Endpoint: GET /api/user/subscription_features
     * 
     * This endpoint returns all subscription features for the user based on their
     * active subscription or falls back to the free plan if no active subscription exists.
     * 
     * Features include:
     * - Quota type: custom_quiz, pyqs, mock_tests (with usage tracking)
     * - Boolean type: quiz_solutions, report_card, performance_analysis, etc.
     * - Credits type: ai_mentor (with usage tracking)
     * 
     * Response includes:
     * - Subscription details (plan name, status, expiry)
     * - All features with their limits, current usage, and remaining quota
     * 
     * @return JSON response with subscription features
     */
    function subscription_features_get() {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // Get subscription features from User_model
            $this->load->model('user/user_model');
            $subscription_data = $this->user_model->get_user_subscription_features($objUser->id);
            
            $response = $this->get_success_response($subscription_data, "Subscription features retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error getting subscription features for user {$objUser->id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve subscription features");
            $this->set_output($response);
        }
    }

    /**
     * Request account deletion (soft delete with 30-day grace period)
     * DELETE /api/user/delete-account
     * 
     * This endpoint marks the user account for deletion:
     * 1. Verifies user password for security
     * 2. Marks account as deleted (soft delete)
     * 3. Clears all active user sessions
     * 4. Sends email notification about 30-day grace period
     * 5. User cannot login after this
     * 6. Admin manually deletes account after 30 days
     * 
     * Request Body:
     * {
     *   "password": "user_current_password",
     *   "reason": "optional deletion reason"
     * }
     * 
     * @return JSON response with success/failure status
     */
    function delete_account_delete() {
        // ✅ SECURE: Require JWT authentication (user deleting their own account)
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        // Validate password is provided
        if (!isset($request['password']) || empty($request['password'])) {
            $response = $this->get_failed_response(NULL, "Password is required to delete your account");
            $this->set_output($response);
            return;
        }

        $password = $request['password'];
        $reason = isset($request['reason']) ? $request['reason'] : null;

        try {
            $this->load->model('user/user_model');

            // Get user with full details to verify password
            $userToDelete = $this->user_model->get_user($objUser->id);
            
            if ($userToDelete == NULL) {
                $response = $this->get_failed_response(NULL, "User not found");
                $this->set_output($response);
                return;
            }

            // Verify password
            if (!password_verify($password, $userToDelete->password)) {
                $response = $this->get_failed_response(NULL, "Incorrect password. Please try again.");
                $this->set_output($response);
                return;
            }

            // Check if account is already marked for deletion
            if (isset($userToDelete->is_deleted) && $userToDelete->is_deleted == 1) {
                $response = $this->get_failed_response(NULL, "Your account is already marked for deletion");
                $this->set_output($response);
                return;
            }

            // Mark user account for deletion (soft delete)
            $deletion_success = $this->user_model->mark_user_for_deletion($objUser->id, $reason);

            if (!$deletion_success) {
                $response = $this->get_failed_response(NULL, "Failed to process account deletion request");
                $this->set_output($response);
                return;
            }

            // Clear all active user sessions
            $this->user_model->clear_user_sessions($objUser->id);

            // Send email notification about account deletion
            $this->load->library('email');
            $this->email->from('noreply@wiziai.com', 'WiziAI Support');
            $this->email->to($userToDelete->username); // username is email
            $this->email->subject('Account Deletion Request - 30 Day Grace Period');
            
            $email_message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f8f9fa; padding: 30px; margin-top: 20px; }
                        .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                        .info-box { background-color: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 30px; color: #6c757d; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Account Deletion Request Received</h2>
                        </div>
                        <div class='content'>
                            <p>Hi {$userToDelete->display_name},</p>
                            
                            <p>We've received your request to delete your WiziAI account. We're sorry to see you go!</p>
                            
                            <div class='warning'>
                                <strong>⚠️ Important Information:</strong>
                                <ul>
                                    <li>Your account has been marked for deletion</li>
                                    <li>You cannot login to your account anymore</li>
                                    <li>Your account will be permanently deleted after <strong>30 days</strong></li>
                                    <li>During this grace period, you can contact support to restore your account</li>
                                </ul>
                            </div>
                            
                            <div class='info-box'>
                                <strong>📅 Timeline:</strong>
                                <ul>
                                    <li><strong>Deletion Requested:</strong> " . date('F j, Y, g:i a') . "</li>
                                    <li><strong>Final Deletion Date:</strong> " . date('F j, Y', strtotime('+30 days')) . "</li>
                                </ul>
                            </div>
                            
                            <p><strong>What happens next?</strong></p>
                            <ul>
                                <li>Your account is now inaccessible (you cannot login)</li>
                                <li>Your data will be kept for 30 days</li>
                                <li>After 30 days, an administrator will permanently delete all your data</li>
                                <li>If you change your mind, contact our support team to restore your account</li>
                            </ul>
                            
                            <p><strong>Want to restore your account?</strong></p>
                            <p>If you didn't request this deletion or changed your mind, please contact us immediately at <a href='mailto:support@wiziai.com'>support@wiziai.com</a></p>
                            
                            <p>Thank you for being part of WiziAI. We wish you all the best!</p>
                            
                            <p>Best regards,<br>The WiziAI Team</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated email. Please do not reply directly to this message.</p>
                            <p>&copy; 2025 WiziAI Educational Platform. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $this->email->message($email_message);
            $this->email->set_mailtype('html');
            
            // Attempt to send email (don't fail if email doesn't send)
            $email_sent = $this->email->send();
            if (!$email_sent) {
                log_message('error', 'Failed to send deletion email to user: ' . $userToDelete->username);
            }

            // Return success response
            $response_data = array(
                'message' => 'Your account has been marked for deletion',
                'grace_period_days' => 30,
                'deletion_date' => date('Y-m-d', strtotime('+30 days')),
                'email_sent' => $email_sent,
                'info' => 'You cannot login anymore. Contact support within 30 days to restore your account.'
            );

            $response = $this->get_success_response(
                $response_data, 
                "Account deletion request processed successfully. You will receive an email confirmation."
            );
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', "Error processing account deletion for user {$objUser->id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "An error occurred while processing your request. Please try again.");
            $this->set_output($response);
        }
    }

    /**
     * Get users pending deletion (Admin only)
     * GET /api/user/pending-deletion
     * 
     * Returns list of all users marked for deletion with grace period info
     * 
     * @return JSON response with list of users pending deletion
     */
    function pending_deletion_get() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $this->load->model('user/user_model');
            $pending_users = $this->user_model->get_users_pending_deletion();
            
            $response = $this->get_success_response(
                $pending_users, 
                "Retrieved " . count($pending_users) . " users pending deletion"
            );
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error getting pending deletion users: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve pending deletion users");
            $this->set_output($response);
        }
    }

    /**
     * Restore a deleted user account (Admin only)
     * POST /api/user/restore-account
     * 
     * Restores a user account that was marked for deletion
     * 
     * Request Body:
     * {
     *   "user_id": 123
     * }
     * 
     * @return JSON response with success/failure status
     */
    function restore_account_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        if (!isset($request['user_id']) || empty($request['user_id'])) {
            $response = $this->get_failed_response(NULL, "User ID is required");
            $this->set_output($response);
            return;
        }

        $user_id = $request['user_id'];

        try {
            $this->load->model('user/user_model');
            
            // Restore the account
            $restore_success = $this->user_model->restore_user_account($user_id);

            if (!$restore_success) {
                $response = $this->get_failed_response(NULL, "Failed to restore account");
                $this->set_output($response);
                return;
            }

            // Get restored user details
            $restored_user = $this->user_model->get_user($user_id);

            // Send email notification about account restoration
            if ($restored_user) {
                $this->load->library('email');
                $this->email->from('noreply@wiziai.com', 'WiziAI Support');
                $this->email->to($restored_user->username);
                $this->email->subject('Your WiziAI Account Has Been Restored');
                
                $email_message = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #28a745;'>Welcome Back to WiziAI! 🎉</h2>
                            <p>Hi {$restored_user->display_name},</p>
                            <p>Great news! Your WiziAI account has been successfully restored.</p>
                            <p><strong>You can now login to your account and continue your learning journey.</strong></p>
                            <p>All your data, quiz history, and points have been preserved.</p>
                            <p>If you have any questions, please contact us at support@wiziai.com</p>
                            <p>Best regards,<br>The WiziAI Team</p>
                        </div>
                    </body>
                    </html>
                ";
                
                $this->email->message($email_message);
                $this->email->set_mailtype('html');
                $this->email->send();
            }

            $response = $this->get_success_response(
                array('user_id' => $user_id, 'username' => $restored_user->username),
                "Account restored successfully"
            );
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', "Error restoring account for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to restore account");
            $this->set_output($response);
        }
    }

}

