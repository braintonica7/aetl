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
  TextInput,useRefresh,
  ReferenceInput,
  SelectInput, 
  useRecordContext,
  usePermissions,
  useResourceContext
} from 'react-admin';
import * as apiClient from '../../common/apiClient';
import {  Drawer } from '@mui/material';

import { on } from 'events';
import { SolutionPopup } from './SolutionPopup';
import { processPermissions, isAdminUser } from "../../common/roleUtils";

const TestFilter = (props) => (
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
        { id: 'regular', name: 'Regular' },
        { id: 'pyq', name: 'PYQ (Previous Year)' },
        { id: 'mock', name: 'Mock Test' }
      ]}
      alwaysOn
    />
    <TextInput source='year' label="Year" alwaysOn />
  </Filter>
);

const SolutionButton = (props) => {
  const record = useRecordContext();
  //console.log(record);
  const handleClick = () => {
    props.handleClick(record);
  };
  return <input type='button' value='Solution' className="theme-button blue size-medium" onClick={handleClick} />;
};

export const QuestionList = (props) => {
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
  const role = localStorage.getItem('role');
  const isStudent = role == 'student';
  return (
    <React.Fragment>
      <List
        title='List of Questions'
        {...props}
        {...propsObj}
        sort={{ field: 'id', order: 'DESC' }}
        filters={<TestFilter />}
      >
        <Datagrid rowClick={false} bulkActionButtons={allowBulkActions ? undefined : false}>
          {propsObj.hasEdit && <EditButton />}
          <ImageField source='question_img_url' />
          <TextField source='question_type' label='Type' />
          <NumberField source='year' label='Year' />
          <NumberField source='duration' />
          <TextField source='level' />
          <TextField source='correct_option' />
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
          basePath="/question"
          record={openedQuestion} />}
      </Drawer>
    </React.Fragment>
  );
};
