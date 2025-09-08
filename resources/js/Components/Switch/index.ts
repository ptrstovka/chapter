export { default as Switch } from './Switch.vue'
export { default as SwitchControl } from './SwitchControl.vue'
export { default as SwitchToggle } from './SwitchToggle.vue'

export interface SwitchToggleProps {
  value: boolean
  url: string | null
  method?: 'get' | 'post' | 'put' | 'patch' | 'delete'
  field: string
}
