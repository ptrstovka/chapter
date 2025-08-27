import { AppPageProps } from '@/Types'
import { trans, transChoice } from 'laravel-vue-i18n'
import AxiosClient from 'axios'

// Extend ImportMeta interface for Vite...
declare global {
  interface Window {
    axios: typeof AxiosClient;
  }
}

declare module 'vite/client' {
  interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;

    [key: string]: string | boolean | undefined;
  }

  interface ImportMeta {
    readonly env: ImportMetaEnv;
    readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
  }
}

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps {
  }
}

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $inertia: typeof Router;
    $page: Page;
    $headManager: ReturnType<typeof createHeadManager>;
    $t: typeof trans;
    $tChoice: typeof transChoice;
  }
}
