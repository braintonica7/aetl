# AI-Powered Quiz Performance Analysis System

## Overview

This system provides comprehensive AI-powered analysis of quiz performance, generating detailed insights, recommendations, and progress tracking for students.

## System Architecture

### Components

1. **Database Schema**
   - `user_question` table: Stores user answers with automatic correction
   - `user_performance` table: Stores comprehensive AI-generated analytics

2. **Models**
   - `User_question_model`: Handles answer submission and quiz completion tracking
   - `User_performance_model`: Manages performance data and analytics
   - `User_performance_object`: Data object for performance records

3. **Controllers**
   - `Quiz`: Handles answer submission with automatic performance analysis trigger
   - `User_performance`: Provides API endpoints for performance management

4. **Services**
   - `AI_Performance_Service`: OpenAI integration for generating detailed analytics
   - `User_performance_library`: Business logic for performance analysis

## Key Features

### 1. Automatic Answer Correction
- Answers are automatically checked against correct options
- `is_correct` field is populated in `user_question` table
- Security: Correct answers and scores are NOT returned in API responses

### 2. Quiz Completion Detection
- System automatically detects when a user completes all questions
- Triggers AI performance analysis upon completion
- Prevents duplicate analysis generation

### 3. AI-Powered Analytics
- **Performance Breakdown**: By difficulty, subject, topic, chapter
- **Strength & Weakness Analysis**: Identifies strong and weak areas
- **Study Recommendations**: Personalized learning suggestions
- **Progress Tracking**: Trends across multiple quiz attempts
- **Motivational Insights**: Encouraging feedback and next steps

### 4. Comprehensive Data Storage
- 30+ fields capturing detailed performance metrics
- JSON storage for complex analytical data
- Historical comparison and trend analysis
- Metadata for analysis versioning

## API Endpoints

### Quiz Controller

#### Submit Answer
```
POST /api/quiz/add_user_answer_post
```

**Request:**
```json
{
    "user_id": 123,
    "quiz_id": 456,
    "question_id": 789,
    "option_answer": "A",
    "duration": 45
}
```

**Response:**
```json
{
    "status": true,
    "message": "Answer submitted successfully",
    "data": {
        "id": 1001,
        "user_id": 123,
        "quiz_id": 456,
        "question_id": 789,
        "option_answer": "A",
        "duration": 45,
        "created_at": "2024-01-15 10:30:00",
        "quiz_completed": true,
        "performance_analysis_generated": true
    }
}
```

### User Performance Controller

#### Generate Performance Analysis
```
POST /api/user_performance/generate_performance_post
```

**Request:**
```json
{
    "user_id": 123,
    "quiz_id": 456
}
```

#### Get Performance Analysis
```
GET /api/user_performance/get_performance_get/{performance_id}
```

#### Get User Performance List
```
GET /api/user_performance/get_user_performance_list_get/{user_id}
```

#### Manual Performance Generation
```
POST /api/user_performance/manual_generate_performance_post
```

## Database Schema

### user_performance Table

| Field | Type | Description |
|-------|------|-------------|
| id | bigint(20) | Primary key |
| user_id | bigint(20) | Foreign key to user |
| quiz_id | bigint(20) | Foreign key to quiz |
| total_questions | int(11) | Total questions in quiz |
| correct_answers | int(11) | Number of correct answers |
| incorrect_answers | int(11) | Number of incorrect answers |
| score_percentage | decimal(5,2) | Overall score percentage |
| time_taken_minutes | decimal(8,2) | Total time taken |
| difficulty_performance | text | JSON: Performance by difficulty |
| subject_performance | text | JSON: Performance by subject |
| topic_performance | text | JSON: Performance by topic |
| chapter_performance | text | JSON: Performance by chapter |
| strengths | text | JSON: Identified strengths |
| weaknesses | text | JSON: Identified weaknesses |
| improvement_areas | text | JSON: Areas for improvement |
| study_recommendations | text | JSON: Study recommendations |
| ai_generated_insights | longtext | Detailed AI analysis |
| performance_trend | varchar(50) | Trend: improving/declining/stable |
| comparative_analysis | text | JSON: Historical comparison |
| personalized_feedback | text | Personalized feedback message |
| next_steps | text | JSON: Recommended next steps |
| motivational_message | text | Motivational message |
| detailed_analytics | text | JSON: Comprehensive analytics |
| metadata | text | JSON: Additional metadata |

## AI Analysis Process

### 1. Data Collection
- Retrieves user's quiz answers and timing
- Gathers question metadata (subject, topic, difficulty)
- Collects historical performance data

### 2. Performance Calculation
- Calculates basic metrics (score, time, accuracy)
- Analyzes performance by various dimensions
- Identifies patterns and trends

### 3. AI Insight Generation
- Uses OpenAI GPT-4 for deep analysis
- Generates personalized feedback
- Creates study recommendations
- Provides motivational insights

### 4. Data Storage
- Saves comprehensive analysis to database
- Maintains historical records
- Enables progress tracking

## Security Features

- **Answer Protection**: Correct answers not exposed in API responses
- **Score Security**: Scores only revealed in performance analysis
- **Input Validation**: All inputs validated and sanitized
- **Error Handling**: Comprehensive error logging and handling

## Usage Workflow

1. **User Takes Quiz**: Submits answers via `/api/quiz/add_user_answer_post`
2. **Auto-Detection**: System detects quiz completion
3. **AI Analysis**: Automatically generates performance analysis
4. **Data Storage**: Saves comprehensive analytics
5. **Access Results**: User can retrieve analysis via performance endpoints

## Configuration

### Environment Variables
- `OPENAI_API_KEY`: Required for AI analysis
- Database connection parameters in CodeIgniter config

### Dependencies
- CodeIgniter 3.x framework
- MySQL database
- OpenAI API access
- PHP cURL extension

## Error Handling

- Comprehensive logging of all operations
- Graceful degradation if AI service unavailable
- Duplicate analysis prevention
- Transaction safety for database operations

## Performance Considerations

- Indexes on key performance fields
- JSON storage for complex data structures
- Efficient query patterns
- Background processing for AI analysis

## Future Enhancements

1. **Real-time Analytics**: Live performance tracking
2. **Adaptive Learning**: AI-driven question recommendations
3. **Peer Comparison**: Anonymous performance benchmarking
4. **Learning Path Optimization**: AI-suggested study sequences
5. **Mobile Analytics**: Mobile app integration
6. **Report Generation**: PDF/Excel export capabilities

## Deployment

1. **Database Setup**: Execute `user_performance.sql` script
2. **File Deployment**: Deploy all PHP files to appropriate directories
3. **Configuration**: Set OpenAI API key and database credentials
4. **Testing**: Verify all endpoints and AI integration
5. **Monitoring**: Set up logging and error monitoring

## Support

For technical support or feature requests, refer to the development team documentation or create an issue in the project repository.
