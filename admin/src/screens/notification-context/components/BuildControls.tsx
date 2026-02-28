import React, { useState } from 'react';
import {
  Paper,
  Typography,
  Box,
  Button,
  TextField,
  FormControlLabel,
  Checkbox,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Alert
} from '@mui/material';
import {
  Build,
  FlashOn,
  RestartAlt,
  Person
} from '@mui/icons-material';
import * as apiClient from '../../../common/apiClient';

interface BuildControlsProps {
  onBuildStart: () => void;
  onSuccess: (message: string) => void;
  onError: (message: string) => void;
  isBuilding: boolean;
}

const BuildControls: React.FC<BuildControlsProps> = ({
  onBuildStart,
  onSuccess,
  onError,
  isBuilding
}) => {
  const [batchSize, setBatchSize] = useState('500');
  const [staleOnly, setStaleOnly] = useState(false);
  const [specificUsers, setSpecificUsers] = useState(false);
  const [userIds, setUserIds] = useState('');
  const [showDialog, setShowDialog] = useState(false);
  const [dialogType, setDialogType] = useState<'full' | 'stale' | 'reset' | 'specific'>('full');
  const [specificUserId, setSpecificUserId] = useState('');

  const handleQuickAction = (type: typeof dialogType) => {
    setDialogType(type);
    setShowDialog(true);
  };

  const handleConfirm = async () => {
    setShowDialog(false);

    try {
      switch (dialogType) {
        case 'full':
          await handleBuildAll();
          break;
        case 'stale':
          await handleRefreshStale();
          break;
        case 'reset':
          await handleResetCounters();
          break;
        case 'specific':
          await handleBuildSpecificUser();
          break;
      }
    } catch (err: any) {
      onError(err.message || 'Operation failed');
    }
  };

  const handleBuildAll = async () => {
    try {
      const response = await apiClient.buildNotificationContexts({
        batch_size: 500,
        stale_only: false,
        user_ids: []
      });

      if (response.success) {
        onSuccess(`Started building contexts for ${response.data.total} users`);
        onBuildStart();
      } else {
        onError(response.message || 'Failed to start build');
      }
    } catch (err: any) {
      onError(err.message);
    }
  };

  const handleRefreshStale = async () => {
    try {
      const response = await apiClient.buildNotificationContexts({
        batch_size: 1000,
        stale_only: true,
        user_ids: []
      });

      if (response.success) {
        onSuccess(`Refreshing ${response.data.total} stale contexts`);
        onBuildStart();
      } else {
        onError(response.message || 'Failed to start refresh');
      }
    } catch (err: any) {
      onError(err.message);
    }
  };

  const handleResetCounters = async () => {
    try {
      const response = await apiClient.resetDailyCounters();

      if (response.success) {
        onSuccess('Daily counters reset successfully');
      } else {
        onError(response.message || 'Failed to reset counters');
      }
    } catch (err: any) {
      onError(err.message);
    }
  };

  const handleBuildSpecificUser = async () => {
    if (!specificUserId || isNaN(Number(specificUserId))) {
      onError('Please enter a valid user ID');
      return;
    }

    try {
      const response = await apiClient.buildUserContext(Number(specificUserId));

      if (response.success) {
        onSuccess(`Context built for user ${specificUserId}`);
      } else {
        onError(response.message || 'Failed to build user context');
      }
    } catch (err: any) {
      onError(err.message);
    }
  };

  const handleCustomBuild = async () => {
    if (!batchSize || isNaN(Number(batchSize)) || Number(batchSize) < 1) {
      onError('Please enter a valid batch size');
      return;
    }

    if (specificUsers && !userIds) {
      onError('Please enter user IDs');
      return;
    }

    try {
      const params: any = {
        batch_size: Number(batchSize),
        stale_only: staleOnly
      };

      if (specificUsers && userIds) {
        const ids = userIds.split(',').map(id => Number(id.trim())).filter(id => !isNaN(id));
        if (ids.length === 0) {
          onError('No valid user IDs provided');
          return;
        }
        params.user_ids = ids;
      }

      const response = await apiClient.buildNotificationContexts(params);

      if (response.success) {
        onSuccess(`Started building contexts for ${response.data.total} users`);
        onBuildStart();
      } else {
        onError(response.message || 'Failed to start build');
      }
    } catch (err: any) {
      onError(err.message);
    }
  };

  const getDialogContent = () => {
    switch (dialogType) {
      case 'full':
        return {
          title: 'Build All Contexts',
          content: 'This will rebuild contexts for all active users. This may take 10-15 minutes. Continue?',
          confirmText: 'Start Build'
        };
      case 'stale':
        return {
          title: 'Refresh Stale Contexts',
          content: 'This will refresh only contexts that are older than 1 hour. Continue?',
          confirmText: 'Refresh Stale'
        };
      case 'reset':
        return {
          title: 'Reset Daily Counters',
          content: 'This will reset the daily notification counters for all users. This is typically done automatically at midnight. Continue?',
          confirmText: 'Reset Counters'
        };
      case 'specific':
        return {
          title: 'Build Specific User',
          content: (
            <TextField
              fullWidth
              label="User ID"
              type="number"
              value={specificUserId}
              onChange={(e) => setSpecificUserId(e.target.value)}
              placeholder="Enter user ID"
              sx={{ mt: 2 }}
            />
          ),
          confirmText: 'Build Context'
        };
    }
  };

  const dialogContent = getDialogContent();

  return (
    <>
      <Paper elevation={2} sx={{ p: 4 }}>
        {/* Quick Actions */}
        <Box sx={{ mb: 4 }}>
          <Typography 
            variant="h6" 
            gutterBottom 
            sx={{ 
              display: 'flex', 
              alignItems: 'center', 
              gap: 1,
              fontWeight: 600,
              color: '#1a237e',
              mb: 3
            }}
          >
            🎯 Quick Actions
          </Typography>
          
          <Box sx={{ 
            display: 'grid',
            gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(4, 1fr)' },
            gap: 2
          }}>
            <Button
              fullWidth
              variant="contained"
              startIcon={<Build />}
              onClick={() => handleQuickAction('full')}
              disabled={isBuilding}
              sx={{ 
                py: 1.5,
                fontSize: '0.9rem',
                textTransform: 'none',
                fontWeight: 500
              }}
            >
                Build All Contexts
              </Button>
            <Button
              fullWidth
              variant="contained"
              color="warning"
              startIcon={<FlashOn />}
              onClick={() => handleQuickAction('stale')}
              disabled={isBuilding}
              sx={{ 
                py: 1.5,
                fontSize: '0.9rem',
                textTransform: 'none',
                fontWeight: 500
              }}
            >
              Refresh Stale Only
            </Button>
            <Button
              fullWidth
              variant="outlined"
              startIcon={<RestartAlt />}
              onClick={() => handleQuickAction('reset')}
              disabled={isBuilding}
              sx={{ 
                py: 1.5,
                fontSize: '0.9rem',
                textTransform: 'none',
                fontWeight: 500
              }}
            >
              Reset Daily Counters
            </Button>
            <Button
              fullWidth
              variant="outlined"
              color="info"
              startIcon={<Person />}
              onClick={() => handleQuickAction('specific')}
              disabled={isBuilding}
              sx={{ 
                py: 1.5,
                fontSize: '0.9rem',
                textTransform: 'none',
                fontWeight: 500
              }}
            >
              Build Specific User
            </Button>
          </Box>
        </Box>

        {/* Advanced Build Options */}
        <Box 
          sx={{ 
            pt: 3, 
            borderTop: '1px solid #e0e0e0'
          }}
        >
          <Typography 
            variant="h6" 
            gutterBottom 
            sx={{ 
              fontWeight: 600,
              color: '#1a237e',
              mb: 3
            }}
          >
            🔨 Custom Build
          </Typography>

          <Box sx={{ 
            display: 'grid',
            gridTemplateColumns: { xs: '1fr', md: '200px 1fr auto' },
            gap: 3,
            alignItems: 'end'
          }}>
            <TextField
              fullWidth
              label="Batch Size"
              type="number"
              value={batchSize}
              onChange={(e) => setBatchSize(e.target.value)}
              helperText="Max: 1000"
              InputProps={{ inputProps: { min: 1, max: 1000 } }}
              disabled={isBuilding}
            />
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 3 }}>
              <FormControlLabel
                control={
                  <Checkbox
                    checked={staleOnly}
                    onChange={(e) => setStaleOnly(e.target.checked)}
                    disabled={isBuilding}
                  />
                }
                label="Stale contexts only"
              />
              <FormControlLabel
                control={
                  <Checkbox
                    checked={specificUsers}
                    onChange={(e) => setSpecificUsers(e.target.checked)}
                    disabled={isBuilding}
                  />
                }
                label="Specific users"
              />
            </Box>
            <Button
              fullWidth
              variant="contained"
              size="large"
              startIcon={<Build />}
              onClick={handleCustomBuild}
              disabled={isBuilding}
              sx={{
                py: 1.5,
                fontSize: '1rem',
                textTransform: 'none',
                fontWeight: 500,
                minWidth: '200px'
              }}
            >
              Start Custom Build
            </Button>
          </Box>

        {isBuilding && (
          <Alert severity="info" sx={{ mt: 3 }}>
            A build is currently in progress. Please wait for it to complete.
          </Alert>
        )}
      </Box>
    </Paper>

      {/* Confirmation Dialog */}
      <Dialog open={showDialog} onClose={() => setShowDialog(false)}>
        <DialogTitle>{dialogContent.title}</DialogTitle>
        <DialogContent>
          {typeof dialogContent.content === 'string' ? (
            <Typography>{dialogContent.content}</Typography>
          ) : (
            dialogContent.content
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setShowDialog(false)}>Cancel</Button>
          <Button onClick={handleConfirm} variant="contained" autoFocus>
            {dialogContent.confirmText}
          </Button>
        </DialogActions>
      </Dialog>
    </>
  );
};

export default BuildControls;
