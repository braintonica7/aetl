import React from 'react';
import { 
    List, 
    Datagrid, 
    TextField,
    ReferenceField,
    Filter,
    SearchInput,
    SelectInput,
    DateField,
    BooleanField,
    NumberField,
    usePermissions,
    useResourceContext,
    EditButton,
    FunctionField,
    DateInput
} from 'react-admin';
import { Chip } from '@mui/material';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const WiziQuizFilter = (props) => (
    <Filter {...props} variant='outlined'>
        <SearchInput source='name' alwaysOn placeholder="Search by Name" />
        
        <SelectInput 
            source='status' 
            label="Status"
            choices={[
                { id: 'draft', name: 'Draft' },
                { id: 'active', name: 'Active' },
                { id: 'completed', name: 'Completed' },
                { id: 'archived', name: 'Archived' }
            ]}
            alwaysOn
        />
        
        <SelectInput 
            source='level' 
            label="Difficulty Level"
            choices={[
                { id: 'Elementary', name: 'Elementary' },
                { id: 'Moderate', name: 'Moderate' },
                { id: 'Advance', name: 'Advance' }
            ]}
            alwaysOn
        />

        <SelectInput 
            source='is_published' 
            label="Published Status"
            choices={[
                { id: '1', name: 'Published' },
                { id: '0', name: 'Unpublished' }
            ]}
        />

        <SelectInput 
            source='language' 
            label="Language"
            choices={[
                { id: 'english', name: 'English' },
                { id: 'hindi', name: 'Hindi (हिंदी)' }
            ]}
        />

        <DateInput 
            source='valid_from' 
            label="Valid From"
        />

        <DateInput 
            source='valid_until' 
            label="Valid Until"
        />
    </Filter>
);

export const WiziQuizList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List 
                title="WiZi Quiz - Full Length Mock Tests" 
                {...props} 
                {...propsObj}
                filters={<WiziQuizFilter />}
                sort={{ field: 'id', order: 'DESC' }}
                perPage={25}
            >
                <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
                    {propsObj.hasEdit && <EditButton />}
                    <TextField source="id" label="ID" />
                    <TextField source="name" label="Quiz Name" />
                    
                    <FunctionField
                        label="Status"
                        render={(record: any) => {
                            const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'default' } = {
                                draft: 'default',
                                active: 'success',
                                completed: 'primary',
                                archived: 'warning'
                            };
                            const labels: { [key: string]: string } = {
                                draft: 'Draft',
                                active: 'Active',
                                completed: 'Completed',
                                archived: 'Archived'
                            };
                            return (
                                <Chip 
                                    label={labels[record?.status] || record?.status} 
                                    color={colors[record?.status] || 'default'} 
                                    size="small" 
                                />
                            );
                        }}
                    />
                    
                    <FunctionField
                        label="Level"
                        render={(record: any) => {
                            const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'default' } = {
                                Elementary: 'success',
                                Moderate: 'primary',
                                Advance: 'error'
                            };
                            return (
                                <Chip 
                                    label={record?.level || 'N/A'} 
                                    color={colors[record?.level] || 'default'} 
                                    size="small" 
                                />
                            );
                        }}
                    />
                    
                    <NumberField source="total_marks" label="Total Marks" />
                    <NumberField source="passing_score" label="Passing Score" />
                    <NumberField source="time_limit" label="Time Limit (mins)" />
                    
                    <TextField source="language" label="Language" />
                    
                    <BooleanField source="is_published" label="Published" />
                    
                    <DateField source="valid_from" label="Valid From" showTime />
                    <DateField source="valid_until" label="Valid Until" showTime />
                    
                    <ReferenceField source="created_by" reference="user" label="Created By" link={false}>
                        <TextField source="display_name" />
                    </ReferenceField>
                    
                    <DateField source="created_at" label="Created" showTime />
                </Datagrid>
            </List>
        </React.Fragment>
    );
};
