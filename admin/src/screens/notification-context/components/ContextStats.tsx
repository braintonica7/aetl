import React from 'react';
import {
  Card,
  CardContent,
  Typography,
  Box,
  Skeleton,
  Chip
} from '@mui/material';
import {
  Storage,
  CheckCircle,
  Schedule,
  Warning,
  Error as ErrorIcon,
  Speed
} from '@mui/icons-material';

interface ContextStatsProps {
  stats: any;
  isLoading: boolean;
}

const ContextStats: React.FC<ContextStatsProps> = ({ stats, isLoading }) => {
  if (isLoading || !stats) {
    return (
      <Box sx={{ 
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
        gap: 3,
        mb: 3
      }}>
        {[1, 2, 3, 4, 5, 6].map((i) => (
          <Card key={i}>
            <CardContent>
              <Skeleton variant="text" width="60%" height={30} />
              <Skeleton variant="text" width="40%" height={50} />
            </CardContent>
          </Card>
        ))}
      </Box>
    );
  }

  const statCards = [
    {
      title: 'Total Contexts',
      value: stats.total_contexts?.toLocaleString() || '0',
      icon: <Storage fontSize="large" />,
      color: '#1976d2',
      bgColor: '#e3f2fd'
    },
    {
      title: 'Eligible for Notifications',
      value: stats.eligible_users || '0',
      subtitle: stats.eligible_percentage ? `${stats.eligible_percentage}%` : '',
      icon: <CheckCircle fontSize="large" />,
      color: '#4caf50',
      bgColor: '#e8f5e9'
    },
    {
      title: 'Last Build',
      value: stats.last_build_relative || 'Never',
      subtitle: stats.last_build_duration || '',
      icon: <Schedule fontSize="large" />,
      color: '#9c27b0',
      bgColor: '#f3e5f5'
    },
    {
      title: 'Stale Contexts',
      value: stats.stale_contexts || '0',
      subtitle: stats.stale_percentage ? `${stats.stale_percentage}%` : '',
      icon: <Warning fontSize="large" />,
      color: '#ff9800',
      bgColor: '#fff3e0'
    },
    {
      title: 'Failed Last Build',
      value: stats.last_build_failed || '0',
      subtitle: stats.last_build_total ? `of ${stats.last_build_total}` : '',
      icon: <ErrorIcon fontSize="large" />,
      color: '#f44336',
      bgColor: '#ffebee'
    }
  ];

  return (
    <>
      
      <Box sx={{ 
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
        gap: 3,
        mb: 3
      }}>
        {statCards.map((stat, index) => (
          <Card 
            key={index}
            className="stat-card"
            sx={{ 
              height: '100%',
              borderLeft: `4px solid ${stat.color}`,
              '&:hover': {
                boxShadow: 3
              }
            }}
          >
            <CardContent>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1 }}>
                <Typography variant="body2" color="text.secondary" gutterBottom>
                  {stat.title}
                </Typography>
                <Box
                  sx={{
                    backgroundColor: stat.bgColor,
                    color: stat.color,
                    borderRadius: '8px',
                    p: 0.5,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                >
                  {stat.icon}
                </Box>
              </Box>
              <Typography variant="h4" component="div" sx={{ fontWeight: 600, mb: 0.5 }}>
                {stat.value}
              </Typography>
              {stat.subtitle && (
                <Typography variant="caption" color="text.secondary">
                  {stat.subtitle}
                </Typography>
              )}
            </CardContent>
          </Card>
      ))}
      </Box>
    </>
  );
};

export default ContextStats;
