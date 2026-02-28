import React from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  IconButton,
  Tooltip
} from '@mui/material';
import {
  Visibility as VisibilityIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon
} from '@mui/icons-material';
import { BatchHistoryRecord } from '../types';

interface SendHistoryProps {
  history: BatchHistoryRecord[];
  onViewDetails?: (record: BatchHistoryRecord) => void;
}

const SendHistory: React.FC<SendHistoryProps> = ({ history, onViewDetails }) => {
  if (!history || history.length === 0) {
    return (
      <Card>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            📜 Send History
          </Typography>
          <Box sx={{ textAlign: 'center', py: 4 }}>
            <Typography variant="body2" color="text.secondary">
              No send history available yet
            </Typography>
          </Box>
        </CardContent>
      </Card>
    );
  }

  const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
  };

  return (
    <Card>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          📜 Send History
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Recent batch notification sends
        </Typography>

        <TableContainer>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell><strong>Date</strong></TableCell>
                <TableCell align="center"><strong>Total</strong></TableCell>
                <TableCell align="center"><strong>Sent</strong></TableCell>
                <TableCell align="center"><strong>Skipped</strong></TableCell>
                <TableCell align="center"><strong>Failed</strong></TableCell>
                <TableCell align="center"><strong>Mode</strong></TableCell>
                <TableCell align="center"><strong>Actions</strong></TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {history.map((record, index) => (
                <TableRow key={index} hover>
                  <TableCell>
                    {formatDate(record.created_at || record.sent_at)}
                  </TableCell>
                  <TableCell align="center">
                    {record.total_processed || 0}
                  </TableCell>
                  <TableCell align="center">
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 0.5 }}>
                      <CheckCircleIcon fontSize="small" color="success" />
                      {record.sent || 0}
                    </Box>
                  </TableCell>
                  <TableCell align="center">
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 0.5 }}>
                      <CancelIcon fontSize="small" color="warning" />
                      {record.skipped || 0}
                    </Box>
                  </TableCell>
                  <TableCell align="center">
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 0.5 }}>
                      <CancelIcon fontSize="small" color="error" />
                      {record.failed || 0}
                    </Box>
                  </TableCell>
                  <TableCell align="center">
                    <Chip
                      label={record.dry_run ? 'Dry Run' : 'Live'}
                      size="small"
                      color={record.dry_run ? 'default' : 'primary'}
                    />
                  </TableCell>
                  <TableCell align="center">
                    <Tooltip title="View Details">
                      <IconButton
                        size="small"
                        onClick={() => onViewDetails && onViewDetails(record)}
                      >
                        <VisibilityIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </CardContent>
    </Card>
  );
};

export default SendHistory;
