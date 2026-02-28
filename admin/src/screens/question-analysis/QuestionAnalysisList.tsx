import React from 'react';
import {
    List,
    Datagrid,
    TextField,
    DateField,
    ImageField,
    Filter,
    TextInput,
    ReferenceInput,
    SelectInput,
    BooleanInput,
    FunctionField,
    useRecordContext,
} from 'react-admin';
import { 
    Box, 
    Typography, 
    Chip, 
    Card, 
    CardContent,
    Tooltip,
    IconButton
} from '@mui/material';
import { 
    Visibility as ViewIcon,
    CheckCircle as CheckIcon,
    Cancel as CancelIcon
} from '@mui/icons-material';

// Custom field to show truncated text with expansion
const TruncatedTextField: React.FC<{ source: string; maxLength?: number }> = ({ 
    source, 
    maxLength = 100 
}) => {
    const record = useRecordContext();
    const [expanded, setExpanded] = React.useState(false);
    
    if (!record || !record[source]) {
        return <Typography variant="body2" color="textSecondary">—</Typography>;
    }
    
    const text = record[source];
    const shouldTruncate = text.length > maxLength;
    const displayText = expanded || !shouldTruncate 
        ? text 
        : `${text.substring(0, maxLength)}...`;
    
    return (
        <Box sx={{ maxWidth: 300 }}>
            <Typography variant="body2" component="div">
                {displayText}
            </Typography>
            {shouldTruncate && (
                <Typography 
                    variant="caption" 
                    color="primary" 
                    sx={{ cursor: 'pointer', textDecoration: 'underline' }}
                    onClick={() => setExpanded(!expanded)}
                >
                    {expanded ? 'Show less' : 'Show more'}
                </Typography>
            )}
        </Box>
    );
};

// Custom field to show content generation status
const ContentStatusField: React.FC = () => {
    const record = useRecordContext();
    
    if (!record) return null;
    
    const hasQuestionText = record.question_text && record.question_text.trim() !== '';
    const hasAiSummary = record.ai_summary && record.ai_summary.trim() !== '';
    const hasSolution = record.solution && record.solution.trim() !== '';
    
    const statuses = [
        { label: 'Text', value: hasQuestionText, color: 'primary' },
        { label: 'Summary', value: hasAiSummary, color: 'secondary' },
        { label: 'Solution', value: hasSolution, color: 'success' }
    ];
    
    return (
        <Box display="flex" gap={0.5} flexWrap="wrap">
            {statuses.map((status) => (
                <Chip
                    key={status.label}
                    label={status.label}
                    size="small"
                    color={status.value ? status.color as any : 'default'}
                    variant={status.value ? 'filled' : 'outlined'}
                    icon={status.value ? <CheckIcon /> : <CancelIcon />}
                />
            ))}
        </Box>
    );
};

// Filter component
const QuestionAnalysisFilter = (props: any) => (
    <Filter {...props}>
        <TextInput 
            label="Search" 
            source="q" 
            placeholder="Search in text, solution, or summary" 
            alwaysOn 
        />
        <ReferenceInput 
            source="subject_id" 
            reference="subject" 
            label="Subject"
        >
            <SelectInput optionText="subject" />
        </ReferenceInput>
        <ReferenceInput 
            source="chapter_id" 
            reference="chapter" 
            label="Chapter"
        >
            <SelectInput optionText="chapter_name" />
        </ReferenceInput>
        <ReferenceInput 
            source="exam_id" 
            reference="exam" 
            label="Exam"
        >
            <SelectInput optionText="exam_name" />
        </ReferenceInput>
        <SelectInput
            source="has_content"
            label="Content Status"
            choices={[
                { id: 'true', name: 'Complete Content' },
                { id: 'false', name: 'Missing Content' }
            ]}
        />
    </Filter>
);

// Custom image field with better sizing
const QuestionImageField: React.FC = () => {
    const record = useRecordContext();
    
    if (!record || !record.question_img_url) {
        return (
            <Box 
                sx={{ 
                    width: 80, 
                    height: 60, 
                    display: 'flex', 
                    alignItems: 'center', 
                    justifyContent: 'center',
                    backgroundColor: 'grey.100',
                    borderRadius: 1
                }}
            >
                <Typography variant="caption" color="textSecondary">
                    No Image
                </Typography>
            </Box>
        );
    }
    
    return (
        <Box sx={{ width: 80, height: 60 }}>
            <img 
                src={record.question_img_url} 
                alt="Question"
                style={{
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                    borderRadius: 4,
                    cursor: 'pointer'
                }}
                onClick={() => window.open(record.question_img_url, '_blank')}
            />
        </Box>
    );
};

export const QuestionAnalysisList: React.FC = (props) => {
    return (
        <List
            {...props}
            title="Questions Analysis"
            filters={<QuestionAnalysisFilter />}
            sort={{ field: 'id', order: 'DESC' }}
            perPage={20}
        >
            <Datagrid 
                rowClick={false}
                sx={{
                    '& .RaDatagrid-rowCell': {
                        whiteSpace: 'normal',
                        wordWrap: 'break-word',
                        verticalAlign: 'top',
                        padding: '12px 8px'
                    },
                    '& .RaDatagrid-headerCell': {
                        fontWeight: 'bold'
                    }
                }}
            >
                <FunctionField 
                    label="Image"
                    render={() => <QuestionImageField />}
                />
                
                <TextField 
                    source="id" 
                    label="ID"
                    sortable
                />
                
                <TextField
                    source="question_text"
                    label="Question Text"
                />
                
                <TextField
                    source="solution"
                    label="Solution"
                />
                
                <TextField
                    source="ai_summary"
                    label="AI Summary"
                />
                
                {/* <DateField 
                    source="summary_generated_at" 
                    label="Generated At"
                    showTime
                /> */}
                
                <TextField 
                    source="subject_name" 
                    label="Subject"
                />
                
                <TextField 
                    source="chapter_name" 
                    label="Chapter"
                />
                
                <FunctionField
                    label="Content Status"
                    render={() => <ContentStatusField />}
                />
            </Datagrid>
        </List>
    );
};
