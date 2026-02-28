import React, { useEffect, useState } from 'react';
import { 
    useParams, 
    useNavigate
} from 'react-router-dom';
import {
    Card,
    CardContent,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Typography,
    Button,
    Box,
    CircularProgress,
    Alert,
    Chip,
    TablePagination,
    IconButton,
    Tooltip,
    TextField
} from '@mui/material';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import ImageIcon from '@mui/icons-material/Image';
import SearchIcon from '@mui/icons-material/Search';
import * as apiClient from '../../common/apiClient';

interface Question {
    id: number;
    question_img_url: string;
    subject_id: number;
    subject_name: string;
    chapter_id: number;
    chapter_name: string;
    topic_id: number;
    topic_name: string;
    exam_id: number;
    exam_name: string;
    level: string;
    question_type: string;
    year: number;
    correct_option: number;
    option_count: number;
}

interface Quiz {
    id: number;
    name: string;
    description: string;
    quiz_reference: string;
    quiz_question_type: string;
    level: string;
    total_questions: number;
    subject_id: number;
    subject_name?: string;
}

export const QuizQuestionsList = () => {
    const { quizId: routeQuizId } = useParams<{ quizId: string }>();
    const navigate = useNavigate();
    
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [quiz, setQuiz] = useState<Quiz | null>(null);
    const [questions, setQuestions] = useState<Question[]>([]);
    const [page, setPage] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(25);
    const [searchQuizId, setSearchQuizId] = useState('');

    const loadQuizAndQuestions = async (quizId: string) => {
        if (!quizId) {
            setError('Quiz ID is required');
            setLoading(false);
            return;
        }

        try {
            setLoading(true);
            setError(null);

            // Load quiz details
            const quizResponse = await apiClient.getRecord('quiz', parseInt(quizId));
            if (quizResponse?.status === 'success' && quizResponse?.result) {
                const quizData = quizResponse.result;
                
                // Fetch subject name if subject_id exists
                if (quizData.subject_id) {
                    try {
                        const subjectResponse = await apiClient.getRecord('subject', quizData.subject_id);
                        if (subjectResponse?.status === 'success' && subjectResponse?.result) {
                            quizData.subject_name = subjectResponse.result.subject;
                        }
                    } catch (err) {
                        console.error('Error loading subject:', err);
                    }
                }
                
                setQuiz(quizData);
            } else {
                setError('Quiz not found');
                setQuiz(null);
                setQuestions([]);
                return;
            }

            // Load questions for quiz
            const questionsResponse = await apiClient.getQuestionsForQuiz(parseInt(quizId));
            
            if (questionsResponse?.status === 'success' && questionsResponse?.result) {
                setQuestions(questionsResponse.result);
            } else {
                setError(questionsResponse?.message || 'Failed to load questions');
            }
        } catch (err: any) {
            console.error('Error loading quiz questions:', err);
            setError(err.message || 'Failed to load quiz questions');
            setQuiz(null);
            setQuestions([]);
        } finally {
            setLoading(false);
        }
    };

    // Load quiz from route on initial mount
    useEffect(() => {
        if (routeQuizId) {
            setSearchQuizId(routeQuizId);
            loadQuizAndQuestions(routeQuizId);
        }
    }, [routeQuizId]);

    const handleSearchClick = () => {
        if (searchQuizId.trim()) {
            setPage(0);
            loadQuizAndQuestions(searchQuizId.trim());
        }
    };

    const handleSearchKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearchClick();
        }
    };

    const handleChangePage = (_event: unknown, newPage: number) => {
        setPage(newPage);
    };

    const handleChangeRowsPerPage = (event: React.ChangeEvent<HTMLInputElement>) => {
        setRowsPerPage(parseInt(event.target.value, 10));
        setPage(0);
    };

    const getLevelColor = (level: string): 'success' | 'primary' | 'error' | 'default' => {
        const colors: { [key: string]: 'success' | 'primary' | 'error' | 'default' } = {
            Elementary: 'success',
            Moderate: 'primary',
            Advance: 'error'
        };
        return colors[level] || 'default';
    };

    const getQuestionTypeColor = (type: string): 'primary' | 'success' | 'warning' | 'default' => {
        const colors: { [key: string]: 'primary' | 'success' | 'warning' | 'default' } = {
            regular: 'primary',
            pyq: 'success',
            mock: 'warning'
        };
        return colors[type] || 'default';
    };

    const getQuestionTypeLabel = (type: string): string => {
        const labels: { [key: string]: string } = {
            regular: 'Regular',
            pyq: 'PYQ',
            mock: 'Mock Test'
        };
        return labels[type] || type;
    };

    const displayedQuestions = questions.slice(
        page * rowsPerPage,
        page * rowsPerPage + rowsPerPage
    );

    return (
        <Box p={3}>
            {/* Search Header */}
            <Box mb={3}>
                <Box display="flex" gap={2} alignItems="center">
                    <Button
                        variant="outlined"
                        startIcon={<ArrowBackIcon />}
                        onClick={() => navigate('/quiz')}
                    >
                        Back to Quiz List
                    </Button>
                    <TextField
                        label="Quiz ID"
                        variant="outlined"
                        size="small"
                        value={searchQuizId}
                        onChange={(e) => setSearchQuizId(e.target.value)}
                        onKeyPress={handleSearchKeyPress}
                        placeholder="Enter Quiz ID"
                        sx={{ width: 200 }}
                    />
                    <Button
                        variant="contained"
                        startIcon={<SearchIcon />}
                        onClick={handleSearchClick}
                        disabled={!searchQuizId.trim()}
                    >
                        Load Quiz
                    </Button>
                </Box>
            </Box>

            {loading && (
                <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
                    <CircularProgress />
                </Box>
            )}

            {error && !loading && (
                <Alert severity="error" sx={{ mb: 2 }}>
                    {error}
                </Alert>
            )}

            {!loading && !error && !quiz && (
                <Alert severity="info">
                    Enter a Quiz ID above to load and view questions.
                </Alert>
            )}

            {/* Quiz Details Header */}
            {!loading && quiz && (
            <Box mb={3}>
                <Box flex={1}>
                    <Typography variant="h5" component="h1" gutterBottom>
                        Questions for: {quiz?.name || `Quiz #${quiz?.id}`}
                    </Typography>
                    {quiz && (
                        <Box display="flex" gap={1} alignItems="center">
                            <Chip 
                                label={`${questions.length} Questions`} 
                                size="small" 
                                color="primary"
                            />
                            <Chip 
                                label={quiz.level} 
                                size="small" 
                                color={getLevelColor(quiz.level)}
                            />
                            <Chip 
                                label={getQuestionTypeLabel(quiz.quiz_question_type)} 
                                size="small" 
                                color={getQuestionTypeColor(quiz.quiz_question_type)}
                            />
                            {quiz.subject_name && (
                                <Chip 
                                    label={`Subject: ${quiz.subject_name}`} 
                                    size="small" 
                                    variant="outlined"
                                    color="secondary"
                                />
                            )}
                            {quiz.quiz_reference && (
                                <Chip 
                                    label={`Ref: ${quiz.quiz_reference}`} 
                                    size="small" 
                                    variant="outlined"
                                />
                            )}
                        </Box>
                    )}
                </Box>
            </Box>
            )}

            {/* Questions Table */}
            {!loading && quiz && (
            <Card>
                <CardContent>
                    {questions.length === 0 ? (
                        <Alert severity="info">
                            No questions found for this quiz.
                        </Alert>
                    ) : (
                        <>
                            {/* Subject Count Summary */}
                            <Box mb={2} p={2} sx={{ backgroundColor: '#f5f5f5', borderRadius: 1 }}>
                                <Typography variant="subtitle2" gutterBottom sx={{ fontWeight: 'bold' }}>
                                    Questions by Subject:
                                </Typography>
                                <Box display="flex" gap={1} flexWrap="wrap">
                                    {Object.entries(
                                        questions.reduce((acc, q) => {
                                            const subject = q.subject_name || 'Unknown';
                                            acc[subject] = (acc[subject] || 0) + 1;
                                            return acc;
                                        }, {} as Record<string, number>)
                                    ).map(([subject, count]) => (
                                        <Chip
                                            key={subject}
                                            label={`${subject}: ${count}`}
                                            size="small"
                                            color="secondary"
                                            variant="outlined"
                                        />
                                    ))}
                                </Box>
                            </Box>
                            <TableContainer component={Paper} variant="outlined">
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableCell>ID</TableCell>
                                            <TableCell>Image</TableCell>
                                            <TableCell>Exam</TableCell>
                                            <TableCell>Subject</TableCell>
                                            <TableCell>Chapter</TableCell>
                                            <TableCell>Topic</TableCell>
                                            <TableCell>Level</TableCell>
                                            <TableCell>Type</TableCell>
                                            <TableCell>Year</TableCell>
                                            <TableCell>Options</TableCell>
                                            <TableCell>Answer</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {displayedQuestions.map((question) => (
                                            <TableRow key={question.id} hover>
                                                <TableCell>{question.id}</TableCell>
                                                <TableCell>
                                                    {question.question_img_url ? (
                                                        <Box
                                                            component="img"
                                                            src={question.question_img_url}
                                                            alt={`Question ${question.id}`}
                                                            sx={{
                                                                width: 250,
                                                                height: "auto",
                                                                objectFit: 'cover',
                                                                borderRadius: 1,
                                                                cursor: 'pointer',
                                                                border: '1px solid #e0e0e0',
                                                                '&:hover': {
                                                                    opacity: 0.8,
                                                                    transform: 'scale(1.05)',
                                                                    transition: 'all 0.2s'
                                                                }
                                                            }}
                                                            onClick={() => window.open(question.question_img_url, '_blank')}
                                                        />
                                                    ) : (
                                                        <Typography variant="body2" color="text.secondary">
                                                            -
                                                        </Typography>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Typography variant="body2">
                                                        {question.exam_name || '-'}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell>
                                                    <Typography variant="body2">
                                                        {question.subject_name || '-'}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell>
                                                    <Typography variant="body2">
                                                        {question.chapter_name || '-'}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell sx={{ maxWidth: 150 }}>
                                                    <Typography 
                                                        variant="body2"
                                                        sx={{
                                                            wordWrap: 'break-word',
                                                            whiteSpace: 'normal'
                                                        }}
                                                    >
                                                        {question.topic_name || '-'}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell>
                                                    <Chip 
                                                        label={question.level} 
                                                        size="small" 
                                                        color={getLevelColor(question.level)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Chip 
                                                        label={getQuestionTypeLabel(question.question_type)} 
                                                        size="small" 
                                                        color={getQuestionTypeColor(question.question_type)}
                                                    />
                                                </TableCell>
                                                <TableCell>{question.year || '-'}</TableCell>
                                                <TableCell>{question.option_count || '-'}</TableCell>
                                                <TableCell>
                                                    <Chip 
                                                        label={`Option ${question.correct_option}`} 
                                                        size="small" 
                                                        color="success"
                                                        variant="outlined"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                            <TablePagination
                                rowsPerPageOptions={[10, 25, 50, 100]}
                                component="div"
                                count={questions.length}
                                rowsPerPage={rowsPerPage}
                                page={page}
                                onPageChange={handleChangePage}
                                onRowsPerPageChange={handleChangeRowsPerPage}
                            />
                        </>
                    )}
                </CardContent>
            </Card>
            )}
        </Box>
    );
};
