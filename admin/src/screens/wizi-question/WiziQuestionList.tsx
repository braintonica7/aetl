import React, { useState } from 'react';
import {
  List,
  Datagrid,
  TextField,
  ReferenceField,
  BooleanField,
  DateField,
  NumberField,
  ImageField,
  EditButton,
  Filter,
  TextInput,
  useRefresh,
  ReferenceInput,
  SelectInput, 
  useRecordContext,
  usePermissions,
  useResourceContext,
  FunctionField
} from 'react-admin';
import { Drawer, Chip } from '@mui/material';
import { SolutionPopup } from './SolutionPopup';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const WiziQuestionFilter = (props) => (
  <Filter {...props} variant='outlined'>
    <ReferenceInput source='exam_id' reference='exam' link={false} alwaysOn>
      <SelectInput optionText='exam_name' />
    </ReferenceInput>
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
    <ReferenceInput source='topic_id' reference='topic' link={false} alwaysOn>
      <SelectInput optionText='topic_name' />
    </ReferenceInput>
    <SelectInput
      source='level'
      choices={[
        { id: 'Elementary', name: 'Elementary' },
        { id: 'Moderate', name: 'Moderate' },
        { id: 'Advance', name: 'Advance' }
      ]}
      alwaysOn
    />
    <SelectInput
      source='question_type'
      label="Question Type"
      choices={[
        { id: 'mcq', name: 'MCQ' },
        { id: 'integer', name: 'Integer' }
      ]}
      alwaysOn
    />
    <SelectInput
      source='invalid_question'
      label="Status"
      choices={[
        { id: '0', name: 'Valid' },
        { id: '1', name: 'Invalid/Flagged' }
      ]}
      alwaysOn
    />
    <TextInput source='year' label="Year" />
    <TextInput source='difficulty' label="Difficulty" />
  </Filter>
);

const SolutionButton = (props) => {
  const record = useRecordContext();
  const handleClick = () => {
    props.handleClick(record);
  };
  return <input type='button' value='Solution' className="theme-button blue size-medium" onClick={handleClick} />;
};

export const WiziQuestionList = (props) => {
  const refresh = useRefresh();
  const { permissions } = usePermissions();
  const resource = useResourceContext();

  const [isSolutionOpen, setIsSolutionOpen] = useState(false);
  const [openedQuestion, setOpenedQuestion] = useState(undefined);

  // Use utility function to process permissions with role-based restrictions
  const propsObj = processPermissions(permissions, resource);

  // Enable bulk actions only for admin users (role_id = 1)
  const allowBulkActions = isAdminUser();

  const onCancel = () => {
    setIsSolutionOpen(false);
    refresh();
  }

  const handleClick = (record) => {
    setOpenedQuestion(record);
    setIsSolutionOpen(true);
  }

  return (
    <React.Fragment>
      <List
        title='WiZi Questions - Mock Test Questions'
        {...props}
        {...propsObj}
        sort={{ field: 'id', order: 'DESC' }}
        filters={<WiziQuestionFilter />}
        perPage={25}
      >
        <Datagrid rowClick={false} bulkActionButtons={allowBulkActions ? undefined : false}>
          {propsObj.hasEdit && <EditButton />}
          <TextField source="id" label="ID" />
          <ImageField source='question_img_url' label="Question Image" />
          
          <FunctionField
            label="Type"
            render={(record: any) => {
              const colors: { [key: string]: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'default' } = {
                regular: 'default',
                pyq: 'primary',
                mock: 'success'
              };
              const labels: { [key: string]: string } = {
                regular: 'Regular',
                pyq: 'PYQ',
                mock: 'Mock'
              };
              return (
                <Chip 
                  label={labels[record?.question_type] || record?.question_type} 
                  color={colors[record?.question_type] || 'default'} 
                  size="small" 
                />
              );
            }}
          />
          
          <NumberField source='year' label='Year' />
          <NumberField source='duration' label='Duration (s)' />
          <TextField source='level' label='Level' />
          <TextField source='difficulty' label='Difficulty' />
          <TextField source='correct_option' label='Answer' />
          
          <ReferenceField source='exam_id' reference='exam' link={false}>
            <TextField source='exam_name' />
          </ReferenceField>
          <ReferenceField source='subject_id' reference='subject' link={false}>
            <TextField source='subject' />
          </ReferenceField>
          <ReferenceField source='chapter_id' reference='chapter' link={false}>
            <TextField source='chapter_name' />
          </ReferenceField>
          <ReferenceField source='topic_id' reference='topic' link={false}>
            <TextField source='topic_name' />
          </ReferenceField>
          
          <BooleanField source='invalid_question' label='Invalid' />
          
          <SolutionButton handleClick={handleClick} />
        </Datagrid>
      </List>
      <Drawer
        anchor="right"
        onClose={onCancel}
        classes={{
          paper: "drawerPaper",
        }}
        open={isSolutionOpen}>
        {isSolutionOpen && <SolutionPopup
          onClose={onCancel}
          onCancel={onCancel}
          {...props}
          basePath="/wizi_question"
          record={openedQuestion} />}
      </Drawer>
    </React.Fragment>
  );
};
