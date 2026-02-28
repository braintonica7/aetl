import React, { useState } from 'react';
import {
  Card,
  CardContent,
  Typography,
  Box,
  Button,
  TextField,
  Alert,
  CircularProgress,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Paper,
  Stack,
  Divider,
  Chip,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Snackbar,
  RadioGroup,
  FormControlLabel,
  Radio
} from '@mui/material';
import {
  Psychology,
  Image,
  Send,
  CheckCircle,
  Info,
  ExpandMore,
  Lightbulb,
  PlayArrow as StepIcon,
  Warning,
  AutoAwesome,
  Search,
  Functions
} from '@mui/icons-material';
import * as http from '../../common/http';
import { APIUrl } from '../../common/apiConfig';
import LaTeXRenderer from '../../components/LaTexRenderer';
import MathJaxRenderer from '../../components/MathJaxRenderer';


interface SolutionFormData {
  image_url: string;
  correct_option: string;
  context: string;
  question_id?: string;
}

interface SolutionStep {
  stepNumber: number;
  title: string;
  explanation: string;
  formula?: string;
  calculation?: string | string[];
  result?: string;
}

interface SolutionResponse {
  status: string;
  result?: {
    questionText: string;
    questionType: string;
    subject: string;
    topic: string;
    difficulty: string;
    givenInformation: string[];
    conceptsUsed: string[];
    solutionSteps: SolutionStep[];
    finalAnswer: string;
    keyInsights: string[];
    commonMistakes: string[];
    alternativeApproaches: string[];
    confidence: number;
    estimatedTimeToSolve: string;
    studyTips: string;
    metadata: {
      solution_timestamp: string;
      model_used: string;
      image_url: string;
      correct_option_provided: boolean;
      has_additional_context: boolean;
    };
  };
  message?: string;
  error?: string;
}

const SolutionGeneratorScreen: React.FC = () => {
  const [formData, setFormData] = useState<SolutionFormData>({
    image_url: '',
    correct_option: '',
    context: '',
    question_id: ''
  });

  const [loading, setLoading] = useState(false);
  const [solution, setSolution] = useState<SolutionResponse['result'] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [snackbarOpen, setSnackbarOpen] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  
  // Renderer selection state
  const [useMathJax, setUseMathJax] = useState(true); // Default to MathJax for better LaTeX handling
  
  // Question loading states
  const [questionId, setQuestionId] = useState('');
  const [loadingQuestion, setLoadingQuestion] = useState(false);
  const [questionError, setQuestionError] = useState<string | null>(null);

  const handleInputChange = (field: keyof SolutionFormData) => (
    event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    setFormData(prev => ({
      ...prev,
      [field]: event.target.value
    }));
  };

  const validateForm = (): boolean => {
    if (!formData.image_url.trim()) {
      setError('Image URL is required');
      return false;
    }
    if (!formData.correct_option.trim()) {
      setError('Correct option is required');
      return false;
    }
    return true;
  };

  // Helper function to render LaTeX content with selected renderer
  const renderLatex = (content: string) => {
    if (useMathJax) {
      return <MathJaxRenderer>{content}</MathJaxRenderer>;
    } else {
      return <LaTeXRenderer>{content}</LaTeXRenderer>;
    }
  };

  const handleLoadQuestion = async () => {
    if (!questionId.trim()) {
      setQuestionError('Question ID is required');
      return;
    }

    setLoadingQuestion(true);
    setQuestionError(null);

    try {
      const response = await http.get(`${APIUrl}question/${questionId}`);
      
      if (response.status === 'success' && response.result) {
        const question = response.result;
        
        // Auto-fill form with question data
        setFormData(prev => ({
          ...prev,
          image_url: question.question_img_url || question.question_img_url || '',
          correct_option: question.correct_option || '',
          context: question.question_text || question.question_text || '',
          question_id: questionId
        }));
        
        // Check if the question already has a solution and display it
        if (question.solution) {
          try {
            const existingSolution = typeof question.solution === 'string' ? JSON.parse(question.solution) : question.solution;
            setSolution(existingSolution);
            setSnackbarMessage(`Question ${questionId} loaded with existing solution!`);
          } catch (parseError) {
            console.error('Error parsing existing solution:', parseError);
            setSnackbarMessage(`Question ${questionId} loaded successfully! (Solution parsing failed)`);
          }
        } else {
          setSolution(null);
          setSnackbarMessage(`Question ${questionId} loaded successfully!`);
        }
        
        setSnackbarOpen(true);
        setQuestionError(null);
      } else {
        setQuestionError(response.message || 'Failed to load question');
      }
    } catch (err) {
      console.error('Error loading question:', err);
      setQuestionError('Error loading question. Please check the question ID.');
    } finally {
      setLoadingQuestion(false);
    }
  };

  const handleGenerateSolution = async () => {
    if (!validateForm()) return;

    setLoading(true);
    setError(null);
    setSolution(null);

    try {
      const response = await http.post(`${APIUrl}image_analysis/generate_solution`, formData);
      
      if (response.status === 'success' && response.result) {
        setSolution(response.result);
        setSnackbarMessage('Solution generated successfully!');
        setSnackbarOpen(true);
      } else {
        setError(response.error || response.message || 'Failed to generate solution');
      }
    } catch (err: any) {
      console.error('Error generating solution:', err);
      setError(err.message || 'An error occurred while generating the solution');
    } finally {
      setLoading(false);
    }
  };

  const handleClearForm = () => {
    setFormData({
      image_url: '',
      correct_option: '',
      context: ''
    });
    setSolution(null);
    setError(null);
  };

  const getDifficultyColor = (difficulty: string) => {
    switch (difficulty?.toLowerCase()) {
      case 'easy': return 'success';
      case 'medium': return 'primary';
      case 'hard': return 'error';
      default: return 'default';
    }
  };

  const content = (
    <Box sx={{ p: 3, maxWidth: 1200, mx: 'auto' }}>
      <Typography variant="h4" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
        <Psychology color="primary" />
        AI Solution Generator
      </Typography>
      <Typography variant="subtitle1" color="textSecondary" gutterBottom>
        Generate step-by-step solutions for quiz questions using AI image analysis
      </Typography>

      {/* Renderer Selection Toggle */}
      <Card sx={{ mb: 3, backgroundColor: '#f8f9fa' }}>
        <CardContent>
          <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Functions color="primary" />
            LaTeX Renderer Settings
          </Typography>
          <FormControl component="fieldset">
            <RadioGroup
              row
              value={useMathJax ? 'mathjax' : 'katex'}
              onChange={(e) => setUseMathJax(e.target.value === 'mathjax')}
            >
              <FormControlLabel 
                value="katex" 
                control={<Radio />} 
                label={
                  <Box>
                    <Typography variant="body2" fontWeight="bold">KaTeX (Fast)</Typography>
                    <Typography variant="caption" color="textSecondary">
                      Faster rendering, requires preprocessing
                    </Typography>
                  </Box>
                }
              />
              <FormControlLabel 
                value="mathjax" 
                control={<Radio />} 
                label={
                  <Box>
                    <Typography variant="body2" fontWeight="bold">MathJax (Robust)</Typography>
                    <Typography variant="caption" color="textSecondary">
                      Better LaTeX handling, handles double backslashes
                    </Typography>
                  </Box>
                }
              />
            </RadioGroup>
          </FormControl>
        </CardContent>
      </Card>

      {/* Load Question by ID */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Search color="primary" />
            Load Question by ID
          </Typography>
          <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
            Enter a question ID to automatically load question details and fill the form below
          </Typography>
          
          <Stack direction="row" spacing={2} alignItems="start">
            <TextField
              label="Question ID"
              value={questionId}
              onChange={(e) => setQuestionId(e.target.value)}
              placeholder="Enter question ID (e.g., 123)"
              sx={{ minWidth: 200 }}
              error={!!questionError}
              helperText={questionError}
            />
            <Button
              variant="contained"
              onClick={handleLoadQuestion}
              disabled={loadingQuestion || !questionId.trim()}
              startIcon={loadingQuestion ? <CircularProgress size={20} /> : <Search />}
              sx={{ minWidth: 140 }}
            >
              {loadingQuestion ? 'Loading...' : 'Load Question'}
            </Button>
          </Stack>
          
          {questionError && (
            <Alert severity="error" sx={{ mt: 2 }}>
              {questionError}
            </Alert>
          )}
        </CardContent>
      </Card>

      {/* Input Form */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Image color="primary" />
            Question Details
          </Typography>
          
          <Stack spacing={3}>
            <TextField
              label="Image URL"
              value={formData.image_url}
              onChange={handleInputChange('image_url')}
              fullWidth
              required
              placeholder="https://example.com/question-image.jpg"
              helperText="Enter the URL of the question image"
            />

            <TextField
              label="Correct Option"
              value={formData.correct_option}
              onChange={handleInputChange('correct_option')}
              fullWidth
              required
              placeholder="A, B, C, D or the correct answer text"
              helperText="Specify the correct answer option or text"
            />

            <TextField
              label="Additional Context"
              value={formData.context}
              onChange={handleInputChange('context')}
              fullWidth
              multiline
              rows={3}
              placeholder="Any additional context about the question (subject, topic, difficulty level, etc.)"
              helperText="Optional: Provide additional context to help generate better solutions"
            />

            {error && (
              <Alert severity="error" onClose={() => setError(null)}>
                {error}
              </Alert>
            )}

            {/* Image Preview Section */}
            {formData.image_url && (
              <Paper sx={{ p: 2, bgcolor: 'grey.50' }}>
                <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <Image color="primary" />
                  Image Preview
                </Typography>
                <Box 
                  sx={{ 
                    display: 'flex', 
                    justifyContent: 'center',
                    border: '1px solid',
                    borderColor: 'grey.300',
                    borderRadius: 1,
                    p: 2,
                    bgcolor: 'white',
                    minHeight: 200
                  }}
                >
                  <img
                    src={formData.image_url}
                    alt="Question Image"
                    style={{
                      maxWidth: '100%',
                      maxHeight: '400px',
                      objectFit: 'contain',
                      borderRadius: '4px'
                    }}
                    onError={(e) => {
                      const target = e.target as HTMLImageElement;
                      target.style.display = 'none';
                      const errorDiv = document.createElement('div');
                      errorDiv.innerHTML = `
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; color: #666;">
                          <div style="font-size: 48px; margin-bottom: 8px;">⚠️</div>
                          <div>Unable to load image</div>
                          <div style="font-size: 12px; margin-top: 4px;">Please check the URL</div>
                        </div>
                      `;
                      target.parentNode?.appendChild(errorDiv);
                    }}
                  />
                </Box>
              </Paper>
            )}

            {formData.question_id && (
              <Box sx={{ mb: 2 }}>
                <Chip 
                  label={`Linked to Question ID: ${formData.question_id}`}
                  color="success"
                  size="small"
                  variant="outlined"
                  icon={<CheckCircle />}
                />
                <Typography variant="caption" color="text.secondary" sx={{ ml: 1 }}>
                  Solution will be saved to database
                </Typography>
              </Box>
            )}

            <Box sx={{ display: 'flex', gap: 2 }}>
              <Button
                variant="contained"
                onClick={handleGenerateSolution}
                disabled={loading}
                startIcon={loading ? <CircularProgress size={20} /> : <AutoAwesome />}
                sx={{ minWidth: 180 }}
              >
                {loading ? 'Generating...' : 'Generate Solution'}
              </Button>
              
              <Button
                variant="outlined"
                onClick={handleClearForm}
                disabled={loading}
              >
                Clear Form
              </Button>
            </Box>
          </Stack>
        </CardContent>
      </Card>

      {/* Solution Display */}
      {solution && (
        <Card>
          <CardContent>
            <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <Lightbulb color="primary" />
              Generated Solution
            </Typography>

            {/* Solution Header */}
            <Box sx={{ mb: 3 }}>
              <Stack direction="row" spacing={2} sx={{ mb: 2 }}>
                <Chip 
                  label={`Difficulty: ${solution.difficulty}`}
                  color={getDifficultyColor(solution.difficulty) as any}
                  variant="outlined"
                />
                <Chip 
                  label={`Est. Time: ${solution.estimatedTimeToSolve}`}
                  color="info"
                  variant="outlined"
                  icon={<Info />}
                />
              </Stack>

              {solution.conceptsUsed && solution.conceptsUsed.length > 0 && (
                <Box sx={{ mb: 2 }}>
                  <Typography variant="subtitle2" gutterBottom>Key Concepts:</Typography>
                  <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                    {solution.conceptsUsed.map((concept, index) => (
                      <Paper 
                        key={index}
                        sx={{ 
                          px: 1, 
                          py: 0.5, 
                          bgcolor: 'primary.light', 
                          color: 'primary.contrastText',
                          border: '1px solid',
                          borderColor: 'primary.main',
                          borderRadius: 2,
                          fontSize: '0.75rem'
                        }}
                      >
                        {renderLatex(concept)}
                      </Paper>
                    ))}
                  </Stack>
                </Box>
              )}
            </Box>

            {/* Question Understanding */}
            <Accordion defaultExpanded>
              <AccordionSummary expandIcon={<ExpandMore />}>
                <Typography variant="h6">Question Understanding</Typography>
              </AccordionSummary>
              <AccordionDetails>
                <Typography variant="body1" sx={{ lineHeight: 1.7, mb: 2 }}>
                  <strong>Question:</strong> {renderLatex(solution.questionText)}
                </Typography>
                <Typography variant="body2" sx={{ mb: 1 }}>
                  <strong>Subject:</strong> {solution.subject} | <strong>Topic:</strong> {solution.topic}
                </Typography>
                {solution.givenInformation && solution.givenInformation.length > 0 && (
                  <Box sx={{ mt: 2 }}>
                    <Typography variant="subtitle2" gutterBottom>Given Information:</Typography>
                    <List dense>
                      {solution.givenInformation.map((info, index) => (
                        <ListItem key={index}>
                          <ListItemIcon>
                            <Info color="primary" />
                          </ListItemIcon>
                          <ListItemText primary={renderLatex(info)} />
                        </ListItem>
                      ))}
                    </List>
                  </Box>
                )}
              </AccordionDetails>
            </Accordion>

            {/* Step-by-Step Solution */}
            <Accordion defaultExpanded>
              <AccordionSummary expandIcon={<ExpandMore />}>
                <Typography variant="h6">Step-by-Step Solution</Typography>
              </AccordionSummary>
              <AccordionDetails>
                <List>
                  {solution.solutionSteps?.map((step, index) => (
                    <ListItem key={index} alignItems="flex-start" sx={{ mb: 2 }}>
                      <ListItemIcon sx={{ mt: 1 }}>
                        <Chip 
                          label={step.stepNumber} 
                          color="primary" 
                          size="small" 
                          icon={<StepIcon />}
                        />
                      </ListItemIcon>
                      <ListItemText>
                        <Typography variant="subtitle1" fontWeight="bold" gutterBottom>
                          {step.title}
                        </Typography>
                        <Typography variant="body1" sx={{ mb: 1, lineHeight: 1.7 }}>
                          {renderLatex(step.explanation)}
                        </Typography>
                        {step.formula && (
                          <Box sx={{ mb: 1 }}>
                            <Typography variant="body2" color="secondary" fontWeight="bold">
                              Formula: {renderLatex(step.formula)}
                            </Typography>
                          </Box>
                        )}
                        {step.calculation && (
                          <Box sx={{ mb: 1 }}>
                            <Typography variant="body2" color="textSecondary" sx={{ mb: 1 }}>
                              <strong>Calculation:</strong>
                            </Typography>
                            {Array.isArray(step.calculation) ? (
                              <List dense sx={{ pl: 2 }}>
                                {step.calculation.map((calc, index) => (
                                  <ListItem key={index} sx={{ py: 0.5 }}>
                                    <Typography variant="body2" component="div">
                                      {renderLatex(calc)}
                                    </Typography>
                                  </ListItem>
                                ))}
                              </List>
                            ) : (
                              <Typography variant="body2" component="div" sx={{ pl: 2 }}>
                                {renderLatex(step.calculation)}
                              </Typography>
                            )}
                          </Box>
                        )}
                        {step.result && (
                          <Alert severity="info" sx={{ mt: 1 }}>
                            <Typography variant="body2">
                              <strong>Result:</strong> {renderLatex(step.result)}
                            </Typography>
                          </Alert>
                        )}
                      </ListItemText>
                    </ListItem>
                  ))}
                </List>
              </AccordionDetails>
            </Accordion>

            {/* Final Answer */}
            <Accordion defaultExpanded>
              <AccordionSummary expandIcon={<ExpandMore />}>
                <Typography variant="h6">Final Answer</Typography>
              </AccordionSummary>
              <AccordionDetails>
                <Alert severity="success" icon={<CheckCircle />}>
                  <Typography variant="body1" fontWeight="bold">
                    {renderLatex(solution.finalAnswer)}
                  </Typography>
                </Alert>
              </AccordionDetails>
            </Accordion>

            {/* Key Insights & Study Tips */}
            {solution.keyInsights && solution.keyInsights.length > 0 && (
              <Accordion>
                <AccordionSummary expandIcon={<ExpandMore />}>
                  <Typography variant="h6">Key Insights</Typography>
                </AccordionSummary>
                <AccordionDetails>
                  <List>
                    {solution.keyInsights.map((insight, index) => (
                      <ListItem key={index}>
                        <ListItemIcon>
                          <Lightbulb color="warning" />
                        </ListItemIcon>
                        <ListItemText>
                          <Typography variant="body1">{renderLatex(insight)}</Typography>
                        </ListItemText>
                      </ListItem>
                    ))}
                  </List>
                </AccordionDetails>
              </Accordion>
            )}

            {/* Common Mistakes */}
            {solution.commonMistakes && solution.commonMistakes.length > 0 && (
              <Accordion>
                <AccordionSummary expandIcon={<ExpandMore />}>
                  <Typography variant="h6">Common Mistakes</Typography>
                </AccordionSummary>
                <AccordionDetails>
                  <List>
                    {solution.commonMistakes.map((mistake, index) => (
                      <ListItem key={index}>
                        <ListItemIcon>
                          <Warning color="error" />
                        </ListItemIcon>
                        <ListItemText>
                          <Typography variant="body1">{renderLatex(mistake)}</Typography>
                        </ListItemText>
                      </ListItem>
                    ))}
                  </List>
                </AccordionDetails>
              </Accordion>
            )}

            {/* Study Tips */}
            {solution.studyTips && (
              <Accordion>
                <AccordionSummary expandIcon={<ExpandMore />}>
                  <Typography variant="h6">Study Tips</Typography>
                </AccordionSummary>
                <AccordionDetails>
                  <Typography variant="body1">{renderLatex(solution.studyTips)}</Typography>
                </AccordionDetails>
              </Accordion>
            )}

            {/* Alternative Approaches */}
            {solution.alternativeApproaches && solution.alternativeApproaches.length > 0 && (
              <Accordion>
                <AccordionSummary expandIcon={<ExpandMore />}>
                  <Typography variant="h6">Alternative Approaches</Typography>
                </AccordionSummary>
                <AccordionDetails>
                  <List>
                    {solution.alternativeApproaches.map((approach, index) => (
                      <ListItem key={index}>
                        <ListItemIcon>
                          <Info color="info" />
                        </ListItemIcon>
                        <ListItemText>
                          <Typography variant="body1">{renderLatex(approach)}</Typography>
                        </ListItemText>
                      </ListItem>
                    ))}
                  </List>
                </AccordionDetails>
              </Accordion>
            )}

            {/* Solution Metadata */}
            <Accordion>
              <AccordionSummary
                expandIcon={<ExpandMore />}
                aria-controls="solution-metadata-content"
                id="solution-metadata-header"
              >
                <Typography variant="h6">Solution Details</Typography>
              </AccordionSummary>
              <AccordionDetails>
                <Stack spacing={2}>
                  <Box display="flex" justifyContent="space-between" alignItems="center">
                    <Typography variant="body2" color="text.secondary">
                      Confidence Score
                    </Typography>
                    <Typography variant="body1">
                      {solution.confidence || 'N/A'}
                    </Typography>
                  </Box>
                  <Box display="flex" justifyContent="space-between" alignItems="center">
                    <Typography variant="body2" color="text.secondary">
                      Difficulty Level
                    </Typography>
                    <Chip 
                      label={solution.difficulty || 'Unknown'} 
                      size="small" 
                      color="primary" 
                      variant="outlined" 
                    />
                  </Box>
                  {solution.metadata?.solution_timestamp && (
                    <Box display="flex" justifyContent="space-between" alignItems="center">
                      <Typography variant="body2" color="text.secondary">
                        Generated At
                      </Typography>
                      <Typography variant="body1">
                        {new Date(solution.metadata.solution_timestamp).toLocaleString()}
                      </Typography>
                    </Box>
                  )}
                </Stack>
              </AccordionDetails>
            </Accordion>
          </CardContent>
        </Card>
      )}

      {/* Snackbar for success messages */}
      <Snackbar
        open={snackbarOpen}
        autoHideDuration={6000}
        onClose={() => setSnackbarOpen(false)}
        message={snackbarMessage}
      />
    </Box>
  );

  // MathJaxProvider is now wrapped at App level for proper initialization
  return content;
};

export default SolutionGeneratorScreen;