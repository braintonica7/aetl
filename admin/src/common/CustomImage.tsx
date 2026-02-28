import React from 'react';
import {useRecordContext} from 'react-admin';
export const CustomLogoInputDisplay = (props) => {
    const record = useRecordContext(props);
    return (
        <span>
            <div>Existing Image</div>
            {record && record.question_img_url && <img className='existing-image' src={record.question_img_url}/>}
        </span>
    )
};
