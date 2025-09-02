import { createContext } from 'reka-ui'
import { type Editor } from '@tiptap/vue-3'
import type { ComputedRef, ShallowRef } from "vue";

export const [useTiptap, provideTiptapContext] = createContext<{
  editor: ShallowRef<Editor | undefined>
  disabled: ComputedRef<boolean>
}>('Tiptap')

/**
 * Checks if a mark exists in the editor schema
 * @param markName - The name of the mark to check
 * @param editor - The editor instance
 * @returns boolean indicating if the mark exists in the schema
 */
export const isMarkInSchema = (
  markName: string,
  editor: Editor | null
): boolean => {
  if (!editor?.schema) return false
  return editor.schema.spec.marks.get(markName) !== undefined
}

/**
 * Checks if a node exists in the editor schema
 * @param nodeName - The name of the node to check
 * @param editor - The editor instance
 * @returns boolean indicating if the node exists in the schema
 */
export const isNodeInSchema = (
  nodeName: string,
  editor: Editor | null
): boolean => {
  if (!editor?.schema) return false
  return editor.schema.spec.nodes.get(nodeName) !== undefined
}
