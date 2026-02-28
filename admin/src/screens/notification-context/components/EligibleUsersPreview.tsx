import React, { useState } from 'react';
import {
  Paper,
  Typography,
  Box,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Card,
  CardContent,
  Chip,
  CircularProgress,
  Alert,
  List,
  ListItem,
  ListItemText
} from '@mui/material';
import * as apiClient from '../../../common/apiClient';

const notificationTypes = [
  { value: 'custom_quiz', label: 'Custom Quiz Suggestions' },
  { value: 'pyq', label: 'PYQ Suggestions' },
  { value: 'mock', label: 'Mock Test Suggestions' },
  { value: 'inactivity', label: 'Inactivity Reminders' },
  { value: 'motivational', label: 'Motivational Messages' },
  { value: 'milestones', label: 'Milestone Achievements' },
  { value: 'quota_warning', label: 'Quota Warnings' }
];

const EligibleUsersPreview: React.FC = () => {
  const [selectedType, setSelectedType] = useState('custom_quiz');
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleTypeChange = async (type: string) => {
    setSelectedType(type);
    setLoading(true);
    setError(null);

    try {
      const response = await apiClient.getEligibleUsersPreview(type, 100);
      
      if (response.success) {
        setData(response.data);
      } else {
        setError(response.message || 'Failed to load eligible users');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to load eligible users');
    } finally {
      setLoading(false);
    }
  };

  // Load initial data
  React.useEffect(() => {
    handleTypeChange(selectedType);
  }, []);

  const renderBreakdown = () => {
    if (!data?.breakdown) return null;

    const breakdown = data.breakdown;

    // Custom Quiz breakdown
    if (breakdown.top_weak_subjects) {
      return (
        <>
          <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
            Top Weak Subjects:
          </Typography>
          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2 }}>
            {Object.entries(breakdown.top_weak_subjects).map(([subject, count]: [string, any]) => (
              <Chip
                key={subject}
                label={`${subject}: ${count}`}
                size="small"
                color="primary"
                variant="outlined"
              />
            ))}
          </Box>
          {breakdown.persona_distribution && (
            <>
              <Typography variant="subtitle2" gutterBottom>
                Persona Distribution:
              </Typography>
              <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2 }}>
                {Object.entries(breakdown.persona_distribution).map(([persona, count]: [string, any]) => (
                  <Chip
                    key={persona}
                    label={`${persona}: ${count}`}
                    size="small"
                    color="secondary"
                    variant="outlined"
                  />
                ))}
              </Box>
            </>
          )}
          {breakdown.avg_accuracy && (
            <Typography variant="body2" color="text.secondary">
              Average Accuracy: <strong>{breakdown.avg_accuracy}</strong>
            </Typography>
          )}
        </>
      );
    }

    // Inactivity breakdown
    if (breakdown.inactive_duration_breakdown) {
      return (
        <>
          <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
            Inactive Duration:
          </Typography>
          <List dense>
            {Object.entries(breakdown.inactive_duration_breakdown).map(([range, count]: [string, any]) => (
              <ListItem key={range}>
                <ListItemText
                  primary={range}
                  secondary={`${count} users`}
                />
              </ListItem>
            ))}
          </List>
          {breakdown.avg_previous_streak && (
            <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
              Avg Previous Streak: <strong>{breakdown.avg_previous_streak} days</strong>
            </Typography>
          )}
        </>
      );
    }

    // Quota warning breakdown
    if (breakdown.quota_usage_breakdown) {
      return (
        <>
          <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
            Quota Usage:
          </Typography>
          <List dense>
            {Object.entries(breakdown.quota_usage_breakdown).map(([range, count]: [string, any]) => (
              <ListItem key={range}>
                <ListItemText
                  primary={range}
                  secondary={`${count} users`}
                />
              </ListItem>
            ))}
          </List>
          {breakdown.avg_remaining && (
            <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
              Avg Remaining: <strong>{breakdown.avg_remaining} quizzes</strong>
            </Typography>
          )}
        </>
      );
    }

    // Milestone breakdown
    if (breakdown.milestone_distribution) {
      return (
        <>
          <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
            Milestones:
          </Typography>
          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2 }}>
            {Object.entries(breakdown.milestone_distribution).map(([milestone, count]: [string, any]) => (
              <Chip
                key={milestone}
                label={`${milestone}: ${count}`}
                size="small"
                color="success"
                variant="outlined"
              />
            ))}
          </Box>
        </>
      );
    }

    // Generic breakdown
    if (breakdown.performance_distribution) {
      return (
        <>
          <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
            Performance:
          </Typography>
          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2 }}>
            {Object.entries(breakdown.performance_distribution).map(([perf, count]: [string, any]) => (
              <Chip
                key={perf}
                label={`${perf}: ${count}`}
                size="small"
                variant="outlined"
              />
            ))}
          </Box>
          {breakdown.avg_accuracy && (
            <Typography variant="body2" color="text.secondary">
              Avg Accuracy: <strong>{breakdown.avg_accuracy}</strong>
            </Typography>
          )}
        </>
      );
    }

    return (
      <Typography variant="body2" color="text.secondary">
        {JSON.stringify(breakdown, null, 2)}
      </Typography>
    );
  };

  return (
    <Paper className="eligible-preview-card" sx={{ p: 3 }}>
      <Typography variant="h6" gutterBottom>
        👥 Eligible Users Preview
      </Typography>

      <FormControl fullWidth sx={{ mb: 2 }}>
        <InputLabel>Notification Type</InputLabel>
        <Select
          value={selectedType}
          label="Notification Type"
          onChange={(e) => handleTypeChange(e.target.value)}
        >
          {notificationTypes.map((type) => (
            <MenuItem key={type.value} value={type.value}>
              {type.label}
            </MenuItem>
          ))}
        </Select>
      </FormControl>

      {loading && (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
          <CircularProgress />
        </Box>
      )}

      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {!loading && !error && data && (
        <Card variant="outlined">
          <CardContent>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
              <Typography variant="h4" color="primary">
                {data.eligible_count}
              </Typography>
              <Chip label="Eligible Users" color="primary" />
            </Box>

            <Typography variant="body2" color="text.secondary" paragraph>
              {data.description}
            </Typography>

            <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
              Criteria:
            </Typography>
            <List dense>
              {data.criteria?.map((criterion: string, index: number) => (
                <ListItem key={index} sx={{ py: 0.5 }}>
                  <ListItemText
                    primary={`• ${criterion}`}
                    primaryTypographyProps={{ variant: 'body2', color: 'text.secondary' }}
                  />
                </ListItem>
              ))}
            </List>

            {renderBreakdown()}
          </CardContent>
        </Card>
      )}
    </Paper>
  );
};

export default EligibleUsersPreview;
