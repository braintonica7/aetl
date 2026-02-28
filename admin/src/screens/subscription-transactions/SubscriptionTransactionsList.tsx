import React from "react";
import {
  List,
  Datagrid,
  TextField,
  DateField,
  NumberField,
  Filter,
  TextInput,
  SelectInput,
  DateInput,
  NumberInput,
  usePermissions,
  useResourceContext,
  FunctionField,
  TopToolbar,
  ExportButton,
  ChipField,
} from "react-admin";
import { Chip, Box, Tooltip } from "@mui/material";
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
      source="payment_status"
      label="Payment Status"
      choices={[
        { id: 'pending', name: 'Pending' },
        { id: 'paid', name: 'Paid' },
        { id: 'failed', name: 'Failed' },
        { id: 'refunded', name: 'Refunded' }
      ]}
    />
    <SelectInput
      source="transaction_type"
      label="Transaction Type"
      choices={[
        { id: 'subscription', name: 'New Subscription' },
        { id: 'renewal', name: 'Renewal' },
        { id: 'upgrade', name: 'Upgrade' },
        { id: 'downgrade', name: 'Downgrade' }
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
    <TextInput
      variant="outlined"
      label="Razorpay Order ID"
      source="razorpay_order_id~like"
    />
    <TextInput
      variant="outlined"
      label="Razorpay Payment ID"
      source="razorpay_payment_id~like"
    />
    <NumberInput
      source="amount_inr>="
      label="Min Amount (₹)"
    />
    <NumberInput
      source="amount_inr<="
      label="Max Amount (₹)"
    />
    <DateInput
      source="paid_at>="
      label="Paid After"
    />
    <DateInput
      source="paid_at<="
      label="Paid Before"
    />
  </Filter>
);

const ListActions = () => (
  <TopToolbar>
    <ExportButton />
  </TopToolbar>
);

const PaymentStatusField = ({ record }) => {
  if (!record) return null;
  
  const getStatusColor = (status) => {
    switch (status) {
      case 'paid': return 'success';
      case 'pending': return 'warning';
      case 'failed': return 'error';
      case 'refunded': return 'info';
      default: return 'default';
    }
  };

  return (
    <Chip
      label={record.payment_status?.toUpperCase()}
      color={getStatusColor(record.payment_status)}
      size="small"
    />
  );
};

const TransactionTypeField = ({ record }) => {
  if (!record) return null;
  
  const getTypeColor = (type) => {
    switch (type) {
      case 'subscription': return 'primary';
      case 'renewal': return 'success';
      case 'upgrade': return 'info';
      case 'downgrade': return 'warning';
      default: return 'default';
    }
  };

  return (
    <Chip
      label={record.transaction_type?.toUpperCase()}
      color={getTypeColor(record.transaction_type)}
      size="small"
      variant="outlined"
    />
  );
};

const AmountField = ({ record }) => {
  if (!record) return null;
  
  return (
    <Box>
      <div style={{ fontWeight: 'bold' }}>₹{Number(record.amount_inr).toLocaleString()}</div>
      {record.net_amount !== record.amount_inr && (
        <div style={{ fontSize: '0.8em', color: '#666' }}>
          Net: ₹{Number(record.net_amount).toLocaleString()}
        </div>
      )}
    </Box>
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

const PaymentDetailsField = ({ record }) => {
  if (!record) return null;
  
  return (
    <Box>
      <div style={{ fontSize: '0.9em' }}>
        Gateway: {record.payment_gateway || 'N/A'}
      </div>
      {record.payment_method && (
        <div style={{ fontSize: '0.8em', color: '#666' }}>
          Method: {record.payment_method}
        </div>
      )}
      {record.razorpay_order_id && (
        <Tooltip title={`Order: ${record.razorpay_order_id}`}>
          <div style={{ fontSize: '0.7em', color: '#999', cursor: 'help' }}>
            Order: {record.razorpay_order_id.substring(0, 12)}...
          </div>
        </Tooltip>
      )}
      {record.razorpay_payment_id && (
        <Tooltip title={`Payment: ${record.razorpay_payment_id}`}>
          <div style={{ fontSize: '0.7em', color: '#999', cursor: 'help' }}>
            Payment: {record.razorpay_payment_id.substring(0, 12)}...
          </div>
        </Tooltip>
      )}
    </Box>
  );
};

const InvoiceField = ({ record }) => {
  if (!record || !record.invoice_number) return null;
  
  return (
    <Chip
      label={record.invoice_number}
      size="small"
      variant="outlined"
      color="info"
    />
  );
};

export const SubscriptionTransactionsList = ({ ...props }) => {
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
      title="Payment Transactions" 
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
        <FunctionField
          label="Type"
          render={TransactionTypeField}
        />
        <TextField source="billing_cycle" label="Billing" />
        <FunctionField
          label="Amount"
          render={AmountField}
        />
        <FunctionField
          label="Status"
          render={PaymentStatusField}
        />
        <FunctionField
          label="Payment Details"
          render={PaymentDetailsField}
        />
        <DateField source="paid_at" label="Paid At" showTime />
        <FunctionField
          label="Invoice"
          render={InvoiceField}
        />
        <DateField source="created_at" label="Created" showTime />
      </Datagrid>
    </List>
  );
};