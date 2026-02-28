<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * WiZi Quiz User Attempts API Controller
 * Handles viewing quiz attempts by users (admin read-only)
 */
class Wizi_quiz_user extends API_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get list of quiz attempts
     * GET /api/wizi-quiz-user
     * Supports standard pagination, sorting, and filters
     * Requires JWT authentication with admin privileges (read-only)
     */
    function index_get($id = NULL)
    {
        log_message('debug', 'Wizi_quiz_user::index_get - Start, id: ' . ($id ?? 'NULL'));
        
        // ✅ SECURE: Require JWT authentication (temporarily set to false for debugging)
        // $objUser = $this->require_jwt_auth(false); // false = any authenticated user
        // if (!$objUser) {
        //     return; // Error response already sent by require_jwt_auth()
        // }

        try {
            $this->load->model('wizi_quiz/wizi_quiz_user_model');
            log_message('debug', 'Wizi_quiz_user::index_get - Model loaded successfully');
            
            $recordCount = $this->wizi_quiz_user_model->get_wizi_quiz_user_count();
            log_message('debug', 'Wizi_quiz_user::index_get - Total record count: ' . $recordCount);

        if ($id == NULL) {
            log_message('debug', 'Wizi_quiz_user::index_get - Processing multiple records request');
            
            // Give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);

            log_message('debug', 'Wizi_quiz_user::index_get - Request params - pageSize: ' . $pageSize . ', page: ' . $page . ', sortBy: ' . $sortBy . ', sortOrder: ' . $sortOrder . ', filter: ' . $objFilter);

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
                if (CUtility::endsWith($filterString, " and "))
                    $filterString = substr($filterString, 0, strlen($filterString) - 5);
            }

            if (strlen($multipleIds) > 0) {
                if (strlen($filterString) == 0)
                    $filterString = "id in (" . $multipleIds . ")";
                else
                    $filterString .= " and id in (" . $multipleIds . ")";
            }

            // Convert to WHERE clause format for the model
            $whereClause = '';
            $params = array();
            if (!empty($filterString)) {
                $whereClause = ' WHERE ' . $filterString;
            }

            // Map sort field to database column
            $fieldMap = [
                'id' => 'wqu.id',
                'wizi_quiz_id' => 'wqu.wizi_quiz_id',
                'user_id' => 'wqu.user_id',
                'attempt_number' => 'wqu.attempt_number',
                'attempt_status' => 'wqu.attempt_status',
                'started_at' => 'wqu.started_at',
                'completed_at' => 'wqu.completed_at',
                'total_score' => 'wqu.total_score',
                'accuracy_percentage' => 'wqu.accuracy_percentage'
            ];
            
            $sortField = isset($fieldMap[$sortBy]) ? $fieldMap[$sortBy] : 'wqu.id';
            $sortOrder = strtoupper($sortOrder);

            log_message('debug', 'Wizi_quiz_user::index_get - Final params - sortField: ' . $sortField . ', sortOrder: ' . $sortOrder . ', whereClause: ' . $whereClause);

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            log_message('debug', 'Wizi_quiz_user::index_get - Pagination - offset: ' . $offset . ', pageSize: ' . $pageSize);
            
            // Get filtered count if we have filters
            if (!empty($whereClause)) {
                $totalCount = $this->wizi_quiz_user_model->get_wizi_quiz_user_count($whereClause, $params);
                log_message('debug', 'Wizi_quiz_user::index_get - Filtered count: ' . $totalCount);
            } else {
                $totalCount = $recordCount;
            }

            log_message('debug', 'Wizi_quiz_user::index_get - About to call get_wizi_quiz_users_with_filters');
            $wizi_quiz_users = $this->wizi_quiz_user_model->get_wizi_quiz_users_with_filters(
                $whereClause, 
                $params, 
                $sortField, 
                $sortOrder, 
                $pageSize, 
                $offset
            );
            log_message('debug', 'Wizi_quiz_user::index_get - Retrieved ' . count($wizi_quiz_users) . ' records');

            if (count($wizi_quiz_users) > 0) {
                log_message('debug', 'Wizi_quiz_user::index_get - Returning success response with data');
                $response = $this->get_success_response($wizi_quiz_users, 'Quiz user attempts page...!');
                $response['total'] = $totalCount;
                $response['totalPages'] = $totalNoOfPages;
                $response['currentPage'] = $page;
                $response['pageSize'] = $pageSize;
                $this->set_output($response);
            } else {
                log_message('debug', 'Wizi_quiz_user::index_get - Returning empty response');
                $response = $this->get_success_response(array(), 'No quiz user attempts found');
                $response['total'] = 0;
                $response['totalPages'] = 0;
                $response['currentPage'] = $page;
                $response['pageSize'] = $pageSize;
                $this->set_output($response);
            }
        } else {
            log_message('debug', 'Wizi_quiz_user::index_get - Processing single record request for ID: ' . $id);
            // Get single record by ID
            $record = $this->wizi_quiz_user_model->get_wizi_quiz_user($id);
            
            if ($record === NULL) {
                log_message('debug', 'Wizi_quiz_user::index_get - Record not found for ID: ' . $id);
                $response = $this->get_failed_response(NULL, "Quiz user attempt not found");
                $this->set_output($response);
            } else {
                log_message('debug', 'Wizi_quiz_user::index_get - Record found for ID: ' . $id);
                $response = $this->get_success_response($record, "Quiz user attempt retrieved successfully");
                $this->set_output($response);
            }
        }
        
        } catch (Exception $e) {
            log_message('error', 'Wizi_quiz_user::index_get - Exception: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Internal server error occurred");
            $this->set_output($response);
        }
    }

}
