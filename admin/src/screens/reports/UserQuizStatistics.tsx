import React, { useState, useEffect } from 'react';
import {
    Card,
    CardContent,
    CardHeader,
    TextField,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Typography,
    Box,
    Chip,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    Button,
    CircularProgress,
    Switch,
    FormControlLabel,
    Grid
} from '@mui/material';
import { 
    Search as SearchIcon, 
    Download as DownloadIcon,
    Refresh as RefreshIcon,
    Assessment as AssessmentIcon 
} from '@mui/icons-material';
import { useNotify } from 'react-admin';
import { getUserQuizStatistics } from '../../common/apiClient';

interface QuizLevelDistribution {
    elementary: number;
    intermediate: number;
    advanced: number;
}

interface UserQuizStats {
    user_id: number;
    username: string;
    display_name: string;
    last_activity: string;
    last_login: string | null;
    total_quizzes_attempted: number;
    total_quizzes_completed: number;
    completion_rate: number;
    total_score: number;
    average_score: number;
    accuracy_percentage: number;
    highest_score: number;
    total_questions_answered: number;
    total_correct_answers: number;
    total_incorrect_answers: number;
    total_skipped_questions: number;
    total_time_spent_seconds: number;
    average_time_per_quiz: number;
    average_time_per_question: number;
    first_quiz_date: string;
    last_quiz_date: string;
    days_since_last_quiz: number;
    active_days_count: number;
    unique_subjects_attempted: number;
    quiz_level_distribution: QuizLevelDistribution;
}

interface Pagination {
    current_page: number;
    total_pages: number;
    total_users: number;
    per_page: number;
    has_next_page: boolean;
    has_prev_page: boolean;
}

const UserQuizStatistics: React.FC = () => {
    const notify = useNotify();
    
    const [loading, setLoading] = useState(false);
    const [users, setUsers] = useState<UserQuizStats[]>([]);
    const [pagination, setPagination] = useState<Pagination | null>(null);
    const [filters, setFilters] = useState({
        search: '',
        date_from: '',
        date_to: '',
        min_quizzes: 0,
        active_only: false,
        sort_by: 'total_quizzes_attempted',
        sort_order: 'DESC' as 'ASC' | 'DESC',
        page: 1,
        limit: 20
    });

    const fetchData = async () => {
        setLoading(true);
        try {
            const params = {
                pagination: { 
                    page: filters.page, 
                    perPage: filters.limit 
                },
                sort: { 
                    field: filters.sort_by, 
                    order: filters.sort_order 
                },
                filter: {
                    search: filters.search,
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                    min_quizzes: filters.min_quizzes,
                    active_only: filters.active_only
                }
            };

            const response = await getUserQuizStatistics(params);
            setUsers(response.users);
            setPagination(response.pagination);
        } catch (error) {
            console.error('Error fetching quiz statistics:', error);
            notify('Error fetching quiz statistics', { type: 'error' });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [filters]);

    const handleFilterChange = (field: string, value: any) => {
        setFilters(prev => ({
            ...prev,
            [field]: value,
            page: 1 // Reset to first page when filtering
        }));
    };

    const handlePageChange = (newPage: number) => {
        setFilters(prev => ({ ...prev, page: newPage }));
    };

    const formatTime = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    };

    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const getAccuracyColor = (accuracy: number): 'success' | 'warning' | 'error' => {
        if (accuracy >= 80) return 'success';
        if (accuracy >= 60) return 'warning';
        return 'error';
    };

    const getCompletionRateColor = (rate: number): 'success' | 'warning' | 'error' => {
        if (rate >= 90) return 'success';
        if (rate >= 70) return 'warning';
        return 'error';
    };

    const exportToCSV = () => {
        if (!users.length) return;

        const headers = [
            'User ID', 'Username', 'Display Name', 'Total Quizzes Attempted',
            'Total Quizzes Completed', 'Completion Rate (%)', 'Accuracy (%)',
            'Total Score', 'Average Score', 'Highest Score', 'Total Questions',
            'Correct Answers', 'Incorrect Answers', 'Skipped Questions',
            'Active Days', 'Unique Subjects', 'Days Since Last Quiz'
        ];

        const csvContent = [
            headers.join(','),
            ...users.map(user => [
                user.user_id,
                `"${user.username}"`,
                `"${user.display_name}"`,
                user.total_quizzes_attempted,
                user.total_quizzes_completed,
                user.completion_rate,
                user.accuracy_percentage,
                user.total_score,
                user.average_score,
                user.highest_score,
                user.total_questions_answered,
                user.total_correct_answers,
                user.total_incorrect_answers,
                user.total_skipped_questions,
                user.active_days_count,
                user.unique_subjects_attempted,
                user.days_since_last_quiz
            ].join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `user_quiz_statistics_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return (
        <Box sx={{ p: 3 }}>
            <Card sx={{ mb: 3 }}>
                <CardHeader
                    title={
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <AssessmentIcon />
                            <Typography variant="h5">User Quiz Statistics Report</Typography>
                        </Box>
                    }
                    action={
                        <Box sx={{ display: 'flex', gap: 1 }}>
                            <Button
                                variant="outlined"
                                startIcon={<RefreshIcon />}
                                onClick={fetchData}
                                disabled={loading}
                            >
                                Refresh
                            </Button>
                            <Button
                                variant="contained"
                                startIcon={<DownloadIcon />}
                                onClick={exportToCSV}
                                disabled={!users.length}
                            >
                                Export CSV
                            </Button>
                        </Box>
                    }
                />
                <CardContent>
                    {/* Filters */}
                    <Box sx={{ mb: 3 }}>
                        <Box sx={{ 
                            display: 'flex', 
                            flexWrap: 'wrap', 
                            gap: 2,
                            '& > *': { 
                                flex: '1 1 200px',
                                minWidth: '200px'
                            }
                        }}>
                            <TextField
                                label="Search Users"
                                placeholder="Username or Display Name"
                                value={filters.search}
                                onChange={(e) => handleFilterChange('search', e.target.value)}
                                InputProps={{
                                    startAdornment: <SearchIcon sx={{ mr: 1, color: 'text.secondary' }} />
                                }}
                            />
                            <TextField
                                type="date"
                                label="Date From"
                                InputLabelProps={{ shrink: true }}
                                value={filters.date_from}
                                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                            />
                            <TextField
                                type="date"
                                label="Date To"
                                InputLabelProps={{ shrink: true }}
                                value={filters.date_to}
                                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                            />
                            <TextField
                                type="number"
                                label="Min Quizzes"
                                value={filters.min_quizzes}
                                onChange={(e) => handleFilterChange('min_quizzes', parseInt(e.target.value) || 0)}
                            />
                            <FormControl>
                                <InputLabel>Sort By</InputLabel>
                                <Select
                                    value={filters.sort_by}
                                    onChange={(e) => handleFilterChange('sort_by', e.target.value)}
                                    label="Sort By"
                                >
                                    <MenuItem value="total_quizzes_attempted">Quiz Count</MenuItem>
                                    <MenuItem value="accuracy_percentage">Accuracy</MenuItem>
                                    <MenuItem value="total_score">Total Score</MenuItem>
                                    <MenuItem value="average_score">Average Score</MenuItem>
                                    <MenuItem value="last_activity">Last Activity</MenuItem>
                                </Select>
                            </FormControl>
                            <FormControlLabel
                                control={
                                    <Switch
                                        checked={filters.active_only}
                                        onChange={(e) => handleFilterChange('active_only', e.target.checked)}
                                    />
                                }
                                label="Active Only"
                                sx={{ 
                                    flex: '0 0 auto',
                                    minWidth: 'auto',
                                    alignSelf: 'center'
                                }}
                            />
                        </Box>
                    </Box>

                    {/* Summary Statistics */}
                    {pagination && users.length > 0 && (
                        <Box sx={{ mb: 3 }}>
                            <Typography variant="h6" gutterBottom>Summary</Typography>
                            <Box sx={{ 
                                display: 'flex', 
                                flexWrap: 'wrap', 
                                gap: 2,
                                '& > *': { 
                                    flex: '1 1 200px',
                                    minWidth: '200px'
                                }
                            }}>
                                <Paper sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="primary">
                                        {pagination.total_users}
                                    </Typography>
                                    <Typography variant="body2">Total Users</Typography>
                                </Paper>
                                <Paper sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="secondary">
                                        {Math.round(users.reduce((sum, user) => sum + user.accuracy_percentage, 0) / users.length)}%
                                    </Typography>
                                    <Typography variant="body2">Avg Accuracy</Typography>
                                </Paper>
                                <Paper sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="success.main">
                                        {Math.round(users.reduce((sum, user) => sum + user.completion_rate, 0) / users.length)}%
                                    </Typography>
                                    <Typography variant="body2">Avg Completion</Typography>
                                </Paper>
                                <Paper sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="warning.main">
                                        {Math.round(users.reduce((sum, user) => sum + user.total_quizzes_attempted, 0) / users.length)}
                                    </Typography>
                                    <Typography variant="body2">Avg Quizzes</Typography>
                                </Paper>
                            </Box>
                        </Box>
                    )}

                    {/* Data Table */}
                    {loading ? (
                        <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}>
                            <CircularProgress />
                        </Box>
                    ) : (
                        <>
                            <TableContainer component={Paper} sx={{ maxHeight: 600 }}>
                                <Table stickyHeader>
                                    <TableHead>
                                        <TableRow>
                                            <TableCell>User</TableCell>
                                            <TableCell align="center">Quizzes</TableCell>
                                            <TableCell align="center">Completion</TableCell>
                                            <TableCell align="center">Accuracy</TableCell>
                                            <TableCell align="center">Score</TableCell>
                                            <TableCell align="center">Questions</TableCell>
                                            <TableCell align="center">Time Stats</TableCell>
                                            <TableCell align="center">Activity</TableCell>
                                            <TableCell align="center">Levels</TableCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {users.map((user) => (
                                            <TableRow key={user.user_id} hover>
                                                <TableCell>
                                                    <Box>
                                                        <Typography variant="subtitle2">
                                                            {user.display_name}
                                                        </Typography>
                                                        <Typography variant="body2" color="text.secondary">
                                                            {user.username}
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box>
                                                        <Typography variant="body2">
                                                            {user.total_quizzes_completed}/{user.total_quizzes_attempted}
                                                        </Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            attempted/completed
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Chip
                                                        label={`${user.completion_rate}%`}
                                                        color={getCompletionRateColor(user.completion_rate)}
                                                        size="small"
                                                    />
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Chip
                                                        label={`${user.accuracy_percentage}%`}
                                                        color={getAccuracyColor(user.accuracy_percentage)}
                                                        size="small"
                                                    />
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box>
                                                        <Typography variant="body2">
                                                            {user.total_score}
                                                        </Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            Avg: {user.average_score}
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box>
                                                        <Typography variant="body2" color="success.main">
                                                            ✓ {user.total_correct_answers}
                                                        </Typography>
                                                        <Typography variant="body2" color="error.main">
                                                            ✗ {user.total_incorrect_answers}
                                                        </Typography>
                                                        <Typography variant="body2" color="warning.main">
                                                            ⊝ {user.total_skipped_questions}
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box>
                                                        <Typography variant="body2">
                                                            {formatTime(Math.round(user.average_time_per_quiz))}
                                                        </Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            per quiz
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box>
                                                        <Typography variant="body2">
                                                            {user.active_days_count} days
                                                        </Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            {user.days_since_last_quiz}d ago
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell align="center">
                                                    <Box>
                                                        {user.quiz_level_distribution.elementary > 0 && (
                                                            <Chip label={`E: ${user.quiz_level_distribution.elementary}`} size="small" sx={{ mr: 0.5, mb: 0.5 }} />
                                                        )}
                                                        {user.quiz_level_distribution.intermediate > 0 && (
                                                            <Chip label={`I: ${user.quiz_level_distribution.intermediate}`} size="small" sx={{ mr: 0.5, mb: 0.5 }} />
                                                        )}
                                                        {user.quiz_level_distribution.advanced > 0 && (
                                                            <Chip label={`A: ${user.quiz_level_distribution.advanced}`} size="small" sx={{ mr: 0.5, mb: 0.5 }} />
                                                        )}
                                                    </Box>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </TableContainer>

                            {/* Pagination */}
                            {pagination && pagination.total_pages > 1 && (
                                <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', mt: 3, gap: 2 }}>
                                    <Button
                                        disabled={!pagination.has_prev_page}
                                        onClick={() => handlePageChange(filters.page - 1)}
                                    >
                                        Previous
                                    </Button>
                                    <Typography>
                                        Page {pagination.current_page} of {pagination.total_pages}
                                    </Typography>
                                    <Button
                                        disabled={!pagination.has_next_page}
                                        onClick={() => handlePageChange(filters.page + 1)}
                                    >
                                        Next
                                    </Button>
                                </Box>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>
        </Box>
    );
};

export default UserQuizStatistics;
