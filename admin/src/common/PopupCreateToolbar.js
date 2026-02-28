import React, { useCallback } from 'react';
import {
    required,
    minLength,
    maxLength,
    minValue,
    maxValue,
    number,
    regex,
    email,
    choices, Button,
    Edit, SimpleForm, TextInput,
    DateInput, BooleanInput, NumberInput,
    ImageInput, ImageField, SaveButton, Toolbar, DeleteButton
} from 'react-admin';
import RichTextInput from 'ra-input-rich-text';
import { useForm } from 'react-final-form';
import CancelIcon from '@material-ui/icons/Cancel';
import SaveIcon from '@material-ui/icons/Save';

const PopupCreateToolbar = props => {
    const form = useForm();
    // const saveData = (props) => {
    //     console.log(props);
        
    // }
    const cancelForm = () => {
        if (props.onCancel)
            props.onCancel();
        else
            props.onClose();
    }
    const label = (props.label) ? props.label : " Save ";
    return (
        <Toolbar {...props} >
            <SaveButton  {...props} onSave={props.onSave}/>
            <Button
                style={{ marginLeft: 10 }}
                variant="contained" color="primary"
                size="medium"
                label="Cancel"
                startIcon={<CancelIcon />}
                onClick={cancelForm}
            />
        </Toolbar>
    )
};
export default PopupCreateToolbar;