import React, { useState, useEffect } from 'react';
import {
  Card,
  CardContent,
  Typography,
  Box,
  Grid,
  LinearProgress,
  Chip,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Alert
} from '@mui/material';
import {
  TrendingUp,
  TrendingDown,
  Timeline,
  Group,
  Stars,
  School,
  Assignment,
  AccessTime
} from '@mui/icons-material';

interface PointSystemStats {
  total_points_awarded: number;
  total_points_spent: number;
  active_users_this_week: number;
  weekly_limit_reached_users: number;
  total_users: number;
  avg_points_per_user: number;
  ai_tutor_sessions_today: number;
  ai_tutor_minutes_used: number;
  top_earners: Array<{
    user_name: string;
    points: number;
  }>;
  recent_activities: Array<{
    type: string;
    description: string;
    timestamp: string;
    points: number;
  }>;
}

const PointSystemStatistics: React.FC = () => {
  const [stats, setStats] = useState<PointSystemStats | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchPointSystemStats();
  }, []);

  const fetchPointSystemStats = async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      // This would be implemented with your admin API
      // const response = await adminApiClient.get('/admin/point-system/stats');
      // setStats(response.data);
      
      // Mock data for now
      setStats({
        total_points_awarded: 125750,
        total_points_spent: 23400,
        active_users_this_week: 67,
        weekly_limit_reached_users: 23,
        total_users: 150,
        avg_points_per_user: 838,
        ai_tutor_sessions_today: 12,
        ai_tutor_minutes_used: 156,
        top_earners: [
          { user_name: 'Alice Johnson', points: 3250 },
          { user_name: 'Bob Smith', points: 2980 },
          { user_name: 'Carol Davis', points: 2750 },
          { user_name: 'David Wilson', points: 2650 },
          { user_name: 'Eva Brown', points: 2500 }
        ],
        recent_activities: [
          {
            type: 'quiz_completion',
            description: 'High score quiz completion bonus',
            timestamp: '2024-01-15T10:30:00Z',
            points: 500
          },
          {
            type: 'ai_tutor',
            description: 'AI Tutor session (15 minutes)',
            timestamp: '2024-01-15T10:15:00Z',
            points: -750
          },
          {
            type: 'quiz_completion',
            description: 'No-skip bonus quiz completion',
            timestamp: '2024-01-15T09:45:00Z',
            points: 400
          }
        ]
      });
    } catch (error) {
      console.error('Failed to fetch point system stats:', error);
      setError('Failed to load point system statistics');
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading || !stats) {
    return (
      <Card>
        <CardContent>
          <Box>
            <LinearProgress />
            <Typography variant="body2" sx={{ mt: 2, textAlign: 'center' }}>
              Loading point system statistics...
            </Typography>
          </Box>
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Alert severity="error">
        {error}
      </Alert>
    );
  }

  const weeklyActivePercentage = (stats.active_users_this_week / stats.total_users) * 100;
  const limitReachedPercentage = (stats.weekly_limit_reached_users / stats.active_users_this_week) * 100;
  const pointsSpentPercentage = (stats.total_points_spent / stats.total_points_awarded) * 100;

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        <Timeline sx={{ mr: 1, verticalAlign: 'middle' }} />
        Point System Statistics
      </Typography>

      <Box sx={{
        display: 'grid',
        gridTemplateColumns: {
          xs: '1fr',
          md: 'repeat(2, 1fr)',
          lg: 'repeat(4, 1fr)'
        },
        gap: 3,
        mb: 3
      }}>
        {/* Overview Cards */}
        <Box>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" justifyContent="space-between">
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    Total Points Awarded
                  </Typography>
                  <Typography variant="h5">
                    {stats.total_points_awarded.toLocaleString()}
                  </Typography>
                </Box>
                <TrendingUp color="success" sx={{ fontSize: 40 }} />
              </Box>
            </CardContent>
          </Card>
        </Box>

        <Box>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" justifyContent="space-between">
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    Points Spent
                  </Typography>
                  <Typography variant="h5">
                    {stats.total_points_spent.toLocaleString()}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    {pointsSpentPercentage.toFixed(1)}% of awarded
                  </Typography>
                </Box>
                <TrendingDown color="primary" sx={{ fontSize: 40 }} />
              </Box>
            </CardContent>
          </Card>
        </Box>

        <Box>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" justifyContent="space-between">
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    Active Users (Week)
                  </Typography>
                  <Typography variant="h5">
                    {stats.active_users_this_week}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    {weeklyActivePercentage.toFixed(1)}% of total
                  </Typography>
                </Box>
                <Group color="info" sx={{ fontSize: 40 }} />
              </Box>
            </CardContent>
          </Card>
        </Box>

        <Box>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" justifyContent="space-between">
                <Box>
                  <Typography color="textSecondary" gutterBottom>
                    Avg Points/User
                  </Typography>
                  <Typography variant="h5">
                    {stats.avg_points_per_user.toLocaleString()}
                  </Typography>
                </Box>
                <Stars color="warning" sx={{ fontSize: 40 }} />
              </Box>
            </CardContent>
          </Card>
        </Box>
      </Box>

      <Box sx={{
        display: 'grid',
        gridTemplateColumns: {
          xs: '1fr',
          md: 'repeat(2, 1fr)'
        },
        gap: 3
      }}>
        {/* AI Tutor Usage */}
        <Box>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                <School sx={{ mr: 1, verticalAlign: 'middle' }} />
                AI Tutor Usage Today
              </Typography>
              <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 2 }}>
                <Box>
                  <Typography color="textSecondary">Sessions Started</Typography>
                  <Typography variant="h4" color="primary">
                    {stats.ai_tutor_sessions_today}
                  </Typography>
                </Box>
                <Box>
                  <Typography color="textSecondary">Minutes Used</Typography>
                  <Typography variant="h4" color="primary">
                    {stats.ai_tutor_minutes_used}
                  </Typography>
                </Box>
              </Box>
              <Box mt={2}>
                <Typography variant="body2" color="textSecondary">
                  Average session: {stats.ai_tutor_sessions_today > 0 
                    ? (stats.ai_tutor_minutes_used / stats.ai_tutor_sessions_today).toFixed(1) 
                    : 0} minutes
                </Typography>
              </Box>
            </CardContent>
          </Card>
        </Box>

        {/* Weekly Limits */}
        <Box>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                <Assignment sx={{ mr: 1, verticalAlign: 'middle' }} />
                Weekly Limits Status
              </Typography>
              <Box mb={2}>
                <Box display="flex" justifyContent="space-between" mb={1}>
                  <Typography variant="body2">Users Reached Limit</Typography>
                  <Typography variant="body2">
                    {stats.weekly_limit_reached_users}/{stats.active_users_this_week}
                  </Typography>
                </Box>
                <LinearProgress 
                  variant="determinate" 
                  value={limitReachedPercentage} 
                  color={limitReachedPercentage > 70 ? 'warning' : 'success'}
                />
              </Box>
              <Box display="flex" gap={1} flexWrap="wrap">
                <Chip 
                  label={`${limitReachedPercentage.toFixed(1)}% at limit`}
                  color={limitReachedPercentage > 70 ? 'warning' : 'success'}
                  size="small"
                />
                <Chip 
                  label={`${(100 - limitReachedPercentage).toFixed(1)}% available`}
                  variant="outlined"
                  size="small"
                />
              </Box>
            </CardContent>
          </Card>
        </Box>

        {/* Top Earners */}
        <Box>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Top Point Earners
              </Typography>
              <List dense>
                {stats.top_earners.map((user, index) => (
                  <ListItem key={index}>
                    <ListItemIcon>
                      <Chip 
                        label={index + 1} 
                        color={index === 0 ? 'warning' : index === 1 ? 'default' : 'primary'}
                        size="small"
                      />
                    </ListItemIcon>
                    <ListItemText 
                      primary={user.user_name}
                      secondary={`${user.points.toLocaleString()} points`}
                    />
                  </ListItem>
                ))}
              </List>
            </CardContent>
          </Card>
        </Box>

        {/* Recent Activities */}
        <Box>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                <AccessTime sx={{ mr: 1, verticalAlign: 'middle' }} />
                Recent Point Activities
              </Typography>
              <List dense>
                {stats.recent_activities.map((activity, index) => (
                  <ListItem key={index}>
                    <ListItemIcon>
                      {activity.points > 0 ? (
                        <TrendingUp color="success" />
                      ) : (
                        <TrendingDown color="primary" />
                      )}
                    </ListItemIcon>
                    <ListItemText 
                      primary={activity.description}
                      secondary={new Date(activity.timestamp).toLocaleString()}
                    />
                    <Chip 
                      label={`${activity.points > 0 ? '+' : ''}${activity.points}`}
                      color={activity.points > 0 ? 'success' : 'primary'}
                      size="small"
                    />
                  </ListItem>
                ))}
              </List>
            </CardContent>
          </Card>
        </Box>
      </Box>
    </Box>
  );
};

export default PointSystemStatistics;