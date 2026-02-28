import React from 'react';
import { 
    List, 
    Datagrid, 
    TextField, 
    ReferenceField, 
    Filter, 
    ReferenceInput, 
    SelectInput, 
    SearchInput,
    usePermissions,
    useResourceContext,
    EditButton
} from 'react-admin';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const TestFilter = (props) => (
    <Filter {...props} variant='outlined'>
        <SearchInput source='topic_name' alwaysOn />
        <ReferenceInput
            source='subject_id'
            reference='subject'
            link={false}
            alwaysOn
        >
            <SelectInput optionText='subject' />
        </ReferenceInput>
        <ReferenceInput source='chapter_id' reference='chapter' link={false} alwaysOn>
            <SelectInput optionText='chapter_name' />
        </ReferenceInput>


    </Filter>
);

export const TopicList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List title="List of Topics" {...props} {...propsObj}
                filters={<TestFilter />}
                sort={{ field: 'topic_name', order: 'ASC' }}>
                <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
                    {propsObj.hasEdit && <EditButton />}
                    <TextField source="id" />
                    <TextField source="topic_name" label="Topic Name" />
                    <ReferenceField source="subject_id" reference="subject" label="Subject">
                        <TextField source="subject" />
                    </ReferenceField>
                    <ReferenceField source="chapter_id" reference="chapter" label="Chapter">
                        <TextField source="chapter_name" />
                    </ReferenceField>
                </Datagrid>
            </List>
        </React.Fragment>
    )
}
