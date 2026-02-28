import React, { useState } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  TextField,
  FormControlLabel,
  Checkbox,
  Button,
  MenuItem,
  Chip,
  Alert
} from '@mui/material';
import { Send as SendIcon, Preview as PreviewIcon } from '@mui/icons-material';

interface SendConfigPanelProps {
  onSend: (config: SendConfig) => void;
  isLoading: boolean;
  isSending: boolean;
}

export interface SendConfig {
  user_ids?: number[];
  max_users: number;
  priority_filter?: string;
  segment_filter?: string;
  dry_run: boolean;
}

const SendConfigPanel: React.FC<SendConfigPanelProps> = ({
  onSend,
  isLoading,
  isSending
}) => {
  const [userIds, setUserIds] = useState<string>('');
  const [maxUsers, setMaxUsers] = useState<number>(50);
  const [priorityFilter, setPriorityFilter] = useState<string>('');
  const [segmentFilter, setSegmentFilter] = useState<string>('');
  const [dryRun, setDryRun] = useState<boolean>(true);

  const handleSend = () => {
    const config: SendConfig = {
      max_users: maxUsers,
      dry_run: dryRun
    };

    if (userIds.trim()) {
      const ids = userIds.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
      if (ids.length > 0) {
        config.user_ids = ids;
      }
    }

    if (priorityFilter) {
      config.priority_filter = priorityFilter;
    }

    if (segmentFilter) {
      config.segment_filter = segmentFilter;
    }

    onSend(config);
  };

  const getUserIdCount = () => {
    if (!userIds.trim()) return 0;
    return userIds.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id)).length;
  };

  return (
    <Card>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          🚀 Batch Send Configuration
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Configure and send notifications to multiple users
        </Typography>

        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
          {/* Target Users */}
          <TextField
            fullWidth
            label="Specific User IDs (optional)"
            placeholder="e.g., 123, 456, 789"
            value={userIds}
            onChange={(e) => setUserIds(e.target.value)}
            helperText={
              userIds.trim()
                ? `${getUserIdCount()} user(s) specified`
                : 'Leave empty to auto-select eligible users'
            }
            disabled={isLoading || isSending}
          />

          {/* Max Users and Priority in a row */}
          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' }, gap: 3 }}>
            <TextField
              fullWidth
              type="number"
              label="Max Users"
              value={maxUsers}
              onChange={(e) => setMaxUsers(parseInt(e.target.value) || 50)}
              inputProps={{ min: 1, max: 200 }}
              helperText="Maximum: 200 users per batch"
              disabled={isLoading || isSending}
            />

            <TextField
              fullWidth
              select
              label="Priority Filter (optional)"
              value={priorityFilter}
              onChange={(e) => setPriorityFilter(e.target.value)}
              disabled={isLoading || isSending}
            >
              <MenuItem value="">All Priorities</MenuItem>
              <MenuItem value="critical">Critical Only</MenuItem>
              <MenuItem value="high">High Priority</MenuItem>
              <MenuItem value="medium">Medium Priority</MenuItem>
              <MenuItem value="low">Low Priority</MenuItem>
            </TextField>
          </Box>

          {/* Segment Filter and Dry Run */}
          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' }, gap: 3 }}>
            <TextField
              fullWidth
              select
              label="Segment Filter (optional)"
              value={segmentFilter}
              onChange={(e) => setSegmentFilter(e.target.value)}
              disabled={isLoading || isSending}
            >
              <MenuItem value="">All Segments</MenuItem>
              <MenuItem value="new_users">New Users</MenuItem>
              <MenuItem value="active_learners">Active Learners</MenuItem>
              <MenuItem value="inactive_users">Inactive Users</MenuItem>
              <MenuItem value="high_performers">High Performers</MenuItem>
            </TextField>

            <Box sx={{ display: 'flex', alignItems: 'center', height: '100%' }}>
              <FormControlLabel
                control={
                  <Checkbox
                    checked={dryRun}
                    onChange={(e) => setDryRun(e.target.checked)}
                    disabled={isLoading || isSending}
                  />
                }
                label={
                  <Box>
                    <Typography variant="body1">Dry Run Mode</Typography>
                    <Typography variant="caption" color="text.secondary">
                      Preview without sending
                    </Typography>
                  </Box>
                }
              />
            </Box>
          </Box>

          {/* Info Alert */}
          {dryRun ? (
            <Alert severity="info">
              🔍 <strong>Dry Run Mode:</strong> Notifications will be previewed but not sent. No FCM messages will be delivered.
            </Alert>
          ) : (
            <Alert severity="warning">
              ⚠️ <strong>Live Send Mode:</strong> Notifications will be sent immediately to users via FCM. This cannot be undone.
            </Alert>
          )}

          {/* Send Button */}
          <Button
            fullWidth
            variant="contained"
            size="large"
            startIcon={dryRun ? <PreviewIcon /> : <SendIcon />}
            onClick={handleSend}
            disabled={isLoading || isSending}
          >
            {isSending
              ? 'Processing...'
              : dryRun
              ? 'Preview Notifications'
              : 'Send Notifications'}
          </Button>
        </Box>
      </CardContent>
    </Card>
  );
};

export default SendConfigPanel;
