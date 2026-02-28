import React from 'react';
import { Create, SimpleForm, TextInput, ReferenceInput, SelectInput, required } from 'react-admin';
import { FormToolbar } from "../../common/FormToolbar";

export const ChapterCreate = props => {
    return (
        <React.Fragment>
            <Create label="Create Chapter" {...props}>
                <SimpleForm  toolbar={<FormToolbar {...props} />}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="chapter_name" label='Chapter Name' fullWidth={true} validate={[required()]} />
                    <ReferenceInput source="subject_id" reference="subject" label="Subject">
                        <SelectInput optionText="subject" validate={[required()]} />
                    </ReferenceInput>
                </SimpleForm>
            </Create>
        </React.Fragment>
    );
}
