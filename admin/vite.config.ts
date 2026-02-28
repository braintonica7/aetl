import react from '@vitejs/plugin-react';
import path from 'path';
import fs from 'fs';
import { defineConfig } from 'vite';
import preserveDirectives from 'rollup-preserve-directives';
import { visualizer } from 'rollup-plugin-visualizer';
/**
 * https://vitejs.dev/config/
 * @type { import('vite').UserConfig }
 */
export default defineConfig(async ({ mode }) => {
    // In codesandbox, we won't have the packages folder
    // We ignore errors in this case
    let aliases: any[] = [];
    try {
        const packages = fs.readdirSync(
            path.resolve(__dirname, '../../packages')
        );
        for (const dirName of packages) {
            if (dirName === 'create-react-admin') continue;
            // eslint-disable-next-line prettier/prettier
            const packageJson = await import(
                path.resolve(
                    __dirname,
                    '../../packages',
                    dirName,
                    'package.json'
                ),
                { with: { type: 'json' } }
            );
            aliases.push({
                find: new RegExp(`^${packageJson.default.name}$`),
                replacement: path.resolve(
                    __dirname,
                    `../../packages/${packageJson.default.name}/src`
                ),
            });
        }
    } catch {}

    return {
        plugins: [
            react(),
            visualizer({
                open: process.env.NODE_ENV !== 'CI',
                filename: './dist/stats.html',
            }),
        ],
        define: {
            'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV),
            'process.env.REACT_APP_DATA_PROVIDER': JSON.stringify(
                process.env.REACT_APP_DATA_PROVIDER
            ),
        },
        server: {
            port: 8000,
            open: true,
            proxy: {
                '/api-proxy': {
                    target: 'https://api.wiziai.com',
                    changeOrigin: true,
                    secure: false,
                    rewrite: (path) => path.replace(/^\/api-proxy/, ''),
                    configure: (proxy, _options) => {
                        proxy.on('error', (err, _req, _res) => {
                            console.log('proxy error', err);
                        });
                        proxy.on('proxyReq', (proxyReq, req, _res) => {
                            console.log('Sending Request:', req.method, req.url);
                        });
                        proxy.on('proxyRes', (proxyRes, req, _res) => {
                            console.log('Received Response:', proxyRes.statusCode, req.url);
                        });
                    },
                }
            }
        },
        base: './',
        esbuild: {
            keepNames: true,
        },
        build: {
            sourcemap: true,
            rollupOptions: {
                plugins: [preserveDirectives()],
            },
        },
        resolve: {
            preserveSymlinks: true,
            alias: [
                // FIXME: doesn't work with react 19
                // allow profiling in production
                // { find: /^react-dom$/, replacement: 'react-dom/profiling' },
                // {
                //     find: 'scheduler/tracing',
                //     replacement: 'scheduler/tracing-profiling',
                // },
                // The 2 next aliases are needed to avoid having multiple react-router instances
                {
                    find: 'react-router-dom',
                    replacement: path.resolve(
                        __dirname,
                        `node_modules/react-router/dist/${mode === 'production' ? 'production' : 'development'}/index.mjs`
                    ),
                },
                {
                    find: 'react-router',
                    replacement: path.resolve(
                        __dirname,
                        `node_modules/react-router/dist/${mode === 'production' ? 'production' : 'development'}/index.mjs`
                    ),
                },
                // The 2 next aliases are needed to avoid having multiple MUI instances
                {
                    find: /^@mui\/([a-zA-Z0-9-_]+)\/*(.*)$/,
                    replacement: `${path.resolve(
                        __dirname,
                        'node_modules/@mui/$1/esm/$2'
                    )}`,
                },
                // we need to manually follow the symlinks for local packages to allow deep HMR
                ...Object.keys(aliases).map(packageName => ({
                    find: packageName,
                    replacement: aliases[packageName],
                })),
            ],
        },
    };
});
