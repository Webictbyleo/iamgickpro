// Test file to verify editor SDK imports work correctly
import { EditorSDK } from './sdk/EditorSDK'
import type { EditorConfig } from './sdk/types'

// This file just tests that imports work - it will be deleted after verification
const testConfig: EditorConfig = {
  container: document.createElement('div'),
  width: 800,
  height: 600,
  backgroundColor: '#ffffff'
}

// This should compile without errors
const editor = new EditorSDK(testConfig)

export { editor }
