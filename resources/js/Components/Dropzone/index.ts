export { default as Dropzone } from './Dropzone.vue'

export async function readFile(file: File): Promise<string | ArrayBuffer> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = ev => {
      const result = ev.target?.result
      if (result) {
        resolve(result)
      } else {
        reject('unable to read a file')
      }
    }
    reader.onerror = () => {
      reject('error: unable to read a file')
    }
    reader.onabort = () => {
      reject('aborted: unable to read a file')
    }
    reader.readAsDataURL(file)
  })
}
