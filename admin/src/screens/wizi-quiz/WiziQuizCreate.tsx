import React from 'react';
import {
  Create,
  SimpleForm,
  TextInput,
  NumberInput,
  SelectInput,
  BooleanInput,
  DateTimeInput,
  ImageInput,
  ImageField,
  required
} from 'react-admin';
import { FormToolbar } from '../../common/FormToolbar';

export const WiziQuizCreate = (props) => {

  return (
    <Create undoable={false} title='Create WiZi Quiz' {...props}>
      <SimpleForm
        className="main-form"
        toolbar={<FormToolbar {...props} />}
      >
        <TextInput
          source='name'
          label='Quiz Name'
          fullWidth={true}
          validate={[required()]}
        />

        <ImageInput 
          source="cover_image" 
          label="Cover Image"
          helperText="Thumbnail image displayed on quiz cards"
        >
          <ImageField source="cover_image" title="Cover Image" />
        </ImageInput>

        <NumberInput
          source='quiz_order'
          label='Display Order'
          helperText="Lower number = higher priority in sorting (e.g., 1 appears before 2)"
          defaultValue={999}
        />

        <TextInput
          source='description'
          label='Description'
          fullWidth={true}
          multiline
          rows={3}
        />

        <NumberInput
          className="one_three_input"
          source='total_marks'
          label='Total Marks'
          defaultValue={200}
          validate={[required()]}
        />

        <NumberInput
          className="two_three_input"
          source='passing_score'
          label='Passing Score'
          defaultValue={100}
          validate={[required()]}
        />

        <NumberInput
          className="last_three_input"
          source='time_limit'
          label='Time Limit (Minutes)'
          defaultValue={180}
          validate={[required()]}
        />

        <SelectInput
          source='level'
          label='Difficulty Level'
          className='one_two_input'
          defaultValue='Moderate'
          validate={[required()]}
          choices={[
            { id: 'Elementary', name: 'Elementary' },
            { id: 'Moderate', name: 'Moderate' },
            { id: 'Advance', name: 'Advance' }
          ]}
        />

        <SelectInput
          source='language'
          label='Language'
          className='last_two_input'
          defaultValue='english'
          validate={[required()]}
          choices={[
            { id: 'english', name: 'English' },
            { id: 'hindi', name: 'Hindi (हिंदी)' }
          ]}
        />

        <SelectInput
          source='status'
          label='Status'
          className='full_width_input'
          defaultValue='draft'
          validate={[required()]}
          choices={[
            { id: 'draft', name: 'Draft' },
            { id: 'active', name: 'Active' },
            { id: 'completed', name: 'Completed' },
            { id: 'archived', name: 'Archived' }
          ]}
        />

        <TextInput
          source='instructions'
          label='Instructions'
          fullWidth={true}
          multiline
          rows={4}
          helperText="Instructions for students taking the quiz"
        />

        <DateTimeInput
          source='valid_from'
          label='Valid From'
          className='one_two_input'
          helperText="Leave empty for immediate availability"
        />

        <DateTimeInput
          source='valid_until'
          label='Valid Until'
          className='last_two_input'
          helperText="Leave empty for no expiry"
        />

        <BooleanInput
          source='is_published'
          label='Publish Immediately'
          defaultValue={false}
          helperText="Make this quiz visible to students"
        />

      </SimpleForm>
    </Create>
  );
};
