// PlanFeaturesEdit.tsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useDataProvider, useNotify, Loading, Title } from 'react-admin';
import {
  Card,
  CardContent,
  Typography,
  Box,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Switch,
  TextField,
  Button,
  Chip,
  FormControlLabel,
  Paper,
  IconButton,
  Tooltip
} from '@mui/material';
import { Save as SaveIcon, ArrowBack as ArrowBackIcon } from '@mui/icons-material';
import { isAdminUser } from '../../common/roleUtils';

interface Feature {
  id: string;
  plan_id: number;
  feature_id: number;
  feature_key: string;
  feature_name: string;
  feature_description: string;
  feature_type: 'quota' | 'boolean' | 'credits';
  reset_cycle: string;
  feature_limit: number | null;
  is_enabled: boolean;
  is_assigned: boolean;
}

interface Plan {
  id: number;
  plan_name: string;
  plan_key: string;
}

export const PlanFeaturesEdit: React.FC = () => {
  const { planId } = useParams<{ planId: string }>();
  const navigate = useNavigate();
  const dataProvider = useDataProvider();
  const notify = useNotify();
  
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [plan, setPlan] = useState<Plan | null>(null);
  const [features, setFeatures] = useState<Feature[]>([]);
  const [changes, setChanges] = useState<{ [key: string]: Partial<Feature> }>({});

  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }

  useEffect(() => {
    if (planId) {
      fetchPlanData();
      fetchFeatures();
    }
  }, [planId]);

  const fetchPlanData = async () => {
    try {
      const result = await dataProvider.getOne('subscription-plans', { id: planId });
      setPlan(result.data);
    } catch (error) {
      notify('Error loading plan data', { type: 'error' });
      console.error('Plan fetch error:', error);
    }
  };

  const fetchFeatures = async () => {
    try {
      setLoading(true);
      const result = await dataProvider.getList(`plan-features/${planId}`, {
        pagination: { page: 1, perPage: 100 },
        sort: { field: 'feature_name', order: 'ASC' },
        filter: {}
      });
      setFeatures(result.data);
    } catch (error) {
      notify('Error loading features', { type: 'error' });
      console.error('Features fetch error:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFeatureToggle = (featureId: string, enabled: boolean) => {
    setChanges(prev => ({
      ...prev,
      [featureId]: {
        ...prev[featureId],
        is_enabled: enabled
      }
    }));
  };

  const handleLimitChange = (featureId: string, limit: string) => {
    const numericLimit = limit === '' ? null : parseInt(limit, 10);
    setChanges(prev => ({
      ...prev,
      [featureId]: {
        ...prev[featureId],
        feature_limit: isNaN(numericLimit!) ? null : numericLimit
      }
    }));
  };

  const getEffectiveFeature = (feature: Feature): Feature => {
    const change = changes[feature.id];
    if (!change) return feature;
    
    return {
      ...feature,
      ...change
    };
  };

  const saveChanges = async () => {
    if (Object.keys(changes).length === 0) {
      notify('No changes to save', { type: 'info' });
      return;
    }

    setSaving(true);
    try {
      const promises = Object.entries(changes).map(([featureId, change]) => {
        const feature = features.find(f => f.id === featureId);
        if (!feature) return Promise.resolve();

        return dataProvider.update(`plan-features/${planId}/${feature.feature_id}`, {
          id: featureId,
          data: change,
          previousData: feature
        });
      });

      await Promise.all(promises);
      notify('Plan features updated successfully', { type: 'success' });
      setChanges({});
      await fetchFeatures(); // Refresh data
    } catch (error) {
      notify('Error saving changes', { type: 'error' });
      console.error('Save error:', error);
    } finally {
      setSaving(false);
    }
  };

  const getFeatureTypeColor = (type: string) => {
    const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'default' } = {
      quota: 'primary',
      boolean: 'secondary',
      credits: 'success'
    };
    return colors[type] || 'default';
  };

  if (loading) {
    return <Loading />;
  }

  const hasChanges = Object.keys(changes).length > 0;

  return (
    <div style={{ padding: '20px' }}>
      <Title title={`Plan Features - ${plan?.plan_name || 'Unknown Plan'}`} />
      
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Box display="flex" alignItems="center">
          <IconButton onClick={() => navigate('/subscription-plans')} sx={{ mr: 1 }}>
            <ArrowBackIcon />
          </IconButton>
          <Typography variant="h4">
            Plan Features: {plan?.plan_name}
          </Typography>
        </Box>
        
        <Button
          variant="contained"
          color="primary"
          startIcon={<SaveIcon />}
          onClick={saveChanges}
          disabled={!hasChanges || saving}
        >
          {saving ? 'Saving...' : 'Save Changes'}
        </Button>
      </Box>

      {hasChanges && (
        <Box mb={2}>
          <Chip label={`${Object.keys(changes).length} unsaved changes`} color="warning" />
        </Box>
      )}

      <Card>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            Feature Assignments
          </Typography>
          
          <TableContainer component={Paper}>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Feature</TableCell>
                  <TableCell>Type</TableCell>
                  <TableCell>Reset Cycle</TableCell>
                  <TableCell>Enabled</TableCell>
                  <TableCell>Limit</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {features.map((feature) => {
                  const effectiveFeature = getEffectiveFeature(feature);
                  const hasChange = changes[feature.id];
                  
                  return (
                    <TableRow 
                      key={feature.id}
                      sx={{ 
                        backgroundColor: hasChange ? 'rgba(255, 193, 7, 0.1)' : 'transparent'
                      }}
                    >
                      <TableCell>
                        <Box>
                          <Typography variant="subtitle2">
                            {feature.feature_name}
                          </Typography>
                          <Typography variant="body2" color="textSecondary">
                            {feature.feature_description}
                          </Typography>
                          <Typography variant="caption" color="textSecondary">
                            Key: {feature.feature_key}
                          </Typography>
                        </Box>
                      </TableCell>
                      
                      <TableCell>
                        <Chip 
                          label={feature.feature_type} 
                          color={getFeatureTypeColor(feature.feature_type)}
                          size="small" 
                        />
                      </TableCell>
                      
                      <TableCell>
                        <Chip 
                          label={feature.reset_cycle} 
                          variant="outlined"
                          size="small" 
                        />
                      </TableCell>
                      
                      <TableCell>
                        <FormControlLabel
                          control={
                            <Switch
                              checked={effectiveFeature.is_enabled}
                              onChange={(e) => handleFeatureToggle(feature.id, e.target.checked)}
                              color="primary"
                            />
                          }
                          label={effectiveFeature.is_enabled ? 'Enabled' : 'Disabled'}
                        />
                      </TableCell>
                      
                      <TableCell>
                        {feature.feature_type === 'boolean' ? (
                          <Typography variant="body2" color="textSecondary">
                            N/A (Boolean feature)
                          </Typography>
                        ) : (
                          <TextField
                            type="number"
                            size="small"
                            value={effectiveFeature.feature_limit?.toString() || ''}
                            onChange={(e) => handleLimitChange(feature.id, e.target.value)}
                            placeholder="Unlimited"
                            helperText={
                              feature.feature_type === 'quota' 
                                ? 'Max allowed per cycle' 
                                : 'Credits available'
                            }
                            InputProps={{
                              style: { width: '120px' }
                            }}
                            disabled={!effectiveFeature.is_enabled}
                          />
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
        </CardContent>
      </Card>
    </div>
  );
};

export default PlanFeaturesEdit;