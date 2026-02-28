import React, { useState } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Chip,
  Stack,
  Alert
} from '@mui/material';
import {
  ExpandMore as ExpandMoreIcon,
  Person as PersonIcon,
  Notifications as NotificationsIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon
} from '@mui/icons-material';
import { BatchSendResult } from '../types';

interface PreviewResultsProps {
  results: BatchSendResult | null;
}

const PreviewResults: React.FC<PreviewResultsProps> = ({ results }) => {
  if (!results) return null;

  return (
    <Card sx={{ mb: 3 }}>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          🔍 Preview Results
        </Typography>

        {/* Summary Stats */}
        <Box sx={{ 
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
          gap: 2,
          mb: 3
        }}>
          <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#f5f5f5', borderRadius: 1 }}>
            <Typography variant="h4" color="primary">
              {results.total_processed || 0}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              Total Processed
            </Typography>
          </Box>
          <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#e8f5e9', borderRadius: 1 }}>
            <Typography variant="h4" color="success.main">
              {results.sent || 0}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              Will Send
            </Typography>
          </Box>
          <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#fff3e0', borderRadius: 1 }}>
            <Typography variant="h4" color="warning.main">
              {results.skipped || 0}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              Skipped
            </Typography>
          </Box>
          <Box sx={{ textAlign: 'center', p: 2, bgcolor: '#ffebee', borderRadius: 1 }}>
            <Typography variant="h4" color="error.main">
              {results.failed || 0}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              Failed
            </Typography>
          </Box>
        </Box>

        {/* Priority Breakdown */}
        {results.breakdown?.by_priority && (
          <Box sx={{ mb: 3 }}>
            <Typography variant="subtitle2" gutterBottom>
              By Priority:
            </Typography>
            <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
              {Object.entries(results.breakdown.by_priority).map(([priority, count]) => (
                <Chip
                  key={priority}
                  label={`${priority}: ${count}`}
                  size="small"
                  color={priority === 'high' ? 'error' : priority === 'medium' ? 'warning' : 'default'}
                />
              ))}
            </Box>
          </Box>
        )}

        {/* Type Breakdown */}
        {results.breakdown?.by_type && (
          <Box sx={{ mb: 3 }}>
            <Typography variant="subtitle2" gutterBottom>
              By Type:
            </Typography>
            <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
              {Object.entries(results.breakdown.by_type).map(([type, count]) => (
                <Chip key={type} label={`${type}: ${count}`} size="small" variant="outlined" />
              ))}
            </Box>
          </Box>
        )}

        <Alert severity="info" sx={{ mb: 2 }}>
          This is a preview. No notifications have been sent. Uncheck "Dry Run Mode" to send.
        </Alert>

        {/* User Details */}
        <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
          User Notifications ({results.details?.length || 0}):
        </Typography>
        <Box sx={{ maxHeight: 400, overflow: 'auto' }}>
          {results.details?.map((detail, index) => (
            <Accordion key={index}>
              <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, width: '100%' }}>
                  <PersonIcon fontSize="small" />
                  <Typography sx={{ flexGrow: 1 }}>
                    {detail.display_name} ({detail.email})
                  </Typography>
                  {detail.status === 'would_send' && (
                    <Chip icon={<CheckCircleIcon />} label="Will Send" size="small" color="success" />
                  )}
                  {detail.status === 'skipped' && (
                    <Chip icon={<CancelIcon />} label="Skipped" size="small" color="warning" />
                  )}
                  {detail.status === 'failed' && (
                    <Chip icon={<CancelIcon />} label="Failed" size="small" color="error" />
                  )}
                </Box>
              </AccordionSummary>
              <AccordionDetails>
                <Box sx={{ 
                  display: 'grid',
                  gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' },
                  gap: 2
                }}>
                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      User ID:
                    </Typography>
                    <Typography variant="body2">
                      {detail.user_id}
                    </Typography>
                  </Box>
                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      Segment:
                    </Typography>
                    <Typography variant="body2">
                      {detail.segment}
                    </Typography>
                  </Box>
                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      Notification Type:
                    </Typography>
                    <Typography variant="body2">
                      {detail.notification_type}
                    </Typography>
                  </Box>
                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      Priority:
                    </Typography>
                    <Chip
                      label={detail.priority}
                      size="small"
                      color={detail.priority === 'high' ? 'error' : detail.priority === 'medium' ? 'warning' : 'default'}
                    />
                  </Box>
                  <Box sx={{ gridColumn: { xs: '1', md: '1 / -1' } }}>
                    <Typography variant="caption" color="text.secondary">
                      Title:
                    </Typography>
                    <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                      {detail.title}
                    </Typography>
                  </Box>
                  <Box sx={{ gridColumn: { xs: '1', md: '1 / -1' } }}>
                    <Typography variant="caption" color="text.secondary">
                      Text:
                    </Typography>
                    <Typography variant="body2">
                      {detail.text}
                    </Typography>
                  </Box>
                  {detail.skip_reason && (
                    <Box sx={{ gridColumn: { xs: '1', md: '1 / -1' } }}>
                      <Alert severity="warning" sx={{ mt: 1 }}>
                        <strong>Skip Reason:</strong> {detail.skip_reason}
                      </Alert>
                    </Box>
                  )}
                  {detail.error && (
                    <Box sx={{ gridColumn: { xs: '1', md: '1 / -1' } }}>
                      <Alert severity="error" sx={{ mt: 1 }}>
                        <strong>Error:</strong> {detail.error}
                      </Alert>
                    </Box>
                  )}
                </Box>
              </AccordionDetails>
            </Accordion>
          ))}
        </Box>
      </CardContent>
    </Card>
  );
};

export default PreviewResults;
