import React from 'react';
import { 
    Edit, 
    SimpleForm, 
    TextInput, 
    ReferenceInput, 
    SelectInput, 
    required,
    usePermissions
} from 'react-admin';
import {FormToolbar} from "../../common/FormToolbar";
import { processPermissions } from "../../common/roleUtils";

export const ChapterEdit = props => {
    const { permissions } = usePermissions();
    let propsObj = { ...props };
    
    if (permissions) {
        // Use utility function to process permissions with role-based restrictions
        const processedPermissions = processPermissions(permissions, props.resource);
        propsObj = { ...propsObj, ...processedPermissions };
    }
    
    return (
        <React.Fragment>
            <Edit label="Edit Chapter" {...propsObj}>
                <SimpleForm toolbar={<FormToolbar {...propsObj} hasDelete={true}/>}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="chapter_name" label='Chapter Name' fullWidth={true} validate={[required()]} />
                    <ReferenceInput source="subject_id" reference="subject" label="Subject">
                        <SelectInput optionText="subject" validate={[required()]} />
                    </ReferenceInput>
                </SimpleForm>
            </Edit>
        </React.Fragment>
    );
}
