import React, { useState, useEffect } from 'react';
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
  FormControlLabel,
  Switch,
  Chip,
  Autocomplete,
  Snackbar,
  Paper,
  Stack
} from '@mui/material';
import { Send, NotificationsActive, Person, Group, Settings, Smartphone, Info } from '@mui/icons-material';
import * as http from '../../common/http';
import { APIUrl } from '../../common/apiConfig';
import './NotificationSend.css';

interface NotificationFormData {
  title: string;
  body: string;
  type: string;
  target_type: string;
  target_user_id: number | null;
  deep_link_screen: string;
  deep_link_data: string;
}

interface User {
  id: number;
  display_name: string;
  userName: string;
}

interface SendResult {
  notification_id: number;
  sent_count: number;
  failed_count: number;
  total_recipients: number;
}

const NotificationTypes = [
  { value: 'general', label: 'General' },
  { value: 'study_reminder', label: 'Study Reminder' },
  { value: 'admin_announcement', label: 'Admin Announcement' },
  { value: 'quiz_notification', label: 'Quiz Notification' },
  { value: 'system_maintenance', label: 'System Maintenance' },
  { value: 'feature_announcement', label: 'Feature Announcement' },
  { value: 'urgent', label: 'Urgent' }
];

const DeepLinkScreens = [
  { value: '', label: 'None' },
  { value: 'dashboard', label: 'Dashboard' },
  { value: 'custom-quiz', label: 'Custom Quiz' },
  { value: 'profile', label: 'Profile' },
  { value: 'notifications', label: 'Notifications' },
  { value: 'leaderboard', label: 'Leaderboard' },
  { value: 'performance', label: 'Performance' },
  { value: 'quiz-history', label: 'Quiz History' }
];

const NotificationSend: React.FC = () => {
  const [formData, setFormData] = useState<NotificationFormData>({
    title: '',
    body: '',
    type: 'general',
    target_type: 'all_users',
    target_user_id: null,
    deep_link_screen: '',
    deep_link_data: ''
  });

  const [users, setUsers] = useState<User[]>([]);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [sendResult, setSendResult] = useState<SendResult | null>(null);
  const [showAdvanced, setShowAdvanced] = useState(false);

  // Load users for autocomplete
  useEffect(() => {
    if (formData.target_type === 'specific_user') {
      fetchUsers();
    }
  }, [formData.target_type]);

  const fetchUsers = async () => {
    setIsLoading(true);
    try {
      const response = await http.get(`${APIUrl}user`);
      if (response && response.status == "success" && response.result) {
        setUsers(response.result);
      } else {
        setError('Failed to load users');
      }
    } catch (error) {
      console.error('Error fetching users:', error);
      setError('Failed to load users');
    } finally {
      setIsLoading(false);
    }
  };

  const handleInputChange = (field: keyof NotificationFormData, value: any) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
    
    // Clear user selection if target type changes
    if (field === 'target_type' && value !== 'specific_user') {
      setFormData(prev => ({ ...prev, target_user_id: null }));
      setSelectedUser(null);
    }
  };

  const handleUserSelect = (user: User | null) => {
    setSelectedUser(user);
    setFormData(prev => ({
      ...prev,
      target_user_id: user ? user.id : null
    }));
  };

  const validateForm = (): string | null => {
    if (!formData.title.trim()) {
      return 'Title is required';
    }
    if (!formData.body.trim()) {
      return 'Message body is required';
    }
    if (formData.title.length > 100) {
      return 'Title must be 100 characters or less';
    }
    if (formData.body.length > 500) {
      return 'Message body must be 500 characters or less';
    }
    if (formData.target_type === 'specific_user' && !formData.target_user_id) {
      return 'Please select a user when targeting specific user';
    }
    if (formData.deep_link_data) {
      try {
        JSON.parse(formData.deep_link_data);
      } catch (e) {
        return 'Deep link data must be valid JSON';
      }
    }
    return null;
  };

  const handleSend = async () => {
    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      return;
    }

    setIsSending(true);
    setError(null);
    setSendResult(null);

    try {
      const payload = {
        title: formData.title,
        body: formData.body,
        type: formData.type,
        target_type: formData.target_type,
        ...(formData.target_user_id && { target_user_id: formData.target_user_id }),
        ...(formData.deep_link_screen && { deep_link_screen: formData.deep_link_screen }),
        ...(formData.deep_link_data && { 
          deep_link_data: JSON.parse(formData.deep_link_data) 
        })
      };

      const response = await http.post(`${APIUrl}notifications/send`, payload);

      if (response && response.success && response.data) {
        setSendResult(response.data);
        setSuccess(
          `Notification sent successfully! ` +
          `Sent to ${response.data.sent_count} users, ` +
          `${response.data.failed_count} failed, ` +
          `${response.data.total_recipients} total recipients.`
        );
        
        // Reset form for next notification
        setFormData({
          title: '',
          body: '',
          type: 'general',
          target_type: 'all_users',
          target_user_id: null,
          deep_link_screen: '',
          deep_link_data: ''
        });
        setSelectedUser(null);
        setShowAdvanced(false);
      } else {
        setError(response?.message || 'Failed to send notification');
      }
    } catch (error) {
      console.error('Error sending notification:', error);
      if (error.message) {
        setError(`Failed to send notification: ${error.message}`);
      } else {
        setError('Network error occurred while sending notification');
      }
    } finally {
      setIsSending(false);
    }
  };

  const handleCloseSnackbar = () => {
    setSuccess(null);
    setSendResult(null);
  };

  return (
    <Box className="notification-send-v2" sx={{ 
      p: 3,
      background: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
      minHeight: '100vh',
      width: '100%'
    }}>
      {/* Header */}
      {/* <Paper 
        elevation={3}
        className="card-entrance"
        sx={{ 
          p: 4, 
          mb: 4, 
          borderRadius: 3,
          background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          color: 'white'
        }}
      >
        <Stack direction="row" alignItems="center" spacing={2}>
          <NotificationsActive sx={{ fontSize: 40 }} />
          <Box>
            <Typography variant="h3" fontWeight="bold">
              Send Notification
            </Typography>
            <Typography variant="h6" sx={{ opacity: 0.9 }}>
              Send push notifications to your users instantly
            </Typography>
          </Box>
        </Stack>
      </Paper> */}

      <Stack spacing={4}>
        {/* Basic Information Card */}
        <Card elevation={3} className="card-entrance hover-lift" sx={{ borderRadius: 3, overflow: 'hidden' }}>
          <Box sx={{ 
            background: 'linear-gradient(90deg, #4facfe 0%, #00f2fe 100%)',
            color: 'white',
            p: 3
          }}>
            <Stack direction="row" alignItems="center" spacing={2}>
              <Smartphone />
              <Typography variant="h5" fontWeight="bold">
                📝 Basic Information
              </Typography>
            </Stack>
          </Box>
          <CardContent sx={{ p: 4, backgroundColor: '#ffffff' }}>
            <Stack spacing={3}>
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={3}>
                <TextField
                  fullWidth
                  label="Notification Title"
                  value={formData.title}
                  onChange={(e) => handleInputChange('title', e.target.value)}
                  placeholder="Enter notification title"
                  required
                  error={formData.title.length > 100}
                  helperText={`${formData.title.length}/100 characters`}
                  variant="outlined"
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: 2,
                      '&:hover': {
                        boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                      }
                    }
                  }}
                />
                {/* <FormControl fullWidth>
                  <InputLabel>Notification Type</InputLabel>
                  <Select
                    value={formData.type}
                    label="Notification Type"
                    onChange={(e) => handleInputChange('type', e.target.value)}
                    sx={{ borderRadius: 2 }}
                  >
                    {NotificationTypes.map((type) => (
                      <MenuItem key={type.value} value={type.value}>
                        {type.label}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl> */}
              </Stack>

              <TextField
                fullWidth
                multiline
                rows={4}
                label="Message Body"
                value={formData.body}
                onChange={(e) => handleInputChange('body', e.target.value)}
                placeholder="Enter notification message"
                required
                error={formData.body.length > 500}
                helperText={`${formData.body.length}/500 characters`}
                variant="outlined"
                sx={{
                  '& .MuiOutlinedInput-root': {
                    borderRadius: 2,
                    '&:hover': {
                      boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                    }
                  }
                }}
              />
            </Stack>
          </CardContent>
        </Card>

        {/* Target Audience Card */}
        <Card elevation={3} className="card-entrance hover-lift" sx={{ borderRadius: 3, overflow: 'hidden' }}>
          <Box sx={{ 
            background: 'linear-gradient(90deg, #a8edea 0%, #fed6e3 100%)',
            color: '#333',
            p: 3
          }}>
            <Stack direction="row" alignItems="center" spacing={2}>
              <Group />
              <Typography variant="h5" fontWeight="bold">
                🎯 Target Audience
              </Typography>
            </Stack>
          </Box>
          <CardContent sx={{ p: 4, backgroundColor: '#ffffff' }}>
            <Stack spacing={3}>
              <Stack direction={{ xs: 'column', md: 'row' }} spacing={3}>
                <FormControl fullWidth>
                  <InputLabel>Target Type</InputLabel>
                  <Select
                    value={formData.target_type}
                    label="Target Type"
                    onChange={(e) => handleInputChange('target_type', e.target.value)}
                    sx={{ borderRadius: 2 }}
                  >
                    <MenuItem value="all_users">
                      <Stack direction="row" alignItems="center" spacing={1}>
                        <Group />
                        <Typography>All Users</Typography>
                      </Stack>
                    </MenuItem>
                    <MenuItem value="specific_user">
                      <Stack direction="row" alignItems="center" spacing={1}>
                        <Person />
                        <Typography>Specific User</Typography>
                      </Stack>
                    </MenuItem>
                  </Select>
                </FormControl>

                {formData.target_type === 'specific_user' && (
                  <Autocomplete
                    fullWidth
                    options={users}
                    getOptionLabel={(option) => `${option.display_name} (${option.id})`}
                    value={selectedUser}
                    onChange={(_, newValue) => handleUserSelect(newValue)}
                    loading={isLoading}
                    renderInput={(params) => (
                      <TextField
                        {...params}
                        label="Select User"
                        placeholder="Search users..."
                        required
                        sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                        InputProps={{
                          ...params.InputProps,
                          endAdornment: (
                            <>
                              {isLoading ? <CircularProgress color="inherit" size={20} /> : null}
                              {params.InputProps.endAdornment}
                            </>
                          ),
                        }}
                      />
                    )}
                  />
                )}
              </Stack>

              {formData.target_type === 'specific_user' && (
                <TextField
                  fullWidth
                  label="User ID (Manual Entry)"
                  type="number"
                  value={formData.target_user_id || ''}
                  onChange={(e) => handleInputChange('target_user_id', e.target.value ? parseInt(e.target.value) : null)}
                  placeholder="Or enter user ID manually"
                  helperText="You can either select from the dropdown above or enter user ID manually"
                  variant="outlined"
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                />
              )}
            </Stack>
          </CardContent>
        </Card>

        {/* Advanced Options Card */}
        <Card elevation={3} className="card-entrance hover-lift" sx={{ borderRadius: 3, overflow: 'hidden' }}>
          {/* <Box sx={{ 
            background: 'linear-gradient(90deg, #ffecd2 0%, #fcb69f 100%)',
            color: '#333',
            p: 3
          }}>
            <Stack direction="row" alignItems="center" spacing={2}>
              <Settings />
              <Typography variant="h5" fontWeight="bold">
                ⚙️ Advanced Options
              </Typography>
              <FormControlLabel
                control={
                  <Switch
                    checked={showAdvanced}
                    onChange={(e) => setShowAdvanced(e.target.checked)}
                    color="primary"
                  />
                }
                label="Enable"
                sx={{ ml: 'auto' }}
              />
            </Stack>
          </Box> */}
          {showAdvanced && (
            <CardContent sx={{ p: 4, backgroundColor: '#ffffff' }}>
              <Stack spacing={3}>
                <FormControl fullWidth>
                  <InputLabel>Deep Link Screen</InputLabel>
                  <Select
                    value={formData.deep_link_screen}
                    label="Deep Link Screen"
                    onChange={(e) => handleInputChange('deep_link_screen', e.target.value)}
                    sx={{ borderRadius: 2 }}
                  >
                    {DeepLinkScreens.map((screen) => (
                      <MenuItem key={screen.value} value={screen.value}>
                        {screen.label}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <TextField
                  fullWidth
                  multiline
                  rows={3}
                  label="Deep Link Data (JSON)"
                  value={formData.deep_link_data}
                  onChange={(e) => handleInputChange('deep_link_data', e.target.value)}
                  placeholder='{"key": "value", "screen_data": "example"}'
                  helperText="Optional JSON data to pass with the deep link"
                  variant="outlined"
                  sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                />
              </Stack>
            </CardContent>
          )}
        </Card>

        {/* Action Buttons */}
        <Paper elevation={3} className="card-entrance" sx={{ p: 4, borderRadius: 3 }}>
          <Stack direction="row" spacing={3} justifyContent="center">
            <Button
              variant="contained"
              size="large"
              onClick={handleSend}
              disabled={isSending}
              className="smooth-transition"
              startIcon={isSending ? <CircularProgress size={20} /> : <Send />}
              sx={{ 
                minWidth: 200,
                py: 2,
                background: 'linear-gradient(45deg, #FE6B8B 30%, #FF8E53 90%)',
                borderRadius: 3,
                boxShadow: '0 3px 5px 2px rgba(255, 105, 135, .3)',
                '&:hover': {
                  background: 'linear-gradient(45deg, #FE6B8B 60%, #FF8E53 100%)',
                  boxShadow: '0 6px 10px 4px rgba(255, 105, 135, .3)',
                }
              }}
            >
              {isSending ? 'Sending...' : 'Send Notification'}
            </Button>
          </Stack>
        </Paper>

        {/* Error Display */}
        {error && (
          <Alert 
            severity="error" 
            onClose={() => setError(null)}
            className="slide-in-up"
            sx={{ borderRadius: 2 }}
          >
            {error}
          </Alert>
        )}

        {/* Success Statistics */}
        {sendResult && (
          <Card 
            elevation={3}
            className="scale-in"
            sx={{ 
              borderRadius: 3,
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white'
            }}
          >
            <CardContent sx={{ p: 4 }}>
              <Stack spacing={2}>
                <Typography variant="h5" fontWeight="bold">
                  ✅ Notification Sent Successfully!
                </Typography>
                <Stack direction="row" spacing={2} flexWrap="wrap">
                  <Chip
                    icon={<Info />}
                    label={`Notification ID: ${sendResult.notification_id}`}
                    sx={{ backgroundColor: 'rgba(255,255,255,0.2)', color: 'white' }}
                  />
                  <Chip
                    label={`✅ Sent: ${sendResult.sent_count}`}
                    sx={{ backgroundColor: '#4caf50', color: 'white' }}
                  />
                  <Chip
                    label={`❌ Failed: ${sendResult.failed_count}`}
                    sx={{ backgroundColor: '#f44336', color: 'white' }}
                  />
                  <Chip
                    label={`👥 Total Recipients: ${sendResult.total_recipients}`}
                    sx={{ backgroundColor: '#2196f3', color: 'white' }}
                  />
                </Stack>
              </Stack>
            </CardContent>
          </Card>
        )}
      </Stack>

      {/* Success Snackbar */}
      <Snackbar
        open={!!success}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <Alert onClose={handleCloseSnackbar} severity="success" sx={{ width: '100%' }}>
          {success}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default NotificationSend;