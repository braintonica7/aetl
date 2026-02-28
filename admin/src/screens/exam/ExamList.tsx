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

export const ExamList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List title="List of Exams" {...props} {...propsObj} 
                  sort={{ field: 'exam_name', order: 'ASC' }}>
                <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
                    {propsObj.hasEdit && <EditButton />}
                    <TextField source="id" />
                    <TextField source="exam_name" label="Exam Name" />
                </Datagrid>
            </List>
        </React.Fragment>
    )
}
