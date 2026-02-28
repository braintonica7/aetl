import React, { useState, useEffect } from 'react';
import {
  Box,
  Container,
  Typography,
  Paper,
  Button,
  Alert,
  Snackbar
} from '@mui/material';
import { Refresh as RefreshIcon } from '@mui/icons-material';
import * as apiClient from '../../common/apiClient';
import ContextStats from './components/ContextStats';
import BuildControls from './components/BuildControls';
import BuildProgress from './components/BuildProgress';
import BuildHistory from './components/BuildHistory';
import EligibleUsersPreview from './components/EligibleUsersPreview';
import './NotificationContextManager.css';

const NotificationContextManager: React.FC = () => {
  const [stats, setStats] = useState<any>(null);
  const [buildStatus, setBuildStatus] = useState<any>(null);
  const [buildHistory, setBuildHistory] = useState<any[]>([]);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Fetch initial data
  useEffect(() => {
    loadDashboardData();
  }, []);

  // Poll build status if building
  useEffect(() => {
    if (buildStatus?.is_building) {
      const interval = setInterval(() => {
        checkBuildStatus();
      }, 3000); // Poll every 3 seconds

      return () => clearInterval(interval);
    }
  }, [buildStatus?.is_building]);

  const loadDashboardData = async () => {
    setIsRefreshing(true);
    try {
      const [statsData, statusData, historyData] = await Promise.all([
        apiClient.getContextStats(),
        apiClient.getBuildStatus(),
        apiClient.getBuildHistory(10)
      ]);

      if (statsData.success) {
        setStats(statsData.data);
      }

      if (statusData.success) {
        setBuildStatus(statusData.data);
      }

      if (historyData.success) {
        setBuildHistory(historyData.data.builds || []);
      }
    } catch (err: any) {
      setError('Failed to load dashboard data: ' + err.message);
    } finally {
      setIsRefreshing(false);
    }
  };

  const checkBuildStatus = async () => {
    try {
      const response = await apiClient.getBuildStatus();
      if (response.success) {
        setBuildStatus(response.data);
        
        // If build just finished, refresh all data
        if (!response.data.is_building && buildStatus?.is_building) {
          loadDashboardData();
          setSuccess('Context build completed successfully!');
        }
      }
    } catch (err: any) {
      console.error('Failed to check build status:', err);
    }
  };

  const handleBuildStart = () => {
    // Start polling for build status
    checkBuildStatus();
  };

  const handleRefresh = () => {
    loadDashboardData();
  };

  const handleCloseSnackbar = () => {
    setError(null);
    setSuccess(null);
  };

  return (
    <Container maxWidth="xl" className="notification-context-manager">
      <Box sx={{ py: 4 }}>
        {/* Header */}
        <Box sx={{ 
          display: 'flex', 
          justifyContent: 'space-between', 
          alignItems: 'center', 
          mb: 4,
          pb: 2,
          borderBottom: '2px solid #e0e0e0'
        }}>
          <Box>
            <Typography variant="h4" gutterBottom sx={{ fontWeight: 600, color: '#1a237e' }}>
              📊 Notification Context Manager
            </Typography>
            <Typography variant="body1" color="text.secondary">
              Manage user notification contexts and trigger context builds
            </Typography>
          </Box>
          <Button
            variant="outlined"
            startIcon={<RefreshIcon />}
            onClick={handleRefresh}
            disabled={isRefreshing || buildStatus?.is_building}
            sx={{ 
              height: 'fit-content',
              px: 3,
              py: 1
            }}
          >
            Refresh
          </Button>
        </Box>

        {/* Statistics Cards */}
        <Box sx={{ mb: 4 }}>
          <ContextStats stats={stats} isLoading={isRefreshing} />
        </Box>

        {/* Build Progress (show if building) */}
        {buildStatus?.is_building && (
          <Box sx={{ mb: 4 }}>
            <BuildProgress buildStatus={buildStatus} />
          </Box>
        )}

        {/* Build Controls */}
        <Box sx={{ mb: 4 }}>
          <BuildControls
            onBuildStart={handleBuildStart}
            onSuccess={(msg) => setSuccess(msg)}
            onError={(msg) => setError(msg)}
            isBuilding={buildStatus?.is_building || false}
          />
        </Box>

        {/* Eligible Users Preview */}
        <Box sx={{ width: '100%' }}>
          <EligibleUsersPreview />
        </Box>
      </Box>

      {/* Success/Error Snackbars */}
      <Snackbar
        open={!!success}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert onClose={handleCloseSnackbar} severity="success" sx={{ width: '100%' }}>
          {success}
        </Alert>
      </Snackbar>

      <Snackbar
        open={!!error}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert onClose={handleCloseSnackbar} severity="error" sx={{ width: '100%' }}>
          {error}
        </Alert>
      </Snackbar>
    </Container>
  );
};

export default NotificationContextManager;
