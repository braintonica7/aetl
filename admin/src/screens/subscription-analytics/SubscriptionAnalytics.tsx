import React, { useState, useEffect } from "react";
import {
  useDataProvider,
  useNotify,
  Loading,
  Title,
} from "react-admin";
import {
  Card,
  CardContent,
  Typography,
  Box,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  LinearProgress,
} from "@mui/material";
import { isAdminUser } from "../../common/roleUtils";

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];

const StatCard = ({ title, value, subtitle, color = "primary" }) => (
  <Card elevation={3} sx={{ height: '100%' }}>
    <CardContent>
      <Typography color="textSecondary" gutterBottom variant="h6">
        {title}
      </Typography>
      <Typography variant="h4" component="div" color={color}>
        {value}
      </Typography>
      {subtitle && (
        <Typography color="textSecondary" variant="body2">
          {subtitle}
        </Typography>
      )}
    </CardContent>
  </Card>
);

const PlanDistributionChart = ({ data }: { data: Array<{plan_name: string, active_subscriptions: number}> | null }) => {
  if (!data || data.length === 0) return null;

  const total = data.reduce((sum, item) => sum + item.active_subscriptions, 0);

  return (
    <Card elevation={3}>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          Active Subscriptions by Plan
        </Typography>
        <Box sx={{ mt: 2 }}>
          {data.map((plan, index) => {
            const percentage = total > 0 ? (plan.active_subscriptions / total) * 100 : 0;
            return (
              <Box key={index} sx={{ mb: 2 }}>
                <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                  <Typography variant="body2">{plan.plan_name}</Typography>
                  <Typography variant="body2">{plan.active_subscriptions} ({percentage.toFixed(1)}%)</Typography>
                </Box>
                <LinearProgress
                  variant="determinate"
                  value={percentage}
                  sx={{
                    height: 8,
                    borderRadius: 4,
                    backgroundColor: '#f0f0f0',
                    '& .MuiLinearProgress-bar': {
                      backgroundColor: COLORS[index % COLORS.length],
                    },
                  }}
                />
              </Box>
            );
          })}
        </Box>
      </CardContent>
    </Card>
  );
};

const SubscriptionStatusChart = ({ data }: { data: Record<string, number> | null }) => {
  if (!data) return null;

  const statusEntries = Object.entries(data);
  const total = statusEntries.reduce((sum, [, count]) => sum + (count as number), 0);

  return (
    <Card elevation={3}>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          Subscription Status Distribution
        </Typography>
        <Box sx={{ mt: 2 }}>
          {statusEntries.map(([status, count], index) => {
            const countNum = count as number;
            const percentage = total > 0 ? (countNum / total) * 100 : 0;
            const statusColor = status === 'active' ? 'success.main' : 
                               status === 'expired' ? 'warning.main' : 
                               status === 'cancelled' ? 'error.main' : 'grey.500';
            
            return (
              <Box key={index} sx={{ mb: 2 }}>
                <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                  <Typography variant="body2">{status.charAt(0).toUpperCase() + status.slice(1)}</Typography>
                  <Typography variant="body2">{countNum} ({percentage.toFixed(1)}%)</Typography>
                </Box>
                <LinearProgress
                  variant="determinate"
                  value={percentage}
                  sx={{
                    height: 8,
                    borderRadius: 4,
                    backgroundColor: '#f0f0f0',
                    '& .MuiLinearProgress-bar': {
                      backgroundColor: statusColor,
                    },
                  }}
                />
              </Box>
            );
          })}
        </Box>
      </CardContent>
    </Card>
  );
};

const RevenueCard = ({ data }: { data: {
  total_revenue: number;
  total_transactions: number;
  avg_transaction_value?: number;
  period_start?: string;
  period_end?: string;
} | null }) => {
  if (!data) return null;

  return (
    <Card elevation={3}>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          Revenue Summary
        </Typography>
        <Box display="flex" flexDirection="column" gap={2}>
          <Box display="flex" justifyContent="space-between">
            <Typography>Total Revenue:</Typography>
            <Typography variant="h6" color="primary">
              ₹{Number(data.total_revenue || 0).toLocaleString()}
            </Typography>
          </Box>
          <Box display="flex" justifyContent="space-between">
            <Typography>Total Transactions:</Typography>
            <Typography variant="h6">
              {data.total_transactions || 0}
            </Typography>
          </Box>
          <Box display="flex" justifyContent="space-between">
            <Typography>Average Transaction:</Typography>
            <Typography variant="h6">
              ₹{Number(data.avg_transaction_value || 0).toLocaleString()}
            </Typography>
          </Box>
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Typography>Period:</Typography>
            <Typography variant="body2">
              {data.period_start} to {data.period_end}
            </Typography>
          </Box>
        </Box>
      </CardContent>
    </Card>
  );
};

export const SubscriptionAnalytics = () => {
  const [loading, setLoading] = useState(true);
  const [analytics, setAnalytics] = useState(null);
  const [timeRange, setTimeRange] = useState('30');
  const dataProvider = useDataProvider();
  const notify = useNotify();

  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }

  const fetchAnalytics = async (days = '30') => {
    setLoading(true);
    try {
      const endDate = new Date();
      const startDate = new Date();
      startDate.setDate(startDate.getDate() - parseInt(days));

      const result = await dataProvider.getList('subscription-admin/analytics', {
        pagination: { page: 1, perPage: 1 },
        sort: { field: 'id', order: 'ASC' },
        filter: {
          start_date: startDate.toISOString().split('T')[0],
          end_date: endDate.toISOString().split('T')[0]
        }
      });

      setAnalytics(result.data[0] || {});
    } catch (error) {
      notify('Error loading analytics', { type: 'error' });
      console.error('Analytics error:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAnalytics(timeRange);
  }, [timeRange]);

  if (loading) {
    return <Loading />;
  }

  const {
    subscription_counts = {},
    plan_distribution = [],
    revenue_summary = {},
    expiring_subscriptions = 0
  } = analytics || {};

  return (
    <div style={{ padding: '20px' }}>
      <Title title="Subscription Analytics" />
      
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h4" gutterBottom>
          Subscription Analytics
        </Typography>
        
        <FormControl variant="outlined" size="small" style={{ minWidth: 200 }}>
          <InputLabel>Time Range</InputLabel>
          <Select
            value={timeRange}
            onChange={(e) => setTimeRange(e.target.value)}
            label="Time Range"
          >
            <MenuItem value="7">Last 7 days</MenuItem>
            <MenuItem value="30">Last 30 days</MenuItem>
            <MenuItem value="90">Last 90 days</MenuItem>
            <MenuItem value="365">Last year</MenuItem>
          </Select>
        </FormControl>
      </Box>

      {/* Key Metrics */}
      <Box display="flex" flexWrap="wrap" gap={3} mb={4}>
        <Box flex="1 1 250px">
          <StatCard
            title="Active Subscriptions"
            value={subscription_counts.active || 0}
            subtitle="Currently active"
            color="success.main"
          />
        </Box>
        <Box flex="1 1 250px">
          <StatCard
            title="Expired Subscriptions"
            value={subscription_counts.expired || 0}
            subtitle="Expired subscriptions"
            color="warning.main"
          />
        </Box>
        <Box flex="1 1 250px">
          <StatCard
            title="Cancelled Subscriptions"
            value={subscription_counts.cancelled || 0}
            subtitle="User cancellations"
            color="error.main"
          />
        </Box>
        <Box flex="1 1 250px">
          <StatCard
            title="Expiring Soon"
            value={expiring_subscriptions}
            subtitle="Within 7 days"
            color="warning.main"
          />
        </Box>
      </Box>

      {/* Charts */}
      <Box display="flex" flexWrap="wrap" gap={3} mb={4}>
        <Box flex="1 1 400px">
          <PlanDistributionChart data={plan_distribution} />
        </Box>
        <Box flex="1 1 400px">
          <SubscriptionStatusChart data={subscription_counts} />
        </Box>
      </Box>

      {/* Revenue Summary */}
      <Box display="flex" flexWrap="wrap" gap={3}>
        <Box flex="1 1 400px">
          <RevenueCard data={revenue_summary} />
        </Box>
        <Box flex="1 1 400px">
          <Card elevation={3}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Plan Performance
              </Typography>
              <TableContainer>
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell>Plan</TableCell>
                      <TableCell align="right">Active Users</TableCell>
                      <TableCell align="right">Status</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {plan_distribution.map((plan, index) => (
                      <TableRow key={index}>
                        <TableCell>{plan.plan_name}</TableCell>
                        <TableCell align="right">{plan.active_subscriptions}</TableCell>
                        <TableCell align="right">
                          <Chip
                            label={plan.active_subscriptions > 0 ? 'Active' : 'No Users'}
                            color={plan.active_subscriptions > 0 ? 'success' : 'default'}
                            size="small"
                          />
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>
            </CardContent>
          </Card>
        </Box>
      </Box>
    </div>
  );
};