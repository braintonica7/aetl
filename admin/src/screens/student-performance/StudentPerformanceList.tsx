import React, { useState, useEffect } from 'react';
import {
    Box,
    Card,
    CardContent,
    CardHeader,
    Typography,
    MenuItem,
    Select,
    FormControl,
    InputLabel,
    Chip,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Alert,
    CircularProgress,
    Accordion,
    AccordionSummary,
    AccordionDetails,
    Divider,
    Stack,
} from '@mui/material';
import {
    ExpandMore as ExpandMoreIcon,
    TrendingUp as TrendingUpIcon,
    TrendingDown as TrendingDownIcon,
    School as SchoolIcon,
    Quiz as QuizIcon,
    Timer as TimerIcon,
    Assessment as AssessmentIcon,
} from '@mui/icons-material';
import { useDataProvider, useNotify, Title } from 'react-admin';
import { formatDistanceToNow } from 'date-fns';
import { APIUrl } from '../../common/apiClient';

interface User {
    id: number;
    username: string;
    display_name: string;
}

interface Quiz {
    id: number;
    quiz_name: string;
}

interface QuizAttempt {
    id: number;
    quiz_id: number;
    quiz_name: string;
    quiz_attempt_number: number;
    total_questions: number;
    correct_answers: number;
    incorrect_answers: number;
    unanswered_questions: number;
    accuracy_percentage: number;
    total_time_spent: number;
    average_time_per_question: number;
    created_at: string;
}

interface PerformanceSummary {
    id: number;
    user_id: number;
    total_quizzes_taken: number;
    total_questions_attempted: number;
    total_correct_answers: number;
    total_incorrect_answers: number;
    overall_accuracy_percentage: number;
    average_quiz_score: number;
    total_time_spent_minutes: number;
    average_time_per_quiz_minutes: number;
    average_time_per_question_seconds: number;
    strongest_overall_subject: string;
    weakest_overall_subject: string;
    strongest_overall_topic: string;
    weakest_overall_topic: string;
    performance_trend: string;
    accuracy_trend: string;
    speed_trend: string;
    consistency_score: number;
    easy_level_accuracy: number;
    medium_level_accuracy: number;
    hard_level_accuracy: number;
    mastery_level: string;
    learning_velocity: string;
    improvement_rate: number;
    ai_overall_assessment: string;
    ai_learning_patterns: string;
    ai_strengths_summary: string;
    ai_improvement_areas: string;
    ai_study_recommendations: string;
    generation_date: string;
}

export const StudentPerformanceList = () => {
    const [selectedUserId, setSelectedUserId] = useState<number | ''>('');
    const [users, setUsers] = useState<User[]>([]);
    const [quizAttempts, setQuizAttempts] = useState<QuizAttempt[]>([]);
    const [performanceSummary, setPerformanceSummary] = useState<PerformanceSummary | null>(null);
    const [loading, setLoading] = useState(false);
    const [loadingUsers, setLoadingUsers] = useState(true);

    const dataProvider = useDataProvider();
    const notify = useNotify();

    // Load users on component mount
    useEffect(() => {
        const loadUsers = async () => {
            try {
                const { data } = await dataProvider.getList('user', {
                    pagination: { page: 1, perPage: 1000 },
                    sort: { field: 'display_name', order: 'ASC' },
                    filter: {}
                });
                setUsers(data);
            } catch (error) {
                console.error('Error loading users:', error);
                notify('Error loading users', { type: 'error' });
            } finally {
                setLoadingUsers(false);
            }
        };

        loadUsers();
    }, [dataProvider, notify]);

    // Load student performance data when user is selected
    useEffect(() => {
        if (selectedUserId) {
            loadStudentData(selectedUserId);
        } else {
            setQuizAttempts([]);
            setPerformanceSummary(null);
        }
    }, [selectedUserId]);

    const loadStudentData = async (userId: number) => {
        setLoading(true);
        try {
            // Get authentication token
            const token = localStorage.getItem('token');
            
            // Load performance summary using the correct API
            const summaryResponse = await fetch(`${APIUrl}user_performance_summary/latest/${userId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': token || '',
                },
            });
            
            if (summaryResponse.ok) {
                const summaryData = await summaryResponse.json();
                console.log('Performance Summary API Response:', summaryData);
                if (summaryData.status === 'success' && summaryData.result) {
                    setPerformanceSummary(summaryData.result);
                } else {
                    console.log('No performance summary data in result:', summaryData);
                    setPerformanceSummary(null);
                }
            } else {
                console.error('Performance summary API failed:', summaryResponse.status, summaryResponse.statusText);
                setPerformanceSummary(null);
            }

            // Load quiz attempts history using the correct API
            const historyResponse = await fetch(`${APIUrl}quiz/user_history/${userId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': token || '',
                },
            });
            
            if (historyResponse.ok) {
                const historyData = await historyResponse.json();
                console.log('Quiz History API Response:', historyData);
                if (historyData.status === 'success' && historyData.result) {
                    // Transform quiz history data to quiz attempts format using correct API field names
                    const transformedAttempts: QuizAttempt[] = historyData.result.map((item: any, index: number) => {
                        // Calculate accuracy percentage from score and total questions
                        const accuracy = item.total_questions > 0 
                            ? Math.max(0, Math.min(100, (item.score / item.total_questions) * 100))
                            : 0;
                        
                        // Calculate correct answers from score (assuming positive score means correct)
                        const correctAnswers = item.score > 0 
                            ? Math.round((item.score / 100) * item.total_questions)
                            : Math.max(0, Math.round(item.total_questions * (item.score + 100) / 100));
                        
                        const incorrectAnswers = item.completed_questions - correctAnswers;
                        const unansweredQuestions = item.total_questions - item.completed_questions;
                        
                        return {
                            id: item.quiz_id + '_' + index, // Create unique ID
                            quiz_id: parseInt(item.quiz_id),
                            quiz_name: item.quiz_name || `Quiz ${item.quiz_id}`,
                            quiz_attempt_number: 1, // Default since not provided in API
                            total_questions: item.total_questions,
                            correct_answers: Math.max(0, correctAnswers),
                            incorrect_answers: Math.max(0, incorrectAnswers),
                            unanswered_questions: Math.max(0, unansweredQuestions),
                            accuracy_percentage: Math.round(accuracy * 100) / 100,
                            total_time_spent: item.time_spent || 0,
                            average_time_per_question: item.total_questions > 0 
                                ? Math.round((item.time_spent / item.total_questions) * 100) / 100 
                                : 0,
                            created_at: item.last_attempt || item.attempt_date,
                        };
                    });

                    setQuizAttempts(transformedAttempts);
                } else {
                    console.log('No quiz history data in result:', historyData);
                    setQuizAttempts([]);
                    notify('No quiz history found for this student', { type: 'info' });
                }
            } else {
                console.error('Quiz history API failed:', historyResponse.status, historyResponse.statusText);
                setQuizAttempts([]);
                notify('Error loading quiz history', { type: 'error' });
            }

        } catch (error) {
            console.error('Error loading student data:', error);
            notify(`Error loading student performance data: ${error.message}`, { type: 'error' });
            setQuizAttempts([]);
            setPerformanceSummary(null);
        } finally {
            setLoading(false);
        }
    };

    const formatTime = (seconds: number) => {
        if (seconds < 60) return `${seconds}s`;
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}m ${remainingSeconds}s`;
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return 'No Date';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Invalid Date';
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        } catch {
            return 'Invalid Date';
        }
    };

    const formatRelativeDate = (dateString: string) => {
        if (!dateString) return 'No Date';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Invalid Date';
            return formatDistanceToNow(date, { addSuffix: true });
        } catch {
            return 'Invalid Date';
        }
    };

    const getTrendIcon = (trend: string) => {
        switch (trend?.toLowerCase()) {
            case 'improving':
                return <TrendingUpIcon sx={{ color: 'green' }} />;
            case 'declining':
                return <TrendingDownIcon sx={{ color: 'red' }} />;
            default:
                return <AssessmentIcon sx={{ color: 'blue' }} />;
        }
    };

    const getMasteryColor = (level: string) => {
        switch (level?.toLowerCase()) {
            case 'expert':
            case 'master':
                return 'success';
            case 'proficient':
                return 'info';
            case 'developing':
                return 'warning';
            case 'beginner':
            default:
                return 'default';
        }
    };

    const selectedUser = users.find(user => user.id === selectedUserId);

    return (
        <Box sx={{ padding: 3 }}>
            <Title title="Student Performance Analysis" />
            
            <Card sx={{ marginBottom: 3 }}>
                <CardHeader
                    title={
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <SchoolIcon />
                            <Typography variant="h5">Student Performance Dashboard</Typography>
                        </Box>
                    }
                    subheader="Select a student to view their detailed quiz performance and AI-powered analytics"
                />
                <CardContent>
                    <FormControl fullWidth sx={{ marginBottom: 2 }}>
                        <InputLabel id="student-select-label">Select Student</InputLabel>
                        <Select
                            labelId="student-select-label"
                            value={selectedUserId}
                            label="Select Student"
                            onChange={(e) => setSelectedUserId(e.target.value as number)}
                            disabled={loadingUsers}
                        >
                            <MenuItem value="">
                                <em>-- Select a Student --</em>
                            </MenuItem>
                            {users.map((user) => (
                                <MenuItem key={user.id} value={user.id}>
                                    {user.display_name} ({user.username})
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>

                    {loadingUsers && (
                        <Box sx={{ display: 'flex', justifyContent: 'center', py: 2 }}>
                            <CircularProgress />
                            <Typography sx={{ ml: 2 }}>Loading students...</Typography>
                        </Box>
                    )}
                </CardContent>
            </Card>

            {selectedUserId && (
                <>
                    {loading ? (
                        <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
                            <CircularProgress size={50} />
                            <Typography sx={{ ml: 2 }}>Loading performance data...</Typography>
                        </Box>
                    ) : (
                        <>
                            {/* Student Info Header */}
                            <Card sx={{ marginBottom: 3 }}>
                                <CardContent>
                                    <Typography variant="h6" gutterBottom>
                                        Performance Overview for: {selectedUser?.display_name}
                                    </Typography>
                                    <Typography variant="body2" color="text.secondary">
                                        Username: {selectedUser?.username} | Total Quiz Attempts: {quizAttempts.length}
                                    </Typography>
                                </CardContent>
                            </Card>

                            {/* Performance Summary Section */}
                            {performanceSummary && (
                                <Card sx={{ marginBottom: 3 }}>
                                    <CardHeader
                                        title={
                                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                                <AssessmentIcon />
                                                <Typography variant="h6">AI Performance Summary</Typography>
                                            </Box>
                                        }
                                        subheader={`Generated on: ${formatDate(performanceSummary.generation_date)}`}
                                    />
                                    <CardContent>
                                        {/* Key Metrics Cards */}
                                        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={3} sx={{ marginBottom: 3, flexWrap: 'wrap' }}>
                                            <Box sx={{ flex: 1, minWidth: { xs: '100%', sm: '250px' } }}>
                                                <Card variant="outlined">
                                                    <CardContent sx={{ textAlign: 'center' }}>
                                                        <Typography variant="h4" color="primary">
                                                            {performanceSummary.overall_accuracy_percentage}%
                                                        </Typography>
                                                        <Typography variant="body2">Overall Accuracy</Typography>
                                                    </CardContent>
                                                </Card>
                                            </Box>
                                            <Box sx={{ flex: 1, minWidth: { xs: '100%', sm: '250px' } }}>
                                                <Card variant="outlined">
                                                    <CardContent sx={{ textAlign: 'center' }}>
                                                        <Typography variant="h4" color="secondary">
                                                            {performanceSummary.total_quizzes_taken}
                                                        </Typography>
                                                        <Typography variant="body2">Total Quizzes</Typography>
                                                    </CardContent>
                                                </Card>
                                            </Box>
                                            <Box sx={{ flex: 1, minWidth: { xs: '100%', sm: '250px' } }}>
                                                <Card variant="outlined">
                                                    <CardContent sx={{ textAlign: 'center' }}>
                                                        <Chip 
                                                            label={performanceSummary.mastery_level} 
                                                            color={getMasteryColor(performanceSummary.mastery_level)}
                                                            sx={{ fontSize: '0.9rem', fontWeight: 'bold' }}
                                                        />
                                                        <Typography variant="body2" sx={{ mt: 1 }}>Mastery Level</Typography>
                                                    </CardContent>
                                                </Card>
                                            </Box>
                                            <Box sx={{ flex: 1, minWidth: { xs: '100%', sm: '250px' } }}>
                                                <Card variant="outlined">
                                                    <CardContent sx={{ textAlign: 'center' }}>
                                                        <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
                                                            {getTrendIcon(performanceSummary.performance_trend)}
                                                            <Typography variant="body1" sx={{ ml: 1, textTransform: 'capitalize' }}>
                                                                {performanceSummary.performance_trend}
                                                            </Typography>
                                                        </Box>
                                                        <Typography variant="body2">Performance Trend</Typography>
                                                    </CardContent>
                                                </Card>
                                            </Box>
                                        </Stack>

                                        {/* Detailed Analysis Accordions */}
                                        <Accordion>
                                            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                                                <Typography variant="h6">Strengths & Weaknesses Analysis</Typography>
                                            </AccordionSummary>
                                            <AccordionDetails>
                                                <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
                                                    <Box sx={{ flex: 1 }}>
                                                        <Typography variant="subtitle1" color="success.main" gutterBottom>
                                                            💪 Strongest Areas
                                                        </Typography>
                                                        <Typography variant="body2" gutterBottom>
                                                            <strong>Subject:</strong> {performanceSummary.strongest_overall_subject}
                                                        </Typography>
                                                        <Typography variant="body2" gutterBottom>
                                                            <strong>Topic:</strong> {performanceSummary.strongest_overall_topic}
                                                        </Typography>
                                                        {performanceSummary.ai_strengths_summary && (
                                                            <Typography variant="body2" sx={{ mt: 1, fontStyle: 'italic' }}>
                                                                {performanceSummary.ai_strengths_summary}
                                                            </Typography>
                                                        )}
                                                    </Box>
                                                    <Box sx={{ flex: 1 }}>
                                                        <Typography variant="subtitle1" color="error.main" gutterBottom>
                                                            📈 Areas for Improvement
                                                        </Typography>
                                                        <Typography variant="body2" gutterBottom>
                                                            <strong>Subject:</strong> {performanceSummary.weakest_overall_subject}
                                                        </Typography>
                                                        <Typography variant="body2" gutterBottom>
                                                            <strong>Topic:</strong> {performanceSummary.weakest_overall_topic}
                                                        </Typography>
                                                        {performanceSummary.ai_improvement_areas && (
                                                            <Typography variant="body2" sx={{ mt: 1, fontStyle: 'italic' }}>
                                                                {performanceSummary.ai_improvement_areas}
                                                            </Typography>
                                                        )}
                                                    </Box>
                                                </Stack>
                                            </AccordionDetails>
                                        </Accordion>

                                        <Accordion>
                                            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                                                <Typography variant="h6">Difficulty Level Performance</Typography>
                                            </AccordionSummary>
                                            <AccordionDetails>
                                                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                                                    <Box sx={{ flex: 1 }}>
                                                        <Card variant="outlined" sx={{ textAlign: 'center', bgcolor: 'success.light', color: 'success.contrastText' }}>
                                                            <CardContent>
                                                                <Typography variant="h5">{performanceSummary.easy_level_accuracy}%</Typography>
                                                                <Typography variant="body2">Easy Questions</Typography>
                                                            </CardContent>
                                                        </Card>
                                                    </Box>
                                                    <Box sx={{ flex: 1 }}>
                                                        <Card variant="outlined" sx={{ textAlign: 'center', bgcolor: 'warning.light', color: 'warning.contrastText' }}>
                                                            <CardContent>
                                                                <Typography variant="h5">{performanceSummary.medium_level_accuracy}%</Typography>
                                                                <Typography variant="body2">Medium Questions</Typography>
                                                            </CardContent>
                                                        </Card>
                                                    </Box>
                                                    <Box sx={{ flex: 1 }}>
                                                        <Card variant="outlined" sx={{ textAlign: 'center', bgcolor: 'error.light', color: 'error.contrastText' }}>
                                                            <CardContent>
                                                                <Typography variant="h5">{performanceSummary.hard_level_accuracy}%</Typography>
                                                                <Typography variant="body2">Hard Questions</Typography>
                                                            </CardContent>
                                                        </Card>
                                                    </Box>
                                                </Stack>
                                            </AccordionDetails>
                                        </Accordion>

                                        <Accordion>
                                            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                                                <Typography variant="h6">AI Insights & Recommendations</Typography>
                                            </AccordionSummary>
                                            <AccordionDetails>
                                                <Stack spacing={2}>
                                                    {performanceSummary.ai_overall_assessment && (
                                                        <Alert severity="info">
                                                            <Typography variant="subtitle2" gutterBottom>Overall Assessment</Typography>
                                                            <Typography variant="body2">
                                                                {performanceSummary.ai_overall_assessment}
                                                            </Typography>
                                                        </Alert>
                                                    )}
                                                    {performanceSummary.ai_learning_patterns && (
                                                        <Alert severity="success">
                                                            <Typography variant="subtitle2" gutterBottom>Learning Patterns</Typography>
                                                            <Typography variant="body2">
                                                                {performanceSummary.ai_learning_patterns}
                                                            </Typography>
                                                        </Alert>
                                                    )}
                                                    {performanceSummary.ai_study_recommendations && (
                                                        <Alert severity="warning">
                                                            <Typography variant="subtitle2" gutterBottom>Study Recommendations</Typography>
                                                            <Typography variant="body2">
                                                                {performanceSummary.ai_study_recommendations}
                                                            </Typography>
                                                        </Alert>
                                                    )}
                                                </Stack>
                                            </AccordionDetails>
                                        </Accordion>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Quiz Attempts Table */}
                            <Card>
                                <CardHeader
                                    title={
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                            <QuizIcon />
                                            <Typography variant="h6">Quiz Attempts History</Typography>
                                        </Box>
                                    }
                                    subheader={`Showing ${quizAttempts.length} quiz attempts`}
                                />
                                <CardContent>
                                    {quizAttempts.length === 0 ? (
                                        <Alert severity="info">
                                            No quiz attempts found for this student.
                                        </Alert>
                                    ) : (
                                        <TableContainer component={Paper} variant="outlined">
                                            <Table>
                                                <TableHead>
                                                    <TableRow>
                                                        <TableCell><strong>Quiz</strong></TableCell>
                                                        <TableCell><strong>Attempt #</strong></TableCell>
                                                        <TableCell><strong>Accuracy</strong></TableCell>
                                                        <TableCell><strong>Correct/Total</strong></TableCell>
                                                        <TableCell><strong>Time Spent</strong></TableCell>
                                                        <TableCell><strong>Avg Time/Q</strong></TableCell>
                                                        <TableCell><strong>Date</strong></TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {quizAttempts.map((attempt) => (
                                                        <TableRow key={attempt.id} hover>
                                                            <TableCell>{attempt.quiz_name}</TableCell>
                                                            <TableCell>
                                                                <Chip 
                                                                    label={attempt.quiz_attempt_number} 
                                                                    size="small" 
                                                                    color="primary" 
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Chip
                                                                    label={`${attempt.accuracy_percentage}%`}
                                                                    color={
                                                                        attempt.accuracy_percentage >= 80 ? 'success' :
                                                                        attempt.accuracy_percentage >= 60 ? 'warning' : 'error'
                                                                    }
                                                                    size="small"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                {attempt.correct_answers}/{attempt.total_questions}
                                                                {attempt.unanswered_questions > 0 && (
                                                                    <Typography variant="caption" color="text.secondary" display="block">
                                                                        ({attempt.unanswered_questions} unanswered)
                                                                    </Typography>
                                                                )}
                                                            </TableCell>
                                                            <TableCell>
                                                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                                                    <TimerIcon fontSize="small" />
                                                                    {formatTime(attempt.total_time_spent)}
                                                                </Box>
                                                            </TableCell>
                                                            <TableCell>
                                                                {attempt.average_time_per_question?.toFixed(1)}s
                                                            </TableCell>
                                                            <TableCell>
                                                                <Typography variant="body2">
                                                                    {formatDate(attempt.created_at)}
                                                                </Typography>
                                                                <Typography variant="caption" color="text.secondary">
                                                                    {formatRelativeDate(attempt.created_at)}
                                                                </Typography>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                    )}
                                </CardContent>
                            </Card>
                        </>
                    )}
                </>
            )}
        </Box>
    );
};
