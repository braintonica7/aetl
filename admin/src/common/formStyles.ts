import { makeStyles } from '@mui/material';

export const containerStyles = makeStyles(theme => ({
    first_inline_input:{
        display:'inline-block',
        width:'48%'
    }, 
    last_inline_input: {
        display:'inline-block',
        marginLeft: '4%',
        width:'48%'
    },
    one_three_input:{
        display:'inline-block',
        width:'30%'
    },
    two_three_input:{
        display:'inline-block',
        marginLeft: '5%',
        width:'30%'
    },
    last_three_input:{
        display:'inline-block',
        marginLeft: '5%',
        width:'30%'
    },
}));