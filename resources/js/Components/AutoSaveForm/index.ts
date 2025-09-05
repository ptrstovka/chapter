import { createContext } from "reka-ui";
import { reactive } from "vue";
import { router } from "@inertiajs/vue3";

export { default as AutoSaveFormField } from './AutoSaveFormField.vue'
export { default as AutoSaveFormInputField } from './AutoSaveFormInputField.vue'
export { default as AutoSaveFormProvider } from './AutoSaveFormProvider.vue'
export { default as AutoSaveFormSelectField } from './AutoSaveFormSelectField.vue'
export { default as AutoSaveFormSwitchField } from './AutoSaveFormSwitchField.vue'

export type FormData = object
export interface AutoSaveForm<TFormData extends FormData> {
  errors: Partial<Record<keyof TFormData, string>>
  setErrors: (errors: Partial<Record<keyof TFormData, string>>) => void
  clearErrors: () => void
  reset: () => void
  submit: () => void
  value: TFormData
}

export interface AutoSaveFormOptions {
  method: 'post' | 'patch' | 'put'
  url: string
}

export function useAutoSaveForm<TFormData extends object>(state: () => TFormData, options: Partial<AutoSaveFormOptions> = {}): AutoSaveForm<TFormData> {
  const errors = reactive({}) as Partial<Record<keyof TFormData, string>>
  const value = reactive(state()) as TFormData

  const normalize = (value: any) => {
    if (value === '' || value === null || value === undefined) {
      return null
    }

    return value
  }

  const reset = () => {
    const newState = state();
    (Object.keys(newState) as Array<keyof TFormData>).forEach(key => {
      value[key] = newState[key]
    });
  }

  const submit = () => {
    const toSubmit: any = {};

    (Object.keys(value) as Array<keyof TFormData>).forEach(key => {
      const updated = normalize(value[key])
      const original = normalize(state()[key])

      if (updated != original) {
        toSubmit[key] = updated
      }
    })

    if (Object.keys(toSubmit).length == 0) {
      return
    }

    (Object.keys(toSubmit) as Array<keyof TFormData>).forEach(key => {
      errors[key] = undefined
    })

    const method = options.method
    const url = options.url

    if (method && url) {
      router.visit(url, {
        method,
        data: toSubmit,
        preserveState: true,
        preserveScroll: true,
        onError: (bag: Record<string, string>) => {
          (Object.keys(toSubmit) as Array<keyof TFormData & string>).forEach(key => {
            if (key in bag) {
              errors[key] = bag[key]
            }
          })
        },
        onSuccess: () => {
          (Object.keys(toSubmit) as Array<keyof TFormData>).forEach(key => {
            value[key] = state()[key]
          })
        }
      })
    }
  }

  const setErrors = (e: Partial<Record<keyof TFormData, string>>) => {
    (Object.keys(e) as Array<keyof TFormData>).forEach(key => {
      errors[key] = e[key]
    })
  }

  const clearErrors = () => {
    (Object.keys(errors) as Array<keyof TFormData>).forEach(e => {
      delete errors[e]
    })
  }

  return {
    value: value as TFormData,
    errors: errors as Partial<Record<keyof TFormData, string>>,
    submit,
    setErrors,
    clearErrors,
    reset,
  }
}

export const [injectAutoSaveForm, provideAutoSaveForm] = createContext<{
  form: AutoSaveForm<any>
}>('AutoSaveForm')
