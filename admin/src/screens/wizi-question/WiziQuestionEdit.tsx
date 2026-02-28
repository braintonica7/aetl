import React from 'react';
import {
  Edit,
  SimpleForm,
  TextInput,
  ReferenceInput,
  SelectInput,
  ImageInput,
  ImageField,
  NumberInput,
  BooleanInput,
  required,
  FormDataConsumer,
  usePermissions
} from 'react-admin';
import { useFormContext } from 'react-hook-form';
import { FormToolbar } from '../../common/FormToolbar';
import { CustomLogoInputDisplay } from '../../common/CustomImage';
import { processPermissions } from "../../common/roleUtils";

const SubjectSelectInput = () => {
  const { setValue } = useFormContext();
  
  return (
    <SelectInput 
      optionText='subject' 
      validate={[required()]} 
      className="one_three_input"
      onChange={() => {
        setValue('chapter_id', null);
        setValue('topic_id', null);
      }}
    />
  );
};

const ChapterSelectInput = ({ disabled }) => {
  const { setValue } = useFormContext();
  
  return (
    <SelectInput 
      className="two_three_input"
      optionText="chapter_name" 
      validate={[required()]} 
      disabled={disabled}
      onChange={() => {
        setValue('topic_id', null);
      }}
    />
  );
};

export const WiziQuestionEdit = (props) => {
  const { permissions } = usePermissions();
  let propsObj = { ...props };
  
  if (permissions) {
    // Use utility function to process permissions with role-based restrictions
    const processedPermissions = processPermissions(permissions, props.resource);
    propsObj = { ...propsObj, ...processedPermissions };
  }

  return (
    <Edit undoable={false} title='WiZi Question Edit' {...propsObj}>
      <SimpleForm className="main-form" toolbar={<FormToolbar {...propsObj} hasDelete={true}/>} >

        <ImageInput source="question_img_url" label="Question Image" >
          <ImageField source="question_img_url" title="Question Image" />
        </ImageInput>
        <CustomLogoInputDisplay />

        <BooleanInput
          source='has_multiple_answer'
          label='Has Multiple Answer'
        />

        <NumberInput
          className="one_three_input"
          source='duration'
          label='Duration (seconds)'
          fullWidth={true}
          validate={[required()]}
        />
        <NumberInput
          className="two_three_input"
          source='option_count'
          label='Option Count'
          fullWidth={true}
          validate={[required()]}
        />

        <ReferenceInput
          source='exam_id'
          className="last_three_input"
          reference='exam'
          fullWidth={true}
        >
          <SelectInput optionText='exam_name' validate={[required()]} className="last_three_input" />
        </ReferenceInput>

        <ReferenceInput
          className="one_three_input"
          source='subject_id'
          reference='subject'
          fullWidth={true}
        >
          <SubjectSelectInput />
        </ReferenceInput>

        <FormDataConsumer>
          {({ formData }) => (
            <ReferenceInput
              className="two_three_input"
              perPage={500}
              sort={{ field: 'chapter_name', order: 'ASC' }}
              source="chapter_id" 
              reference="chapter" 
              label="Chapter"
              filter={formData.subject_id ? { "subject_id=": formData.subject_id } : {}}
            >
              <ChapterSelectInput disabled={!formData.subject_id} />
            </ReferenceInput>
          )}
        </FormDataConsumer>

        <FormDataConsumer>
          {({ formData }) => (
            <ReferenceInput
              className="last_three_input"
              source='topic_id'
              reference='topic'
              perPage={500}
              fullWidth={true}
              filter={formData.chapter_id ? {"chapter_id=": formData.chapter_id } : {}}
            >
              <SelectInput 
                className="last_three_input"
                optionText="topic_name" 
                validate={[required()]}
                disabled={!formData.chapter_id}
              />
            </ReferenceInput>
          )}
        </FormDataConsumer>

        <SelectInput
          source='level'
          label='Level'
          className='one_three_input'
          fullWidth={true}
          validate={[required()]}
          choices={[
            { id: 'Elementary', name: 'Elementary' },
            { id: 'Moderate', name: 'Moderate' },
            { id: 'Advance', name: 'Advance' }
          ]}
        />

        <TextInput
          source='correct_option'
          label='Correct Option'
          className='two_three_input'
          fullWidth={true}
          validate={[required()]}
        />

        <SelectInput
          source='question_type'
          label='Question Type'
          className='last_three_input'
          fullWidth={true}
          validate={[required()]}
          choices={[
            { id: 'regular', name: 'Regular' },
            { id: 'pyq', name: 'PYQ (Previous Year Question)' },
            { id: 'mock', name: 'Mock Test' }
          ]}
        />

        <NumberInput
          className="one_three_input"
          source='year'
          label='Year'
          fullWidth={true}
          validate={[required()]}
        />

        <TextInput
          source='difficulty'
          label='Difficulty (AI Generated)'
          className='two_three_input'
          fullWidth={true}
          helperText="e.g., Easy, Medium, Hard"
        />

        <TextInput
          source='solution'
          label='Solution'
          fullWidth={true}
          multiline
          rows={4}
        />

        <TextInput
          source='question_text'
          label='Question Text'
          fullWidth={true}
          multiline
          rows={3}
          helperText="Extracted text from question image"
        />

        <TextInput
          source='option_a'
          label='Option A'
          className='one_two_input'
          fullWidth={true}
        />

        <TextInput
          source='option_b'
          label='Option B'
          className='last_two_input'
          fullWidth={true}
        />

        <TextInput
          source='option_c'
          label='Option C'
          className='one_two_input'
          fullWidth={true}
        />

        <TextInput
          source='option_d'
          label='Option D'
          className='last_two_input'
          fullWidth={true}
        />

        <TextInput
          source='subject_name'
          label='Subject Name (AI Extracted)'
          className='one_three_input'
          fullWidth={true}
          helperText="Auto-populated from AI analysis"
        />

        <TextInput
          source='topic_name'
          label='Topic Name (AI Extracted)'
          className='two_three_input'
          fullWidth={true}
          helperText="Auto-populated from AI analysis"
        />

        <TextInput
          source='chapter_name'
          label='Chapter Name'
          className='last_three_input'
          fullWidth={true}
        />

        <BooleanInput
          source='invalid_question'
          label='Mark as Invalid'
        />

        <TextInput
          source='flag_reason'
          label='Flag Reason'
          fullWidth={true}
          helperText="Reason for marking question as invalid"
        />

        <TextInput
          source='ai_summary'
          label='AI Summary'
          fullWidth={true}
          multiline
          rows={3}
          helperText="AI-generated summary for performance analysis"
        />

      </SimpleForm>
    </Edit>
  );
};
