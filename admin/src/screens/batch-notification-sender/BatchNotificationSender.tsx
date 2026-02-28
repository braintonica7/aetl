import React, { useState, useEffect } from 'react';
import {
  Box,
  Container,
  Typography,
  Button,
  Grid,
  Alert,
  Snackbar
} from '@mui/material';
import { Refresh as RefreshIcon } from '@mui/icons-material';
import { sendBatchNotifications, getBatchSendHistory, getBatchSendStats } from '../../common/apiClient';
import SendStats from './components/SendStats';
import SendConfigPanel, { SendConfig } from './components/SendConfigPanel';
import PreviewResults from './components/PreviewResults';
import SendProgress from './components/SendProgress';
import SendHistory from './components/SendHistory';
import { BatchSendResult, BatchStats, BatchHistoryRecord } from './types';
import './BatchNotificationSender.css';

const BatchNotificationSender: React.FC = () => {
  const [stats, setStats] = useState<BatchStats | null>(null);
  const [history, setHistory] = useState<BatchHistoryRecord[]>([]);
  const [previewResults, setPreviewResults] = useState<BatchSendResult | null>(null);
  const [sendResults, setSendResults] = useState<BatchSendResult | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Load initial data
  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    setIsLoading(true);
    try {
      const [statsData, historyData] = await Promise.all([
        getBatchSendStats(),
        getBatchSendHistory(20)
      ]);
      console.log('History Data:', historyData);
      console.log('Stats Data:', statsData);
      if (statsData.success) {
        setStats(statsData.data);
      }

      if (historyData.success) {
        setHistory(historyData.data || []);
      }
    } catch (err: any) {
      setError('Failed to load dashboard data: ' + err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSend = async (config: SendConfig) => {
    setIsSending(true);
    setError(null);
    setSuccess(null);
    
    // Clear previous results
    if (config.dry_run) {
      setPreviewResults(null);
      setSendResults(null);
    } else {
      setSendResults(null);
    }

    try {
      const result = await sendBatchNotifications(config);
      console.log('Send Result:', result);
      if (config.dry_run) {
        setPreviewResults(result);
        setSuccess(`Preview generated for ${result.total_processed || 0} users`);
      } else {
        setSendResults(result);
        setSuccess(
          `Batch send complete! ${result.sent || 0} sent, ${result.skipped || 0} skipped, ${result.failed || 0} failed`
        );
        // Refresh history after real send
        loadDashboardData();
      }
    } catch (err: any) {
      setError('Failed to process batch: ' + err.message);
    } finally {
      setIsSending(false);
    }
  };

  const handleRefresh = () => {
    loadDashboardData();
  };

  const handleCloseSnackbar = () => {
    setError(null);
    setSuccess(null);
  };

  const handleViewDetails = (record: any) => {
    // TODO: Implement details modal
    console.log('View details:', record);
  };

  return (
    <Container maxWidth="xl" className="batch-notification-sender">
      <Box sx={{ py: 4 }}>
        {/* Header */}
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 4 }}>
          <Box>
            <Typography variant="h4" gutterBottom>
              🚀 Batch Notification Sender
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Send notifications to multiple users with intelligent suggestion-based selection
            </Typography>
          </Box>
          <Button
            variant="outlined"
            startIcon={<RefreshIcon />}
            onClick={handleRefresh}
            disabled={isLoading || isSending}
          >
            Refresh
          </Button>
        </Box>

        {/* Statistics Cards */}
        <SendStats stats={stats} isLoading={isLoading} />

        {/* Configuration Panel */}
        <Box sx={{ mb: 3 }}>
          <SendConfigPanel
            onSend={handleSend}
            isLoading={isLoading}
            isSending={isSending}
          />
        </Box>

        {/* Send Progress (for live sends) */}
        {sendResults && (
          <SendProgress results={sendResults} isLoading={isSending} />
        )}

        {/* Preview Results (for dry run) */}
        {previewResults && (
          <PreviewResults results={previewResults} />
        )}

        {/* History */}
        <Box sx={{ mt: 3 }}>
          <SendHistory
            history={history}
            onViewDetails={handleViewDetails}
          />
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

export default BatchNotificationSender;
