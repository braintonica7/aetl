import React from "react";
import {
  Create,
  SimpleForm,
  TextInput,
  NumberInput,
  BooleanInput,
  required,
  minValue,
  maxLength,
  usePermissions,
} from "react-admin";
import { Box, Typography } from "@mui/material";
import { isAdminUser } from "../../common/roleUtils";
import { FormToolbar } from "../../common/FormToolbar";

const validatePlanKey = [required(), maxLength(50)];
const validatePlanName = [required(), maxLength(100)];
const validatePrice = [minValue(0)];
const validateSortOrder = [minValue(0)];

export const SubscriptionPlansCreate = (props) => {
  const { permissions } = usePermissions();
  
  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }

  return (
    <Create {...props} title="Create Subscription Plan">
      <SimpleForm toolbar={<FormToolbar />}>
        <Typography variant="h6" gutterBottom>
          Basic Information
        </Typography>
        
        <Box display="flex" gap={2} width="100%">
          <TextInput
            source="plan_key"
            label="Plan Key"
            validate={validatePlanKey}
            helperText="Unique identifier (e.g., 'basic', 'premium')"
            sx={{ flex: 1 }}
          />
          <TextInput
            source="plan_name"
            label="Plan Name"
            validate={validatePlanName}
            helperText="Display name for the plan"
            sx={{ flex: 1 }}
          />
        </Box>

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
            defaultValue={true}
            sx={{ flex: 1 }}
          />
          <NumberInput
            source="sort_order"
            label="Sort Order"
            validate={validateSortOrder}
            helperText="Display order (lower numbers first)"
            defaultValue={0}
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
          defaultValue="#007bff"
        />
      </SimpleForm>
    </Create>
  );
};