# History Service Integration Guide

## Overview

The new `HistoryService` is a comprehensive, generic history management system that uses JSON Patch for efficient diffing and storage. It can be used for any object type, not just design data.

## Key Features

### 🚀 **Universal Design**
- Works with any object type
- Not tied to the editor specifically
- Reusable across different parts of the application

### ⚡ **Efficient Diffing**
- Uses `fast-json-patch` for minimal memory footprint
- Only stores the differences between states
- Smart snapshot system for fast reconstruction

### 🎛️ **Configurable**
- Adjustable history size limits
- Debounce rapid changes
- Exclude specific paths from tracking
- Customizable snapshot intervals

### 🔔 **Event-Driven**
- Emits events for all history operations
- Easy integration with Vue reactivity
- Real-time UI updates

## Basic Usage

```typescript
import { HistoryService } from '@/services/HistoryService'

// Create a history service for any object type
const historyService = new HistoryService<MyDataType>(initialData, {
  maxEntries: 50,
  debounceMs: 300,
  excludePaths: ['/timestamp', '/lastModified']
})

// Add changes
historyService.addEntry(newData, 'User Action Description')

// Undo/Redo
const previousState = historyService.undo()
const nextState = historyService.redo()

// Check capabilities
const canUndo = historyService.canUndo()
const canRedo = historyService.canRedo()
```

## Vue Integration

```typescript
import { useDesignHistory } from '@/composables/useDesignHistory'

// In a Vue component
const {
  canUndo,
  canRedo,
  addLayerHistoryEntry,
  undo,
  redo,
  getCurrentDesign
} = useDesignHistory(initialDesign)

// Track layer changes
addLayerHistoryEntry(design, 'add', 'Text Layer')
addLayerHistoryEntry(design, 'modify', 'Changed font size')

// Undo/Redo with reactive updates
const previousDesign = undo()
const nextDesign = redo()
```

## Editor Integration Example

Here's how you can replace the current history system in the editor:

```typescript
// In EditorLayout.vue or useDesignEditor
import { useDesignHistory } from '@/composables/useDesignHistory'

export function useDesignEditor() {
  const {
    canUndo,
    canRedo,
    addLayerHistoryEntry,
    addCanvasHistoryEntry,
    undo,
    redo,
    initialize,
    getCurrentDesign
  } = useDesignHistory()

  // Initialize when loading a design
  const loadDesign = async (design: Design) => {
    initialize(design, 'Design Loaded')
    // ... rest of load logic
  }

  // Track layer operations
  const addLayer = (layer: Layer) => {
    // ... add layer logic
    const updatedDesign = getCurrentDesign()
    if (updatedDesign) {
      addLayerHistoryEntry(updatedDesign, 'add', `${layer.type} layer`)
    }
  }

  // Track canvas changes
  const updateCanvasSize = (width: number, height: number) => {
    // ... resize logic
    const updatedDesign = getCurrentDesign()
    if (updatedDesign) {
      addCanvasHistoryEntry(updatedDesign, 'resize', `${width}x${height}`)
    }
  }

  return {
    canUndo,
    canRedo,
    undo,
    redo,
    loadDesign,
    addLayer,
    updateCanvasSize
    // ... other methods
  }
}
```

## Migration from Current History

To replace the existing history system:

1. **Replace imports**: Change from old history to new `useDesignHistory`
2. **Update method calls**: Use descriptive action names instead of generic tracking
3. **Leverage events**: Use the event system for UI updates
4. **Configure exclusions**: Exclude timestamps and other non-essential changes

## Advanced Features

### Batch Operations
```typescript
history.batch([
  () => addLayer(layer1),
  () => addLayer(layer2),
  () => updateCanvas()
], 'Add Multiple Layers')
```

### Memory Management
```typescript
const stats = history.getStats()
console.log(`Memory usage: ${stats.memoryUsage} bytes`)
console.log(`Average patch size: ${stats.averagePatchSize}`)
```

### Custom Events
```typescript
history.on('history:change', ({ canUndo, canRedo }) => {
  // Update UI state
})

history.on('history:add', ({ entry, totalEntries }) => {
  // Show notification or update counter
})
```

## Benefits Over Current System

1. **Memory Efficient**: Only stores diffs, not full state copies
2. **Type Safe**: Full TypeScript support with generics
3. **Flexible**: Works with any data structure
4. **Performant**: Smart diffing and debouncing
5. **Observable**: Event-driven for reactive UIs
6. **Configurable**: Adaptable to different use cases

## File Structure

```
src/
├── services/
│   └── HistoryService.ts          # Core history service
├── composables/
│   └── useDesignHistory.ts        # Design-specific composable
└── components/
    └── editor/
        └── HistoryPanel.vue       # (Optional) History UI panel
```

This system is ready to replace the current history implementation and can be extended for other parts of the application that need undo/redo functionality.
