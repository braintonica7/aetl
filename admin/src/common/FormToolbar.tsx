import React from 'react';
import { Toolbar, SaveButton, DeleteButton } from 'react-admin';
import { useNavigate } from "react-router-dom";
import Button from '@mui/material/Button';
import { canDelete } from './roleUtils';

export const FormToolbar = props => {
    const navigate = useNavigate();
    const cancelForm = () => {
        navigate(-1);
    }
    let showSave = true;
    let showCancel = false;

    let showDelete = false;
    if (props.hideSave) {
        showSave = false;
    }
    if (props.showCancel) {
        showCancel = true;
    }
    // Role-based delete restriction: Only admin users (role_id = 1) can delete
    if (props.hasDelete && canDelete()) {
        showDelete = props.hasDelete;
        console.log('FormToolbar: Delete button enabled for admin user');
    } else if (props.hasDelete && !canDelete()) {
        console.log('FormToolbar: Delete button hidden - user is not admin');
    }
    return (
        <Toolbar {...props} >
            <div className="main">
                <div className="btnparent" style={{marginTop:6, marginBottom:6}}>
                    {showSave && <SaveButton disabled={showSave ? false : true}  {...props} />}
                    {!showCancel && <Button
                        style={{ marginLeft: 10, }}
                        className="cancel_button"
                        variant="contained" color="primary"
                        onClick={cancelForm}
                    >Back</Button>}
                    {showCancel && <Button
                        style={{ marginLeft: 10, }}
                        className="cancel_button"
                        variant="contained" color="primary"
                        size="medium"
                        onClick={props.onCancel}
                    >Cancel</Button>}
                </div>
                {(showDelete) ? <DeleteButton   {...props} /> : null}

            </div>
        </Toolbar>
    )
};