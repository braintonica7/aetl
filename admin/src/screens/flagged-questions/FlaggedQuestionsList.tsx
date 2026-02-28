import React, { useState } from 'react';
import {
  List,
  Datagrid,
  TextField,
  ImageField,
  DateField,
  NumberField,
  EditButton,
  Button,
  Filter,
  TextInput,
  useRefresh,
  ReferenceField,
  useRecordContext,
  usePermissions,
  useResourceContext,
  useNotify,
  useRedirect,
  SelectInput,
  BooleanField
} from 'react-admin';
import { 
  Drawer, 
  Chip, 
  Box, 
  Typography, 
  IconButton,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  DialogContentText
} from '@mui/material';
import { 
  Flag as FlagIcon, 
  CheckCircle as ResolveIcon,
  Visibility as ViewIcon,
  Close as CloseIcon 
} from '@mui/icons-material';
import * as apiClient from '../../common/apiClient';
import { processPermissions, isAdminUser } from "../../common/roleUtils";
import './style.css';

const FlaggedQuestionsFilter = (props) => (
  <Filter {...props} variant='outlined'>
    <TextInput source='q' placeholder='Search by question text...' alwaysOn />
    <SelectInput
      source='level'
      choices={[
        { id: 'Elementary', name: 'Elementary' },
        { id: 'Moderate', name: 'Moderate' },
        { id: 'Advance', name: 'Advance' }
      ]}
      alwaysOn
    />
  </Filter>
);

const QuestionDetailsDialog = ({ open, onClose, record }) => {
  if (!record) return null;

  return (
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle>
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <Typography variant="h6">Question Details</Typography>
          <IconButton onClick={onClose}>
            <CloseIcon />
          </IconButton>
        </Box>
      </DialogTitle>
      <DialogContent>
        <Box mb={2}>
          <Typography variant="subtitle2" color="textSecondary">Question ID:</Typography>
          <Typography variant="body1">{record.id}</Typography>
        </Box>
        
        {record.question_img_url && (
          <Box mb={2}>
            <Typography variant="subtitle2" color="textSecondary">Question Image:</Typography>
            <img 
              src={record.question_img_url} 
              alt="Question" 
              style={{ maxWidth: '100%', height: 'auto', marginTop: 8 }}
            />
          </Box>
        )}
        
        {record.question_text && (
          <Box mb={2}>
            <Typography variant="subtitle2" color="textSecondary">Question Text:</Typography>
            <Typography variant="body1">{record.question_text}</Typography>
          </Box>
        )}

        <Box mb={2}>
          <Typography variant="subtitle2" color="textSecondary">Difficulty Level:</Typography>
          <Typography variant="body1">{record.level || 'Not specified'}</Typography>
        </Box>

        <Box mb={2}>
          <Typography variant="subtitle2" color="textSecondary">Correct Option:</Typography>
          <Typography variant="body1">{record.correct_option || 'Not specified'}</Typography>
        </Box>

        <Box mb={2}>
          <Typography variant="subtitle2" color="textSecondary">Reported By:</Typography>
          <Typography variant="body1">{record.reporter_name || 'Unknown'}</Typography>
          {record.reporter_email && (
            <Typography variant="body2" color="textSecondary">({record.reporter_email})</Typography>
          )}
        </Box>

        <Box mb={2}>
          <Typography variant="subtitle2" color="textSecondary">Reported Date:</Typography>
          <Typography variant="body1">
            {record.reported_date ? new Date(record.reported_date).toLocaleString() : 'Not available'}
          </Typography>
        </Box>

        {record.flag_reason && (
          <Box mb={2}>
            <Typography variant="subtitle2" color="textSecondary">Flag Reason:</Typography>
            <Typography variant="body1" sx={{ color: 'error.main', fontWeight: 500 }}>
              {record.flag_reason}
            </Typography>
          </Box>
        )}

        {record.solution && (
          <Box mb={2}>
            <Typography variant="subtitle2" color="textSecondary">Solution:</Typography>
            <Typography variant="body1">{record.solution}</Typography>
          </Box>
        )}
      </DialogContent>
    </Dialog>
  );
};

const UnflagButton = ({ record, onSuccess }) => {
  const notify = useNotify();
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleUnflag = async () => {
    setLoading(true);
    try {
      // First check for JWT access token, fallback to old token
      let token = localStorage.getItem('jwt_access_token');
      if (!token) {
        token = localStorage.getItem('token');
      }
      
      const authHeader = token ? `Bearer ${token}` : '';
      const response = await fetch(`${apiClient.APIUrl}/question/${record.id}/unflag`, {
        method: 'PUT',
        headers: {
          'Authorization': authHeader,
          'Content-Type': 'application/json'
        }
      });

      const result = await response.json();
      
      if (response.ok && result.success) {
        notify('Question unflagged successfully', { type: 'success' });
        onSuccess();
      } else {
        notify(result.message || 'Failed to unflag question', { type: 'error' });
      }
    } catch (error) {
      console.error('Error unflagging question:', error);
      notify('Error unflagging question', { type: 'error' });
    } finally {
      setLoading(false);
      setConfirmOpen(false);
    }
  };

  return (
    <>
      <Tooltip title="Mark as resolved">
        <IconButton
          onClick={() => setConfirmOpen(true)}
          color="success"
          size="small"
          disabled={loading}
        >
          <ResolveIcon />
        </IconButton>
      </Tooltip>

      <Dialog open={confirmOpen} onClose={() => setConfirmOpen(false)}>
        <DialogTitle>Confirm Resolution</DialogTitle>
        <DialogContent>
          <DialogContentText>
            Are you sure you want to mark this question as resolved? This will remove it from the flagged questions list and make it available in quizzes again.
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setConfirmOpen(false)} color="primary">
            Cancel
          </Button>
          <Button onClick={handleUnflag} color="success" disabled={loading}>
            {loading ? 'Processing...' : 'Resolve'}
          </Button>
        </DialogActions>
      </Dialog>
    </>
  );
};

const ViewButton = (props) => {
  const record = useRecordContext();
  return (
    <Tooltip title="View details">
      <IconButton
        onClick={() => props.onClick(record)}
        color="primary"
        size="small"
      >
        <ViewIcon />
      </IconButton>
    </Tooltip>
  );
};

const StatusChip = () => {
  const record = useRecordContext();
  
  const getStatusColor = (status) => {
    switch (status) {
      case 'reported':
        return 'error';
      case 'corrected':
        return 'success';
      case 'dismissed':
        return 'default';
      default:
        return 'warning';
    }
  };

  return (
    <Chip 
      label={record.report_status || 'Reported'}
      color={getStatusColor(record.report_status)}
      size="small"
      icon={<FlagIcon />}
    />
  );
};

const UnflagButtonWrapper = (props) => {
  const record = useRecordContext();
  return <UnflagButton record={record} onSuccess={props.onSuccess} />;
};

export const FlaggedQuestionsList = (props) => {
  const refresh = useRefresh();
  const { permissions } = usePermissions();
  const resource = useResourceContext();
  const [selectedQuestion, setSelectedQuestion] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);

  // Use utility function to process permissions with role-based restrictions
  const propsObj = processPermissions(permissions, resource);

  // Only admin users should be able to access flagged questions
  const allowAccess = isAdminUser();

  if (!allowAccess) {
    return (
      <Box p={3}>
        <Typography variant="h6" color="error">
          Access Denied
        </Typography>
        <Typography>
          You don't have permission to view flagged questions. This feature is only available to administrators.
        </Typography>
      </Box>
    );
  }

  const handleViewDetails = (record) => {
    setSelectedQuestion(record);
    setDetailsOpen(true);
  };

  const handleCloseDetails = () => {
    setDetailsOpen(false);
    setSelectedQuestion(null);
  };

  const handleUnflagSuccess = () => {
    refresh();
  };

  return (
    <>
      <List
        title='Flagged Questions'
        {...props}
        sort={{ field: 'reported_date', order: 'DESC' }}
        filters={<FlaggedQuestionsFilter />}
        perPage={25}
      >
        <Datagrid rowClick={false} bulkActionButtons={false}>
          <NumberField source='id' label='Question ID' />
          <ImageField source='question_img_url' label='Question Image' />
          <TextField source='level' label='Difficulty' />
          <TextField source='correct_option' label='Correct Answer' />
          <ReferenceField source='subject_id' reference='subject' link={false} label='Subject'>
            <TextField source='subject' />
          </ReferenceField>
          <TextField source='flag_reason' label='Flag Reason' />
          <TextField source='reporter_name' label='Reported By' />
          <DateField source='reported_date' label='Reported Date' showTime />
          <StatusChip />
          <ViewButton onClick={handleViewDetails} />
          <UnflagButtonWrapper onSuccess={handleUnflagSuccess} />
        </Datagrid>
      </List>

      <QuestionDetailsDialog
        open={detailsOpen}
        onClose={handleCloseDetails}
        record={selectedQuestion}
      />
    </>
  );
};