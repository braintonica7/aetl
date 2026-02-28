import React from "react";

import {
  List,
  Datagrid,
  TextField,
  DateField,
  ReferenceField,
  NumberField,
  BooleanField,
  EditButton,
  DeleteButton,
  ImageField,
  Filter,
  ReferenceInput,
  SearchInput,
  SelectField,
  TextInput,
  SelectInput,
  DateInput,
  usePermissions,
  useResourceContext,
} from "react-admin";
import { canDelete, processPermissions, isAdminUser } from "../../common/roleUtils";

const Filters = (props) => (
  <Filter {...props} variant="outlined">
    <TextInput
      variant="outlined"
      label="User Name"
      source="username"
      alwaysOn
    />
    <TextInput
      variant="outlined"
      label="Display Name"
      source="display_name"
      alwaysOn
    />
    <TextInput
      variant="outlined"
      label="Mobile Number"
      source="mobile_number"
      alwaysOn
    />
    <ReferenceInput 
      source="role_id" 
      reference="role" 
      link={false}
    >
      <SelectInput optionText="role" />
    </ReferenceInput>
    <SelectInput
      source="allow_login"
      label="Login Status"
      choices={[
        { id: 1, name: 'Allowed' },
        { id: 0, name: 'Not Allowed' }
      ]}
    />
    <DateInput
      source="last_login>="
      label="Last Login From"
    />
    <DateInput
      source="last_login<="
      label="Last Login To"
    />
  </Filter>
);
const GetField = (props) => {
  const type = props.type;
  const name = props.name;
  const label= props.label;
  if (type == "DateField")
    return <DateField source={name} label={label} />;

  return <TextField source={name} label={label} />;
}
export const UsersList = ({ ...props }) => {
  const { isPending, permissions } = usePermissions();
  const resource = useResourceContext();
  const fields = [
    { name: 'id', label:'User Id', type: 'TextField' },
    { name: 'username',label:'User Name', type: 'TextField' },
    { name: 'mobile_number',label:'Mobile Number', type: 'TextField' },
    { name: 'display_name', label: 'Display Name', type: 'TextField' },
  ];
  
  // Debug logging
  // console.log('UsersList - Permissions:', permissions);
  // console.log('UsersList - Resource:', resource);
  // console.log('UsersList - IsAdmin:', isAdminUser());
  
  // Use utility function to process permissions with role-based restrictions
  const propsObj = processPermissions(permissions, resource);
  
  console.log('UsersList - Processed Props:', propsObj);
  
  // Enable bulk actions only for admin users (role_id = 1)
  const allowBulkActions = isAdminUser();
  
  return (
    <List {...propsObj} exporter={propsObj.hasExport} title="User List" filters={<Filters />}  >
      <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
        {propsObj.hasEdit && <EditButton />}
        {fields.map((item, index) => {
          return (
            <GetField key={index} type={item.type} name={item.name} label={item.label}/>
          )
        })}  
         <ReferenceField source="role_id" reference="role" label="Role" link={false}>
          <TextField source="role" />
        </ReferenceField>  
        <BooleanField source="allow_login" label="Login Allowed" />
      </Datagrid>
    </List>
  );
};
