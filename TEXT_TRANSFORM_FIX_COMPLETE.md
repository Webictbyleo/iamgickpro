# Text Transform Fix After Underline Implementation

## Problem
After implementing underline support in the TextLayerRenderer, text resizing stopped working. The issue was that the TransformManager was expecting to work directly with `Konva.Text` nodes, but the underline implementation changed the structure to use `Konva.Group` containing both text and underline elements.

## Root Cause
The underline implementation changed the text layer structure:

**Before (single text node):**
```
layer.konvaNode = Konva.Text
```

**After (group with text and underlines):**
```
layer.konvaNode = Konva.Group {
  children: [
    Konva.Text,
    Konva.Line (underline),
    Konva.Line (for multi-line text)
  ]
}
```

All transformation methods in TransformManager were casting `layer.konvaNode` directly to `Konva.Text`, which failed with the new group structure.

## Solution
Updated all text transformation methods in TransformManager to handle both structures:

### 1. Helper Pattern for Getting Text Node
```typescript
// Get the actual text node - handle both Group (with underlines) and direct Text structures
let textNode: Konva.Text | null = null
if (layer.konvaNode instanceof Konva.Group) {
  // New group-based structure (with underlines)
  textNode = layer.konvaNode.findOne('Text') as Konva.Text
} else if (layer.konvaNode instanceof Konva.Text) {
  // Legacy single text node structure
  textNode = layer.konvaNode
}
```

### 2. Updated Methods

#### `handleTextTransformRealtime()`
- Now finds the actual text node within group structure
- Applies width resizing to the text node correctly
- Maintains all existing functionality for non-underlined text

#### `handleTextTransform()`
- Updated to work with both group and direct text structures
- Added underline updates after font size scaling
- Maintains backward compatibility

#### `addNodeTransformHandlers()`
- Fixed to find text nodes within groups for real-time transforms
- Updates underlines during width resizing
- Preserves all existing transform behavior

#### `updateTextUnderlines()` (New Method)
- Recreates underlines after transform operations
- Handles font size scaling and width changes
- Maintains proper underline positioning and thickness

## Files Modified
- `/frontend/src/editor/sdk/TransformManager.ts`

## Key Changes

### 1. Text Node Detection
All methods now use the helper pattern to detect whether the layer uses a Group or direct Text structure.

### 2. Underline Updates
When transforming group-based text, underlines are recreated with proper dimensions:
```typescript
// If this is a group-based text (with underlines), update underlines
if (layer.konvaNode instanceof Konva.Group) {
  this.updateTextUnderlines(layer.konvaNode, textNode, layer.properties)
}
```

### 3. Backward Compatibility
The fix maintains full backward compatibility with existing text layers that don't use underlines.

## Testing
The fix handles these scenarios:

1. **Regular Text (no underline)**: Works exactly as before
2. **Underlined Text**: Resizing works with proper underline updates
3. **Mixed Text Layers**: Both types can be selected and transformed correctly
4. **Dynamic Underline Toggle**: Text can be resized before and after enabling underlines

## Performance Impact
- Minimal overhead from runtime type checking
- Underlines only recreated when necessary (during width/font changes)
- No impact on non-underlined text performance

## Verification
Text resizing should now work correctly for:
- Width resizing (middle handles) - text wrapping
- Font size scaling (corner handles) - uniform scaling
- Both underlined and non-underlined text
- Real-time transform feedback

The fix ensures that the underline implementation doesn't break existing text transformation functionality while adding proper support for decorated text.
