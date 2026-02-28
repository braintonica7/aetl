import React from "react";
import {
  Edit,
  SimpleForm,
  TextInput,
  NumberInput,
  BooleanInput,
  required,
  minValue,
  maxLength,
  usePermissions,
  DateField,
  TextField,
} from "react-admin";
import { Box, Typography, Paper, Divider } from "@mui/material";
import { isAdminUser } from "../../common/roleUtils";
import { FormToolbar } from "../../common/FormToolbar";

const validatePlanName = [required(), maxLength(100)];
const validatePrice = [minValue(0)];
const validateSortOrder = [minValue(0)];

export const SubscriptionPlansEdit = (props) => {
  const { permissions } = usePermissions();
  
  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }

  return (
    <Edit {...props} title="Edit Subscription Plan">
      <SimpleForm toolbar={<FormToolbar hasDelete />}>
        <Paper sx={{ p: 2, mb: 2 }}>
          <Typography variant="h6" gutterBottom>
            Plan Information
          </Typography>
          <Box display="flex" gap={2} width="100%">
            <TextField source="id" label="Plan ID" sx={{ flex: 1 }} />
            <TextField source="plan_key" label="Plan Key" sx={{ flex: 1 }} />
          </Box>
          <Box display="flex" gap={2} width="100%" mt={1}>
            <DateField source="created_at" label="Created" showTime sx={{ flex: 1 }} />
            <DateField source="updated_at" label="Updated" showTime sx={{ flex: 1 }} />
          </Box>
        </Paper>

        <Typography variant="h6" gutterBottom>
          Basic Information
        </Typography>
        
        <TextInput
          source="plan_name"
          label="Plan Name"
          validate={validatePlanName}
          helperText="Display name for the plan"
          fullWidth
        />

        <TextInput
          source="plan_description"
          label="Plan Description"
          multiline
          rows={3}
          fullWidth
          helperText="Detailed description of the plan features"
        />

        <Typography variant="h6" gutterBottom sx={{ mt: 3 }}>
          Pricing
        </Typography>

        <Box display="flex" gap={2} width="100%">
          <NumberInput
            source="monthly_price_inr"
            label="Monthly Price (₹)"
            validate={validatePrice}
            helperText="Monthly subscription price in INR"
            sx={{ flex: 1 }}
          />
          <NumberInput
            source="academic_session_price_inr"
            label="Academic Session Price (₹)"
            validate={validatePrice}
            helperText="Annual academic session price in INR"
            sx={{ flex: 1 }}
          />
        </Box>

        <Typography variant="h6" gutterBottom sx={{ mt: 3 }}>
          Plan Settings
        </Typography>

        <Box display="flex" gap={2} width="100%">
          <BooleanInput
            source="is_free"
            label="Free Plan"
            helperText="Mark this as a free plan"
            sx={{ flex: 1 }}
          />
          <BooleanInput
            source="is_default"
            label="Default Plan"
            helperText="Set as default plan for new users"
            sx={{ flex: 1 }}
          />
        </Box>

        <Box display="flex" gap={2} width="100%">
          <BooleanInput
            source="is_active"
            label="Active"
            helperText="Plan is available for subscription"
            sx={{ flex: 1 }}
          />
          <NumberInput
            source="sort_order"
            label="Sort Order"
            validate={validateSortOrder}
            helperText="Display order (lower numbers first)"
            sx={{ flex: 1 }}
          />
        </Box>

        <Typography variant="h6" gutterBottom sx={{ mt: 3 }}>
          Appearance
        </Typography>

        <TextInput
          source="plan_color"
          label="Plan Color"
          helperText="Hex color code for the plan (e.g., #007bff)"
        />
      </SimpleForm>
    </Edit>
  );
};