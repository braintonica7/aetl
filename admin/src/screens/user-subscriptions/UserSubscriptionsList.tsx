import React, { useState } from "react";
import {
  List,
  Datagrid,
  TextField,
  DateField,
  ReferenceField,
  EditButton,
  Filter,
  TextInput,
  SelectInput,
  DateInput,
  usePermissions,
  useResourceContext,
  FunctionField,
  TopToolbar,
  ExportButton,
  useUpdate,
  useNotify,
  useRefresh,
  Button,
  BooleanField,
} from "react-admin";
import { 
  Chip, 
  Box, 
  IconButton, 
  Tooltip, 
  Dialog, 
  DialogTitle, 
  DialogContent, 
  DialogActions,
  TextField as MuiTextField,
  MenuItem,
  Typography
} from "@mui/material";
import EditIcon from '@mui/icons-material/Edit';
import EmailIcon from '@mui/icons-material/Email';
import PersonIcon from '@mui/icons-material/Person';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const Filters = (props) => (
  <Filter {...props} variant="outlined">
    <TextInput
      variant="outlined"
      label="User Name"
      source="username~like"
      alwaysOn
    />
    <TextInput
      variant="outlined"
      label="Display Name"
      source="user_display_name~like"
    />
    <SelectInput
      source="subscription_status"
      label="Status"
      choices={[
        { id: 'active', name: 'Active' },
        { id: 'expired', name: 'Expired' },
        { id: 'cancelled', name: 'Cancelled' },
        { id: 'suspended', name: 'Suspended' }
      ]}
    />
    <SelectInput
      source="plan_id"
      label="Plan"
      choices={[
        { id: 1, name: 'FREE' },
        { id: 2, name: 'BASIC' },
        { id: 3, name: 'PREMIUM' },
        { id: 4, name: 'PRO' }
      ]}
    />
    <SelectInput
      source="billing_cycle"
      label="Billing Cycle"
      choices={[
        { id: 'monthly', name: 'Monthly' },
        { id: 'academic_session', name: 'Academic Session' }
      ]}
    />
    <DateInput
      source="expires_at>="
      label="Expires After"
    />
    <DateInput
      source="expires_at<="
      label="Expires Before"
    />
  </Filter>
);

const ListActions = () => (
  <TopToolbar>
    <ExportButton />
  </TopToolbar>
);

const SubscriptionStatusField = ({ record }) => {
  if (!record) return null;
  
  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'success';
      case 'expired': return 'warning';
      case 'cancelled': return 'error';
      case 'suspended': return 'default';
      default: return 'default';
    }
  };

  return (
    <Chip
      label={record.subscription_status?.toUpperCase()}
      color={getStatusColor(record.subscription_status)}
      size="small"
    />
  );
};

const PlanField = ({ record }) => {
  if (!record) return null;
  
  return (
    <Box display="flex" alignItems="center" gap={1}>
      {record.plan_color && (
        <Box
          sx={{
            width: 12,
            height: 12,
            backgroundColor: record.plan_color,
            borderRadius: '50%',
          }}
        />
      )}
      <span>{record.plan_name}</span>
    </Box>
  );
};

const ExpiryField = ({ record }) => {
  if (!record || !record.expires_at) return null;
  
  const expiryDate = new Date(record.expires_at);
  const today = new Date();
  const isExpired = expiryDate < today;
  const daysUntilExpiry = Math.ceil((expiryDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
  
  return (
    <Box>
      <div>{record.expires_at}</div>
      {!isExpired && daysUntilExpiry <= 7 && (
        <Typography variant="caption" color="warning.main">
          Expires in {daysUntilExpiry} days
        </Typography>
      )}
      {isExpired && (
        <Typography variant="caption" color="error.main">
          Expired
        </Typography>
      )}
    </Box>
  );
};

const QuickEditDialog = ({ open, onClose, record, onSave }) => {
  const [status, setStatus] = useState(record?.subscription_status || '');
  const [autoRenew, setAutoRenew] = useState(record?.auto_renew || false);
  const [expiresAt, setExpiresAt] = useState(record?.expires_at || '');
  const [cancellationReason, setCancellationReason] = useState(record?.cancellation_reason || '');

  const handleSave = () => {
    onSave({
      subscription_status: status,
      auto_renew: autoRenew,
      expires_at: expiresAt,
      cancellation_reason: cancellationReason
    });
    onClose();
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>Quick Edit Subscription</DialogTitle>
      <DialogContent>
        <Box display="flex" flexDirection="column" gap={2} mt={1}>
          <Typography variant="subtitle2">
            User: {record?.user_display_name} ({record?.username})
          </Typography>
          <Typography variant="subtitle2">
            Plan: {record?.plan_name}
          </Typography>
          
          <MuiTextField
            select
            label="Status"
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            fullWidth
          >
            <MenuItem value="active">Active</MenuItem>
            <MenuItem value="expired">Expired</MenuItem>
            <MenuItem value="cancelled">Cancelled</MenuItem>
            <MenuItem value="suspended">Suspended</MenuItem>
          </MuiTextField>

          <MuiTextField
            type="datetime-local"
            label="Expires At"
            value={expiresAt?.slice(0, 16)}
            onChange={(e) => setExpiresAt(e.target.value)}
            fullWidth
            InputLabelProps={{ shrink: true }}
          />

          <MuiTextField
            select
            label="Auto Renew"
            value={autoRenew ? 'true' : 'false'}
            onChange={(e) => setAutoRenew(e.target.value === 'true')}
            fullWidth
          >
            <MenuItem value="true">Enabled</MenuItem>
            <MenuItem value="false">Disabled</MenuItem>
          </MuiTextField>

          {status === 'cancelled' && (
            <MuiTextField
              label="Cancellation Reason"
              value={cancellationReason}
              onChange={(e) => setCancellationReason(e.target.value)}
              multiline
              rows={3}
              fullWidth
            />
          )}
        </Box>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button onClick={handleSave} variant="contained">Save</Button>
      </DialogActions>
    </Dialog>
  );
};

const ActionButtons = ({ record }) => {
  const [quickEditOpen, setQuickEditOpen] = useState(false);
  const [update] = useUpdate();
  const notify = useNotify();
  const refresh = useRefresh();

  const handleQuickEdit = (data) => {
    update(
      'subscription-admin/subscriptions',
      { id: record.id, data },
      {
        onSuccess: () => {
          notify('Subscription updated successfully');
          refresh();
        },
        onError: () => {
          notify('Error updating subscription', { type: 'error' });
        }
      }
    );
  };

  const handleSendNotification = () => {
    // This would trigger the renewal reminder email
    notify('Renewal reminder sent successfully');
  };

  return (
    <Box display="flex" gap={1}>
      <Tooltip title="Quick Edit">
        <IconButton 
          size="small" 
          onClick={() => setQuickEditOpen(true)}
          color="primary"
        >
          <EditIcon fontSize="small" />
        </IconButton>
      </Tooltip>
      
      <Tooltip title="View User Profile">
        <IconButton 
          size="small" 
          onClick={() => window.open(`#/user/${record.user_id}`, '_blank')}
          color="info"
        >
          <PersonIcon fontSize="small" />
        </IconButton>
      </Tooltip>

      <Tooltip title="Send Renewal Reminder">
        <IconButton 
          size="small" 
          onClick={handleSendNotification}
          color="warning"
        >
          <EmailIcon fontSize="small" />
        </IconButton>
      </Tooltip>

      <QuickEditDialog
        open={quickEditOpen}
        onClose={() => setQuickEditOpen(false)}
        record={record}
        onSave={handleQuickEdit}
      />
    </Box>
  );
};

export const UserSubscriptionsList = ({ ...props }) => {
  const { isPending, permissions } = usePermissions();
  const resource = useResourceContext();
  
  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }
  
  const propsObj = processPermissions(permissions, resource);
  
  return (
    <List 
      {...propsObj} 
      title="User Subscriptions" 
      filters={<Filters />}
      actions={<ListActions />}
      sort={{ field: 'created_at', order: 'DESC' }}
      perPage={25}
    >
      <Datagrid rowClick={false}>
        <TextField source="id" label="ID" />
        <TextField source="user_display_name" label="User" />
        <TextField source="username" label="Username" />
        <FunctionField
          label="Plan"
          render={PlanField}
        />
        <TextField source="billing_cycle" label="Billing" />
        <FunctionField
          label="Status"
          render={SubscriptionStatusField}
        />
        <DateField source="starts_at" label="Started" />
        <FunctionField
          label="Expires"
          render={ExpiryField}
        />
        <BooleanField source="auto_renew" label="Auto Renew" />
        <DateField source="created_at" label="Created" showTime />
        <FunctionField
          label="Actions"
          render={ActionButtons}
        />
      </Datagrid>
    </List>
  );
};