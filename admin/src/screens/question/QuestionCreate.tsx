import React from 'react';
import {
  Edit,
  Create,
  SimpleForm,
  TextInput,
  ReferenceInput,
  SelectInput,
  ImageInput,
  ImageField,
  NumberInput,
  BooleanInput,
  required,
  AutocompleteInput,
  FormDataConsumer
} from 'react-admin';
import { useFormContext } from 'react-hook-form';
import { FormToolbar } from '../../common/FormToolbar';
import { CustomLogoInputDisplay } from '../../common/CustomImage';

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

export const QuestionCreate = (props) => {

  return (
    <Create undoable={false} title='Question Create' {...props}>
      <SimpleForm
        className="main-form"
        toolbar={<FormToolbar {...props} />}
      >

        <ImageInput source="question_img_url" label="Question Image">
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
              filter={formData.chapter_id ? { "chapter_id=": formData.chapter_id } : {}}
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
      </SimpleForm>
    </Create>
  );
};
