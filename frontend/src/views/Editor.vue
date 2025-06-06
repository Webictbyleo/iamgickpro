<template>
  <EditorLayout />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import EditorLayout from '@/components/editor/EditorLayout.vue'
import { useDesignStore } from '@/stores/design'

const route = useRoute()
const designStore = useDesignStore()

// Load design when component mounts
onMounted(async () => {
  const designId = route.params.id as string
  if (designId && designId !== 'new') {
    try {
      const loadResult = await designStore.loadDesign(designId)
      
      if (!loadResult.success) {
        console.warn(`Failed to load design ${designId}:`, loadResult.error)
        
        // Create a fallback design when loading fails
        console.log('Creating fallback design due to load failure')
        const fallbackDesign = designStore.createNewDesign(800, 600)
        fallbackDesign.name = `Design ${designId} (Recovery)`
        fallbackDesign.title = `Design ${designId} (Recovery)`
        
        // Don't save the fallback design automatically - let user decide
        console.log('Fallback design created:', fallbackDesign)
      }
    } catch (error) {
      console.error('Failed to load design:', error)
      
      // Create a fallback design when exception occurs
      console.log('Creating fallback design due to exception')
      const fallbackDesign = designStore.createNewDesign(800, 600)
      fallbackDesign.name = `Design ${designId} (Recovery)`
      fallbackDesign.title = `Design ${designId} (Recovery)`
      
      // Don't save the fallback design automatically - let user decide
      console.log('Fallback design created:', fallbackDesign)
    }
  } else {
    // Create new design
    const newDesign = designStore.createNewDesign(800, 600)
    newDesign.name = 'Untitled Design'
    newDesign.title = 'Untitled Design'
    
    // Save the new design to the backend
    try {
      await designStore.saveDesign(newDesign)
    } catch (error) {
      console.error('Failed to save new design:', error)
      // Design is still available locally even if save fails
    }
  }
})
</script>
