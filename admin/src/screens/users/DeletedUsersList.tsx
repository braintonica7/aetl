import React, { useState, useEffect } from 'react';
import {
    List,
    Datagrid,
    TextField,
    DateField,
    FunctionField,
    useNotify,
    useRefresh,
    Button,
    useDataProvider,
    TopToolbar,
    FilterButton,
    TextInput
} from 'react-admin';
import { Card, CardContent, Typography, Chip, Box } from '@mui/material';
import RestoreIcon from '@mui/icons-material/Restore';
import DeleteForeverIcon from '@mui/icons-material/DeleteForever';
import WarningIcon from '@mui/icons-material/Warning';

/**
 * Deleted Users List Component
 * Shows all users who have requested account deletion
 * Displays grace period information and allows admin to restore accounts
 */

const DeletedUsersActions = () => (
    <TopToolbar>
        <FilterButton />
    </TopToolbar>
);

const deletedUsersFilters = [
    <TextInput label="Search Username" source="username" alwaysOn />,
    <TextInput label="Search Display Name" source="display_name" />,
];

const RestoreButton = ({ record }: any) => {
    const notify = useNotify();
    const refresh = useRefresh();
    const dataProvider = useDataProvider();

    const handleRestore = async () => {
        if (!window.confirm(`Are you sure you want to restore account for ${record.display_name}?`)) {
            return;
        }

        try {
            // Call the restore API endpoint
            await dataProvider.create('user/restore-account', {
                data: { user_id: record.id }
            });

            notify('Account restored successfully', { type: 'success' });
            refresh();
        } catch (error: any) {
            notify(error.message || 'Failed to restore account', { type: 'error' });
        }
    };

    return (
        <Button
            label="Restore"
            onClick={handleRestore}
            startIcon={<RestoreIcon />}
            color="primary"
            variant="contained"
            size="small"
        />
    );
};

const GracePeriodChip = ({ record }: any) => {
    if (!record.days_until_deletion) {
        return <Chip label="Unknown" color="default" size="small" />;
    }

    const daysRemaining = record.days_until_deletion;
    
    let color: "error" | "warning" | "success" = "success";
    let label = `${daysRemaining} days left`;

    if (daysRemaining <= 0) {
        color = "error";
        label = "Grace period expired";
    } else if (daysRemaining <= 7) {
        color = "error";
        label = `${daysRemaining} days left`;
    } else if (daysRemaining <= 14) {
        color = "warning";
        label = `${daysRemaining} days left`;
    }

    return <Chip label={label} color={color} size="small" icon={<WarningIcon />} />;
};

const DeletionReasonField = ({ record }: any) => {
    if (!record.deletion_reason) {
        return <Typography variant="body2" color="textSecondary">No reason provided</Typography>;
    }

    return (
        <Typography 
            variant="body2" 
            style={{ 
                maxWidth: '300px', 
                overflow: 'hidden', 
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap' 
            }}
            title={record.deletion_reason}
        >
            {record.deletion_reason}
        </Typography>
    );
};

export const DeletedUsersList = (props: any) => {
    return (
        <>
            <Card style={{ marginBottom: '20px', backgroundColor: '#fff3cd' }}>
                <CardContent>
                    <Box display="flex" alignItems="center" gap={2}>
                        <WarningIcon color="warning" fontSize="large" />
                        <div>
                            <Typography variant="h6" gutterBottom>
                                Deleted Accounts Management
                            </Typography>
                            <Typography variant="body2" color="textSecondary">
                                These accounts are marked for deletion with a 30-day grace period.
                                You can restore accounts within the grace period or permanently delete them after 30 days.
                            </Typography>
                        </div>
                    </Box>
                </CardContent>
            </Card>

            <List
                {...props}
                resource="user/pending-deletion"
                filters={deletedUsersFilters}
                actions={<DeletedUsersActions />}
                perPage={25}
                sort={{ field: 'deletion_requested_at', order: 'DESC' }}
                empty={
                    <Card>
                        <CardContent>
                            <Typography variant="h6" align="center" color="textSecondary">
                                No accounts pending deletion
                            </Typography>
                        </CardContent>
                    </Card>
                }
            >
                <Datagrid bulkActionButtons={false}>
                    <TextField source="id" label="User ID" />
                    <TextField source="username" label="Email" />
                    <TextField source="display_name" label="Name" />
                    <DateField 
                        source="deletion_requested_at" 
                        label="Deletion Requested" 
                        showTime 
                    />
                    <FunctionField
                        label="Grace Period"
                        render={(record: any) => <GracePeriodChip record={record} />}
                    />
                    <FunctionField
                        label="Reason"
                        render={(record: any) => <DeletionReasonField record={record} />}
                    />
                    <FunctionField
                        label="Actions"
                        render={(record: any) => (
                            <Box display="flex" gap={1}>
                                <RestoreButton record={record} />
                            </Box>
                        )}
                    />
                </Datagrid>
            </List>
        </>
    );
};

export default DeletedUsersList;
