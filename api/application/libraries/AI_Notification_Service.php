<?php

/**
 * AI Notification Service
 * 
 * Generates personalized notification messages using OpenAI GPT models.
 * Optimized for cost efficiency with intelligent caching and template fallbacks.
 * 
 * Features:
 * - Context-aware message generation
 * - Multiple notification type templates
 * - Smart caching to reduce API costs
 * - Graceful fallback to pre-written templates
 * - Tone adjustment based on user motivation level
 * 
 * @package WiziAI
 * @subpackage Libraries
 * @category Notification
 */
class AI_Notification_Service {
    
    private $CI;
    private $openai_api_key;
    private $openai_model;
    private $cache_ttl = 3600; // 1 hour cache
    
    public function __construct() {
        $this->CI =& get_instance();
        
        // Load configuration
        $this->openai_api_key = getenv('OPENAI_API_KEY') ?: 
                               $this->CI->config->item('openai_api_key');
        
        $this->openai_model = getenv('OPENAI_MODEL') ?: 
                             $this->CI->config->item('openai_model') ?: 
                             'gpt-4o-mini';
        
        // Load cache library if available
        if (class_exists('CI_Cache')) {
            $this->CI->load->driver('cache', ['adapter' => 'file']);
        }
    }
    
    /**
     * Generate personalized notification message
     * 
     * @param object $user_context User notification context from database
     * @param string $notification_type Type of notification
     * @param array $additional_data Extra data for message generation
     * @return array [title, body, deep_link_screen, deep_link_data]
     */
    public function generate_notification($user_context, $notification_type, $additional_data = []) {
        try {
            // Check cache first
            $cache_key = $this->get_cache_key($user_context, $notification_type);
            $cached = $this->get_from_cache($cache_key);
            
            if ($cached) {
                log_message('info', "Using cached notification for type: {$notification_type}");
                return $this->personalize_message($cached, $user_context, $additional_data);
            }
            
            // Generate new message using AI
            $message = $this->generate_with_ai($user_context, $notification_type, $additional_data);
            
            // Cache the result
            $this->save_to_cache($cache_key, $message);
            
            return $message;
            
        } catch (Exception $e) {
            log_message('error', "AI Notification generation error: " . $e->getMessage());
            
            // Fallback to template
            return $this->get_template_notification($user_context, $notification_type, $additional_data);
        }
    }
    
    /**
     * Generate notification using AI
     */
    private function generate_with_ai($user_context, $notification_type, $additional_data) {
        $prompt = $this->build_prompt($user_context, $notification_type, $additional_data);
        
        $response = $this->call_openai($prompt);
        
        return $this->parse_ai_response($response, $user_context, $notification_type, $additional_data);
    }
    
    /**
     * Build AI prompt based on notification type and user context
     */
    private function build_prompt($context, $type, $additional_data) {
        $base_context = $this->get_base_context_string($context);
        $tone = $this->get_tone_instruction($context->motivation_level ?? 'steady');
        
        $prompts = [
            'custom_quiz_suggestion' => $this->get_custom_quiz_prompt($context, $base_context, $tone),
            'pyq_suggestion' => $this->get_pyq_suggestion_prompt($context, $base_context, $tone),
            'mock_suggestion' => $this->get_mock_suggestion_prompt($context, $base_context, $tone),
            'inactivity_reminder' => $this->get_inactivity_reminder_prompt($context, $base_context, $tone),
            'motivational_message' => $this->get_motivational_prompt($context, $base_context, $tone, $additional_data),
            'milestone_achievement' => $this->get_milestone_prompt($context, $base_context, $additional_data),
            'quota_warning' => $this->get_quota_warning_prompt($context, $base_context, $tone)
        ];
        
        return $prompts[$type] ?? $prompts['motivational_message'];
    }
    
    /**
     * Get base context string for prompts
     */
    private function get_base_context_string($context) {
        return sprintf(
            "User Profile: %s level student, %s motivation. Accuracy: %.1f%%, Total quizzes: %d, Last quiz: %d days ago. %s",
            $context->ai_persona_category ?? 'intermediate',
            $context->motivation_level ?? 'steady',
            $context->current_accuracy_percentage ?? 0,
            $context->total_quizzes_all_time ?? 0,
            $context->days_since_last_quiz ?? 0,
            $context->weakest_subject ? "Weak in: " . $context->weakest_subject : ""
        );
    }
    
    /**
     * Get tone instruction based on motivation level
     */
    private function get_tone_instruction($motivation_level) {
        $tones = [
            'needs_encouragement' => 'Use a supportive, gentle, and encouraging tone. Avoid pressure. Focus on small wins and progress.',
            'steady' => 'Use a friendly, motivating tone. Be positive and actionable.',
            'highly_motivated' => 'Use an energetic, challenging tone. Celebrate achievements and push for excellence.'
        ];
        
        return $tones[$motivation_level] ?? $tones['steady'];
    }
    
    // ========================================================================
    // NOTIFICATION TYPE SPECIFIC PROMPTS
    // ========================================================================
    
    /**
     * Custom Quiz Suggestion Prompt
     */
    private function get_custom_quiz_prompt($context, $base_context, $tone) {
        $subject_focus = $context->weakest_subject ?? $context->strongest_subject ?? 'your favorite topics';
        
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}

Task: Create a push notification suggesting the user create a custom quiz focused on "{$subject_focus}".

Requirements:
1. Title: 4-6 words, catchy and motivating
2. Body: Maximum 2 short sentences (under 100 characters total)
3. {$tone}
4. Include subtle hint about personalization or weakness improvement
5. Use emojis sparingly (max 1-2)
6. Do NOT mention specific scores or numbers
7. End with a call-to-action

Output ONLY valid JSON in this exact format:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}

Do not include any other text or explanation.
PROMPT;
    }
    
    /**
     * PYQ Suggestion Prompt
     */
    private function get_pyq_suggestion_prompt($context, $base_context, $tone) {
        $subject = $context->strongest_subject ?? 'your best subjects';
        
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}

Task: Create a push notification encouraging user to try Previous Year Questions (PYQ) for exam preparation.

Requirements:
1. Title: 4-6 words, emphasize exam readiness
2. Body: Maximum 2 short sentences (under 100 characters)
3. {$tone}
4. Highlight that they're ready for PYQ based on their performance
5. Mention subject: {$subject}
6. Use emojis sparingly (max 1-2)
7. Create urgency for exam preparation

Output ONLY valid JSON:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}
PROMPT;
    }
    
    /**
     * Mock Test Suggestion Prompt
     */
    private function get_mock_suggestion_prompt($context, $base_context, $tone) {
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}

Task: Create a push notification suggesting user take a full-length mock test.

Requirements:
1. Title: 4-6 words, emphasize complete exam simulation
2. Body: Maximum 2 short sentences (under 100 characters)
3. {$tone}
4. Highlight benefits: real exam experience, time management, confidence building
5. Use emojis sparingly (max 1-2)
6. Make it feel like a challenge/achievement opportunity

Output ONLY valid JSON:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}
PROMPT;
    }
    
    /**
     * Inactivity Reminder Prompt
     */
    private function get_inactivity_reminder_prompt($context, $base_context, $tone) {
        $days_inactive = $context->days_since_last_quiz ?? 3;
        $streak_lost = $context->quiz_streak_days > 0 ? "Their {$context->quiz_streak_days}-day streak was lost." : "";
        
        $urgency_level = $days_inactive >= 7 ? 'high' : ($days_inactive >= 5 ? 'medium' : 'low');
        
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}
Inactive for: {$days_inactive} days. {$streak_lost}

Task: Create a push notification to bring user back to the app.

Urgency: {$urgency_level}

Requirements:
1. Title: 4-6 words, welcoming and non-judgmental
2. Body: Maximum 2 short sentences (under 100 characters)
3. {$tone}
4. Do NOT guilt-trip or be negative
5. Focus on: "We miss you" or "Quick comeback quiz" or "Continue your journey"
6. If streak was lost, gently mention starting fresh
7. Use emojis sparingly (max 1-2)

Output ONLY valid JSON:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}
PROMPT;
    }
    
    /**
     * Motivational Message Prompt
     */
    private function get_motivational_prompt($context, $base_context, $tone, $additional_data) {
        $trigger = $additional_data['trigger'] ?? 'daily';
        $score = $additional_data['score'] ?? null;
        
        $context_detail = '';
        if ($trigger === 'high_score' && $score) {
            $context_detail = "User just scored {$score}% on a quiz (excellent performance).";
        } elseif ($trigger === 'low_score' && $score) {
            $context_detail = "User scored {$score}% on a quiz (struggling). Need supportive message.";
        } elseif ($trigger === 'improvement') {
            $context_detail = "User's performance is improving. Celebrate progress.";
        } else {
            $context_detail = "Daily motivational message to keep user engaged.";
        }
        
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}
Context: {$context_detail}

Task: Create a motivational push notification.

Requirements:
1. Title: 3-5 words, inspirational
2. Body: Maximum 2 short sentences (under 100 characters)
3. {$tone}
4. Be specific to their situation
5. Include actionable encouragement
6. Use emojis sparingly (max 2)
7. Avoid clichés like "you can do it"

Output ONLY valid JSON:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}
PROMPT;
    }
    
    /**
     * Milestone Achievement Prompt
     */
    private function get_milestone_prompt($context, $base_context, $additional_data) {
        $milestone_name = $additional_data['milestone_name'] ?? $context->next_milestone_name ?? 'Achievement';
        $milestone_type = $additional_data['milestone_type'] ?? 'quiz_count';
        
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}

Task: Create a celebratory push notification for unlocking milestone: "{$milestone_name}"

Requirements:
1. Title: 3-5 words, celebratory and exciting
2. Body: Maximum 2 short sentences (under 100 characters)
3. Use enthusiastic, congratulatory tone
4. Include celebration emojis (2-3: 🎉, 🏆, 🌟, etc.)
5. Make user feel proud of achievement
6. Encourage them to check their achievement badge

Output ONLY valid JSON:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}
PROMPT;
    }
    
    /**
     * Quota Warning Prompt
     */
    private function get_quota_warning_prompt($context, $base_context, $tone) {
        $remaining = $context->custom_quiz_quota_remaining ?? 0;
        $percentage = $context->custom_quiz_quota_percentage ?? 0;
        
        return <<<PROMPT
You are a friendly AI study assistant for WiziAI, an educational quiz app.

{$base_context}
Quota: {$percentage}% used, {$remaining} custom quizzes remaining in free plan.

Task: Create a push notification alerting about quota limit.

Requirements:
1. Title: 4-6 words, informative not pushy
2. Body: Maximum 2 short sentences (under 100 characters)
3. {$tone}
4. Mention remaining quota
5. Subtle suggestion to upgrade for unlimited quizzes
6. Do NOT use salesy language
7. Use emojis sparingly (max 1)

Output ONLY valid JSON:
{
  "title": "Your notification title here",
  "body": "Your notification body here"
}
PROMPT;
    }
    
    // ========================================================================
    // AI API INTEGRATION
    // ========================================================================
    
    /**
     * Call OpenAI API
     */
    private function call_openai($prompt) {
        if (!$this->openai_api_key || $this->openai_api_key === 'your-openai-api-key-here') {
            throw new Exception('OpenAI API key not configured');
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->openai_model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert at creating engaging, concise push notifications for educational apps. Always respond with valid JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 150,
            'response_format' => ['type' => 'json_object']
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("OpenAI API request failed: {$error}");
        }
        
        if ($http_code !== 200) {
            throw new Exception("OpenAI API returned status code: {$http_code}");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception("Invalid OpenAI API response format");
        }
        
        return $result['choices'][0]['message']['content'];
    }
    
    /**
     * Parse AI response
     */
    private function parse_ai_response($response, $context, $type, $additional_data) {
        $json = json_decode($response, true);
        
        if (!$json || !isset($json['title']) || !isset($json['body'])) {
            throw new Exception("Invalid AI response JSON format");
        }
        
        // Get deep link configuration
        $deep_link = $this->get_deep_link_config($type, $context, $additional_data);
        
        return [
            'title' => $json['title'],
            'body' => $json['body'],
            'deep_link_screen' => $deep_link['screen'],
            'deep_link_data' => $deep_link['data']
        ];
    }
    
    // ========================================================================
    // DEEP LINK CONFIGURATION
    // ========================================================================
    
    /**
     * Get deep link configuration for notification type
     */
    private function get_deep_link_config($type, $context, $additional_data) {
        $configs = [
            'custom_quiz_suggestion' => [
                'screen' => 'custom-quiz-builder',
                'data' => [
                    'suggested_subject' => $context->weakest_subject,
                    'difficulty' => $context->performance_category,
                    'source' => 'notification'
                ]
            ],
            'pyq_suggestion' => [
                'screen' => 'quiz-list',
                'data' => [
                    'filter' => 'pyq',
                    'subject' => $context->strongest_subject,
                    'source' => 'notification'
                ]
            ],
            'mock_suggestion' => [
                'screen' => 'quiz-list',
                'data' => [
                    'filter' => 'mock',
                    'source' => 'notification'
                ]
            ],
            'inactivity_reminder' => [
                'screen' => 'dashboard',
                'data' => [
                    'highlight' => 'continue_learning',
                    'source' => 'notification'
                ]
            ],
            'motivational_message' => [
                'screen' => 'dashboard',
                'data' => [
                    'source' => 'notification',
                    'trigger' => $additional_data['trigger'] ?? 'daily'
                ]
            ],
            'milestone_achievement' => [
                'screen' => 'achievements',
                'data' => [
                    'milestone_type' => $additional_data['milestone_type'] ?? 'quiz_count',
                    'celebrate' => true,
                    'source' => 'notification'
                ]
            ],
            'quota_warning' => [
                'screen' => 'subscription',
                'data' => [
                    'highlight' => 'upgrade',
                    'source' => 'notification'
                ]
            ]
        ];
        
        return $configs[$type] ?? [
            'screen' => 'dashboard',
            'data' => ['source' => 'notification']
        ];
    }
    
    // ========================================================================
    // TEMPLATE FALLBACKS
    // ========================================================================
    
    /**
     * Get template-based notification (fallback when AI fails)
     */
    private function get_template_notification($context, $type, $additional_data) {
        $templates = [
            'custom_quiz_suggestion' => [
                'title' => '📚 Practice Time!',
                'body' => $context->weakest_subject 
                    ? "Ready to improve your {$context->weakest_subject}? Create a custom quiz now!" 
                    : "Create a personalized quiz and boost your knowledge!"
            ],
            'pyq_suggestion' => [
                'title' => '🎯 Try PYQ!',
                'body' => "You're ready for previous year questions. Ace your exam prep!"
            ],
            'mock_suggestion' => [
                'title' => '⏱️ Mock Test Time',
                'body' => "Take a full mock test and experience real exam conditions!"
            ],
            'inactivity_reminder' => [
                'title' => '👋 We Miss You!',
                'body' => "Come back and continue your learning journey. Quick 5-min quiz?"
            ],
            'motivational_message' => [
                'title' => '💪 Keep Going!',
                'body' => "Every quiz brings you closer to your goals. You've got this!"
            ],
            'milestone_achievement' => [
                'title' => '🎉 Achievement Unlocked!',
                'body' => "Congratulations on reaching {$context->next_milestone_name}!"
            ],
            'quota_warning' => [
                'title' => '⚠️ Quota Alert',
                'body' => "Only {$context->custom_quiz_quota_remaining} custom quizzes left. Upgrade for unlimited!"
            ]
        ];
        
        $template = $templates[$type] ?? $templates['motivational_message'];
        $deep_link = $this->get_deep_link_config($type, $context, $additional_data);
        
        return [
            'title' => $template['title'],
            'body' => $template['body'],
            'deep_link_screen' => $deep_link['screen'],
            'deep_link_data' => $deep_link['data']
        ];
    }
    
    // ========================================================================
    // CACHING
    // ========================================================================
    
    /**
     * Generate cache key
     */
    private function get_cache_key($context, $type) {
        $cache_factors = [
            'type' => $type,
            'persona' => $context->ai_persona_category ?? 'intermediate',
            'motivation' => $context->motivation_level ?? 'steady',
            'performance' => $context->performance_category ?? 'average',
            'weakest_subject' => $context->weakest_subject ?? 'general',
            'strongest_subject' => $context->strongest_subject ?? 'general'
        ];
        
        return 'notification_ai_' . md5(json_encode($cache_factors));
    }
    
    /**
     * Get from cache
     */
    private function get_from_cache($key) {
        if (!isset($this->CI->cache)) {
            return null;
        }
        
        return $this->CI->cache->get($key);
    }
    
    /**
     * Save to cache
     */
    private function save_to_cache($key, $data) {
        if (!isset($this->CI->cache)) {
            return;
        }
        
        $this->CI->cache->save($key, $data, $this->cache_ttl);
    }
    
    /**
     * Personalize cached message with user-specific data
     */
    private function personalize_message($cached_message, $context, $additional_data) {
        // Replace placeholders if any
        $replacements = [
            '{user_name}' => $context->display_name ?? 'there',
            '{weakest_subject}' => $context->weakest_subject ?? 'your weak areas',
            '{strongest_subject}' => $context->strongest_subject ?? 'your best subjects',
            '{remaining_quota}' => $context->custom_quiz_quota_remaining ?? 0,
            '{milestone_name}' => $context->next_milestone_name ?? 'your achievement'
        ];
        
        $message = $cached_message;
        foreach ($replacements as $placeholder => $value) {
            if (isset($message['title'])) {
                $message['title'] = str_replace($placeholder, $value, $message['title']);
            }
            if (isset($message['body'])) {
                $message['body'] = str_replace($placeholder, $value, $message['body']);
            }
        }
        
        return $message;
    }
}
