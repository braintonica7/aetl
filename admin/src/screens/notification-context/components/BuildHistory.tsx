import React from 'react';
import {
  Paper,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  Box,
  IconButton,
  Tooltip
} from '@mui/material';
import { Refresh as RefreshIcon } from '@mui/icons-material';

interface BuildHistoryProps {
  builds: any[];
  onRefresh: () => void;
}

const BuildHistory: React.FC<BuildHistoryProps> = ({ builds, onRefresh }) => {
  return (
    <Paper sx={{ p: 3 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6">
          📜 Recent Build History
        </Typography>
        <Tooltip title="Refresh">
          <IconButton size="small" onClick={onRefresh}>
            <RefreshIcon />
          </IconButton>
        </Tooltip>
      </Box>

      {builds.length === 0 ? (
        <Box sx={{ textAlign: 'center', py: 4 }}>
          <Typography color="text.secondary">
            No build history available
          </Typography>
        </Box>
      ) : (
        <TableContainer>
          <Table className="build-history-table" size="small">
            <TableHead>
              <TableRow>
                <TableCell>Time</TableCell>
                <TableCell>Type</TableCell>
                <TableCell align="center">Success</TableCell>
                <TableCell align="center">Failed</TableCell>
                <TableCell>Duration</TableCell>
                <TableCell>Triggered By</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {builds.map((build, index) => (
                <TableRow key={index} hover>
                  <TableCell>
                    <Tooltip title={build.completed_at}>
                      <span>{build.relative_time}</span>
                    </Tooltip>
                  </TableCell>
                  <TableCell>
                    <Chip 
                      label={build.type} 
                      size="small" 
                      variant="outlined"
                      color={
                        build.type === 'Full Build' ? 'primary' :
                        build.type === 'Stale Only' ? 'warning' :
                        'default'
                      }
                    />
                  </TableCell>
                  <TableCell align="center">
                    <Chip
                      label={build.processed}
                      size="small"
                      color="success"
                      sx={{ minWidth: 50 }}
                    />
                  </TableCell>
                  <TableCell align="center">
                    {build.failed > 0 ? (
                      <Chip
                        label={build.failed}
                        size="small"
                        color="error"
                        sx={{ minWidth: 50 }}
                      />
                    ) : (
                      <Chip
                        label="0"
                        size="small"
                        variant="outlined"
                        sx={{ minWidth: 50 }}
                      />
                    )}
                  </TableCell>
                  <TableCell>{build.duration_formatted}</TableCell>
                  <TableCell>
                    <Chip 
                      label={build.triggered_by} 
                      size="small"
                      variant="outlined"
                      color={build.triggered_by === 'Cron' ? 'default' : 'info'}
                    />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Paper>
  );
};

export default BuildHistory;
