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
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    useTheme,
    Tooltip,
    IconButton,
    Accordion,
    AccordionSummary,
    AccordionDetails
} from '@mui/material';
import {
    Book as BookIcon,
    Subject as SubjectIcon,
    Assessment as AssessmentIcon,
    QuestionAnswer as QuestionAnswerIcon,
    SmartToy as SmartToyIcon,
    Psychology as PsychologyIcon,
    Assignment as AssignmentIcon,
    Refresh as RefreshIcon,
    ExpandMore as ExpandMoreIcon,
    Analytics as AnalyticsIcon
} from '@mui/icons-material';
import { getQuestionSummary, getQuestionsBySubject, getAllSubjects } from '../../common/apiClient';

interface SubjectOption {
    id: string;
    subject: string;
}

interface ChapterData {
    chapter_id: number;
    chapter_name: string;
    question_count: number;
    content_stats: {
        question_text_generated: number;
        ai_summary_generated: number;
        solution_generated: number;
        completion_percentage: number;
    };
}

interface SubjectAnalysisData {
    subject_details: {
        subject_id: number;
        subject_name: string;
    };
    total_questions: number;
    questions_by_chapter: ChapterData[];
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
}

const ContentGenerationCard: React.FC<{
    title: string;
    icon: React.ReactNode;
    generated: number;
    total: number;
    percentage: number;
    color: string;
}> = ({ title, icon, generated, total, percentage, color }) => {
    const theme = useTheme();
    
    return (
        <Card elevation={2} sx={{ height: '100%' }}>
            <CardContent>
                <Box display="flex" alignItems="center" justifyContent="space-between" mb={2}>
                    <Typography variant="h6" fontWeight="bold" color={color}>
                        {title}
                    </Typography>
                    <Box sx={{ color: color }}>
                        {icon}
                    </Box>
                </Box>
                
                <Box mb={2}>
                    <Typography variant="h4" fontWeight="bold" color={color}>
                        {generated.toLocaleString()}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        of {total.toLocaleString()} questions
                    </Typography>
                </Box>
                
                <Box mb={1}>
                    <Box display="flex" justifyContent="space-between" alignItems="center" mb={0.5}>
                        <Typography variant="body2" color="text.secondary">
                            Completion
                        </Typography>
                        <Typography variant="body2" fontWeight="bold" color={color}>
                            {percentage}%
                        </Typography>
                    </Box>
                    <LinearProgress 
                        variant="determinate" 
                        value={percentage} 
                        sx={{ 
                            height: 8, 
                            borderRadius: 4,
                            backgroundColor: theme.palette.grey[200],
                            '& .MuiLinearProgress-bar': {
                                backgroundColor: color,
                                borderRadius: 4,
                            }
                        }} 
                    />
                </Box>
            </CardContent>
        </Card>
    );
};

const SubjectQuestionAnalysis: React.FC = () => {
    const [subjects, setSubjects] = useState<SubjectOption[]>([]);
    const [selectedSubject, setSelectedSubject] = useState<string | ''>('');
    const [data, setData] = useState<SubjectAnalysisData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const theme = useTheme();

    // Load subjects on component mount
    useEffect(() => {
        loadSubjects();
    }, []);

    const loadSubjects = async () => {
        try {
            const response = await getAllSubjects();
            if (response && response.status === 'success' && response.result) {
                setSubjects(response.result || []);
            }
        } catch (err) {
            console.error('Error loading subjects:', err);
            setError('Failed to load subjects');
        }
    };

    const loadSubjectData = async (subjectId: string) => {
        setLoading(true);
        setError(null);
        
        try {
            const response = await getQuestionsBySubject(subjectId);
            console.log('Subject API Response:', response); // Debug log
            if (response && response.status === 'success' && response.result) {
                setData(response.result);
                console.log('Subject data loaded successfully:', response.result); // Debug log
            } else {
                setError('Failed to load subject data');
            }
        } catch (err) {
            console.error('Error loading subject data:', err);
            setError('Failed to load subject data');
        } finally {
            setLoading(false);
        }
    };

    const handleSubjectChange = (subjectId: string) => {
        setSelectedSubject(subjectId);
        if (subjectId) {
            loadSubjectData(subjectId);
        } else {
            setData(null);
        }
    };

    const handleRefresh = () => {
        if (selectedSubject) {
            loadSubjectData(selectedSubject);
        }
    };

    return (
        <Box sx={{ p: 3 }}>
            {/* Header */}
            <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
                <Typography variant="h4" fontWeight="bold" color="primary.main">
                    <AnalyticsIcon sx={{ mr: 2, verticalAlign: 'middle' }} />
                    Subject Question Analysis
                </Typography>
                
                {selectedSubject && (
                    <Tooltip title="Refresh Data">
                        <IconButton onClick={handleRefresh} disabled={loading}>
                            <RefreshIcon />
                        </IconButton>
                    </Tooltip>
                )}
            </Box>

            {/* Subject Selector */}
            <Card elevation={2} sx={{ mb: 3 }}>
                <CardContent>
                    <FormControl fullWidth>
                        <InputLabel id="subject-select-label">Select Subject</InputLabel>
                        <Select
                            labelId="subject-select-label"
                            value={selectedSubject}
                            label="Select Subject"
                            onChange={(e) => handleSubjectChange(e.target.value as string)}
                        >
                            <MenuItem value="">
                                <em>Choose a subject to analyze</em>
                            </MenuItem>
                            {subjects.map((subject) => (
                                <MenuItem key={subject.id} value={subject.id}>
                                    <Box display="flex" justifyContent="space-between" alignItems="center" width="100%">
                                        <Typography>{subject.subject}</Typography>
                                    </Box>
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                </CardContent>
            </Card>

            {/* Error Display */}
            {error && (
                <Alert severity="error" sx={{ mb: 3 }}>
                    {error}
                </Alert>
            )}

            {/* Loading State */}
            {loading && (
                <Box display="flex" justifyContent="center" alignItems="center" py={8}>
                    <CircularProgress size={60} />
                    <Typography variant="h6" sx={{ ml: 2 }}>
                        Loading subject analysis...
                    </Typography>
                </Box>
            )}

            {/* Subject Analysis Results */}
            {data && !loading && (
                <Box>
                    {/* Subject Info Header */}
                    <Card elevation={1} sx={{ mb: 3, background: theme.palette.primary.main, color: 'white' }}>
                        <CardContent>
                            <Box display="flex" justifyContent="space-between" alignItems="center">
                                <Box>
                                    <Typography variant="h5" fontWeight="bold">
                                        <SubjectIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                                        {data.subject_details.subject_name}
                                    </Typography>
                                    <Typography variant="body1" sx={{ opacity: 0.9 }}>
                                        Complete question analysis and content generation overview
                                    </Typography>
                                </Box>
                                <Box textAlign="right">
                                    <Typography variant="h3" fontWeight="bold">
                                        {data.total_questions.toLocaleString()}
                                    </Typography>
                                    <Typography variant="body1" sx={{ opacity: 0.9 }}>
                                        Total Questions
                                    </Typography>
                                </Box>
                            </Box>
                        </CardContent>
                    </Card>

                    {/* Content Generation Overview */}
                    <Box display="flex" gap={3} flexWrap="wrap" mb={4}>
                        <Box flex="1 1 300px" minWidth="300px">
                            <ContentGenerationCard
                                title="Question Text Generated"
                                icon={<QuestionAnswerIcon />}
                                generated={data.content_generation_stats.question_text.generated}
                                total={data.content_generation_stats.total_questions}
                                percentage={data.content_generation_stats.question_text.percentage_generated}
                                color={theme.palette.primary.main}
                            />
                        </Box>
                        
                        <Box flex="1 1 300px" minWidth="300px">
                            <ContentGenerationCard
                                title="AI Summary Generated"
                                icon={<SmartToyIcon />}
                                generated={data.content_generation_stats.ai_summary.generated}
                                total={data.content_generation_stats.total_questions}
                                percentage={data.content_generation_stats.ai_summary.percentage_generated}
                                color={theme.palette.secondary.main}
                            />
                        </Box>
                        
                        <Box flex="1 1 300px" minWidth="300px">
                            <ContentGenerationCard
                                title="Solution Generated"
                                icon={<AssignmentIcon />}
                                generated={data.content_generation_stats.solution.generated}
                                total={data.content_generation_stats.total_questions}
                                percentage={data.content_generation_stats.solution.percentage_generated}
                                color={theme.palette.success.main}
                            />
                        </Box>
                    </Box>

                    {/* Chapter-wise Breakdown */}
                    <Accordion defaultExpanded>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography variant="h6" fontWeight="bold">
                                <BookIcon sx={{ mr: 1, verticalAlign: 'middle' }} />
                                Chapter-wise Question Breakdown
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <TableContainer component={Paper} elevation={0}>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableCell><strong>Chapter</strong></TableCell>
                                            <TableCell align="center"><strong>Total Questions</strong></TableCell>
                                            <TableCell align="center"><strong>Question Text</strong></TableCell>
                                            <TableCell align="center"><strong>AI Summary</strong></TableCell>
                                            <TableCell align="center"><strong>Solution</strong></TableCell>
                                            <TableCell align="center"><strong>Overall Progress</strong></TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.questions_by_chapter.map((chapter) => (
                                            <TableRow key={chapter.chapter_id} hover>
                                                <TableCell>
                                                    <Typography fontWeight="medium">
                                                        {chapter.chapter_name}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Chip 
                                                        label={chapter.question_count.toLocaleString()} 
                                                        color="primary" 
                                                        variant="outlined"
                                                        size="small"
                                                    />
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Chip 
                                                        label={chapter.content_stats.question_text_generated} 
                                                        color="info" 
                                                        variant="filled"
                                                        size="small"
                                                    />
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Chip 
                                                        label={chapter.content_stats.ai_summary_generated} 
                                                        color="secondary" 
                                                        variant="filled"
                                                        size="small"
                                                    />
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Chip 
                                                        label={chapter.content_stats.solution_generated} 
                                                        color="success" 
                                                        variant="filled"
                                                        size="small"
                                                    />
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box display="flex" alignItems="center" justifyContent="center" gap={1}>
                                                        <LinearProgress 
                                                            variant="determinate" 
                                                            value={chapter.content_stats.completion_percentage} 
                                                            sx={{ 
                                                                width: 80, 
                                                                height: 6, 
                                                                borderRadius: 3,
                                                                backgroundColor: theme.palette.grey[200],
                                                            }} 
                                                        />
                                                        <Typography variant="body2" fontWeight="bold" color="primary">
                                                            {chapter.content_stats.completion_percentage}%
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>
                        </AccordionDetails>
                    </Accordion>
                </Box>
            )}

            {/* Empty State */}
            {!selectedSubject && !loading && (
                <Card elevation={1} sx={{ py: 8 }}>
                    <CardContent>
                        <Box textAlign="center">
                            <AnalyticsIcon sx={{ fontSize: 80, color: 'text.secondary', mb: 2 }} />
                            <Typography variant="h5" color="text.secondary" gutterBottom>
                                Select a Subject to Begin Analysis
                            </Typography>
                            <Typography variant="body1" color="text.secondary">
                                Choose a subject from the dropdown above to view detailed question analytics,
                                chapter-wise breakdowns, and content generation progress.
                            </Typography>
                        </Box>
                    </CardContent>
                </Card>
            )}
        </Box>
    );
};

export default SubjectQuestionAnalysis;
