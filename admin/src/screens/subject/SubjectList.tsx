import React from 'react';
import { 
    List, 
    Datagrid, 
    TextField,
    usePermissions,
    useResourceContext,
    EditButton
} from 'react-admin';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

export const SubjectList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Debug logging to understand the permissions structure
    console.log('SubjectList - Permissions:', permissions);
    console.log('SubjectList - Resource:', resource);
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    console.log('SubjectList - Processed Props:', propsObj);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List title="List of Subjects" {...props} {...propsObj} 
                  sort={{ field: 'subject', order: 'ASC' }}>
                <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
                    {propsObj.hasEdit && <EditButton />}
                    <TextField source="id" />
                    <TextField source="subject" label="Subject" />
                </Datagrid>
            </List>
        </React.Fragment>
    )
}