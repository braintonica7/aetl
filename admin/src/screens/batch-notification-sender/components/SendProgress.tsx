import React from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  LinearProgress,
  Alert,
  Chip
} from '@mui/material';
import {
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  Timer as TimerIcon
} from '@mui/icons-material';
import { BatchSendResult } from '../types';

interface SendProgressProps {
  results: BatchSendResult | null;
  isLoading: boolean;
}

const SendProgress: React.FC<SendProgressProps> = ({ results, isLoading }) => {
  if (!isLoading && !results) return null;

  const { summary } = results || {};
  const total = summary?.total_processed || 0;
  const sent = summary?.sent || 0;
  const skipped = summary?.skipped || 0;
  const failed = summary?.failed || 0;
  const progress = total > 0 ? ((sent + skipped + failed) / total) * 100 : 0;

  return (
    <Card sx={{ mb: 3 }}>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          {isLoading ? '⏳ Sending in Progress...' : '✅ Send Complete'}
        </Typography>

        {isLoading && (
          <Box sx={{ mb: 3 }}>
            <LinearProgress variant="indeterminate" />
          </Box>
        )}

        {summary && (
          <>
            {/* Progress Stats */}
            <Box sx={{ 
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
              gap: 2,
              mb: 3
            }}>
              <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#f5f5f5', borderRadius: 1 }}>
                <Typography variant="h4">
                  {total}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Total Processed
                </Typography>
              </Box>
              <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#e8f5e9', borderRadius: 1 }}>
                <CheckCircleIcon sx={{ fontSize: 32, color: 'success.main', mb: 1 }} />
                <Typography variant="h4" color="success.main">
                  {sent}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Sent
                </Typography>
              </Box>
              <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#fff3e0', borderRadius: 1 }}>
                <CancelIcon sx={{ fontSize: 32, color: 'warning.main', mb: 1 }} />
                <Typography variant="h4" color="warning.main">
                  {skipped}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Skipped
                </Typography>
              </Box>
              <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#ffebee', borderRadius: 1 }}>
                <CancelIcon sx={{ fontSize: 32, color: 'error.main', mb: 1 }} />
                <Typography variant="h4" color="error.main">
                  {failed}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Failed
                </Typography>
              </Box>
            </Box>

            {/* Processing Time */}
            {summary.processing_time && (
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
                <TimerIcon fontSize="small" color="action" />
                <Typography variant="body2" color="text.secondary">
                  Processing Time: <strong>{summary.processing_time}</strong>
                </Typography>
              </Box>
            )}

            {/* Priority Breakdown */}
            {summary.by_priority && (
              <Box sx={{ mb: 2 }}>
                <Typography variant="subtitle2" gutterBottom>
                  By Priority:
                </Typography>
                <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                  {Object.entries(summary.by_priority).map(([priority, count]: [string, any]) => (
                    <Chip
                      key={priority}
                      label={`${priority}: ${count}`}
                      size="small"
                      color={priority === 'critical' ? 'error' : 'default'}
                    />
                  ))}
                </Box>
              </Box>
            )}

            {/* Type Breakdown */}
            {summary.by_type && (
              <Box sx={{ mb: 2 }}>
                <Typography variant="subtitle2" gutterBottom>
                  By Type:
                </Typography>
                <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                  {Object.entries(summary.by_type).map(([type, count]: [string, any]) => (
                    <Chip key={type} label={`${type}: ${count}`} size="small" variant="outlined" />
                  ))}
                </Box>
              </Box>
            )}

            {/* Success Message */}
            {!isLoading && sent > 0 && (
              <Alert severity="success">
                <strong>Success!</strong> {sent} notification{sent > 1 ? 's' : ''} sent successfully.
              </Alert>
            )}

            {/* Warning Message */}
            {!isLoading && skipped > 0 && (
              <Alert severity="warning" sx={{ mt: 1 }}>
                <strong>Note:</strong> {skipped} notification{skipped > 1 ? 's were' : ' was'} skipped.
              </Alert>
            )}

            {/* Error Message */}
            {!isLoading && failed > 0 && (
              <Alert severity="error" sx={{ mt: 1 }}>
                <strong>Error:</strong> {failed} notification{failed > 1 ? 's' : ''} failed to send.
              </Alert>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
};

export default SendProgress;
