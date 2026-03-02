import React, { FC, useState, useEffect } from 'react';
import polyglotI18nProvider from 'ra-i18n-polyglot';
import {
    Admin,
    CustomRoutes,
    Resource,
    localStorageStore,
    useStore,
    StoreContextProvider,
    EditGuesser,
    ListGuesser,
} from 'react-admin';
import { Route } from 'react-router';
import jwtAuthProvider from './common/jwtAuthProvider';
import { Dashboard } from './screens/dashboard';
import englishMessages from './common/i18n/en';
import { Layout, Login } from './layout';
import { themes, ThemeName } from './common/themes/themes';
import VSDataProvider from "./common/VSDataProvider";
import { APIUrl } from './common/apiClient';
import users from './screens/users';
import subjects from './screens/subject';
import "./App.css";
import LoginNew from './layout/LoginNew';
import { MyDataProvider } from './common/MyDataProvider';
import exam from './screens/exam';
import chapter from './screens/chapter';
import topic from './screens/topic';
import question from './screens/question';
import studentPerformance from './screens/student-performance';
import UserQuizStatistics from './screens/reports';
import quiz from './screens/quiz';
import NotificationSend from './screens/notifications/NotificationSend';
import { MathJaxProvider } from './components/MathJaxRenderer';
import { QuizQuestionsList } from './screens/quiz/QuizQuestionsList';
import NotificationContextManager from './screens/notification-context';
import notificationPreview from './screens/notification-preview';

const i18nProvider = polyglotI18nProvider(
    locale => {
        if (locale === 'fr') {
            return import('./common/i18n/fr').then(messages => messages.default);
        }

        // Always fallback on english
        return englishMessages;
    },
    'en',
    [
        { locale: 'en', name: 'English' },
        { locale: 'fr', name: 'Français' },
    ]
);

const store = localStorageStore(undefined, 'VS-Framework');

const App = () => {
    const [themeName] = useStore<ThemeName>('themeName', 'radiant');
    const lightTheme = themes.find(theme => theme.name === themeName)?.light;
    const darkTheme = themes.find(theme => theme.name === themeName)?.dark;

    const [dataProvider, setDataProvider]:any = useState({});

    useEffect(() => {
      let provider = MyDataProvider(APIUrl);
      setDataProvider(provider);
  
    }, []);

    return (
        <Admin
            title=""
            dataProvider={dataProvider}
            store={store}
            authProvider={jwtAuthProvider}
            dashboard={Dashboard}
            loginPage={LoginNew}
            layout={Layout}
            i18nProvider={i18nProvider}
            disableTelemetry
            lightTheme={lightTheme}
            darkTheme={darkTheme}
            defaultTheme="light"
        >
            <Resource name="user" {...users} />
            <Resource name="subject" {...subjects} />
            <Resource name="role" list={ListGuesser} edit={EditGuesser} />
            <Resource name="exam" {...exam} />
            <Resource name="chapter" {...chapter} />
            <Resource name="topic" {...topic} />
            <Resource name="question" {...question} />
            <Resource name="quiz" {...quiz} />
            <Resource name="student-performance" {...studentPerformance} />
            <Resource name="notification-preview" {...notificationPreview} />
            <CustomRoutes>
                <Route path="/reports/user-quiz-statistics" element={<UserQuizStatistics />} />
                <Route path="/notifications/send" element={<NotificationSend />} />
                <Route path="/notifications/context-manager" element={<NotificationContextManager />} />
                <Route path="/quiz/:quizId/questions" element={<QuizQuestionsList />} />
            </CustomRoutes>
            
        </Admin>
    );
};

const AppWrapper = () => (
    <StoreContextProvider value={store}>
        <MathJaxProvider>
            <App />
        </MathJaxProvider>
    </StoreContextProvider>
);

export default AppWrapper;
