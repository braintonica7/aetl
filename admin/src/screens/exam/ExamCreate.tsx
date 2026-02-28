import React from 'react';
import { Create, SimpleForm, TextInput, required } from 'react-admin';
import { FormToolbar } from "../../common/FormToolbar";

export const ExamCreate = props => {
    return (
        <React.Fragment>
            <Create label="Create Exam" {...props}>
                <SimpleForm  toolbar={<FormToolbar {...props} />}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="exam_name" label='Exam Name' fullWidth={true} validate={[required()]} />
                </SimpleForm>
            </Create>
        </React.Fragment>
    );
}
