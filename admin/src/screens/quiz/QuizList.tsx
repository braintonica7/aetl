import React from 'react';
import { 
    List, 
    Datagrid, 
    TextField,
    ReferenceField,
    Filter,
    SearchInput,
    SelectInput,
    ReferenceInput,
    AutocompleteInput,
    FunctionField,
    DateField,
    BooleanField,
    usePermissions,
    useResourceContext,
    EditButton,
    useRecordContext
} from 'react-admin';
import { Chip, Button } from '@mui/material';
import { useNavigate } from 'react-router-dom';
import ListIcon from '@mui/icons-material/List';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const QuizFilter = (props) => (
    <Filter {...props} variant='outlined'>
        <SearchInput source='name' alwaysOn placeholder="Search by Quiz Name" />
        
        <SelectInput 
            source='quiz_question_type' 
            label="Question Type"
            choices={[
                { id: 'regular', name: 'Regular' },
                { id: 'pyq', name: 'PYQ (Previous Year)' },
                { id: 'mock', name: 'Mock Test' }
            ]}
            alwaysOn
        />
        
        <ReferenceInput
            source='exam_id'
            reference='exam'
            link={false}
            alwaysOn
        >
            <SelectInput optionText='exam_name' label="Exam" />
        </ReferenceInput>

        <ReferenceInput
            source='subject_id'
            reference='subject'
            link={false}
        >
            <SelectInput optionText='subject' label="Subject" />
        </ReferenceInput>

        <SelectInput 
            source='level' 
            label="Level"
            choices={[
                { id: 'Elementary', name: 'Elementary' },
                { id: 'Moderate', name: 'Moderate' },
                { id: 'Advance', name: 'Advance' }
            ]}
        />

        <SelectInput 
            source='quiz_type' 
            label="Quiz Type"
            choices={[
                { id: 'public', name: 'Public' },
                { id: 'private', name: 'Private' }
            ]}
        />

        <ReferenceInput
            source='quiz_user_id'
            reference='user'
            link={false}
            filterToQuery={searchText => ({ display_name: searchText })}
             alwaysOn
        >
            <AutocompleteInput 
                optionText={(record) => `${record.display_name} (${record.username})`}
                label="Creator (User)"
                sx={{ minWidth: 300 }}
            />
        </ReferenceInput>
    </Filter>
);

const ViewQuestionsButton = () => {
    const record = useRecordContext();
    const navigate = useNavigate();

    if (!record) return null;

    const handleClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        navigate(`/quiz/${record.id}/questions`);
    };

    return (
        <Button
            onClick={handleClick}
            startIcon={<ListIcon />}
            variant="outlined"
            size="small"
            color="primary"
        >
            View Questions
        </Button>
    );
};

export const QuizList = props => {
    const { permissions } = usePermissions();
    const resource = useResourceContext();
    
    // Use utility function to process permissions with role-based restrictions
    const propsObj = processPermissions(permissions, resource);
    
    // Enable bulk actions only for admin users (role_id = 1)
    const allowBulkActions = isAdminUser();
    
    return (
        <React.Fragment>
            <List 
                title="List of Quizzes" 
                {...props} 
                {...propsObj}
                filters={<QuizFilter />}
                sort={{ field: 'id', order: 'DESC' }}
                perPage={25}
            >
                <Datagrid rowClick={propsObj.hasEdit ? 'edit' : false} bulkActionButtons={allowBulkActions ? undefined : false}>
                    
                    <TextField source="id" label="ID" />
                    <TextField source="name" label="Quiz Name" />
                    
                    <ReferenceField source="exam_id" reference="exam" label="Exam" link={false}>
                        <TextField source="exam_name" />
                    </ReferenceField>
                    
                    <ReferenceField source="subject_id" reference="subject" label="Subject" link={false}>
                        <TextField source="subject" />
                    </ReferenceField>
                    
                    <FunctionField
                        label="Question Type"
                        render={(record: any) => {
                            const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'default' } = {
                                regular: 'primary',
                                pyq: 'success',
                                mock: 'warning'
                            };
                            const labels: { [key: string]: string } = {
                                regular: 'Regular',
                                pyq: 'PYQ',
                                mock: 'Mock Test'
                            };
                            return (
                                <Chip 
                                    label={labels[record?.quiz_question_type] || record?.quiz_question_type} 
                                    color={colors[record?.quiz_question_type] || 'default'} 
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
                                    label={record?.level} 
                                    color={colors[record?.level] || 'default'} 
                                    size="small" 
                                />
                            );
                        }}
                    />
                    
                    
                    <ReferenceField source="user_id" reference="user" label="Creator" link={false}>
                        <TextField source="display_name" />
                    </ReferenceField>
                    
                    <TextField source="quiz_reference" label="Reference" />
                    
                    <TextField source="total_questions" label="Total Questions" />
                    <TextField source="correct_answers" label="Correct" />
                    <TextField source="incorrect_answers" label="Incorrect" />
                    <TextField source="total_score" label="Total Score" />
                    
                    <FunctionField 
                        label="Actions" 
                        render={() => <ViewQuestionsButton />}
                    />
                    
                </Datagrid>
            </List>
        </React.Fragment>
    );
};
