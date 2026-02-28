import React from 'react';
import { 
    Edit, 
    SimpleForm, 
    TextInput,
    AutocompleteInput, 
    ReferenceInput, 
    SelectInput, 
    required,
    usePermissions
} from 'react-admin';
import { FormToolbar } from "../../common/FormToolbar";
import { processPermissions } from "../../common/roleUtils";

export const TopicEdit = props => {
    const { permissions } = usePermissions();
    let propsObj = { ...props };
    
    if (permissions) {
        // Use utility function to process permissions with role-based restrictions
        const processedPermissions = processPermissions(permissions, props.resource);
        propsObj = { ...propsObj, ...processedPermissions };
    }
    
    return (
        <React.Fragment>
            <Edit label="Edit Topic" {...propsObj}>
                <SimpleForm toolbar={<FormToolbar {...propsObj} hasDelete={true} />}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="topic_name" label='Topic Name' fullWidth={true} validate={[required()]} />
                    <ReferenceInput source="subject_id" reference="subject" label="Subject">
                        <SelectInput optionText="subject" validate={[required()]} />
                    </ReferenceInput>
                    <ReferenceInput 
                    perPage={25} sort={{ field: 'name', order: 'ASC' }}
                    source="chapter_id" reference="chapter"  label="Chapter">
                        <AutocompleteInput  filterToQuery={searchText => ({ 'chapter_name': searchText })} optionText="chapter_name" shouldRenderSuggestions={(val) => { return val && val.trim().length >= 1 }} validate={[required()]} />
                    </ReferenceInput>
                </SimpleForm>
            </Edit>
        </React.Fragment>
    );
}
