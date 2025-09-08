import './bootstrap';
import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { RootLayout } from '@/Layouts'
import { MotionPlugin } from '@vueuse/motion'
import { i18nVue } from 'laravel-vue-i18n'
import { DataTablePlugin } from '@/Components/DataTable'
import { initializeTheme, useAppTitle } from "@/Composables";

const resolveAppTitle = useAppTitle()

createInertiaApp({
  title: resolveAppTitle,
  resolve: async (name) => {
    const page = await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue'))
    page.default.layout = page.default.layout || RootLayout
    return page
  },
  setup({el, App, props, plugin}) {
    createApp({render: () => h(App, props)})
      .use(plugin)
      .use(MotionPlugin)
      .use(DataTablePlugin)
      .use(ZiggyVue)
      .use(i18nVue, {
        lang: props.initialPage?.props.app.locale || 'en',
        resolve: async (lang: string) => {
          const files = import.meta.glob('../../lang/*.json');
          return await files[`../../lang/${lang}.json`]();
        }
      })
      .mount(el);
  },
  progress: {
    color: 'var(--primary)',
  },
});

initializeTheme()
