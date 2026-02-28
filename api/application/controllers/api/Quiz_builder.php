<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Quiz_builder extends API_Controller
{

    public function __constructor()
    {
        parent::__construct();
    }

    /**
     * Generate a UUID v4
     * @return string
     */
    private function generateUUID()
    {
        // Generate random bytes
        $data = random_bytes(16);
        
        // Set version (4) and variant bits
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bits
        
        // Format as UUID string without hyphens
        return sprintf('%s%s%s%s%s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }

  
    public function calculateStringTimeToMiliseconds($timeInString)
    {
        $startTime = new DateTime("now");
        $endDate = new DateTime($timeInString);

        $interval = $startTime->diff($endDate);

        $totalMiliseconds = 0;
        $totalMiliseconds += $interval->m * 2630000000;
        $totalMiliseconds += $interval->d * 86400000;
        $totalMiliseconds += $interval->h * 3600000;
        $totalMiliseconds += $interval->i * 60000;
        $totalMiliseconds += $interval->s * 1000;
		$totalMiliseconds += $startTime->format("u")/1000;
      
        return  round($totalMiliseconds);
    }
 
    function index_get($id = NULL)
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id == NULL) {
            $id = $this->input->get("id");
        }
        $this->load->model('quiz/quiz_model');
        $recordCount = $this->quiz_model->get_quiz_count();

        if (empty($id) || $id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);

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
            else
                $multipleIds = trim($multipleIds);
            if (CUtility::endsWith($multipleIds, ",")) {
                $multipleIds = substr($multipleIds, 0, strlen($multipleIds) - 1);
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
                $filterString = substr($filterString, 0, strlen($filterString) - 5);
            }

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
            $quizs = $this->quiz_model->get_paginated_quiz($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($quizs) > 0) {
                $response = $this->get_success_response($quizs, 'quiz page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($quizs);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($quizs, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            // Check if the id is a quiz_reference (non-numeric) or quiz_id (numeric)
            if (is_numeric($id)) {
                $objquiz = $this->quiz_model->get_quiz($id);
            } else {
                log_message('debug', 'Quiz API: Looking up quiz by reference: ' . $id);
                
                $objquiz = $this->quiz_model->get_Quiz_by_reference($id);
            }
            
            if ($objquiz == NULL) {
                log_message('warning', "Quiz API: Quiz not found for ID/reference: " . $id);
                $response = $this->get_failed_response(NULL, "quiz not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                log_message('debug', "Quiz API: Quiz found successfully for ID/reference: " . $id);
                $response = $this->get_success_response($objquiz, "quiz details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post()
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $objquiz = new quiz_object();
        $objquiz->id = 0;
        $objquiz->name = $request['name'];
        $objquiz->description = $request['description'];
        $objquiz->class_id = $request['class_id'];
        $objquiz->subject_id = $request['subject_id'];
        $objquiz->exam_id = array_key_exists('exam_id', $request) ? $request['exam_id'] : 0;
        $objquiz->quiz_detail_image = array_key_exists('quiz_detail_image',$request) ? $request['quiz_detail_image'] : "";
        $objquiz->youtube_video_id = array_key_exists('youtube_video_id', $request) ? $request['youtube_video_id'] : "";
        $objquiz->marking = array_key_exists('marking',$request) ? $request['marking'] : "";

        $this->load->model('quiz/quiz_model');
        $objquiz = $this->quiz_model->add_quiz($objquiz);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($id)
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('quiz/quiz_model');
        $objquizOriginal = $this->quiz_model->get_quiz($id);

        $objquiz = new quiz_object();
        $objquiz->id = $objquizOriginal->id;
        $objquiz->name = $request['name'];
        $objquiz->description = $request['description'];
        $objquiz->class_id = $request['class_id'];
        $objquiz->subject_id = $request['subject_id'];
        $objquiz->exam_id = array_key_exists('exam_id', $request) ? $request['exam_id'] : 0;
        $objquiz->quiz_detail_image = $request['quiz_detail_image'];
        $objquiz->youtube_video_id = $request['youtube_video_id'];
        $objquiz->marking = $request['marking'];


        $objquiz = $this->quiz_model->update_quiz($objquiz);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($id = NULL)
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id != NULL) {
            $this->load->model('quiz/quiz_model');
            $deleted = $this->quiz_model->delete_quiz($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "quiz deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "quiz deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    /**
     * Get subjects that have questions with question counts
     * GET /quiz-builder/subjects
     */
    function subjects_get()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('subject/subject_model');
        
        try {
            $subjects = $this->subject_model->get_subjects_with_question_counts();
            
            if (count($subjects) > 0) {
                $response = $this->get_success_response($subjects, 'Subjects with questions retrieved successfully!');
                $response['total'] = count($subjects);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response([], 'No subjects with questions found!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error retrieving subjects: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get chapters that have questions with question counts
     * GET /quiz-builder/chapters?subject_id=1
     */
    function chapters_get()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $subject_id = $this->input->get('subject_id', true);
        
        if (empty($subject_id)) {
            $response = $this->get_failed_response(NULL, "subject_id parameter is required!");
            $this->set_output($response);
            return;
        }

        $this->load->model('chapter/chapter_model');
        
        try {
            $chapters = $this->chapter_model->get_chapters_with_question_counts($subject_id);
            
            if (count($chapters) > 0) {
                $response = $this->get_success_response($chapters, 'Chapters with questions retrieved successfully!');
                $response['total'] = count($chapters);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response([], 'No chapters with questions found for this subject!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error retrieving chapters: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get topics that have questions with question counts
     * GET /quiz-builder/topics?chapter_id=1
     */
    function topics_get()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $chapter_id = $this->input->get('chapter_id', true);
        
        if (empty($chapter_id)) {
            $response = $this->get_failed_response(NULL, "chapter_id parameter is required!");
            $this->set_output($response);
            return;
        }

        $this->load->model('topic/topic_model');
        
        try {
            $topics = $this->topic_model->get_topics_with_question_counts($chapter_id);
            
            if (count($topics) > 0) {
                $response = $this->get_success_response($topics, 'Topics with questions retrieved successfully!');
                $response['total'] = count($topics);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response([], 'No topics with questions found for this chapter!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error retrieving topics: " . $e->getMessage());
            $this->set_output($response);
        }
    }

}
