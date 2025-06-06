// Test file to debug EditorSDK import side effects
console.log('🔍 DEBUG: Starting debug-import.ts')

// Hook into createElement before importing anything
const originalCreateElement = document.createElement
let canvasCreationCount = 0

document.createElement = function(tagName: string) {
  const element = originalCreateElement.call(document, tagName)
  if (tagName.toLowerCase() === 'canvas') {
    canvasCreationCount++
    console.log(`🚨 DEBUG: CANVAS CREATED #${canvasCreationCount} during import!`)
    console.error('Canvas creation stack trace:', new Error().stack)
  }
  return element
}

console.log('🔍 DEBUG: About to import EditorSDK...')
console.log('🔍 DEBUG: Global canvas count before import:', document.querySelectorAll('canvas').length)

// Import the EditorSDK
import { EditorSDK } from './sdk/EditorSDK'

console.log('🔍 DEBUG: EditorSDK imported successfully')
console.log('🔍 DEBUG: Global canvas count after import:', document.querySelectorAll('canvas').length)
console.log('🔍 DEBUG: Total canvas elements created during import:', canvasCreationCount)

// Restore original createElement
document.createElement = originalCreateElement

console.log('🔍 DEBUG: debug-import.ts complete')

// Export something to make this a valid module
export { EditorSDK }
