import React from 'react';
import { Create, SimpleForm, TextInput, required } from 'react-admin';
import { FormToolbar } from "../../common/FormToolbar";

export const SubjectCreate = props => {
    return (
        <React.Fragment>
            <Create label="Edit Subject" {...props}>
                <SimpleForm toolbar={<FormToolbar {...props} />}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="subject" label='Subject' fullWidth={true} validate={[required()]} />
                </SimpleForm>
            </Create>
        </React.Fragment>
    );
}