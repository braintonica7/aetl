import React from 'react';
import { 
    Edit, 
    SimpleForm, 
    TextInput, 
    required,
    usePermissions
} from 'react-admin';
import {FormToolbar} from "../../common/FormToolbar";
import { processPermissions } from "../../common/roleUtils";

export const ExamEdit = props => {
    const { permissions } = usePermissions();
    let propsObj = { ...props };
    
    if (permissions) {
        // Use utility function to process permissions with role-based restrictions
        const processedPermissions = processPermissions(permissions, props.resource);
        propsObj = { ...propsObj, ...processedPermissions };
    }
    
    return (
        <React.Fragment>
            <Edit label="Edit Exam" {...propsObj}>
                <SimpleForm toolbar={<FormToolbar {...propsObj} hasDelete={true}/>}>
                    {/* <TextInput source="id" /> */}
                    <TextInput source="exam_name" label='Exam Name' fullWidth={true} validate={[required()]} />
                </SimpleForm>
            </Edit>
        </React.Fragment>
    );
}
