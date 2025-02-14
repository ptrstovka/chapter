import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { vite as vidstack } from 'vidstack/plugins';

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.ts',
      refresh: true
    }),
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
  ]
})
