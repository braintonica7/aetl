<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Image Analysis Controller
 * Handles AI-powered image analysis for question extraction
 */
class Image_analysis extends API_Controller {

    function __construct() {
        parent::__construct();
        
        // Load the image analysis service
        $this->load->library('AI_Image_Analysis_Service');
    }

    /**
     * Analyze a single question image
     * POST /api/image_analysis/analyze_question_post
     * 
     * Request body:
     * {
     *   "image_url": "https://example.com/question.jpg",
     *   "additional_context": "This is a physics question about mechanics"
     * }
     */
    public function analyze_question_post() {
        $this->require_jwt_auth(true); // Admin level auth - AI image analysis is expensive
        try {
            // Get request data
            $request_data = $this->get_request();
            
            if (!$request_data) {
                $response = $this->get_failed_response("", "Request data is required");
                $this->set_output($response);
                return;
            }
            
            // Validate image URL
            if (empty($request_data['image_url'])) {
                $response = $this->get_failed_response("", "Image URL is required");
                $this->set_output($response);
                return;
            }
            
            $image_url = $request_data['image_url'];
            $additional_context = $request_data['additional_context'] ?? '';
            
            // Perform image analysis
            $analysis_result = $this->ai_image_analysis_service->analyze_question_image($image_url, $additional_context);
            
            if ($analysis_result === FALSE) {
                $response = $this->get_failed_response("", "Failed to analyze image. Please check the URL and try again.");
                $this->set_output($response);
                return;
            }
            
            // Return successful analysis
            $response = $this->get_success_response($analysis_result, "Image analyzed successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in analyze_question_post: " . $e->getMessage());
            $response = $this->get_failed_response("", "An error occurred during image analysis");
            $this->set_output($response);
        }
    }
    
    /**
     * Generate step-by-step solution for a question image
     * POST /api/image_analysis/generate_solution_post
     * 
     * Request body:
     * {
     *   "image_url": "https://example.com/question.jpg",
     *   "correct_option": "Option B (optional)",
     *   "additional_context": "This is a physics mechanics problem",
     *   "question_id": 123 (optional - if provided, solution will be saved to question table)
     * }
     */
    public function generate_solution_post() {
        $this->require_jwt_auth(true); // Admin level auth - AI solution generation is expensive
        try {
            // Get request data
            $request_data = $this->get_request();
            
            if (!$request_data) {
                $response = $this->get_failed_response("", "Request data is required");
                $this->set_output($response);
                return;
            }
            
            // Validate image URL
            if (empty($request_data['image_url'])) {
                $response = $this->get_failed_response("", "Image URL is required");
                $this->set_output($response);
                return;
            }
            
            $image_url = $request_data['image_url'];
            $correct_option = $request_data['correct_option'] ?? '';
            $additional_context = $request_data['additional_context'] ?? '';
            $question_id = $request_data['question_id'] ?? null;
            log_message('debug', 'Question ID for solution generation: ' . $question_id);
            // Generate solution for the image
            $solution_result = $this->ai_image_analysis_service->generate_solution_for_image($image_url, $correct_option, $additional_context);
            // getting error  Array to string conversion for below line
            //log_message('debug', 'Solution Result response: ' . print_r($solution_result, true));
            log_message('debug', 'Before Solution Received for : ' . $question_id);
            if ($solution_result === FALSE) {
                $response = $this->get_failed_response("", "Failed to generate solution. Please check the URL and try again.");
                $this->set_output($response);
                return;
            }
            log_message('debug', 'Solution Received for : ' . $question_id);
            // If question_id is provided, save the solution to the question table
            if (!empty($question_id)) {
                $this->load->model('question/question_model');
                log_message('debug', 'Saving solution for question ID: ' . $question_id);
                // Prepare solution data for database storage
                $solution_json = json_encode($solution_result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                log_message('debug', 'Saving solution JSON: ' . $solution_json);

                $update_data = array(
                    'solution' => $solution_json,
                    'id' => $question_id
                );
                
                // Update the question with the generated solution
                $update_result = $this->question_model->update_solution_latest($question_id, $solution_json);
                log_message('debug', 'Update Result: ' . var_export($update_result, true));

                if ($update_result) {
                    log_message('debug', "Successfully updated question ID: " . $question_id);
                    // Add success info to the response metadata
                    $solution_result['metadata']['question_updated'] = true;
                    $solution_result['metadata']['question_id'] = $question_id;
                    $solution_result['metadata']['solution_saved_at'] = date('Y-m-d H:i:s');
                } else {
                    // Log warning but don't fail the entire request
                    log_message('debug', "Warning: Failed to save solution to question ID: " . $question_id);
                    
                    $solution_result['metadata']['question_updated'] = false;
                    $solution_result['metadata']['save_error'] = 'Failed to update question table';
                }
            }
            
            // Return successful solution generation
            $response = $this->get_success_response($solution_result, "Solution generated successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in generate_solution_post: " . $e->getMessage());
            $response = $this->get_failed_response("", "An error occurred during solution generation");
            $this->set_output($response);
        }
    }
    
    /**
     * Batch analyze multiple question images
     * POST /api/image_analysis/batch_analyze_post
     * 
     * Request body:
     * {
     *   "image_urls": [
     *     "https://example.com/question1.jpg",
     *     "https://example.com/question2.jpg"
     *   ],
     *   "additional_context": "These are physics questions"
     * }
     */
    public function batch_analyze_post() {
        $this->require_jwt_auth(true); // Admin level auth - batch AI analysis is very expensive
        try {
            // Get request data
            $request_data = $this->get_request();
            
            if (!$request_data) {
                $response = $this->get_failed_response("", "Request data is required");
                $this->set_output($response);
                return;
            }
            
            // Validate input
            if (!isset($request_data['image_urls']) || !is_array($request_data['image_urls']) || empty($request_data['image_urls'])) {
                $response = $this->get_failed_response("", "Image URLs array is required and cannot be empty");
                $this->set_output($response);
                return;
            }
            
            $image_urls = $request_data['image_urls'];
            $additional_context = $request_data['additional_context'] ?? '';
            
            // Limit batch size for performance
            if (count($image_urls) > 10) {
                $response = $this->get_failed_response("", "Maximum 10 images allowed per batch");
                $this->set_output($response);
                return;
            }
            
            // Perform batch analysis
            $batch_result = $this->ai_image_analysis_service->batch_analyze_images($image_urls, $additional_context);
            
            // Return results
            $response = $this->get_success_response($batch_result, "Batch analysis completed");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in batch_analyze_post: " . $e->getMessage());
            $response = $this->get_failed_response("", "An error occurred during batch analysis");
            $this->set_output($response);
        }
    }
    
    /**
     * Get service health status
     * GET /api/image_analysis/health_get
     */
    public function health_get() {
        try {
            $status = $this->ai_image_analysis_service->get_service_status();
            $response = $this->get_success_response($status, "Service status retrieved");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in health_get: " . $e->getMessage());
            $response = $this->get_failed_response("", "Failed to get service status");
            $this->set_output($response);
        }
    }
    
    /**
     * Test image analysis with a sample image
     * POST /api/image_analysis/test_post
     * 
     * Request body:
     * {
     *   "test_image_url": "https://example.com/sample-question.jpg"
     * }
     */
    public function test_post() {
        $this->require_jwt_auth(true); // Admin level auth - testing endpoint
        try {
            // Get request data
            $request_data = $this->get_request();
            
            if (!$request_data || !isset($request_data['test_image_url'])) {
                $response = $this->get_failed_response("", "Test image URL is required");
                $this->set_output($response);
                return;
            }
            
            $test_image_url = $request_data['test_image_url'];
            
            // Perform test analysis
            $start_time = microtime(true);
            $analysis_result = $this->ai_image_analysis_service->analyze_question_image(
                $test_image_url, 
                "This is a test analysis"
            );
            $end_time = microtime(true);
            $processing_time = round(($end_time - $start_time) * 1000, 2); // milliseconds
            
            if ($analysis_result === FALSE) {
                $response = $this->get_failed_response("", "Test analysis failed");
                $this->set_output($response);
                return;
            }
            
            // Add test metadata
            $test_result = array(
                'test_status' => 'success',
                'processing_time_ms' => $processing_time,
                'analysis_result' => $analysis_result,
                'test_timestamp' => date('Y-m-d H:i:s')
            );
            
            $response = $this->get_success_response($test_result, "Test analysis completed successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in test_post: " . $e->getMessage());
            $response = $this->get_failed_response("", "Test analysis failed");
            $this->set_output($response);
        }
    }
    
    /**
     * Extract question data and save to database
     * POST /api/image_analysis/extract_and_save_post
     * 
     * Request body:
     * {
     *   "image_url": "https://example.com/question.jpg",
     *   "quiz_id": 123,
     *   "additional_context": "Physics mechanics question"
     * }
     */
    public function extract_and_save_post() {
        $this->require_jwt_auth(true); // Admin level auth - extracts and saves questions
        try {
            // Get request data
            $request_data = $this->get_request();
            
            if (!$request_data || !isset($request_data['image_url']) || !isset($request_data['quiz_id'])) {
                $response = $this->get_failed_response("", "Image URL and Quiz ID are required");
                $this->set_output($response);
                return;
            }
            
            $image_url = $request_data['image_url'];
            $quiz_id = $request_data['quiz_id'];
            $additional_context = $request_data['additional_context'] ?? '';
            
            // Perform image analysis
            $analysis_result = $this->ai_image_analysis_service->analyze_question_image($image_url, $additional_context);
            
            if ($analysis_result === FALSE) {
                $response = $this->get_failed_response("", "Failed to analyze image");
                $this->set_output($response);
                return;
            }
            
            // Load question model to save extracted data
            $this->load->model('question_model');
            
            // Prepare question data for database
            $question_data = array(
                'quiz_id' => $quiz_id,
                'question' => $analysis_result['questionText'],
                'ai_summary' => $analysis_result['aiSummary'],
                'summary_generated_at' => date('Y-m-d H:i:s'),
                'summary_confidence' => $analysis_result['confidence'],
                'option_a' => $analysis_result['extractedOptions'][0] ?? '',
                'option_b' => $analysis_result['extractedOptions'][1] ?? '',
                'option_c' => $analysis_result['extractedOptions'][2] ?? '',
                'option_d' => $analysis_result['extractedOptions'][3] ?? '',
                'subject_name' => $analysis_result['subject'],
                'chapter_name' => $analysis_result['chapter'],
                'topic_name' => $analysis_result['topic'],
                'difficulty' => $analysis_result['difficulty'],
                'image_url' => $image_url,
                'ai_confidence' => $analysis_result['confidence'],
                'has_formulas' => $analysis_result['hasFormulas'] ? 1 : 0,
                'has_diagrams' => $analysis_result['hasDiagrams'] ? 1 : 0,
                'additional_info' => $analysis_result['additionalInfo'],
                'created_at' => date('Y-m-d H:i:s')
            );
            
            // Save to database
            $question_id = $this->question_model->add_question($question_data);
            
            if ($question_id === FALSE) {
                $response = $this->get_failed_response("", "Failed to save question to database");
                $this->set_output($response);
                return;
            }
            
            // Return success with both analysis and save results
            $result = array(
                'question_id' => $question_id,
                'analysis_result' => $analysis_result,
                'saved_successfully' => true
            );
            
            $response = $this->get_success_response($result, "Question extracted and saved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in extract_and_save_post: " . $e->getMessage());
            $response = $this->get_failed_response("", "Failed to extract and save question");
            $this->set_output($response);
        }
    }
    
    /**
     * Get supported image formats and service info
     * GET /api/image_analysis/info_get
     */
    public function info_get() {
        $this->require_jwt_auth(false); // User level auth - service info
        $info = array(
            'service_name' => 'AI Image Analysis API',
            'version' => '1.0.0',
            'supported_formats' => array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'),
            'max_batch_size' => 10,
            'max_image_size' => '20MB',
            'timeout' => '60 seconds',
            'features' => array(
                'question_text_extraction',
                'option_extraction',
                'subject_classification',
                'chapter_identification',
                'topic_identification',
                'difficulty_assessment',
                'formula_detection',
                'diagram_detection',
                'batch_processing',
                'confidence_scoring',
                'step_by_step_solution_generation',
                'solution_explanation',
                'common_mistakes_identification',
                'alternative_approaches',
                'study_tips_generation'
            ),
            'endpoints' => array(
                'POST /api/image_analysis/analyze_question_post' => 'Analyze single image',
                'POST /api/image_analysis/generate_solution_post' => 'Generate step-by-step solution',
                'POST /api/image_analysis/batch_analyze_post' => 'Analyze multiple images',
                'POST /api/image_analysis/extract_and_save_post' => 'Extract and save to database',
                'POST /api/image_analysis/test_post' => 'Test analysis',
                'GET /api/image_analysis/health_get' => 'Service health check',
                'GET /api/image_analysis/info_get' => 'Service information'
            )
        );
        
        $response = $this->get_success_response($info, "Service information retrieved");
        $this->set_output($response);
    }
}

?>
