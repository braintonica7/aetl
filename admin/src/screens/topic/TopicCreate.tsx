import React from 'react';
import { Create, SimpleForm, TextInput, ReferenceInput, SelectInput, AutocompleteInput, required } from 'react-admin';
import { FormToolbar } from "../../common/FormToolbar";

export const TopicCreate = props => {
    return (
        <React.Fragment>
            <Create label="Create Topic" {...props}>
                <SimpleForm toolbar={<FormToolbar {...props} />}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="topic_name" label='Topic Name' fullWidth={true} validate={[required()]} />
                    <ReferenceInput source="subject_id" reference="subject" label="Subject">
                        <SelectInput optionText="subject" validate={[required()]} />
                    </ReferenceInput>
                    <ReferenceInput
                        perPage={20} sort={{ field: 'name', order: 'ASC' }}
                        source="chapter_id" reference="chapter" label="Chapter">
                        <AutocompleteInput  filterToQuery={searchText => ({ 'chapter_name': searchText })} optionText="chapter_name" shouldRenderSuggestions={(val) => { return val && val.trim().length >= 1 }} validate={[required()]} />
                    </ReferenceInput>
                </SimpleForm>
            </Create>
        </React.Fragment>
    );
}
