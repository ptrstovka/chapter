import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import path from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { vite as vidstack } from 'vidstack/plugins';

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.ts',
      refresh: true
    }),
    tailwindcss(),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false
        },
        compilerOptions: {
          isCustomElement: (tag) => tag.startsWith('media-'),
        },
      }
    }),
    vidstack({
      include: /resources\/js\/Components\/Player\//
    })
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      '@stacktrace/ui': path.resolve(__dirname, './vendor/stacktrace/ui')
    },
  },
})
