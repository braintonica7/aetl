import React from 'react';
import { 
    List, 
    Datagrid, 
    TextField, 
    ReferenceField, 
    Filter, 
    SearchInput, 
    ReferenceInput, 
    SelectInput,
    usePermissions,
    useResourceContext,
    EditButton
} from 'react-admin';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const TestFilter = (props) => (
    <Filter {...props} variant='outlined'>
        <SearchInput source='chapter_name' alwaysOn />
        <ReferenceInput
            source='subject_id'
            reference='subject'
            link={false}
            alwaysOn
        >
            <SelectInput optionText='subject' />
        </ReferenceInput>
        

    </Filter>
);

export const ChapterList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List title="List of Chapters" {...props} {...propsObj}
             filters={<TestFilter />}
            sort={{ field: 'chapter_name', order: 'ASC' }}>
                <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
                    {propsObj.hasEdit && <EditButton />}
                    <TextField source="id" />
                    <TextField source="chapter_name" label="Chapter Name" />
                    <ReferenceField source="subject_id" reference="subject" label="Subject">
                        <TextField source="subject" />
                    </ReferenceField>
                </Datagrid>
            </List>
        </React.Fragment>
    )
}
