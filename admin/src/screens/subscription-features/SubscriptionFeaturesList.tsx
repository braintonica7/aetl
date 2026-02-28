// SubscriptionFeaturesList.tsx
import React from 'react';
import {
  List,
  Datagrid,
  TextField,
  BooleanField,
  EditButton,
  DeleteButton,
  ShowButton,
  TopToolbar,
  CreateButton,
  ExportButton,
  NumberField,
  FunctionField
} from 'react-admin';
import { Chip } from '@mui/material';
import { isAdminUser } from '../../common/roleUtils';

const ListActions = () => (
  <TopToolbar>
    <CreateButton />
    <ExportButton />
  </TopToolbar>
);

export const SubscriptionFeaturesList = () => {
  // Admin only access
  if (!isAdminUser()) {
    return <div>Access Denied: Admin privileges required</div>;
  }

  return (
    <List
      title="Subscription Features"
      perPage={25}
      sort={{ field: 'sort_order', order: 'ASC' }}
      actions={<ListActions />}
    >
      <Datagrid>
        <TextField source="id" />
        <TextField source="feature_key" label="Feature Key" />
        <TextField source="feature_name" label="Feature Name" />
        <TextField source="feature_description" label="Description" />
        <FunctionField
          label="Type"
          render={(record: any) => {
            const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'default' } = {
              quota: 'primary',
              boolean: 'secondary',
              credits: 'success'
            };
            return <Chip label={record?.feature_type} color={colors[record?.feature_type] || 'default'} size="small" />;
          }}
        />
        <FunctionField
          label="Reset Cycle"
          render={(record: any) => {
            const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'default' } = {
              none: 'default',
              monthly: 'primary',
              academic_session: 'secondary',
              weekly: 'success'
            };
            return <Chip label={record?.reset_cycle} color={colors[record?.reset_cycle] || 'default'} size="small" />;
          }}
        />
        <BooleanField source="is_active" label="Active" />
        <NumberField source="sort_order" label="Sort Order" />
        <ShowButton />
        <EditButton />
        <DeleteButton />
      </Datagrid>
    </List>
  );
};

export default SubscriptionFeaturesList;