import * as React from 'react';
import { Box, Card, CardActions, Button, Typography } from '@mui/material';
import HomeIcon from '@mui/icons-material/Home';
import CodeIcon from '@mui/icons-material/Code';
import { useTranslate } from 'react-admin';


const Welcome = () => {
    const translate = useTranslate();
    return (
        <strong>Dashboard</strong>
    );
};

export default Welcome;
