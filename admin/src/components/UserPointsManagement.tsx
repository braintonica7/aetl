import React, { useState, useEffect } from 'react';
import {
  Card,
  CardContent,
  Typography,
  Box,
  Button,
  TextField,
  Alert,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Grid,
  CircularProgress
} from '@mui/material';
import { Add, Remove, TrendingUp, Person } from '@mui/icons-material';

interface UserPointsData {
  user_id: number;
  user_name: string;
  total_points: number;
  current_week_points: number;
  weekly_limit_reached: boolean;
  last_activity: string;
}

interface PointAdjustment {
  user_id: number;
  points: number;
  reason: string;
  type: 'award' | 'deduct';
}

const UserPointsManagement: React.FC = () => {
  const [users, setUsers] = useState<UserPointsData[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [adjustDialogOpen, setAdjustDialogOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<UserPointsData | null>(null);
  const [adjustment, setAdjustment] = useState<PointAdjustment>({
    user_id: 0,
    points: 0,
    reason: '',
    type: 'award'
  });

  useEffect(() => {
    fetchUserPoints();
  }, []);

  const fetchUserPoints = async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      // This would be implemented with your admin API
      // const response = await adminApiClient.get('/admin/user-points');
      // setUsers(response.data);
      
      // Mock data for now
      setUsers([
        {
          user_id: 1,
          user_name: 'John Doe',
          total_points: 1250,
          current_week_points: 450,
          weekly_limit_reached: false,
          last_activity: '2024-01-15T10:30:00Z'
        },
        {
          user_id: 2,
          user_name: 'Jane Smith',
          total_points: 2100,
          current_week_points: 1500,
          weekly_limit_reached: true,
          last_activity: '2024-01-15T14:20:00Z'
        }
      ]);
    } catch (error) {
      console.error('Failed to fetch user points:', error);
      setError('Failed to load user points data');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAdjustPoints = async () => {
    if (!selectedUser || !adjustment.points || !adjustment.reason) {
      setError('Please fill in all required fields');
      return;
    }

    try {
      // const response = await adminApiClient.post('/admin/user-points/adjust', {
      //   user_id: selectedUser.user_id,
      //   points: adjustment.type === 'deduct' ? -adjustment.points : adjustment.points,
      //   reason: adjustment.reason
      // });

      // Mock successful adjustment
      const pointsChange = adjustment.type === 'deduct' ? -adjustment.points : adjustment.points;
      setUsers(prev => prev.map(user => 
        user.user_id === selectedUser.user_id 
          ? { ...user, total_points: user.total_points + pointsChange }
          : user
      ));

      setAdjustDialogOpen(false);
      setSelectedUser(null);
      setAdjustment({ user_id: 0, points: 0, reason: '', type: 'award' });
      
    } catch (error) {
      console.error('Failed to adjust points:', error);
      setError('Failed to adjust user points');
    }
  };

  const openAdjustDialog = (user: UserPointsData) => {
    setSelectedUser(user);
    setAdjustment({ ...adjustment, user_id: user.user_id });
    setAdjustDialogOpen(true);
  };

  const filteredUsers = users.filter(user =>
    user.user_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.user_id.toString().includes(searchTerm)
  );

  if (isLoading) {
    return (
      <Card>
        <CardContent>
          <Box display="flex" justifyContent="center" alignItems="center" minHeight={300}>
            <CircularProgress />
          </Box>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardContent>
        <Box display="flex" justifyContent="between" alignItems="center" mb={3}>
          <Typography variant="h6" component="h2">
            <Person sx={{ mr: 1, verticalAlign: 'middle' }} />
            User Points Management
          </Typography>
        </Box>

        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        <Box mb={3}>
          <TextField
            fullWidth
            label="Search users..."
            variant="outlined"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Search by name or user ID"
          />
        </Box>

        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>User ID</TableCell>
                <TableCell>Name</TableCell>
                <TableCell align="right">Total Points</TableCell>
                <TableCell align="right">This Week</TableCell>
                <TableCell align="center">Weekly Limit</TableCell>
                <TableCell align="center">Last Activity</TableCell>
                <TableCell align="center">Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {filteredUsers.map((user) => (
                <TableRow key={user.user_id}>
                  <TableCell>{user.user_id}</TableCell>
                  <TableCell>{user.user_name}</TableCell>
                  <TableCell align="right">
                    <Typography variant="h6" color="primary">
                      {user.total_points.toLocaleString()}
                    </Typography>
                  </TableCell>
                  <TableCell align="right">
                    {user.current_week_points.toLocaleString()}
                  </TableCell>
                  <TableCell align="center">
                    <Chip
                      label={user.weekly_limit_reached ? 'Reached' : 'Available'}
                      color={user.weekly_limit_reached ? 'warning' : 'success'}
                      size="small"
                    />
                  </TableCell>
                  <TableCell align="center">
                    {new Date(user.last_activity).toLocaleDateString()}
                  </TableCell>
                  <TableCell align="center">
                    <Button
                      variant="outlined"
                      size="small"
                      onClick={() => openAdjustDialog(user)}
                      startIcon={<TrendingUp />}
                    >
                      Adjust
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>

        {/* Point Adjustment Dialog */}
        <Dialog open={adjustDialogOpen} onClose={() => setAdjustDialogOpen(false)} maxWidth="sm" fullWidth>
          <DialogTitle>
            Adjust Points for {selectedUser?.user_name}
          </DialogTitle>
          <DialogContent>
            <Box sx={{ mt: 2 }}>
              <Box display="flex" gap={2} mb={2}>
                <Button
                  variant={adjustment.type === 'award' ? 'contained' : 'outlined'}
                  onClick={() => setAdjustment({ ...adjustment, type: 'award' })}
                  startIcon={<Add />}
                  color="success"
                >
                  Award Points
                </Button>
                <Button
                  variant={adjustment.type === 'deduct' ? 'contained' : 'outlined'}
                  onClick={() => setAdjustment({ ...adjustment, type: 'deduct' })}
                  startIcon={<Remove />}
                  color="error"
                >
                  Deduct Points
                </Button>
              </Box>
              
              <TextField
                fullWidth
                label="Points Amount"
                type="number"
                value={adjustment.points}
                onChange={(e) => setAdjustment({ ...adjustment, points: parseInt(e.target.value) || 0 })}
                inputProps={{ min: 1 }}
                sx={{ mb: 2 }}
              />
              
              <TextField
                fullWidth
                label="Reason"
                multiline
                rows={3}
                value={adjustment.reason}
                onChange={(e) => setAdjustment({ ...adjustment, reason: e.target.value })}
                placeholder="Explain the reason for this point adjustment..."
                sx={{ mb: 2 }}
              />
              
              <Alert severity="info">
                Current Points: {selectedUser?.total_points.toLocaleString()}
                <br />
                {adjustment.type === 'award' 
                  ? `New Total: ${((selectedUser?.total_points || 0) + adjustment.points).toLocaleString()}`
                  : `New Total: ${((selectedUser?.total_points || 0) - adjustment.points).toLocaleString()}`
                }
              </Alert>
            </Box>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setAdjustDialogOpen(false)}>Cancel</Button>
            <Button 
              onClick={handleAdjustPoints}
              variant="contained"
              disabled={!adjustment.points || !adjustment.reason}
            >
              {adjustment.type === 'award' ? 'Award' : 'Deduct'} Points
            </Button>
          </DialogActions>
        </Dialog>
      </CardContent>
    </Card>
  );
};

export default UserPointsManagement;