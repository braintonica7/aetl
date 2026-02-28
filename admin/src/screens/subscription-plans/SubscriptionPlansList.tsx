import React from "react";
import {
  List,
  Datagrid,
  TextField,
  DateField,
  NumberField,
  BooleanField,
  EditButton,
  DeleteButton,
  Filter,
  TextInput,
  NumberInput,
  BooleanInput,
  usePermissions,
  useResourceContext,
  ChipField,
  FunctionField,
  CreateButton,
  TopToolbar,
  ExportButton,
  Button,
} from "react-admin";
import { Chip, Box } from "@mui/material";
import { Settings as SettingsIcon } from "@mui/icons-material";
import { useNavigate } from "react-router-dom";
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const Filters = (props) => (
  <Filter {...props} variant="outlined">
    <TextInput
      variant="outlined"
      label="Plan Name"
      source="plan_name~like"
      alwaysOn
    />
    <TextInput
      variant="outlined"
      label="Plan Key"
      source="plan_key~like"
    />
    <BooleanInput
      source="is_active"
      label="Active Status"
    />
    <BooleanInput
      source="is_free"
      label="Free Plan"
    />
    <NumberInput
      source="monthly_price_inr>="
      label="Min Price (INR)"
    />
    <NumberInput
      source="monthly_price_inr<="
      label="Max Price (INR)"
    />
  </Filter>
);

const ListActions = () => (
  <TopToolbar>
    <CreateButton />
    <ExportButton />
  </TopToolbar>
);

const PlanStatusField = ({ record }) => {
  if (!record) return null;
  
  const getStatusColor = (isActive, isFree) => {
    if (!isActive) return "error";
    if (isFree) return "success";
    return "primary";
  };

  const getStatusText = (isActive, isFree) => {
    if (!isActive) return "Inactive";
    if (isFree) return "Free Plan";
    return "Active";
  };

  return (
    <Chip
      label={getStatusText(record.is_active, record.is_free)}
      color={getStatusColor(record.is_active, record.is_free)}
      size="small"
    />
  );
};

const PriceField = ({ record }) => {
  if (!record) return null;
  
  return (
    <Box>
      <div>₹{record.monthly_price_inr}/month</div>
      {record.academic_session_price_inr > 0 && (
        <div style={{ fontSize: '0.8em', color: '#666' }}>
          ₹{record.academic_session_price_inr}/year
        </div>
      )}
    </Box>
  );
};

const PlanColorField = ({ record }) => {
  if (!record || !record.plan_color) return null;
  
  return (
    <Box display="flex" alignItems="center" gap={1}>
      <Box
        sx={{
          width: 20,
          height: 20,
          backgroundColor: record.plan_color,
          borderRadius: '50%',
          border: '1px solid #ddd'
        }}
      />
      <span>{record.plan_color}</span>
    </Box>
  );
};

const ManageFeaturesButton = ({ record }) => {
  const navigate = useNavigate();
  
  const handleClick = () => {
    navigate(`/plan-features/${record.id}`);
  };
  
  return (
    <Button
      onClick={handleClick}
      label="Features"
      startIcon={<SettingsIcon />}
      size="small"
      variant="outlined"
    />
  );
};

export const SubscriptionPlansList = ({ ...props }) => {
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
      title="Subscription Plans" 
      filters={<Filters />}
      actions={<ListActions />}
      sort={{ field: 'sort_order', order: 'ASC' }}
    >
      <Datagrid rowClick={false}>
        {propsObj.hasEdit && <EditButton />}
        <TextField source="id" label="ID" />
        <TextField source="plan_key" label="Plan Key" />
        <TextField source="plan_name" label="Plan Name" />
        <FunctionField
          label="Status"
          render={PlanStatusField}
        />
        <FunctionField
          label="Pricing"
          render={PriceField}
        />
        <TextField source="sort_order" label="Sort Order" />
        <FunctionField
          label="Color"
          render={PlanColorField}
        />
        <DateField source="created_at" label="Created" showTime />
        <DateField source="updated_at" label="Updated" showTime />
        <FunctionField
          label="Actions"
          render={(record) => <ManageFeaturesButton record={record} />}
        />
        {propsObj.hasDelete && <DeleteButton />}
      </Datagrid>
    </List>
  );
};