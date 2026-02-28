import React from 'react';
import {
  Paper,
  Typography,
  Box,
  LinearProgress,
  Chip
} from '@mui/material';
import {
  CheckCircle,
  Error as ErrorIcon,
  HourglassEmpty
} from '@mui/icons-material';

interface BuildProgressProps {
  buildStatus: any;
}

const BuildProgress: React.FC<BuildProgressProps> = ({ buildStatus }) => {
  if (!buildStatus?.is_building) {
    return null;
  }

  const {
    progress_percentage = 0,
    processed = 0,
    total = 0,
    failed = 0,
    elapsed_seconds = 0,
    build_type = 'Building',
    triggered_by = 'Unknown'
  } = buildStatus;

  const estimatedTotal = total > 0 && processed > 0 
    ? Math.round((elapsed_seconds / processed) * total)
    : 0;
  
  const estimatedRemaining = Math.max(0, estimatedTotal - elapsed_seconds);

  const formatTime = (seconds: number) => {
    if (seconds < 60) return `${Math.round(seconds)}s`;
    const mins = Math.floor(seconds / 60);
    const secs = Math.round(seconds % 60);
    return `${mins}m ${secs}s`;
  };

  return (
    <Paper 
      className="progress-card"
      sx={{ 
        p: 3,
        borderLeft: `4px solid ${failed > 0 ? '#f44336' : '#1976d2'}`
      }}
    >
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Box>
          <Typography variant="h6" sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <HourglassEmpty />
            Building Contexts...
          </Typography>
          <Typography variant="body2" color="text.secondary">
            {build_type} • Triggered by {triggered_by}
          </Typography>
        </Box>
        <Box sx={{ display: 'flex', gap: 1 }}>
          <Chip
            icon={<CheckCircle />}
            label={`${processed} processed`}
            color="primary"
            size="small"
          />
          {failed > 0 && (
            <Chip
              icon={<ErrorIcon />}
              label={`${failed} failed`}
              color="error"
              size="small"
            />
          )}
        </Box>
      </Box>

      <Box sx={{ mb: 2 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
          <Typography variant="body2" fontWeight={600}>
            Progress: {progress_percentage}%
          </Typography>
          <Typography variant="body2" color="text.secondary">
            {processed} / {total} users
          </Typography>
        </Box>
        <LinearProgress
          variant="determinate"
          value={progress_percentage}
          sx={{ 
            height: 8, 
            borderRadius: 4,
            '& .MuiLinearProgress-bar': {
              borderRadius: 4
            }
          }}
        />
      </Box>

      <Box sx={{ display: 'flex', gap: 3 }}>
        <Typography variant="body2" color="text.secondary">
          <strong>Elapsed:</strong> {formatTime(elapsed_seconds)}
        </Typography>
        {estimatedRemaining > 0 && (
          <Typography variant="body2" color="text.secondary">
            <strong>Estimated:</strong> ~{formatTime(estimatedRemaining)} remaining
          </Typography>
        )}
      </Box>
    </Paper>
  );
};

export default BuildProgress;
