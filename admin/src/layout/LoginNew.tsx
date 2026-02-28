import * as React from 'react';
import { Typography } from '@mui/material';
import { Login as RaLogin, LoginForm } from 'react-admin';

const LoginNew = () => (
    <RaLogin sx={{ background: 'none' }}>
        <Typography
            sx={{
                color: 'text.disabled',
                textAlign: 'center',
            }}
        >
            Hint: demo / demo
        </Typography>
        <LoginForm />
    </RaLogin>
);

export default LoginNew;
