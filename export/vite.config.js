import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig, loadEnv } from 'vite'
import themePlugin from './plugins/theme.ts'

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '')
    return {
        build: {
            buildDirectory: '_build',
            rollupOptions: {
                output: {
                    manualChunks: (id) => {
                        if (id.includes('node_modules')) return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                }
            }
        },
        plugins: [
            themePlugin({
                tokensGlob: 'resources/design-tokens/**/*.json',
                outputPath: 'resources/css/theme.css',
                tokensDir: 'resources/design-tokens',
            }),
            tailwindcss(),
            laravel({
                refresh: true,
                buildDirectory: '_build',
                input: [
                    'resources/css/site.css',
                    'resources/js/site.js',
                ]
            })
        ],
        server: {
            open: env.APP_URL,
            cors: true,
        }
    }
});
