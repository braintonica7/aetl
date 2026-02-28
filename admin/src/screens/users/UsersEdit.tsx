import React from "react";
import {

  Edit,
  FormDataConsumer,
  SimpleForm,
  TextInput,
  PasswordInput,
  NumberInput,
  ReferenceInput,
  SelectInput,
  required,
  DateInput,
  BooleanInput,
} from "react-admin";
import { FormToolbar } from "../../common/FormToolbar";
import { canDelete, processPermissions } from "../../common/roleUtils";
export const UsersEdit = (props) => {
  let propsObj = { ...props };
  if (propsObj.permissions) {
    // Use utility function to process permissions with role-based restrictions
    const processedPermissions = processPermissions(propsObj.permissions, props.resource);
    propsObj = { ...propsObj, ...processedPermissions };
  }
  return (
    <Edit title="User Edit" {...propsObj}>
      <SimpleForm toolbar={<FormToolbar {...props} />} className="main-form">
        <TextInput source="id" validate={[required()]} className="one_three_input" />
        <TextInput source="username" className="two_three_input" />
        <TextInput source="display_name" className="last_three_input" />

        <ReferenceInput label="Role" source="role_id" reference="role" className="one_three_input">
          <SelectInput optionText="role" validate={[required()]} />
        </ReferenceInput>
                
        <BooleanInput source="isActive" validate={[required()]} label="Active" className="one_three_input" />
      </SimpleForm>
    </Edit>
  );
};
