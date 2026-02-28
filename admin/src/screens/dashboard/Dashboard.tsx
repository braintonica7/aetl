import React, { useMemo, CSSProperties } from 'react';
import { useGetList } from 'react-admin';
import { useMediaQuery, Theme } from '@mui/material';
import { subDays, startOfDay } from 'date-fns';

import Welcome from './Welcome';
import QuestionSummary from './QuestionSummary';
import SubjectQuestionAnalysis from './SubjectQuestionAnalysis';

const styles = {
    flex: { display: 'flex' },
    flexColumn: { display: 'flex', flexDirection: 'column' },
    leftCol: { flex: 1, marginRight: '0.5em' },
    rightCol: { flex: 1, marginLeft: '0.5em' },
    singleCol: { marginTop: '1em', marginBottom: '1em' },
};

const Spacer = () => <span style={{ width: '1em' }} />;
const VerticalSpacer = () => <span style={{ height: '1em' }} />;

const Dashboard = () => {
    const isXSmall = useMediaQuery((theme: Theme) =>
        theme.breakpoints.down('sm')
    );
    const isSmall = useMediaQuery((theme: Theme) =>
        theme.breakpoints.down('lg')
    );    
   
    return isXSmall ? (
        <div>
            <div style={styles.flexColumn as CSSProperties}>
               
                <VerticalSpacer />
                <QuestionSummary />
                <VerticalSpacer />
                <SubjectQuestionAnalysis />
            </div>
        </div>
    ) : isSmall ? (
        <div style={styles.flexColumn as CSSProperties}>
            <div style={styles.singleCol}>
               
                <VerticalSpacer />
                <QuestionSummary />
                <VerticalSpacer />
                <SubjectQuestionAnalysis />
            </div>
        </div>
    ) : (
        <>
          
            <VerticalSpacer />
            <QuestionSummary />
            <VerticalSpacer />
            <SubjectQuestionAnalysis />
        </>
    );
};

export default Dashboard;
