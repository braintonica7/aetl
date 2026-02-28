import React from 'react';
import { Box, Card, CardContent, Typography, Skeleton } from '@mui/material';
import {
  People as PeopleIcon,
  Notifications as NotificationsIcon,
  Schedule as ScheduleIcon,
  CheckCircle as CheckCircleIcon
} from '@mui/icons-material';
import { BatchStats } from '../types';

interface SendStatsProps {
  stats: BatchStats | null;
  isLoading: boolean;
}

const SendStats: React.FC<SendStatsProps> = ({ stats, isLoading }) => {
  const statsCards = [
    {
      title: 'Eligible Users',
      value: stats?.eligible_users || 0,
      icon: <PeopleIcon fontSize="large" />,
      color: '#1976d2'
    },
    {
      title: 'Notification Types',
      value: stats?.notification_types?.length || 0,
      icon: <NotificationsIcon fontSize="large" />,
      color: '#f57c00'
    },
    {
      title: 'Sent Today',
      value: stats?.total_sent_today || 0,
      icon: <CheckCircleIcon fontSize="large" />,
      color: '#388e3c'
    },
    {
      title: 'Sent This Week',
      value: stats?.total_sent_this_week || 0,
      icon: <ScheduleIcon fontSize="large" />,
      color: '#7b1fa2'
    }
  ];

  return (
    <Box sx={{ 
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
      gap: 3,
      mb: 3
    }}>
      {statsCards.map((stat, index) => (
        <Card sx={{ height: '100%' }} key={index}>
          <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                <Box
                  sx={{
                    backgroundColor: `${stat.color}20`,
                    borderRadius: '8px',
                    p: 1,
                    mr: 2,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                >
                  {React.cloneElement(stat.icon, { sx: { color: stat.color } })}
                </Box>
                <Typography variant="body2" color="text.secondary">
                  {stat.title}
                </Typography>
              </Box>
              {isLoading ? (
                <Skeleton width="60%" height={40} />
              ) : (
                <Typography variant="h4" sx={{ fontWeight: 'bold' }}>
                  {stat.value.toLocaleString()}
                </Typography>
              )}
            </CardContent>
          </Card>
      ))}
    </Box>
  );
};

export default SendStats;
