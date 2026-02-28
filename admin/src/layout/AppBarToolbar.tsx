import { LoadingIndicator, LocalesMenuButton } from 'react-admin';

import { ThemeSwapper } from '../common/themes/ThemeSwapper';

export const AppBarToolbar = () => (
    <>
        <LocalesMenuButton />
        <ThemeSwapper />
        <LoadingIndicator />
    </>
);
