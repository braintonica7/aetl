import React, { useState, useEffect } from 'react';
import {
    Card,
    CardContent,
    Typography,
    Box,
    CircularProgress,
    LinearProgress,
    Chip,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Alert,
    useTheme,
    Tooltip,
    IconButton,
    Accordion,
    AccordionSummary,
    AccordionDetails,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    SelectChangeEvent
} from '@mui/material';
import {
    Assessment as AssessmentIcon,
    QuestionAnswer as QuestionAnswerIcon,
    Subject as SubjectIcon,
    Book as BookIcon,
    School as SchoolIcon,
    SmartToy as SmartToyIcon,
    Psychology as PsychologyIcon,
    Assignment as AssignmentIcon,
    Refresh as RefreshIcon,
    TrendingUp as TrendingUpIcon,
    ExpandMore as ExpandMoreIcon
} from '@mui/icons-material';
import { getQuestionSummary } from '../../common/apiClient';

interface QuestionSummaryData {
    question_type?: string;
    total_questions: number;
    questions_by_subject: Array<{
        subject_id: number;
        subject_name: string;
        question_count: number;
    }>;
    questions_by_chapter: Array<{
        chapter_id: number;
        chapter_name: string;
        subject_name: string;
        question_count: number;
    }>;
    questions_by_topic: Array<{
        topic_id: number;
        topic_name: string;
        chapter_name: string;
        subject_name: string;
        question_count: number;
    }>;
    questions_by_exam: Array<{
        exam_id: number;
        exam_name: string;
        question_count: number;
    }>;
    content_generation_stats: {
        total_questions: number;
        question_text: {
            generated: number;
            not_generated: number;
            percentage_generated: number;
        };
        ai_summary: {
            generated: number;
            not_generated: number;
            percentage_generated: number;
        };
        solution: {
            generated: number;
            not_generated: number;
            percentage_generated: number;
        };
    };
    recent_activity: Array<{
        date: string;
        questions_processed: number;
    }>;
}

const QuestionSummary: React.FC = () => {
    const theme = useTheme();
    const [data, setData] = useState<QuestionSummaryData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [questionType, setQuestionType] = useState<string>('regular');

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await getQuestionSummary(questionType);
            if (response && response.status === 'success') {
                setData(response.result);
            } else {
                setError('Failed to fetch question summary');
            }
        } catch (err) {
            console.error('Error fetching question summary:', err);
            setError('Network error occurred while fetching data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [questionType]);

    const ContentGenerationCard = ({ title, icon, stats, color }: { 
        title: string; 
        icon: React.ReactNode; 
        stats: { generated: number; not_generated: number; percentage_generated: number }; 
        color: string 
    }) => (
        <Card elevation={2} sx={{ height: '100%' }}>
            <CardContent>
                <Box display="flex" alignItems="center" mb={2}>
                    <Box 
                        sx={{ 
                            mr: 2, 
                            p: 1, 
                            borderRadius: 2, 
                            backgroundColor: `${color}15`,
                            color: color 
                        }}
                    >
                        {icon}
                    </Box>
                    <Typography variant="h6" fontWeight="bold">
                        {title}
                    </Typography>
                </Box>
                
                <Box mb={2}>
                    <Box display="flex" justifyContent="space-between" mb={1}>
                        <Typography variant="body2" color="text.secondary">
                            Progress
                        </Typography>
                        <Typography variant="body2" fontWeight="bold" color={color}>
                            {stats.percentage_generated}%
                        </Typography>
                    </Box>
                    <LinearProgress 
                        variant="determinate" 
                        value={stats.percentage_generated} 
                        sx={{ 
                            height: 8, 
                            borderRadius: 4,
                            backgroundColor: `${color}20`,
                            '& .MuiLinearProgress-bar': {
                                backgroundColor: color
                            }
                        }}
                    />
                </Box>

                <Box display="flex" justifyContent="space-between">
                    <Box textAlign="center" flex={1}>
                        <Typography variant="h4" fontWeight="bold" color={color}>
                            {stats.generated.toLocaleString()}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                            Generated
                        </Typography>
                    </Box>
                    <Box textAlign="center" flex={1}>
                        <Typography variant="h4" fontWeight="bold" color="text.secondary">
                            {stats.not_generated.toLocaleString()}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                            Pending
                        </Typography>
                    </Box>
                </Box>
            </CardContent>
        </Card>
    );

    if (loading) {
        return (
            <Card>
                <CardContent>
                    <Box display="flex" justifyContent="center" alignItems="center" minHeight={200}>
                        <CircularProgress />
                        <Typography variant="h6" ml={2}>
                            Loading question summary...
                        </Typography>
                    </Box>
                </CardContent>
            </Card>
        );
    }

    if (error || !data) {
        return (
            <Card>
                <CardContent>
                    <Alert severity="error" sx={{ mb: 2 }}>
                        {error || 'No data available'}
                    </Alert>
                    <Box textAlign="center">
                        <IconButton onClick={fetchData} color="primary">
                            <RefreshIcon />
                        </IconButton>
                        <Typography variant="body2" color="text.secondary">
                            Click to retry
                        </Typography>
                    </Box>
                </CardContent>
            </Card>
        );
    }

    return (
        <Box>
            {/* Header with Question Type Filter */}
            <Box display="flex" justifyContent="space-between" alignItems="center" mb={3} flexWrap="wrap" gap={2}>
                <Typography variant="h4" fontWeight="bold" color="primary">
                    Question Database Summary
                </Typography>
                
                <Box display="flex" alignItems="center" gap={2}>
                    <FormControl sx={{ minWidth: 200 }} size="small">
                        <InputLabel id="question-type-label">Question Type</InputLabel>
                        <Select
                            labelId="question-type-label"
                            id="question-type-select"
                            value={questionType}
                            label="Question Type"
                            onChange={(e: SelectChangeEvent) => setQuestionType(e.target.value)}
                        >
                            <MenuItem value="regular">Regular</MenuItem>
                            <MenuItem value="pyq">Previous Year Questions (PYQ)</MenuItem>
                            <MenuItem value="mock">Mock Test</MenuItem>
                        </Select>
                    </FormControl>
                    
                    <Tooltip title="Refresh Data">
                        <IconButton onClick={fetchData} color="primary">
                            <RefreshIcon />
                        </IconButton>
                    </Tooltip>
                </Box>
            </Box>

            {/* Overview Cards */}
            <Box display="flex" gap={3} mb={4} flexWrap="wrap">
                <Box flex="1 1 300px" minWidth="250px">
                    <Card elevation={2} sx={{ height: '100%', background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white' }}>
                        <CardContent>
                            <Box display="flex" alignItems="center" justifyContent="space-between">
                                <Box>
                                    <Typography variant="h3" fontWeight="bold">
                                        {data.total_questions.toLocaleString()}
                                    </Typography>
                                    <Typography variant="h6">
                                        Total Questions
                                    </Typography>
                                </Box>
                                <QuestionAnswerIcon sx={{ fontSize: 48, opacity: 0.8 }} />
                            </Box>
                        </CardContent>
                    </Card>
                </Box>

                <Box flex="1 1 300px" minWidth="250px">
                    <Card elevation={2} sx={{ height: '100%', background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', color: 'white' }}>
                        <CardContent>
                            <Box display="flex" alignItems="center" justifyContent="space-between">
                                <Box>
                                    <Typography variant="h3" fontWeight="bold">
                                        {data.questions_by_subject.length}
                                    </Typography>
                                    <Typography variant="h6">
                                        Active Subjects
                                    </Typography>
                                </Box>
                                <SubjectIcon sx={{ fontSize: 48, opacity: 0.8 }} />
                            </Box>
                        </CardContent>
                    </Card>
                </Box>

                <Box flex="1 1 300px" minWidth="250px">
                    <Card elevation={2} sx={{ height: '100%', background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)', color: 'white' }}>
                        <CardContent>
                            <Box display="flex" alignItems="center" justifyContent="space-between">
                                <Box>
                                    <Typography variant="h3" fontWeight="bold">
                                        {data.content_generation_stats.ai_summary.percentage_generated}%
                                    </Typography>
                                    <Typography variant="h6">
                                        AI Generated
                                    </Typography>
                                </Box>
                                <SmartToyIcon sx={{ fontSize: 48, opacity: 0.8 }} />
                            </Box>
                        </CardContent>
                    </Card>
                </Box>

                <Box flex="1 1 300px" minWidth="250px">
                    <Card elevation={2} sx={{ height: '100%', background: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)', color: 'white' }}>
                        <CardContent>
                            <Box display="flex" alignItems="center" justifyContent="space-between">
                                <Box>
                                    <Typography variant="h3" fontWeight="bold">
                                        {data.questions_by_exam.length}
                                    </Typography>
                                    <Typography variant="h6">
                                        Active Exams
                                    </Typography>
                                </Box>
                                <SchoolIcon sx={{ fontSize: 48, opacity: 0.8 }} />
                            </Box>
                        </CardContent>
                    </Card>
                </Box>
            </Box>

            {/* Content Generation Statistics */}
            <Box mb={4}>
                <Typography variant="h5" fontWeight="bold" mb={2} color="text.primary">
                    <AssessmentIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                    Content Generation Statistics
                </Typography>
                
                <Box display="flex" gap={3} flexWrap="wrap">
                    <Box flex="1 1 300px" minWidth="300px">
                        <ContentGenerationCard
                            title="Question Text"
                            icon={<QuestionAnswerIcon />}
                            stats={data.content_generation_stats.question_text}
                            color={theme.palette.primary.main}
                        />
                    </Box>
                    
                    <Box flex="1 1 300px" minWidth="300px">
                        <ContentGenerationCard
                            title="AI Summary"
                            icon={<PsychologyIcon />}
                            stats={data.content_generation_stats.ai_summary}
                            color={theme.palette.secondary.main}
                        />
                    </Box>
                    
                    <Box flex="1 1 300px" minWidth="300px">
                        <ContentGenerationCard
                            title="Solutions"
                            icon={<AssignmentIcon />}
                            stats={data.content_generation_stats.solution}
                            color={theme.palette.success.main}
                        />
                    </Box>
                </Box>
            </Box>

            {/* Distribution Tables */}
            <Box display="flex" gap={3} flexWrap="wrap">
                {/* Questions by Subject */}
                <Box flex="1 1 400px" minWidth="400px">
                    <Accordion defaultExpanded>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography variant="h6" fontWeight="bold">
                                <SubjectIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                                Questions by Subject
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <TableContainer component={Paper} elevation={0}>
                                <Table size="small">
                                    <TableHead>
                                        <TableRow>
                                            <TableCell><strong>Subject</strong></TableCell>
                                            <TableCell align="right"><strong>Questions</strong></TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.questions_by_subject.slice(0, 10).map((subject) => (
                                            <TableRow key={subject.subject_id}>
                                                <TableCell>{subject.subject_name}</TableCell>
                                                <TableCell align="right">
                                                    <Chip 
                                                        label={subject.question_count.toLocaleString()} 
                                                        size="small" 
                                                        color="primary" 
                                                        variant="outlined"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </AccordionDetails>
                    </Accordion>
                </Box>

                {/* Questions by Exam */}
                <Box flex="1 1 400px" minWidth="400px">
                    <Accordion defaultExpanded>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography variant="h6" fontWeight="bold">
                                <SchoolIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                                Questions by Exam
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <TableContainer component={Paper} elevation={0}>
                                <Table size="small">
                                    <TableHead>
                                        <TableRow>
                                            <TableCell><strong>Exam</strong></TableCell>
                                            <TableCell align="right"><strong>Questions</strong></TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.questions_by_exam.slice(0, 10).map((exam) => (
                                            <TableRow key={exam.exam_id}>
                                                <TableCell>{exam.exam_name}</TableCell>
                                                <TableCell align="right">
                                                    <Chip 
                                                        label={exam.question_count.toLocaleString()} 
                                                        size="small" 
                                                        color="secondary" 
                                                        variant="outlined"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </AccordionDetails>
                    </Accordion>
                </Box>

                {/* Top Chapters */}
                <Box flex="1 1 400px" minWidth="400px">
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography variant="h6" fontWeight="bold">
                                <BookIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                                Top Chapters by Questions
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <TableContainer component={Paper} elevation={0}>
                                <Table size="small">
                                    <TableHead>
                                        <TableRow>
                                            <TableCell><strong>Chapter</strong></TableCell>
                                            <TableCell><strong>Subject</strong></TableCell>
                                            <TableCell align="right"><strong>Questions</strong></TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.questions_by_chapter.slice(0, 10).map((chapter) => (
                                            <TableRow key={chapter.chapter_id}>
                                                <TableCell>{chapter.chapter_name}</TableCell>
                                                <TableCell>
                                                    <Chip 
                                                        label={chapter.subject_name} 
                                                        size="small" 
                                                        variant="outlined"
                                                    />
                                                </TableCell>
                                                <TableCell align="right">
                                                    <Chip 
                                                        label={chapter.question_count.toLocaleString()} 
                                                        size="small" 
                                                        color="info" 
                                                        variant="outlined"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </AccordionDetails>
                    </Accordion>
                </Box>

                {/* Recent Activity */}
                <Box flex="1 1 400px" minWidth="400px">
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography variant="h6" fontWeight="bold">
                                <TrendingUpIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                                Recent AI Processing Activity
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            {data.recent_activity.length > 0 ? (
                                <TableContainer component={Paper} elevation={0}>
                                    <Table size="small">
                                        <TableHead>
                                            <TableRow>
                                                <TableCell><strong>Date</strong></TableCell>
                                                <TableCell align="right"><strong>Questions Processed</strong></TableCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody>
                                            {data.recent_activity.map((activity, index) => (
                                                <TableRow key={index}>
                                                    <TableCell>
                                                        {new Date(activity.date).toLocaleDateString()}
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        <Chip 
                                                            label={activity.questions_processed.toLocaleString()} 
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
                            ) : (
                                <Alert severity="info">
                                    No recent AI processing activity found in the last 30 days.
                                </Alert>
                            )}
                        </AccordionDetails>
                    </Accordion>
                </Box>
            </Box>
        </Box>
    );
};

export default QuestionSummary;
