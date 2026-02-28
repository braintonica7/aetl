import React, { useState } from 'react';
import {
    List,
    Datagrid,
    TextField,
    EmailField,
    FunctionField,
    SearchInput,
    SelectInput,
    useRecordContext,
    useListContext,
    useNotify,
    useRefresh,
    TopToolbar,
    FilterButton,
    ExportButton,
    Button
} from 'react-admin';
import {
    Chip,
    Box,
    IconButton,
    Tooltip,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Typography,
    Card,
    CardContent,
    Stack
} from '@mui/material';
import {
    Send as SendIcon,
    Visibility as PreviewIcon,
    Close as CloseIcon,
    CheckCircle as SuccessIcon
} from '@mui/icons-material';
import { sendNotification, sendBulkNotifications } from '../../common/apiClient';

/**
 * Priority Badge Component
 */
const PriorityBadge: React.FC<{ priority: string }> = ({ priority }) => {
    const colors: Record<string, 'error' | 'warning' | 'default'> = {
        high: 'error',
        medium: 'warning',
        low: 'default'
    };

    return (
        <Chip
            label={priority.toUpperCase()}
            color={colors[priority] || 'default'}
            size="small"
            sx={{ fontWeight: 'bold' }}
        />
    );
};

/**
 * User Segment Badge Component
 */
const SegmentBadge: React.FC<{ segment: string }> = ({ segment }) => {
    const segmentLabels: Record<string, string> = {
        inactive_user: '😴 Inactive',
        dormant_user: '💤 Dormant',
        high_performer: '⭐ High Performer',
        needs_support: '💙 Needs Support',
        new_user: '🆕 New User',
        at_risk: '⚠️ At Risk',
        milestone_achiever: '🏆 Near Milestone',
        high_performer_never_tried_custom: '🎯 Try Custom Quiz',
        experienced_user_needs_pyq: '📚 Try PYQ',
        active_user_ready_for_mock: '🎓 Ready for Mock',
        free_user_high_usage: '📊 High Usage',
        premium_user_expiring: '⏳ Expiring Soon',
        streak_rebuilder: '🔄 Rebuild Streak'
    };

    return (
        <Chip
            label={segmentLabels[segment] || segment}
            variant="outlined"
            size="small"
            sx={{ fontSize: '0.75rem' }}
        />
    );
};

/**
 * Notification Preview Dialog
 */
const NotificationPreviewDialog: React.FC<{
    open: boolean;
    onClose: () => void;
    record: any;
    onSend: () => void;
}> = ({ open, onClose, record, onSend }) => {
    if (!record) return null;

    return (
        <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
            <DialogTitle>
                <Box display="flex" justifyContent="space-between" alignItems="center">
                    <Typography variant="h6">📧 Notification Preview</Typography>
                    <IconButton onClick={onClose} size="small">
                        <CloseIcon />
                    </IconButton>
                </Box>
            </DialogTitle>
            <DialogContent dividers>
                <Box sx={{ 
                  display: 'grid',
                  gridTemplateColumns: { xs: '1fr', md: 'repeat(3, 1fr)' },
                  gap: 2
                }}>
                    {/* User Info */}
                    <Card variant="outlined" sx={{ height: '100%' }}>
                        <CardContent>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom sx={{ fontWeight: 600 }}>
                                    👤 User Information
                                </Typography>
                                <Stack spacing={1.5} sx={{ mt: 2 }}>
                                    <Box>
                                        <Typography variant="caption" color="text.secondary">Name</Typography>
                                        <Typography variant="body2" fontWeight="500">{record.display_name}</Typography>
                                    </Box>
                                    <Box>
                                        <Typography variant="caption" color="text.secondary">Email</Typography>
                                        <Typography variant="body2" fontWeight="500">{record.email}</Typography>
                                    </Box>
                                    <Box>
                                        <Typography variant="caption" color="text.secondary">Mobile</Typography>
                                        <Typography variant="body2" fontWeight="500">{record.mobile_number || 'N/A'}</Typography>
                                    </Box>
                                </Stack>
                            </CardContent>
                        </Card>

                    {/* Classification */}
                    <Card variant="outlined" sx={{ height: '100%' }}>
                        <CardContent>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom sx={{ fontWeight: 600 }}>
                                    🏷️ Classification
                                </Typography>
                                <Stack spacing={2} sx={{ mt: 2 }}>
                                    <Box>
                                        <Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>Type</Typography>
                                        <Chip 
                                            label={record.notification_type?.replace(/_/g, ' ').toUpperCase()} 
                                            size="small" 
                                            variant="outlined"
                                            sx={{ fontWeight: 500 }}
                                        />
                                    </Box>
                                    <Box>
                                        <Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>Priority</Typography>
                                        <PriorityBadge priority={record.priority} />
                                    </Box>
                                    <Box>
                                        <Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>User Segment</Typography>
                                        <SegmentBadge segment={record.user_segment} />
                                    </Box>
                                </Stack>
                            </CardContent>
                        </Card>

                    {/* Context Data */}
                    <Card variant="outlined" sx={{ height: '100%' }}>
                        <CardContent>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom sx={{ fontWeight: 600 }}>
                                    📊 Context Data
                                </Typography>
                                <Stack spacing={1.5} sx={{ mt: 2 }}>
                                    {record.context_data && Object.entries(record.context_data).map(([key, value]: [string, any]) => {
                                        const formatKey = (k: string) => {
                                            return k.split('_').map(word => 
                                                word.charAt(0).toUpperCase() + word.slice(1)
                                            ).join(' ');
                                        };
                                        
                                        const formatValue = (v: any) => {
                                            if (typeof v === 'number') {
                                                if (Number.isInteger(v)) return v.toString();
                                                return v.toFixed(1);
                                            }
                                            return v || 'N/A';
                                        };
                                        
                                        return (
                                            <Box key={key}>
                                                <Typography variant="caption" color="text.secondary">
                                                    {formatKey(key)}
                                                </Typography>
                                                <Typography variant="body2" fontWeight="500">
                                                    {formatValue(value)}
                                                </Typography>
                                            </Box>
                                        );
                                    })}
                                </Stack>
                            </CardContent>
                        </Card>

                    {/* Notification Content - Full Width */}
                    <Box sx={{ gridColumn: { xs: '1', md: '1 / -1' } }}>
                        <Card variant="outlined">
                            <CardContent>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom sx={{ fontWeight: 600 }}>
                                    📱 Notification Content
                                </Typography>
                                <Box sx={{ 
                                    mt: 2, 
                                    p: 2.5, 
                                    bgcolor: '#f5f5f5',
                                    borderRadius: 2,
                                    border: '1px solid #e0e0e0',
                                    boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
                                }}>
                                    <Box sx={{ display: 'flex', alignItems: 'center', mb: 1.5 }}>
                                        <Box sx={{ 
                                            width: 6, 
                                            height: 6, 
                                            borderRadius: '50%', 
                                            bgcolor: 'primary.main',
                                            mr: 1
                                        }} />
                                        <Typography variant="subtitle2" fontWeight="bold" color="primary">
                                            {record.notification_title}
                                        </Typography>
                                    </Box>
                                    <Typography variant="body2" color="text.secondary" sx={{ pl: 2 }}>
                                        {record.notification_text}
                                    </Typography>
                                </Box>
                            </CardContent>
                        </Card>
                    </Box>
                </Box>
            </DialogContent>
            <DialogActions sx={{ px: 3, py: 2 }}>
                <Button onClick={onClose} variant="outlined">Close</Button>
                <Button
                    variant="contained"
                    startIcon={<SendIcon />}
                    onClick={onSend}
                    disabled={record.send_status !== 'ready'}
                    color="primary"
                    size="large"
                >
                    Send Notification
                </Button>
            </DialogActions>
        </Dialog>
    );
};

/**
 * Row Actions Component
 */
const RowActions = () => {
    const record = useRecordContext();
    const notify = useNotify();
    const refresh = useRefresh();
    const [previewOpen, setPreviewOpen] = useState(false);
    const [sending, setSending] = useState(false);

    const handlePreview = () => {
        setPreviewOpen(true);
    };

    const handleSend = async () => {
        setSending(true);
        try {
            await sendNotification(
                record.user_id, 
                record.notification_type,
                record.notification_title,
                record.notification_text
            );
            notify('Notification sent successfully', { type: 'success' });
            setPreviewOpen(false);
            refresh();
        } catch (error: any) {
            notify(error.message || 'Failed to send notification', { type: 'error' });
        } finally {
            setSending(false);
        }
    };

    if (!record) return null;

    return (
        <>
            <Box display="flex" gap={1}>
                <Tooltip title="Preview Notification">
                    <IconButton size="small" onClick={handlePreview} color="primary">
                        <PreviewIcon fontSize="small" />
                    </IconButton>
                </Tooltip>
                <Tooltip title={record.send_status === 'ready' ? 'Send Now' : `Cannot send: ${record.send_status}`}>
                    <span>
                        <IconButton
                            size="small"
                            onClick={handleSend}
                            color="success"
                            disabled={record.send_status !== 'ready' || sending}
                        >
                            <SendIcon fontSize="small" />
                        </IconButton>
                    </span>
                </Tooltip>
            </Box>

            <NotificationPreviewDialog
                open={previewOpen}
                onClose={() => setPreviewOpen(false)}
                record={record}
                onSend={handleSend}
            />
        </>
    );
};

/**
 * List Filters
 */
const notificationFilters = [
    <SearchInput source="search" placeholder="Search by name, email, or mobile" alwaysOn />,
    <SelectInput
        source="notification_type"
        choices={[
            { id: 'inactivity', name: '⏰ Inactivity' },
            { id: 'milestone', name: '🏆 Milestone' },
            { id: 'custom_quiz', name: '🎯 Custom Quiz' },
            { id: 'pyq', name: '📚 PYQ' },
            { id: 'mock_test', name: '🎓 Mock Test' },
            { id: 'performance_declining', name: '📉 Performance Declining' },
            { id: 'performance_improving', name: '📈 Performance Improving' },
            { id: 'quota_warning', name: '⚠️ Quota Warning' },
            { id: 'subscription_expiry', name: '⏳ Subscription Expiry' },
            { id: 'streak_broken', name: '🔄 Streak Broken' }
        ]}
    />,
    <SelectInput
        source="priority"
        choices={[
            { id: 'high', name: 'High Priority' },
            { id: 'medium', name: 'Medium Priority' },
            { id: 'low', name: 'Low Priority' }
        ]}
    />,
    <SelectInput
        source="segment"
        choices={[
            { id: 'inactive_user', name: '😴 Inactive User' },
            { id: 'dormant_user', name: '💤 Dormant User' },
            { id: 'high_performer', name: '⭐ High Performer' },
            { id: 'needs_support', name: '💙 Needs Support' },
            { id: 'new_user', name: '🆕 New User' },
            { id: 'at_risk', name: '⚠️ At Risk' }
        ]}
    />
];

/**
 * Bulk Actions
 */
const BulkSendButton = (props: any) => {
    const { selectedIds } = useListContext();
    const { data } = useListContext();
    const notify = useNotify();
    const refresh = useRefresh();
    const [sending, setSending] = useState(false);

    const handleBulkSend = async () => {
        setSending(true);
        try {
            const users = selectedIds.map((id: any) => {
                const record = data?.find((r: any) => r.id === id);
                return {
                    user_id: record?.user_id,
                    notification_type: record?.notification_type,
                    title: record?.notification_title,
                    body: record?.notification_text
                };
            }).filter(u => u.user_id && u.notification_type);

            const result = await sendBulkNotifications(users);
            notify(`Sent ${result.sent} of ${result.total} notifications`, {
                type: result.sent > 0 ? 'success' : 'error'
            });
            refresh();
        } catch (error: any) {
            notify(error.message || 'Failed to send bulk notifications', { type: 'error' });
        } finally {
            setSending(false);
        }
    };

    return (
        <Button
            label="Send to Selected"
            onClick={handleBulkSend}
            disabled={sending || selectedIds.length === 0}
        >
            <SendIcon />
        </Button>
    );
};

/**
 * List Actions
 */
const ListActions = () => (
    <TopToolbar>
        <FilterButton />
        <ExportButton />
    </TopToolbar>
);

/**
 * Main List Component
 */
export const NotificationPreviewList = () => {
    return (
        <List
            filters={notificationFilters}
            sort={{ field: 'priority', order: 'DESC' }}
            perPage={50}
            actions={<ListActions />}
            title="📧 Notification Suggestions"
        >
            <Datagrid
                bulkActionButtons={<BulkSendButton />}
                rowClick={false}
            >
                <TextField source="id" label="User ID" />
                <TextField source="display_name" label="User Name" />
                <EmailField source="email" label="Email" />
                <TextField source="mobile_number" label="Mobile" />
                <FunctionField
                    label="Notification"
                    render={(record: any) => (
                        <Box>
                            <Typography variant="body2" fontWeight="bold">
                                {record.notification_title}
                            </Typography>
                            <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 400, display: 'block' }}>
                                {record.notification_text}
                            </Typography>
                        </Box>
                    )}
                />
                <FunctionField
                    label="Priority"
                    render={(record: any) => <PriorityBadge priority={record.priority} />}
                />
                <FunctionField
                    label="Segment"
                    render={(record: any) => <SegmentBadge segment={record.user_segment} />}
                />
                <FunctionField
                    label="Status"
                    render={(record: any) => (
                        <Chip
                            label={record.send_status}
                            color={record.send_status === 'ready' ? 'success' : 'default'}
                            size="small"
                        />
                    )}
                />
                <FunctionField
                    label="Actions"
                    render={() => <RowActions />}
                />
            </Datagrid>
        </List>
    );
};
