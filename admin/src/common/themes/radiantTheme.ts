import { alpha, createTheme, PaletteOptions, Theme } from '@mui/material';
import { RaThemeOptions } from 'react-admin';


/**
 * Radiant: A theme emphasizing clarity and ease of use.
 *
 * Uses generous margins, outlined inputs and buttons, no uppercase, and an acid color palette.
 */

const componentsOverrides = (theme: Theme) => {
    const shadows = [
        alpha(theme.palette.primary.main, 0.2),
        alpha(theme.palette.primary.main, 0.1),
        alpha(theme.palette.primary.main, 0.05),
    ];
    return {
        MuiAppBar: {
            defaultProps: {
                elevation: 1,
            },
            styleOverrides: {
                root: {
                    boxShadow: 'none',
                },
                colorSecondary: {
                    backgroundColor: theme.palette.background.default,
                    color: theme.palette.text.primary,
                },
            },
        },
        MuiAutocomplete: {
            defaultProps: {
                fullWidth: true,
            },
        },
        MuiButton: {
            defaultProps: {
                variant: 'outlined' as const,
                size: 'small' as const,
            },
            styleOverrides: {
                sizeSmall: {
                    padding: `${theme.spacing(0.5)} ${theme.spacing(1.5)}`,
                },
                root: {
                    paddingTop: theme.spacing(0.2),
                    paddingBottom: theme.spacing(0.2),
                },
            },
        },
        MuiMenuItem: {
            styleOverrides: {
                root: {
                    paddingTop: theme.spacing(0.5),
                    paddingBottom: theme.spacing(0.5),
                    paddingLeft: theme.spacing(1),
                    paddingRight: theme.spacing(1),
                },
            },
        },
        MuiFormControl: {
            defaultProps: {
                variant: 'outlined' as const,
                margin: 'dense' as const,
                size: 'small' as const,
                fullWidth: true,
            },
        },
        MuiPaper: {
            styleOverrides: {
                elevation1: {
                    border: `1px solid #aaa`
                },
                root: {
                    backgroundClip: 'padding-box',
                },
            },
        },
        MuiTabs: {
            styleOverrides: {
                root: {
                    '&.MuiTabs-root': {
                        minHeight: theme.spacing(3.5),
                    },
                },
            },
        },
        MuiTab: {
            styleOverrides: {
                root: {
                    '&.MuiTab-root': {
                        padding: `${theme.spacing(0.5)} ${theme.spacing(1)}`,
                        minHeight: theme.spacing(3.5),
                        minWidth: theme.spacing(10),
                    },
                },
            },
        },
        MuiTable: {
            defaultProps: {
                size: 'small' as const,
            },
        },
        MuiTableCell: {
            styleOverrides: {
                root: {
                    padding: theme.spacing(0.2),
                    '&.MuiTableCell-sizeSmall': {
                        padding: '2px!important',
                    },
                    '&.MuiTableCell-paddingNone': {
                        padding: 0,
                    },
                },
            },
        },
        MuiTableRow: {
            styleOverrides: {
                root: {
                    '&:last-child td': { borderBottom: 0 },
                },
            },
        },
        MuiToolbar: {
            defaultProps: {
                variant: 'dense' as const,
            },
            styleOverrides: {
                root: {
                    minHeight: theme.spacing(4.5),
                },
                regular: {
                    backgroundColor: theme.palette.background.paper,
                },
            },
        },
        RaToolbar: {
            styleOverrides: {
                root: {
                    padding: 0,
                },
            },
        },
        MuiTextField: {
            defaultProps: {
                variant: 'outlined' as const,
                margin: 'dense' as const,
                size: 'small' as const,
                fullWidth: true,
            },

        },

        RaDatagrid: {
            styleOverrides: {
                root: {
                    '& .RaDatagrid-headerCell': {
                        color: theme.palette.primary.main,
                    },
                    '& .RaDatagrid-tableWrapper': {
                        borderRadius: 0,
                    }
                },

            },
        },
        RaFilterForm: {
            styleOverrides: {
                root: {
                    [theme.breakpoints.up('sm')]: {
                        minHeight: theme.spacing(6),
                    },
                },
            },
        },
        MuiTableSortLabel: {
            styleOverrides: {
                root: {
                    '&:hover': {
                        color: '#000!important'
                    }
                },
            },
        },
        RaLayout: {
            styleOverrides: {
                root: {
                    '& .RaLayout-appFrame': { marginTop: theme.spacing(4.5) },
                },
            },
        },
        RaMenuItemLink: {
            styleOverrides: {
                root: {
                    paddingLeft: theme.spacing(.5),
                    paddingRight: theme.spacing(.5),
                    paddingTop: theme.spacing(.3),
                    paddingBottom: theme.spacing(.3),
                    borderLeft: `3px solid ${theme.palette.primary.contrastText}`,
                    '&:hover': {
                        borderRadius: '0px 100px 100px 0px',
                    },
                    '&.RaMenuItemLink-active': {
                        borderLeft: `3px solid ${theme.palette.primary.main}`,
                        borderRadius: '0px 100px 100px 0px',
                        backgroundImage: `linear-gradient(98deg, ${theme.palette.primary.light}, ${theme.palette.primary.dark} 94%)`,
                        boxShadow: theme.shadows[1],
                        color: theme.palette.primary.contrastText,

                        '& .MuiSvgIcon-root': {
                            fill: theme.palette.primary.contrastText,
                        },
                    },
                },
            },
        },
        RaSimpleFormIterator: {
            defaultProps: {
                fullWidth: true,
            },
        },
        RaTranslatableInputs: {
            defaultProps: {
                fullWidth: true,
            },
        },
        MuiPaginationItem: {
            styleOverrides: {
                root: {
                    '&.Mui-selected': {
                        backgroundColor: '#1e3343',
                        border: '1px solid #ccc'
                    }
                },
                text: {
                    color: '#ccc'
                },

            },

        }
    };
};

const alert = {
    error: { main: '#DB488B' },
    warning: { main: '#F2E963' },
    info: { main: '#3ED0EB' },
    success: { main: '#0FBF9F' },
};

const darkPalette: PaletteOptions = {
    primary: { main: '#9055fd' },
    secondary: { main: '#FF83F6' },
    background: { default: '#110e1c', paper: '#151221' },
    ...alert,
    mode: 'dark' as 'dark',
};

const lightPalette: PaletteOptions = {
    primary: { main: '#00585C' },
    secondary: { main: '#A270FF' },
    background: { default: '#f0f1f6', },
    text: {
        primary: '#212b36',
        secondary: '#a0a0a0',
    },
    ...alert,
    mode: 'light' as 'light',
};

const createRadiantTheme = (palette: RaThemeOptions['palette']) => {
    const themeOptions = {
        palette,
        shape: { borderRadius: 6 },
        sidebar: { width: 180, closedWidth: 30, },
        spacing: 8,
        typography: {
            fontFamily: 'Onest, sans-serif',
            fontSize:12,
            h1: {
                fontWeight: 500,
                fontSize: '6rem',
            },
            h2: { fontWeight: 600 },
            h3: { fontWeight: 700 },
            h4: { fontWeight: 800 },
            h5: { fontWeight: 900 },
            button: { textTransform: undefined, fontWeight: 700 },
        },
    };
    const theme = createTheme(themeOptions);
    theme.components = componentsOverrides(theme);
    return theme;
};

export const radiantLightTheme = createRadiantTheme(lightPalette);
export const radiantDarkTheme = createRadiantTheme(darkPalette);
