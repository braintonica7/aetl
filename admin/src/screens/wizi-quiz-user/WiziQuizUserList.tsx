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
    NumberField,
    usePermissions,
    useResourceContext,
    FunctionField,
    DateInput,
    NumberInput
} from 'react-admin';
import { Chip, Box, Typography } from '@mui/material';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const WiziQuizUserFilter = (props) => (
    <Filter {...props} variant='outlined'>
        <SearchInput 
            source='quiz_name' 
            alwaysOn 
            placeholder="Search by Quiz Name" 
        />
        
        <SearchInput 
            source='user_name' 
            alwaysOn 
            placeholder="Search by User Name/Email" 
        />
        
        <SelectInput 
            source='attempt_status' 
            label="Attempt Status"
            choices={[
                { id: 'not_started', name: 'Not Started' },
                { id: 'in_progress', name: 'In Progress' },
                { id: 'completed', name: 'Completed' },
                { id: 'abandoned', name: 'Abandoned' },
                { id: 'timeout', name: 'Timeout' }
            ]}
            alwaysOn
        />

        <DateInput 
            source='started_at_gte' 
            label="Started From"
        />

        <DateInput 
            source='started_at_lte' 
            label="Started Until"
        />

        <DateInput 
            source='completed_at_gte' 
            label="Completed From"
        />

        <DateInput 
            source='completed_at_lte' 
            label="Completed Until"
        />

        <NumberInput 
            source='total_score_gte' 
            label="Min Score"
        />

        <NumberInput 
            source='total_score_lte' 
            label="Max Score"
        />

        <NumberInput 
            source='accuracy_percentage_gte' 
            label="Min Accuracy %"
        />

        <NumberInput 
            source='accuracy_percentage_lte' 
            label="Max Accuracy %"
        />
    </Filter>
);

/**
 * Format time in seconds to MM:SS format
 */
const formatTimeSpent = (seconds: number): string => {
    if (!seconds || seconds === 0) return '00:00';
    
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

export const WiziQuizUserList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List 
                title="WiZi Quiz User Attempts" 
                {...props} 
                {...propsObj}
                filters={<WiziQuizUserFilter />}
                sort={{ field: 'id', order: 'DESC' }}
                perPage={25}
                // Disable edit/create/delete for read-only view
                hasCreate={false}
                hasEdit={false}
            >
                <Datagrid 
                    rowClick={false} 
                    bulkActionButtons={false}
                >
                    <TextField source="id" label="ID" />
                    
                    <FunctionField
                        label="Quiz Name"
                        render={(record: any) => (
                            <Box>
                                <Typography variant="body2" fontWeight="medium">
                                    {record?.quiz_name || 'N/A'}
                                </Typography>
                                {record?.quiz_level && (
                                    <Chip 
                                        label={record.quiz_level} 
                                        size="small" 
                                        sx={{ mt: 0.5 }}
                                    />
                                )}
                            </Box>
                        )}
                    />
                    
                    <FunctionField
                        label="User"
                        render={(record: any) => (
                            <Box>
                                <Typography variant="body2" fontWeight="medium">
                                    {record?.user_name || 'N/A'}
                                </Typography>
                                <Typography variant="caption" color="textSecondary">
                                    {record?.user_email || ''}
                                </Typography>
                            </Box>
                        )}
                    />
                    
                    <NumberField source="attempt_number" label="Attempt #" />
                    
                    <FunctionField
                        label="Status"
                        render={(record: any) => {
                            const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'default' } = {
                                not_started: 'default',
                                in_progress: 'warning',
                                completed: 'success',
                                abandoned: 'error',
                                timeout: 'error'
                            };
                            const labels: { [key: string]: string } = {
                                not_started: 'Not Started',
                                in_progress: 'In Progress',
                                completed: 'Completed',
                                abandoned: 'Abandoned',
                                timeout: 'Timeout'
                            };
                            return (
                                <Chip 
                                    label={labels[record?.attempt_status] || record?.attempt_status} 
                                    color={colors[record?.attempt_status] || 'default'} 
                                    size="small" 
                                />
                            );
                        }}
                    />
                    
                    <FunctionField
                        label="Score"
                        render={(record: any) => (
                            <Box>
                                <Typography variant="body2" fontWeight="bold">
                                    {record?.total_score || 0} / {record?.total_marks || 0}
                                </Typography>
                            </Box>
                        )}
                    />
                    
                    <FunctionField
                        label="Accuracy"
                        render={(record: any) => {
                            const accuracy = parseFloat(record?.accuracy_percentage || 0);
                            let color: 'success' | 'warning' | 'error' = 'error';
                            
                            if (accuracy >= 75) color = 'success';
                            else if (accuracy >= 50) color = 'warning';
                            
                            return (
                                <Chip 
                                    label={`${accuracy.toFixed(2)}%`} 
                                    color={color} 
                                    size="small" 
                                />
                            );
                        }}
                    />
                    
                    <FunctionField
                        label="Questions"
                        render={(record: any) => (
                            <Box>
                                <Typography variant="body2">
                                    {record?.answered_questions || 0} / {record?.total_questions || 0}
                                </Typography>
                                <Typography variant="caption" color="textSecondary">
                                    ✓ {record?.correct_answers || 0} 
                                    {' '}✗ {record?.incorrect_answers || 0}
                                    {' '}⊘ {record?.skipped_questions || 0}
                                </Typography>
                            </Box>
                        )}
                    />
                    
                    <FunctionField
                        label="Time Spent"
                        render={(record: any) => (
                            <Typography variant="body2" fontFamily="monospace">
                                {formatTimeSpent(record?.time_spent || 0)}
                            </Typography>
                        )}
                    />
                    
                    <DateField 
                        source="started_at" 
                        label="Started At" 
                        showTime 
                    />
                    
                    <DateField 
                        source="completed_at" 
                        label="Completed At" 
                        showTime 
                    />
                    
                    <FunctionField
                        label="Passed"
                        render={(record: any) => {
                            if (record?.is_passed === null || record?.is_passed === undefined) {
                                return <Chip label="N/A" size="small" />;
                            }
                            return (
                                <Chip 
                                    label={record.is_passed ? 'Yes' : 'No'} 
                                    color={record.is_passed ? 'success' : 'error'} 
                                    size="small" 
                                />
                            );
                        }}
                    />
                    
                    <NumberField source="rank" label="Rank" />
                    
                    <FunctionField
                        label="Best Attempt"
                        render={(record: any) => {
                            if (record?.best_attempt === 1) {
                                return <Chip label="★ Best" color="primary" size="small" />;
                            }
                            return null;
                        }}
                    />
                    
                    <DateField source="created_at" label="Created" showTime />
                </Datagrid>
            </List>
        </React.Fragment>
    );
};
