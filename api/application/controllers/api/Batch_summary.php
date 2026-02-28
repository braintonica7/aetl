<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Batch_summary extends API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('AI_Image_Analysis_Service');
        $this->load->model('user_performance/user_performance_model');
    }

    /**
     * Generate question text and AI summaries from images for questions that don't have them
     * GET /api/batch_summary/generate_from_images/{limit}
     */
    function generate_from_images_get($limit = 10) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        // $objUser = $this->require_jwt_auth(true); // true = admin required
        // if (!$objUser) {
        //     return; // Error response already sent by require_jwt_auth()
        // }

        try {
            $pdo = CDatabase::getPdo();
            
            // Get questions with images but without question text or AI summaries
            $sql = "SELECT id, question_img_url, question_text, ai_summary FROM question WHERE  language = 'en' AND question_img_url IS NOT NULL AND question_img_url != '' AND ( (question_text IS NULL OR question_text = '') OR (ai_summary IS NULL OR ai_summary = '')) LIMIT 0, $limit";
            log_message('debug', 'SQL : ' . $sql);

            $statement = $pdo->prepare($sql);
            $statement->execute();
            $questions = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($questions)) {
                $response = $this->get_success_response(
                    array('processed' => 0, 'message' => 'No questions need image analysis'),
                    "All questions with images already have text and summaries"
                );
                $this->set_output($response);
                return;
            }
            
            $processed = 0;
            $errors = array();
            $skipped = 0;
            // log number of questions fetched //
            //log_message('debug', 'Fetched questions: ' . count($questions));

            foreach ($questions as $question) {
                try {
                    // Analyze the question image to get text and summary
                    //log the image url
                    log_message('debug', 'Analyzing image for question ID ' . $question['id'] . ': ' . $question['question_img_url']);
                    $analysis_result = $this->ai_image_analysis_service->analyze_question_image($question['question_img_url']);
                    //log analysis result
                    log_message('debug', 'Analysis result for question ID ' . $question['id'] . ': ' . json_encode($analysis_result));  

                    if ($analysis_result && !empty($analysis_result['questionText'])) {
                        $update_fields = array();
                        $update_values = array();
                        
                        // Update question text if missing
                        if (empty($question['question_text'])) {
                            $update_fields[] = "question_text = ?";
                            $update_values[] = $analysis_result['questionText'];
                        }
                        
                        // Update AI summary if missing
                        if (empty($question['ai_summary']) && !empty($analysis_result['aiSummary'])) {
                            $update_fields[] = "ai_summary = ?";
                            $update_fields[] = "summary_confidence = ?";
                            $update_values[] = $analysis_result['aiSummary'];
                            $update_values[] = $analysis_result['confidence'] ?? 0.8;
                        }
                        
                        // Update other fields if available
                        if (!empty($analysis_result['extractedOptions'][0])) {
                            $update_fields[] = "option_a = ?";
                            $update_values[] = $analysis_result['extractedOptions'][0] ?? '';
                        }
                        if (!empty($analysis_result['extractedOptions'][1])) {
                            $update_fields[] = "option_b = ?";
                            $update_values[] = $analysis_result['extractedOptions'][1] ?? '';
                        }
                        if (!empty($analysis_result['extractedOptions'][2])) {
                            $update_fields[] = "option_c = ?";
                            $update_values[] = $analysis_result['extractedOptions'][2] ?? '';
                        }
                        if (!empty($analysis_result['extractedOptions'][3])) {
                            $update_fields[] = "option_d = ?";
                            $update_values[] = $analysis_result['extractedOptions'][3] ?? '';
                        }
                        
                        if (!empty($analysis_result['subject'])) {
                            $update_fields[] = "subject_name = ?";
                            $update_values[] = $analysis_result['subject'];
                        }
                        if (!empty($analysis_result['chapter'])) {
                            $update_fields[] = "chapter_name = ?";
                            $update_values[] = $analysis_result['chapter'];
                        }
                        if (!empty($analysis_result['topic'])) {
                            $update_fields[] = "topic_name = ?";
                            $update_values[] = $analysis_result['topic'];
                        }
                        if (!empty($analysis_result['difficulty'])) {
                            $update_fields[] = "difficulty = ?";
                            $update_values[] = $analysis_result['difficulty'];
                        }
                        
                        if (!empty($update_fields)) {
                            $update_values[] = $question['id']; // For WHERE clause
                            
                            // Add timestamp fields that don't need parameters
                            $timestamp_fields = array();
                            if (empty($question['ai_summary']) && !empty($analysis_result['aiSummary'])) {
                                $timestamp_fields[] = "summary_generated_at = NOW()";
                            }
                            
                            // Combine parameterized fields with timestamp fields
                            $all_fields = array_merge($update_fields, $timestamp_fields);
                            
                            $update_sql = "UPDATE question SET " . implode(', ', $all_fields) . " WHERE id = ?";
                            //log_message('debug', 'update SQL : ' . $update_sql);

                            $update_statement = $pdo->prepare($update_sql);
                            $update_statement->execute($update_values);
                        }
                        
                        $processed++;
                    } else {
                        log_message('debug', 'Failed to analyze image for question ID: ' . $question['id']);
                        $errors[] = "Failed to analyze image for question ID: " . $question['id'];
                    }
                    
                    // Add delay to avoid rate limiting
                    sleep(2); // 2 second delay for image analysis
                    
                } catch (Exception $e) {
                    $errors[] = "Error processing question ID " . $question['id'] . ": " . $e->getMessage();
                    log_message('error', "Batch Image Analysis Error: " . $e->getMessage());
                }
            }
            
            $response_data = array(
                'processed' => $processed,
                'skipped' => $skipped,
                'total_found' => count($questions),
                'errors' => $errors,
                'success_rate' => count($questions) > 0 ? round(($processed / count($questions)) * 100, 2) : 0
            );
            
            $response = $this->get_success_response($response_data, "Batch image analysis completed");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Batch Image Analysis Controller Error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to process batch image analysis: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Generate AI summaries for existing questions that have text but no summaries
     * GET /api/batch_summary/generate_missing_summaries/{limit}
     */
    function generate_missing_summaries_get($limit = 50) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $pdo = CDatabase::getPdo();
            
            // Get questions with text but without AI summaries
            $sql = "SELECT id, question_text 
                   FROM question 
                   WHERE (ai_summary IS NULL OR ai_summary = '') 
                   AND language = 'en' 
                   AND question_text IS NOT NULL 
                   AND question_text != ''
                   LIMIT ?";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($limit));
            $questions = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($questions)) {
                $response = $this->get_success_response(
                    array('processed' => 0, 'message' => 'No questions need summary generation'),
                    "All questions already have summaries"
                );
                $this->set_output($response);
                return;
            }
            
            $processed = 0;
            $errors = array();
            
            foreach ($questions as $question) {
                try {
                    // Generate AI summary
                    $summary_result = $this->ai_image_analysis_service->generate_question_summary($question['question_text']);
                    
                    if ($summary_result && !empty($summary_result['summary'])) {
                        // Update the question with AI summary
                        $update_sql = "UPDATE question 
                                      SET ai_summary = ?, 
                                          summary_generated_at = NOW(), 
                                          summary_confidence = ?
                                      WHERE id = ?";
                        
                        $update_statement = $pdo->prepare($update_sql);
                        $update_statement->execute(array(
                            $summary_result['summary'],
                            $summary_result['confidence'],
                            $question['id']
                        ));
                        
                        $processed++;
                    } else {
                        $errors[] = "Failed to generate summary for question ID: " . $question['id'];
                    }
                    
                    // Add small delay to avoid rate limiting
                    usleep(100000); // 0.1 second delay
                    
                } catch (Exception $e) {
                    $errors[] = "Error processing question ID " . $question['id'] . ": " . $e->getMessage();
                    log_message('error', "Batch Summary Error: " . $e->getMessage());
                }
            }
            
            $response_data = array(
                'processed' => $processed,
                'total_found' => count($questions),
                'errors' => $errors,
                'success_rate' => count($questions) > 0 ? round(($processed / count($questions)) * 100, 2) : 0
            );
            
            $response = $this->get_success_response($response_data, "Batch summary generation completed");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Batch Summary Controller Error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to process batch summaries: " . $e->getMessage());
            $this->set_output($response);
        }
    }
    
    /**
     * Get statistics about question summaries and image analysis
     * GET /api/batch_summary/stats
     */
    function stats_get() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $pdo = CDatabase::getPdo();
            
            // Get comprehensive statistics
            $sql = "SELECT 
                       COUNT(*) as total_questions,
                       SUM(CASE WHEN question_text IS NOT NULL AND question_text != '' THEN 1 ELSE 0 END) as with_text,
                       SUM(CASE WHEN question_text IS NULL OR question_text = '' THEN 1 ELSE 0 END) as without_text,
                       SUM(CASE WHEN ai_summary IS NOT NULL AND ai_summary != '' THEN 1 ELSE 0 END) as with_summaries,
                       SUM(CASE WHEN ai_summary IS NULL OR ai_summary = '' THEN 1 ELSE 0 END) as without_summaries,
                       SUM(CASE WHEN question_image_url IS NOT NULL AND question_image_url != '' THEN 1 ELSE 0 END) as with_images,
                       SUM(CASE WHEN question_image_url IS NULL OR question_image_url = '' THEN 1 ELSE 0 END) as without_images,
                       SUM(CASE WHEN question_image_url IS NOT NULL AND question_image_url != '' AND (question_text IS NULL OR question_text = '') THEN 1 ELSE 0 END) as images_need_analysis,
                       SUM(CASE WHEN (question_text IS NOT NULL AND question_text != '') AND (ai_summary IS NULL OR ai_summary = '') THEN 1 ELSE 0 END) as text_needs_summary,
                       AVG(summary_confidence) as avg_confidence,
                       MIN(summary_generated_at) as first_generated,
                       MAX(summary_generated_at) as last_generated
                   FROM question";
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $stats = $statement->fetch(PDO::FETCH_ASSOC);
            
            // Calculate percentages
            $stats['text_coverage_percentage'] = $stats['total_questions'] > 0 
                ? round(($stats['with_text'] / $stats['total_questions']) * 100, 2) 
                : 0;
                
            $stats['summary_coverage_percentage'] = $stats['total_questions'] > 0 
                ? round(($stats['with_summaries'] / $stats['total_questions']) * 100, 2) 
                : 0;
                
            $stats['image_coverage_percentage'] = $stats['total_questions'] > 0 
                ? round(($stats['with_images'] / $stats['total_questions']) * 100, 2) 
                : 0;
            
            $response = $this->get_success_response($stats, "Comprehensive statistics retrieved");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Batch Summary Stats Error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to get summary statistics: " . $e->getMessage());
            $this->set_output($response);
        }
    }
    
    /**
     * Generate solutions for questions that don't have them
     * GET /api/batch_summary/generate_batch_solutions/{limit}
     */
    function generate_batch_solutions_get($limit = 10) {
        // ✅ CRON JOB AUTH: Check for cron job header or skip authentication
        $is_cron_job = $this->input->get_request_header('X-Cron-Job') === 'true' || 
                       $this->input->get('cron_key') === 'batch_cron_2024';
        
        if (!$is_cron_job) {
            // Require admin auth for manual calls
            $objUser = $this->require_jwt_auth(true);
            if (!$objUser) {
                return; // Error response already sent by require_jwt_auth()
            }
        }

        try {
            $pdo = CDatabase::getPdo();
            
            // Get questions with images but without solutions
            $sql = "SELECT id, question_img_url, correct_option FROM question WHERE  language = 'en' AND question_img_url IS NOT NULL AND question_img_url != '' AND (solution IS NULL OR solution = '') LIMIT 10";

            log_message('debug', 'Batch Solutions SQL: ' . $sql . ' with limit: ' . $limit);
            
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $questions = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($questions)) {
                $response = $this->get_success_response(
                    array('processed' => 0, 'message' => 'No questions need solution generation'),
                    "All questions with images already have solutions"
                );
                $this->set_output($response);
                return;
            }
            
            log_message('debug', 'Found ' . count($questions) . ' questions needing solutions');
            
            $processed = 0;
            $errors = array();
            $processed_question_ids = array(); // Track successfully processed question IDs
            
            // Load required model for saving solutions
            $this->load->model('question/question_model');
            
            foreach ($questions as $question) {
                try {
                    $question_id = $question['id'];
                    $image_url = $question['question_img_url'];
                    $correct_option = $question['correct_option'] ?? '';
                    
                    log_message('debug', 'Generating solution for question ID: ' . $question_id . ' with image: ' . $image_url);
                    
                    // Generate solution using AI service
                    $solution_result = $this->ai_image_analysis_service->generate_solution_for_image(
                        $image_url, 
                        $correct_option, 
                        '' // no additional context needed
                    );
                    
                    if ($solution_result === FALSE || empty($solution_result)) {
                        log_message('debug', 'Failed to generate solution for question ID: ' . $question_id);
                        $errors[] = "Failed to generate solution for question ID: " . $question_id;
                        continue; // Skip to next question
                    }
                    
                    log_message('debug', 'Solution generated for question ID: ' . $question_id);
                    
                    // Prepare solution data for database storage
                    $solution_json = json_encode($solution_result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    
                    // Save solution using the same method as individual generation
                    $update_result = $this->question_model->update_solution_latest($question_id, $solution_json);
                    
                    if ($update_result) {
                        log_message('debug', 'Successfully saved solution for question ID: ' . $question_id);
                        $processed++;
                        $processed_question_ids[] = $question_id; // Add to successful IDs list
                    } else {
                        log_message('debug', 'Failed to save solution for question ID: ' . $question_id);
                        $errors[] = "Failed to save solution for question ID: " . $question_id;
                    }
                    
                    // Add 5 second delay to avoid rate limiting
                    sleep(5);
                    
                } catch (Exception $e) {
                    $error_msg = "Error processing question ID " . $question['id'] . ": " . $e->getMessage();
                    $errors[] = $error_msg;
                    log_message('error', "Batch Solution Generation Error: " . $error_msg);
                    // Continue to next question on error
                }
            }
            
            $response_data = array(
                'processed' => $processed,
                'total_found' => count($questions),
                'processed_question_ids' => $processed_question_ids, // Include list of successfully processed question IDs
                'errors' => $errors,
                'success_rate' => count($questions) > 0 ? round(($processed / count($questions)) * 100, 2) : 0,
                'is_cron_job' => $is_cron_job
            );
            
            log_message('debug', 'Batch solution generation completed. Processed: ' . $processed . ' out of ' . count($questions));
            
            $response = $this->get_success_response($response_data, "Batch solution generation completed");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Batch Solution Generation Controller Error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to process batch solution generation: " . $e->getMessage());
            $this->set_output($response);
        }
    }
    
    /**
     * Force regenerate summaries for questions with low confidence
     * POST /api/batch_summary/regenerate_low_confidence/{min_confidence}
     */
    function regenerate_low_confidence_post($min_confidence = 0.7) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $pdo = CDatabase::getPdo();
            
            // Get questions with low confidence summaries
            $sql = "SELECT id, question_text, ai_summary, summary_confidence
                   FROM question 
                   WHERE summary_confidence < ? 
                   AND question_text IS NOT NULL 
                   AND question_text != ''
                   LIMIT 20";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($min_confidence));
            $questions = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($questions)) {
                $response = $this->get_success_response(
                    array('processed' => 0, 'message' => 'No low confidence summaries found'),
                    "All summaries meet confidence threshold"
                );
                $this->set_output($response);
                return;
            }
            
            $processed = 0;
            $improved = 0;
            
            foreach ($questions as $question) {
                try {
                    // Generate new AI summary
                    $summary_result = $this->ai_image_analysis_service->generate_question_summary($question['question_text']);
                    
                    if ($summary_result && !empty($summary_result['summary'])) {
                        // Only update if confidence improved
                        if ($summary_result['confidence'] > $question['summary_confidence']) {
                            $update_sql = "UPDATE question 
                                          SET ai_summary = ?, 
                                              summary_generated_at = NOW(), 
                                              summary_confidence = ?
                                          WHERE id = ?";
                            
                            $update_statement = $pdo->prepare($update_sql);
                            $update_statement->execute(array(
                                $summary_result['summary'],
                                $summary_result['confidence'],
                                $question['id']
                            ));
                            
                            $improved++;
                        }
                        $processed++;
                    }
                    
                    // Add delay
                    usleep(150000); // 0.15 second delay
                    
                } catch (Exception $e) {
                    log_message('error', "Regenerate Summary Error: " . $e->getMessage());
                }
            }
            
            $response_data = array(
                'processed' => $processed,
                'improved' => $improved,
                'total_found' => count($questions),
                'min_confidence_threshold' => $min_confidence
            );
            
            $response = $this->get_success_response($response_data, "Summary regeneration completed");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Regenerate Summary Controller Error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to regenerate summaries: " . $e->getMessage());
            $this->set_output($response);
        }
    }
}
