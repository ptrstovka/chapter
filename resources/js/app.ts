import './bootstrap';
import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { RootLayout } from '@/Layouts'
import { MotionPlugin } from '@vueuse/motion'
import { i18nVue } from 'laravel-vue-i18n'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: async (name) => {
    const page = await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue'))
    page.default.layout = page.default.layout || RootLayout
    return page
  },
  setup({el, App, props, plugin}) {
    createApp({render: () => h(App, props)})
      .use(plugin)
      .use(MotionPlugin)
      .use(ZiggyVue)
      .use(i18nVue, {
        lang: props.initialPage?.props.locale || 'en',
        resolve: async (lang: string) => {
          const files = import.meta.glob('../../lang/*.json');
          return await files[`../../lang/${lang}.json`]();
        }
      })
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
