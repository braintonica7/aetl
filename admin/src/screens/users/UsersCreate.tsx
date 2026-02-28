import React from "react";
import {
  Edit,
  Create,
  SimpleForm,
  TextInput,
  PasswordInput,
  NumberInput,
  ReferenceInput,
  AutocompleteInput,
  SelectInput,
  required,
  DateInput,
  BooleanInput,
  FormDataConsumer
} from "react-admin";

import { FormToolbar } from "../../common/FormToolbar";

export const UsersCreate = (props) => {
  
  return (
    <Create title="User Create" {...props}>
      <SimpleForm className="main-form"  toolbar={<FormToolbar {...props} showDelete={false} />}>
        <TextInput source="code" validate={[required()]} className="one_three_input" />
        <TextInput source="userName" className="two_three_input" />
        <PasswordInput source="password" className="last_three_input" />

        <ReferenceInput label="Role" source="roleTypeId" reference="role-types" className="one_three_input">
          <SelectInput optionText="roleName" validate={[required()]} />
        </ReferenceInput>
        <ReferenceInput label="Type" className="two_three_input"
          perPage={5000} source="typeId"
          filter={{ type: 'USR' }}
          reference="lookups" >
          <SelectInput optionText="name" variant="outlined" className="one_three_input" />
        </ReferenceInput>

        <DateInput source="startDate" className="last_three_input" />
        <DateInput source="endDate" className="one_three_input" />
        <NumberInput source="allowEditDays" label="Allow Editing For Days " className="two_three_input" />
        <NumberInput source="allowAddDays" label="Allow Deletion For Days " className="last_three_input" />

        <BooleanInput source="isActive" validate={[required()]} label="Active" className="two_three_input" />


      </SimpleForm>
    </Create>
  );
};
