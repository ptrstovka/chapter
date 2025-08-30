export { default as FileListInput } from './FileListInput.vue'

export interface FileListItem {
  id: string | number
  type: 'existing' | 'temporary'
  url: string
  name: string
  size: string
  mime: string
}

