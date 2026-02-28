import React, { useState, useEffect } from 'react';
import {
  Card,
  CardContent,
  Typography,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Button,
  Checkbox,
  TextField,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  Box,
  CircularProgress,
  Alert,
  Snackbar,
  Collapse
} from '@mui/material';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import AddIcon from '@mui/icons-material/Add';
import RefreshIcon from '@mui/icons-material/Refresh';
import ImageIcon from '@mui/icons-material/Image';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';
import { Title, useDataProvider, useNotify } from 'react-admin';
import * as apiClient from '../../common/apiClient';

interface WiziQuiz {
  id: number;
  name: string;
  status: string;
  total_marks: number;
}

interface WiziQuizQuestion {
  id: number;
  wizi_quiz_id: number;
  wizi_question_id: number;
  question_order: number;
  marks: number;
  negative_marks: number;
  question_img_url?: string;
  question_text?: string;
}

interface AvailableQuestion {
  id: number;
  question_img_url: string;
  question_text: string;
  exam_id: number;
  subject_id: number;
  subject_name: string;
  chapter_id: number;
  chapter_name: string;
  topic_id: number;
  topic_name: string;
  level: string;
  question_type: string;
  year: number;
  duration: number;
  option_count: number;
  correct_option: string;
}

interface Filters {
  exam_id: string;
  subject_id: string;
  chapter_id: string;
  topic_id: string;
  level: string;
  question_type: string;
  year: string;
  id_start: string;
  id_end: string;
  exclude_invalid: boolean;
}

export const WiziQuizQuestionManage = () => {
  const dataProvider = useDataProvider();
  const notify = useNotify();

  // State
  const [quizzes, setQuizzes] = useState<WiziQuiz[]>([]);
  const [selectedQuizId, setSelectedQuizId] = useState<number | null>(null);
  const [existingQuestions, setExistingQuestions] = useState<WiziQuizQuestion[]>([]);
  const [availableQuestions, setAvailableQuestions] = useState<AvailableQuestion[]>([]);
  const [selectedQuestionIds, setSelectedQuestionIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(false);
  const [loadingAvailable, setLoadingAvailable] = useState(false);
  
  // Bulk add settings
  const [bulkMarks, setBulkMarks] = useState<number>(4);
  const [bulkNegativeMarks, setBulkNegativeMarks] = useState<number>(-1.0);
  
  // Filters
  const [filters, setFilters] = useState<Filters>({
    exam_id: '',
    subject_id: '',
    chapter_id: '',
    topic_id: '',
    level: '',
    question_type: '',
    year: '',
    id_start: '',
    id_end: '',
    exclude_invalid: true
  });

  // Reference data
  const [exams, setExams] = useState<any[]>([]);
  const [subjects, setSubjects] = useState<any[]>([]);
  const [chapters, setChapters] = useState<any[]>([]);
  const [topics, setTopics] = useState<any[]>([]);

  // Dialogs
  const [previewDialog, setPreviewDialog] = useState<{ open: boolean; question: AvailableQuestion | WiziQuizQuestion | null }>({ open: false, question: null });
  const [editDialog, setEditDialog] = useState<{ open: boolean; question: WiziQuizQuestion | null }>({ open: false, question: null });
  const [editMarks, setEditMarks] = useState<number>(4);
  const [editNegativeMarks, setEditNegativeMarks] = useState<number>(-1.0);
  const [editOrder, setEditOrder] = useState<number>(1);

  // Snackbar
  const [snackbar, setSnackbar] = useState<{ open: boolean; message: string; severity: 'success' | 'error' | 'info' }>({ 
    open: false, 
    message: '', 
    severity: 'info' 
  });
  
  // Collapse state for filters
  const [filtersExpanded, setFiltersExpanded] = useState(true);

  // Load quizzes on mount
  useEffect(() => {
    loadQuizzes();
    loadReferenceData();
  }, []);

  // Load existing questions when quiz is selected
  useEffect(() => {
    if (selectedQuizId) {
      loadExistingQuestions();
      loadAvailableQuestions();
    }
  }, [selectedQuizId]);

  // Reload available questions when filters change
  useEffect(() => {
    if (selectedQuizId) {
      loadAvailableQuestions();
    }
  }, [filters]);

  const loadQuizzes = async () => {
    try {
      const response = await dataProvider.getList('wizi-quiz', {
        pagination: { page: 1, perPage: 1000 },
        sort: { field: 'id', order: 'DESC' },
        filter: {}
      });
      setQuizzes(response.data);
    } catch (error) {
      notify('Failed to load quizzes', { type: 'error' });
    }
  };

  const loadReferenceData = async () => {
    try {
      const [examsRes, subjectsRes, chaptersRes, topicsRes] = await Promise.all([
        dataProvider.getList('exam', { pagination: { page: 1, perPage: 1000 }, sort: { field: 'id', order: 'ASC' }, filter: {} }),
        dataProvider.getList('subject', { pagination: { page: 1, perPage: 1000 }, sort: { field: 'id', order: 'ASC' }, filter: {} }),
        dataProvider.getList('chapter', { pagination: { page: 1, perPage: 1000 }, sort: { field: 'id', order: 'ASC' }, filter: {} }),
        dataProvider.getList('topic', { pagination: { page: 1, perPage: 1000 }, sort: { field: 'id', order: 'ASC' }, filter: {} })
      ]);
      setExams(examsRes.data);
      setSubjects(subjectsRes.data);
      setChapters(chaptersRes.data);
      setTopics(topicsRes.data);
    } catch (error) {
      console.error('Failed to load reference data', error);
    }
  };

  const loadExistingQuestions = async () => {
    if (!selectedQuizId) return;
    
    setLoading(true);
    try {
      const response = await apiClient.getWiziQuizQuestions(selectedQuizId);
      if (response.success) {
        setExistingQuestions(response.data);
      }
    } catch (error) {
      notify('Failed to load existing questions', { type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const loadAvailableQuestions = async () => {
    if (!selectedQuizId) return;
    
    setLoadingAvailable(true);
    try {
      const response = await apiClient.getAvailableWiziQuestions(selectedQuizId, filters, 100);
      if (response.success) {
        setAvailableQuestions(response.data);
      }
    } catch (error) {
      notify('Failed to load available questions', { type: 'error' });
    } finally {
      setLoadingAvailable(false);
    }
  };

  const handleBulkAdd = async () => {
    if (selectedQuestionIds.length === 0) {
      setSnackbar({ open: true, message: 'Please select questions to add', severity: 'error' });
      return;
    }

    setLoading(true);
    try {
      const response = await apiClient.bulkAddWiziQuizQuestions({
        wizi_quiz_id: selectedQuizId!,
        question_ids: selectedQuestionIds,
        marks: bulkMarks,
        negative_marks: bulkNegativeMarks
      });
      
      if (response.success) {
        setSnackbar({ 
          open: true, 
          message: response.message || `Added ${response.data.added_count} questions successfully`, 
          severity: 'success' 
        });
        setSelectedQuestionIds([]);
        loadExistingQuestions();
        loadAvailableQuestions();
      } else {
        setSnackbar({ open: true, message: response.message || 'Failed to add questions', severity: 'error' });
      }
    } catch (error) {
      setSnackbar({ open: true, message: 'Error adding questions', severity: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteQuestion = async (questionId: number) => {
    if (!window.confirm('Are you sure you want to remove this question from the quiz?')) {
      return;
    }

    try {
      const response = await apiClient.deleteWiziQuizQuestion(questionId);
      
      if (response.success) {
        setSnackbar({ open: true, message: 'Question removed successfully', severity: 'success' });
        loadExistingQuestions();
        loadAvailableQuestions();
      } else {
        setSnackbar({ open: true, message: response.message || 'Failed to remove question', severity: 'error' });
      }
    } catch (error) {
      setSnackbar({ open: true, message: 'Error removing question', severity: 'error' });
    }
  };

  const handleUpdateMarks = async () => {
    if (!editDialog.question) return;

    try {
      const response = await apiClient.updateWiziQuizQuestionMarks(editDialog.question.id, {
        question_order: editOrder,
        marks: editMarks,
        negative_marks: editNegativeMarks
      });
      
      if (response.success) {
        setSnackbar({ open: true, message: 'Marks updated successfully', severity: 'success' });
        setEditDialog({ open: false, question: null });
        loadExistingQuestions();
      } else {
        setSnackbar({ open: true, message: response.message || 'Failed to update marks', severity: 'error' });
      }
    } catch (error) {
      setSnackbar({ open: true, message: 'Error updating marks', severity: 'error' });
    }
  };

  const handleToggleQuestion = (questionId: number) => {
    setSelectedQuestionIds(prev => 
      prev.includes(questionId) 
        ? prev.filter(id => id !== questionId)
        : [...prev, questionId]
    );
  };

  const handleSelectAll = () => {
    if (selectedQuestionIds.length === availableQuestions.length) {
      setSelectedQuestionIds([]);
    } else {
      setSelectedQuestionIds(availableQuestions.map(q => q.id));
    }
  };

  const selectedQuiz = quizzes.find(q => q.id === selectedQuizId);

  return (
    <div style={{ padding: '20px' }}>
      <Title title="WiZi Quiz Questions Management" />
      
      {/* Quiz Selector */}
      <Card style={{ marginBottom: '20px' }}>
        <CardContent>
          <FormControl fullWidth>
            <InputLabel>Select WiZi Quiz</InputLabel>
            <Select
              value={selectedQuizId || ''}
              onChange={(e) => setSelectedQuizId(e.target.value as number)}
              label="Select WiZi Quiz"
            >
              <MenuItem value="">-- Select Quiz --</MenuItem>
              {quizzes.map(quiz => (
                <MenuItem key={quiz.id} value={quiz.id}>
                  {quiz.name} ({existingQuestions.filter(q => q.wizi_quiz_id === quiz.id).length} questions) - {quiz.status}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
          
          {selectedQuiz && (
            <Box mt={2}>
              <Typography variant="body2" color="textSecondary">
                Status: <Chip label={selectedQuiz.status} size="small" /> | 
                Total Marks: {selectedQuiz.total_marks} | 
                Questions: {existingQuestions.length}
              </Typography>
            </Box>
          )}
        </CardContent>
      </Card>

      {selectedQuizId && (
        <div style={{ display: 'flex', gap: '24px', flexWrap: 'wrap', width: '100%' }}>
          {/* LEFT SIDE - Existing Questions */}
          <div style={{ flex: '1 1 400px', minWidth: '350px', maxWidth: '500px' }}>
            <Card>
              <CardContent>
                <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
                  <Typography variant="h6">Current Questions in Quiz</Typography>
                  <Button
                    size="small"
                    startIcon={<RefreshIcon />}
                    onClick={loadExistingQuestions}
                  >
                    Refresh
                  </Button>
                </Box>

                {loading ? (
                  <Box display="flex" justifyContent="center" p={3}>
                    <CircularProgress />
                  </Box>
                ) : existingQuestions.length === 0 ? (
                  <Alert severity="info">No questions added to this quiz yet.</Alert>
                ) : (
                  <TableContainer component={Paper} style={{ maxHeight: '600px' }}>
                    <Table stickyHeader size="small">
                      <TableHead>
                        <TableRow>
                          <TableCell>Order</TableCell>
                          <TableCell>Q ID</TableCell>
                          <TableCell>Preview</TableCell>
                          <TableCell>Marks</TableCell>
                          <TableCell>Neg.</TableCell>
                          <TableCell>Actions</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {existingQuestions.map((question) => (
                          <TableRow key={question.id}>
                            <TableCell>{question.question_order}</TableCell>
                            <TableCell>{question.wizi_question_id}</TableCell>
                            <TableCell>
                              {question.question_img_url ? (
                                <IconButton 
                                  size="small" 
                                  onClick={() => setPreviewDialog({ open: true, question })}
                                >
                                  <ImageIcon />
                                </IconButton>
                              ) : (
                                <Typography variant="caption" color="textSecondary">
                                  {question.question_text?.substring(0, 30)}...
                                </Typography>
                              )}
                            </TableCell>
                            <TableCell>{question.marks}</TableCell>
                            <TableCell>{question.negative_marks}</TableCell>
                            <TableCell>
                              <IconButton
                                size="small"
                                onClick={() => {
                                  setEditDialog({ open: true, question });
                                  setEditMarks(question.marks);
                                  setEditNegativeMarks(question.negative_marks);
                                  setEditOrder(question.question_order);
                                }}
                              >
                                <EditIcon fontSize="small" />
                              </IconButton>
                              <IconButton
                                size="small"
                                color="error"
                                onClick={() => handleDeleteQuestion(question.id)}
                              >
                                <DeleteIcon fontSize="small" />
                              </IconButton>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </TableContainer>
                )}
              </CardContent>
            </Card>
          </div>

          {/* RIGHT SIDE - Add New Questions */}
          <div style={{ flex: '2 1 600px', minWidth: '500px' }}>
            <Card>
              <CardContent>
                <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
                  <Typography variant="h6">Add New Questions</Typography>
                  <Button
                    size="small"
                    onClick={() => setFiltersExpanded(!filtersExpanded)}
                    endIcon={filtersExpanded ? <ExpandLessIcon /> : <ExpandMoreIcon />}
                  >
                    {filtersExpanded ? 'Hide Filters' : 'Show Filters'}
                  </Button>
                </Box>

                {/* Filters */}
                <Collapse in={filtersExpanded}>
                  <Box mb={3}>
                    <Typography variant="subtitle2" gutterBottom>Filters</Typography>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                    <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                      <FormControl style={{ flex: '1 1 calc(50% - 8px)', minWidth: '200px' }}>
                        <InputLabel>Exam</InputLabel>
                        <Select
                          value={filters.exam_id}
                          onChange={(e) => setFilters({ ...filters, exam_id: e.target.value })}
                          label="Exam"
                        >
                          <MenuItem value="">All</MenuItem>
                          {exams.map(exam => (
                            <MenuItem key={exam.id} value={exam.id}>{exam.exam_name}</MenuItem>
                          ))}
                        </Select>
                      </FormControl>
                      <FormControl style={{ flex: '1 1 calc(50% - 8px)', minWidth: '200px' }}>
                        <InputLabel>Subject</InputLabel>
                        <Select
                          value={filters.subject_id}
                          onChange={(e) => setFilters({ ...filters, subject_id: e.target.value })}
                          label="Subject"
                        >
                          <MenuItem value="">All</MenuItem>
                          {subjects.map(subject => (
                            <MenuItem key={subject.id} value={subject.id}>{subject.subject_name}</MenuItem>
                          ))}
                        </Select>
                      </FormControl>
                    </div>
                    <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                      <FormControl style={{ flex: '1 1 calc(50% - 8px)', minWidth: '200px' }}>
                        <InputLabel>Chapter</InputLabel>
                        <Select
                          value={filters.chapter_id}
                          onChange={(e) => setFilters({ ...filters, chapter_id: e.target.value })}
                          label="Chapter"
                        >
                          <MenuItem value="">All</MenuItem>
                          {chapters.map(chapter => (
                            <MenuItem key={chapter.id} value={chapter.id}>{chapter.chapter_name}</MenuItem>
                          ))}
                        </Select>
                      </FormControl>
                      <FormControl style={{ flex: '1 1 calc(50% - 8px)', minWidth: '200px' }}>
                        <InputLabel>Topic</InputLabel>
                        <Select
                          value={filters.topic_id}
                          onChange={(e) => setFilters({ ...filters, topic_id: e.target.value })}
                          label="Topic"
                        >
                          <MenuItem value="">All</MenuItem>
                          {topics.map(topic => (
                            <MenuItem key={topic.id} value={topic.id}>{topic.topic_name}</MenuItem>
                          ))}
                        </Select>
                      </FormControl>
                    </div>
                    <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                      <FormControl style={{ flex: '1 1 calc(33.333% - 11px)', minWidth: '150px' }}>
                        <InputLabel>Level</InputLabel>
                        <Select
                          value={filters.level}
                          onChange={(e) => setFilters({ ...filters, level: e.target.value })}
                          label="Level"
                        >
                          <MenuItem value="">All</MenuItem>
                          <MenuItem value="Elementary">Elementary</MenuItem>
                          <MenuItem value="Moderate">Moderate</MenuItem>
                          <MenuItem value="Advance">Advance</MenuItem>
                        </Select>
                      </FormControl>
                      <FormControl style={{ flex: '1 1 calc(33.333% - 11px)', minWidth: '150px' }}>
                        <InputLabel>Type</InputLabel>
                        <Select
                          value={filters.question_type}
                          onChange={(e) => setFilters({ ...filters, question_type: e.target.value })}
                          label="Type"
                        >
                          <MenuItem value="">All</MenuItem>
                          <MenuItem value="regular">Regular</MenuItem>
                          <MenuItem value="pyq">PYQ</MenuItem>
                          <MenuItem value="mock">Mock</MenuItem>
                        </Select>
                      </FormControl>
                      <TextField
                        style={{ flex: '1 1 calc(33.333% - 11px)', minWidth: '150px' }}
                        label="Year"
                        value={filters.year}
                        onChange={(e) => setFilters({ ...filters, year: e.target.value })}
                      />
                    </div>
                    <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                      <TextField
                        style={{ flex: '1 1 calc(50% - 8px)', minWidth: '200px' }}
                        label="ID Start"
                        type="number"
                        value={filters.id_start}
                        onChange={(e) => setFilters({ ...filters, id_start: e.target.value })}
                      />
                      <TextField
                        style={{ flex: '1 1 calc(50% - 8px)', minWidth: '200px' }}
                        label="ID End"
                        type="number"
                        value={filters.id_end}
                        onChange={(e) => setFilters({ ...filters, id_end: e.target.value })}
                      />
                    </div>
                    </div>
                  </Box>
                </Collapse>

                {/* Bulk Add Settings */}
                <Box mb={2} p={2} bgcolor="#f5f5f5" borderRadius={1}>
                  <Typography variant="subtitle2" gutterBottom>Bulk Add Settings</Typography>
                  <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                    <TextField
                      style={{ flex: '1 1 calc(50% - 8px)', minWidth: '150px' }}
                      size="small"
                      label="Marks"
                      type="number"
                      value={bulkMarks}
                      onChange={(e) => setBulkMarks(Number(e.target.value))}
                    />
                    <TextField
                      style={{ flex: '1 1 calc(50% - 8px)', minWidth: '150px' }}
                      size="small"
                      label="Negative Marks"
                      type="number"
                      inputProps={{ step: 0.1 }}
                      value={bulkNegativeMarks}
                      onChange={(e) => setBulkNegativeMarks(Number(e.target.value))}
                    />
                  </div>
                </Box>

                {/* Available Questions List */}
                <Box>
                  <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                    <Typography variant="subtitle2">
                      Available Questions ({availableQuestions.length})
                      {selectedQuestionIds.length > 0 && ` - ${selectedQuestionIds.length} selected`}
                    </Typography>
                    <Box>
                      <Button size="small" onClick={handleSelectAll}>
                        {selectedQuestionIds.length === availableQuestions.length ? 'Deselect All' : 'Select All'}
                      </Button>
                      <Button
                        size="small"
                        variant="contained"
                        color="primary"
                        startIcon={<AddIcon />}
                        onClick={handleBulkAdd}
                        disabled={selectedQuestionIds.length === 0 || loading}
                        style={{ marginLeft: '8px' }}
                      >
                        Add Selected
                      </Button>
                    </Box>
                  </Box>

                  {loadingAvailable ? (
                    <Box display="flex" justifyContent="center" p={3}>
                      <CircularProgress />
                    </Box>
                  ) : availableQuestions.length === 0 ? (
                    <Alert severity="info">No more questions available with current filters.</Alert>
                  ) : (
                    <TableContainer component={Paper} style={{ maxHeight: '400px' }}>
                      <Table stickyHeader size="small">
                        <TableHead>
                          <TableRow>
                            <TableCell padding="checkbox">
                              <Checkbox
                                checked={selectedQuestionIds.length === availableQuestions.length}
                                indeterminate={selectedQuestionIds.length > 0 && selectedQuestionIds.length < availableQuestions.length}
                                onChange={handleSelectAll}
                              />
                            </TableCell>
                            <TableCell>ID</TableCell>
                            <TableCell>Preview</TableCell>
                            <TableCell>Level</TableCell>
                            <TableCell>Type</TableCell>
                            <TableCell>Year</TableCell>
                          </TableRow>
                        </TableHead>
                        <TableBody>
                          {availableQuestions.map((question) => (
                            <TableRow key={question.id}>
                              <TableCell padding="checkbox">
                                <Checkbox
                                  checked={selectedQuestionIds.includes(question.id)}
                                  onChange={() => handleToggleQuestion(question.id)}
                                />
                              </TableCell>
                              <TableCell>{question.id}</TableCell>
                              <TableCell>
                                {question.question_img_url ? (
                                  <IconButton 
                                    size="small" 
                                    onClick={() => setPreviewDialog({ open: true, question })}
                                  >
                                    <ImageIcon />
                                  </IconButton>
                                ) : (
                                  <Typography variant="caption">
                                    {question.question_text?.substring(0, 20)}...
                                  </Typography>
                                )}
                              </TableCell>
                              <TableCell>
                                <Chip label={question.level} size="small" />
                              </TableCell>
                              <TableCell>
                                <Chip label={question.question_type} size="small" variant="outlined" />
                              </TableCell>
                              <TableCell>{question.year}</TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </TableContainer>
                  )}
                </Box>
              </CardContent>
            </Card>
          </div>
        </div>
      )}

      {/* Preview Dialog */}
      <Dialog
        open={previewDialog.open}
        onClose={() => setPreviewDialog({ open: false, question: null })}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>Question Preview</DialogTitle>
        <DialogContent>
          {previewDialog.question && (
            <Box>
              {(previewDialog.question as any).question_img_url && (
                <img
                  src={(previewDialog.question as any).question_img_url}
                  alt="Question"
                  style={{ width: '100%', maxHeight: '500px', objectFit: 'contain' }}
                />
              )}
              {(previewDialog.question as any).question_text && (
                <Typography variant="body1" mt={2}>
                  {(previewDialog.question as any).question_text}
                </Typography>
              )}
            </Box>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setPreviewDialog({ open: false, question: null })}>Close</Button>
        </DialogActions>
      </Dialog>

      {/* Edit Marks Dialog */}
      <Dialog
        open={editDialog.open}
        onClose={() => setEditDialog({ open: false, question: null })}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>Edit Question Details</DialogTitle>
        <DialogContent>
          <Box mt={2}>
            <TextField
              fullWidth
              label="Question Order"
              type="number"
              value={editOrder}
              onChange={(e) => setEditOrder(Number(e.target.value))}
              margin="normal"
              helperText="Display order in the quiz"
            />
            <TextField
              fullWidth
              label="Marks"
              type="number"
              value={editMarks}
              onChange={(e) => setEditMarks(Number(e.target.value))}
              margin="normal"
            />
            <TextField
              fullWidth
              label="Negative Marks"
              type="number"
              inputProps={{ step: 0.1 }}
              value={editNegativeMarks}
              onChange={(e) => setEditNegativeMarks(Number(e.target.value))}
              margin="normal"
            />
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setEditDialog({ open: false, question: null })}>Cancel</Button>
          <Button onClick={handleUpdateMarks} variant="contained" color="primary">
            Update
          </Button>
        </DialogActions>
      </Dialog>

      {/* Snackbar */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={6000}
        onClose={() => setSnackbar({ ...snackbar, open: false })}
      >
        <Alert severity={snackbar.severity} onClose={() => setSnackbar({ ...snackbar, open: false })}>
          {snackbar.message}
        </Alert>
      </Snackbar>
    </div>
  );
};
