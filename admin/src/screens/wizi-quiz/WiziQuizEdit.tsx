import React from 'react';
import {
  Edit,
  SimpleForm,
  TextInput,
  NumberInput,
  SelectInput,
  BooleanInput,
  DateTimeInput,
  ImageInput,
  ImageField,
  required,
  usePermissions
} from 'react-admin';
import { FormToolbar } from '../../common/FormToolbar';
import { processPermissions } from "../../common/roleUtils";

export const WiziQuizEdit = (props) => {
  const { permissions } = usePermissions();
  let propsObj = { ...props };
  
  if (permissions) {
    // Use utility function to process permissions with role-based restrictions
    const processedPermissions = processPermissions(permissions, props.resource);
    propsObj = { ...propsObj, ...processedPermissions };
  }

  return (
    <Edit undoable={false} title='Edit WiZi Quiz' {...propsObj}>
      <SimpleForm 
        className="main-form" 
        toolbar={<FormToolbar {...propsObj} hasDelete={true}/>}
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
          validate={[required()]}
        />

        <NumberInput
          className="two_three_input"
          source='passing_score'
          label='Passing Score'
          validate={[required()]}
        />

        <NumberInput
          className="last_three_input"
          source='time_limit'
          label='Time Limit (Minutes)'
          validate={[required()]}
        />

        <SelectInput
          source='level'
          label='Difficulty Level'
          className='one_two_input'
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
          label='Published'
          helperText="Make this quiz visible to students"
        />

      </SimpleForm>
    </Edit>
  );
};
