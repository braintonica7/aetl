<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Help Chatbot Controller
 * 
 * API endpoints for the help chatbot functionality using RAG (Retrieval-Augmented Generation)
 * 
 * @author WiziAI Development Team
 */
class Help_chatbot extends API_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('Help_chatbot_service');
    }
    
    /**
     * Chat endpoint - Get response for user query
     * 
     * POST /api/help_chatbot/chat
     * 
     * Request body:
     * {
     *   "query": "How do I create an account?",
     *   "options": {
     *     "model": "gpt-4o-mini",
     *     "top_k": 3
     *   }
     * }
     * 
     * Response:
     * {
     *   "status": true,
     *   "message": "Response generated successfully",
     *   "data": {
     *     "answer": "...",
     *     "sources": [...],
     *     "metadata": {...}
     *   }
     * }
     */
    public function chat_post() {
        try {
            // Get request data
            $query = $this->post('query');
            $options = $this->post('options') ?: [];
            
            // Validate input
            if (empty($query)) {
                $response = $this->get_failed_response(null, 'Query parameter is required');
                $this->set_output($response);
                return;
            }
            
            // Check if chatbot is ready
            if (!$this->help_chatbot_service->isReady()) {
                $response = $this->get_failed_response(null, 'Help chatbot is not available. Please contact support.');
                $this->set_output($response);
                return;
            }
            
            // Get chatbot response
            $result = $this->help_chatbot_service->getChatResponse($query, $options);
            
            if (!$result['success']) {
                $data = [
                    'answer' => $result['answer'] ?? 'I apologize, but I encountered an error. Please try again or contact support.'
                ];
                $response = $this->get_failed_response($data, $result['error'] ?? 'Failed to generate response');
                $this->set_output($response);
                return;
            }
            
            // Success response
            $data = [
                'answer' => $result['answer'],
                'sources' => $result['sources'],
                'metadata' => $result['metadata']
            ];
            $response = $this->get_success_response($data, 'Response generated successfully');
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', 'Help chatbot error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, 'An error occurred while processing your request');
            $this->set_output($response);
        }
    }
    
    /**
     * Search endpoint - Get relevant document chunks
     * 
     * POST /api/help_chatbot/search
     * 
     * Request body:
     * {
     *   "query": "study plan",
     *   "top_k": 5
     * }
     * 
     * Response:
     * {
     *   "status": true,
     *   "message": "Search completed successfully",
     *   "data": {
     *     "results": [...]
     *   }
     * }
     */
    public function search_post() {
        try {
            // Get request data
            $query = $this->post('query');
            $top_k = $this->post('top_k') ?: 5;
            
            // Validate input
            if (empty($query)) {
                $response = $this->get_failed_response(null, 'Query parameter is required');
                $this->set_output($response);
                return;
            }
            
            // Check if chatbot is ready
            if (!$this->help_chatbot_service->isReady()) {
                $response = $this->get_failed_response(null, 'Help chatbot is not available. Please contact support.');
                $this->set_output($response);
                return;
            }
            
            // Search for relevant context
            $results = $this->help_chatbot_service->searchRelevantContext($query, $top_k);
            
            // Format results (remove embeddings for response)
            $formatted_results = array_map(function($item) {
                $chunk = $item['chunk'];
                unset($chunk['embedding']); // Don't send embeddings in response
                
                return [
                    'document' => $chunk['document'],
                    'section' => $chunk['section'],
                    'subsection' => $chunk['subsection'] ?? null,
                    'text' => $chunk['text'],
                    'metadata' => $chunk['metadata'],
                    'relevance_score' => round($item['score'], 3)
                ];
            }, $results);
            
            $data = [
                'query' => $query,
                'results' => $formatted_results,
                'total_results' => count($formatted_results)
            ];
            $response = $this->get_success_response($data, 'Search completed successfully');
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', 'Help chatbot search error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, 'An error occurred while searching');
            $this->set_output($response);
        }
    }
    
    /**
     * Status endpoint - Check chatbot availability
     * 
     * GET /api/help_chatbot/status
     * 
     * Response:
     * {
     *   "status": true,
     *   "message": "Help chatbot is ready",
     *   "data": {
     *     "ready": true,
     *     "metadata": {...}
     *   }
     * }
     */
    public function status_get() {
        try {
            $is_ready = $this->help_chatbot_service->isReady();
            $metadata = $this->help_chatbot_service->getMetadata();
            
            $data = [
                'ready' => $is_ready,
                'metadata' => $metadata
            ];
            $message = $is_ready ? 'Help chatbot is ready' : 'Help chatbot is not configured';
            $response = $this->get_success_response($data, $message);
            $this->set_output($response);
            
        } catch (Exception $e) {
            $data = [
                'ready' => false,
                'error' => $e->getMessage()
            ];
            $response = $this->get_failed_response($data, 'Help chatbot is not available');
            $this->set_output($response);
        }
    }
    
    /**
     * Suggested questions endpoint
     * 
     * GET /api/help_chatbot/suggested_questions
     * 
     * Response:
     * {
     *   "status": true,
     *   "message": "Suggested questions retrieved",
     *   "data": {
     *     "questions": [...]
     *   }
     * }
     */
    public function suggested_questions_get() {
        // Predefined suggested questions based on common topics
        $questions = [
            [
                'category' => 'Getting Started',
                'questions' => [
                    'How do I create an account?',
                    'What subscription plans are available?',
                    'How do I reset my password?',
                    'Can I use WiziAI on mobile?'
                ]
            ],
            [
                'category' => 'Quizzes & Tests',
                'questions' => [
                    'What types of quizzes are available?',
                    'How do I create a custom quiz?',
                    'Can I retake a quiz?',
                    'How is my performance analyzed?'
                ]
            ],
            [
                'category' => 'Study Plan',
                'questions' => [
                    'How do I create a study plan?',
                    'Can I customize my study plan?',
                    'How does the AI tutor work?',
                    'What are AI tutor credits?'
                ]
            ],
            [
                'category' => 'Points & Gamification',
                'questions' => [
                    'How do I earn points?',
                    'What can I do with my points?',
                    'How does the leaderboard work?',
                    'What are achievement badges?'
                ]
            ],
            [
                'category' => 'Subscription & Billing',
                'questions' => [
                    'What features are included in each plan?',
                    'How do I upgrade my subscription?',
                    'What is academic session billing?',
                    'Can I cancel my subscription?'
                ]
            ]
        ];
        
        $data = ['questions' => $questions];
        $response = $this->get_success_response($data, 'Suggested questions retrieved successfully');
        $this->set_output($response);
    }
    
    /**
     * Contact form submission endpoint
     * 
     * POST /api/help_chatbot/contact
     * 
     * Request body:
     * {
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "phone": "9876543210",
     *   "query": "Question details..."
     * }
     * 
     * Response:
     * {
     *   "status": true,
     *   "message": "Contact request submitted successfully",
     *   "data": {
     *     "reference_id": "..."
     *   }
     * }
     */
    public function contact_post() {
        try {
            // Get request data
            $name = $this->post('name');
            $email = $this->post('email');
            $phone = $this->post('phone');
            $query = $this->post('query');
            
            // Validate input
            if (empty($name) || empty($email) || empty($phone) || empty($query)) {
                $response = $this->get_failed_response(null, 'All fields are required');
                $this->set_output($response);
                return;
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = $this->get_failed_response(null, 'Invalid email address');
                $this->set_output($response);
                return;
            }
            
            // Validate phone format (10 digits)
            $phone_cleaned = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone_cleaned) !== 10) {
                $response = $this->get_failed_response(null, 'Invalid phone number. Please enter a 10-digit number');
                $this->set_output($response);
                return;
            }
            
            // Generate reference ID
            $reference_id = 'HC' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
            
            // Store contact request in database
            $this->db->insert('help_chatbot_contacts', [
                'reference_id' => $reference_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone_cleaned,
                'query' => $query,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send notification email to support team
            $this->sendSupportNotification([
                'reference_id' => $reference_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone_cleaned,
                'query' => $query
            ]);
            
            // Send confirmation email to user
            $this->sendUserConfirmation($email, $name, $reference_id);
            
            $data = ['reference_id' => $reference_id];
            $response = $this->get_success_response($data, 'Contact request submitted successfully. We will get back to you soon!');
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', 'Help chatbot contact form error: ' . $e->getMessage());
            $response = $this->get_failed_response(null, 'Failed to submit contact request. Please try again.');
            $this->set_output($response);
        }
    }
    
    /**
     * Send notification email to support team
     */
    private function sendSupportNotification($data) {
        try {
            $this->load->library('email');
            
            $message = "
                <h2>New Help Chatbot Contact Request</h2>
                <p><strong>Reference ID:</strong> {$data['reference_id']}</p>
                <p><strong>Name:</strong> {$data['name']}</p>
                <p><strong>Email:</strong> {$data['email']}</p>
                <p><strong>Phone:</strong> {$data['phone']}</p>
                <p><strong>Query:</strong></p>
                <p>{$data['query']}</p>
                <hr>
                <p><small>Submitted at: " . date('Y-m-d H:i:s') . "</small></p>
            ";
            
            $this->email->from('noreply@wiziai.com', 'WiziAI Help Chatbot');
            $this->email->to('support@wiziai.com'); // Change to your support email
            $this->email->subject('New Help Request - ' . $data['reference_id']);
            $this->email->message($message);
            $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Failed to send support notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Send confirmation email to user
     */
    private function sendUserConfirmation($email, $name, $reference_id) {
        try {
            $this->load->library('email');
            
            $message = "
                <h2>Thank You for Contacting WiziAI!</h2>
                <p>Dear {$name},</p>
                <p>We've received your query and our support team will get back to you within 24 hours.</p>
                <p><strong>Reference ID:</strong> {$reference_id}</p>
                <p>Please save this reference ID for future correspondence.</p>
                <br>
                <p>Best regards,<br>WiziAI Support Team</p>
                <hr>
                <p><small>This is an automated message. Please do not reply to this email.</small></p>
            ";
            
            $this->email->from('noreply@wiziai.com', 'WiziAI Support');
            $this->email->to($email);
            $this->email->subject('We Received Your Query - ' . $reference_id);
            $this->email->message($message);
            $this->email->send();
            
        } catch (Exception $e) {
            log_message('error', 'Failed to send user confirmation: ' . $e->getMessage());
        }
    }
}
