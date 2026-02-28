// SubscriptionFeaturesCreate.tsx
import React from 'react';
import {
  Create,
  SimpleForm,
  TextInput,
  SelectInput,
  BooleanInput,
  NumberInput,
  required
} from 'react-admin';
import { isAdminUser } from '../../common/roleUtils';
import { FormToolbar } from '../../common/FormToolbar';

const featureTypeChoices = [
  { id: 'quota', name: 'Quota (Limited number)' },
  { id: 'boolean', name: 'Boolean (Enabled/Disabled)' },
  { id: 'credits', name: 'Credits (Consumable points)' }
];

const resetCycleChoices = [
  { id: 'none', name: 'Never Reset' },
  { id: 'monthly', name: 'Monthly' },
  { id: 'academic_session', name: 'Academic Session' },
  { id: 'weekly', name: 'Weekly' }
];

export const SubscriptionFeaturesCreate = () => {
  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }

  return (
    <Create title="Create Subscription Feature">
      <SimpleForm toolbar={<FormToolbar />}>
        <TextInput 
          source="feature_key" 
          label="Feature Key" 
          validate={[required()]}
          helperText="Unique identifier (e.g., custom_quiz, quiz_solutions, pyqs)"
        />
        <TextInput 
          source="feature_name" 
          label="Feature Name" 
          validate={[required()]}
          helperText="Display name for the feature"
        />
        <TextInput 
          source="feature_description" 
          label="Description" 
          multiline
          rows={3}
          helperText="Detailed description of what this feature provides"
        />
        <SelectInput
          source="feature_type"
          label="Feature Type"
          choices={featureTypeChoices}
          validate={[required()]}
          defaultValue="quota"
          helperText="Type determines how limits are applied"
        />
        <SelectInput
          source="reset_cycle"
          label="Reset Cycle"
          choices={resetCycleChoices}
          defaultValue="none"
          helperText="When usage counters reset"
        />
        <NumberInput 
          source="sort_order" 
          label="Sort Order" 
          defaultValue={0}
          helperText="Display order in lists (lower numbers first)"
        />
        <BooleanInput 
          source="is_active" 
          label="Active" 
          defaultValue={true}
          helperText="Whether this feature is available for assignment"
        />
      </SimpleForm>
    </Create>
  );
};

export default SubscriptionFeaturesCreate;